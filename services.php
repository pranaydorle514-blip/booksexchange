<?php

function required_env(string $name): string
{
    $value = getenv($name);
    if ($value === false || trim($value) === "") {
        throw new RuntimeException("The site administrator has not configured " . $name . ".");
    }
    return trim($value);
}

function normalize_phone(string $phone): ?string
{
    $phone = preg_replace('/[\s().-]/', '', trim($phone));
    if (preg_match('/^[0-9]{10}$/', $phone)) {
        $phone = '+91' . $phone;
    }
    return preg_match('/^\+[1-9][0-9]{7,14}$/', $phone) ? $phone : null;
}

function send_whatsapp_otp(string $phone, string $code): void
{
    $phoneNumberId = required_env("META_WA_PHONE_NUMBER_ID");
    $accessToken = required_env("META_WA_ACCESS_TOKEN");
    $apiVersion = required_env("META_GRAPH_API_VERSION");
    $templateName = getenv("META_WA_TEMPLATE_NAME") ?: "authentication";
    $language = getenv("META_WA_TEMPLATE_LANGUAGE") ?: "en_US";

    if (!preg_match('/^v[0-9]+\.[0-9]+$/', $apiVersion)
        || !preg_match('/^[a-z0-9_]+$/', $templateName)
        || !preg_match('/^[A-Za-z0-9_]+$/', $language)) {
        throw new RuntimeException("The WhatsApp OTP configuration is invalid.");
    }

    $payload = [
        "messaging_product" => "whatsapp",
        "to" => ltrim($phone, "+"),
        "type" => "template",
        "template" => [
            "name" => $templateName,
            "language" => ["code" => $language],
            "components" => [
                [
                    "type" => "body",
                    "parameters" => [["type" => "text", "text" => $code]],
                ],
                [
                    "type" => "button",
                    "sub_type" => "url",
                    "index" => "0",
                    "parameters" => [["type" => "text", "text" => $code]],
                ],
            ],
        ],
    ];
    $curl = curl_init("https://graph.facebook.com/" . $apiVersion . "/" . rawurlencode($phoneNumberId) . "/messages");
    if ($curl === false) {
        throw new RuntimeException("Could not initialize the WhatsApp service.");
    }
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer " . $accessToken,
            "Content-Type: application/json",
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
    ]);
    $response = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    $result = is_string($response) ? json_decode($response, true) : null;
    if ($response === false || $status < 200 || $status >= 300
        || !is_array($result) || empty($result["messages"][0]["id"])) {
        error_log("WhatsApp OTP delivery failed (HTTP " . $status . "): " . $curlError);
        throw new RuntimeException("WhatsApp could not deliver the code. Check the Meta sender and approved OTP template.");
    }
}

function issue_phone_otp(PDO $pdo, string $phone, string $purpose): void
{
    if (!in_array($purpose, ["login", "register"], true)) {
        throw new InvalidArgumentException("Invalid OTP purpose.");
    }
    required_env("META_WA_PHONE_NUMBER_ID");
    required_env("META_WA_ACCESS_TOKEN");
    required_env("META_GRAPH_API_VERSION");

    $code = (string)random_int(100000, 999999);
    $hash = password_hash($code, PASSWORD_DEFAULT);
    $now = time();
    $pdo->beginTransaction();
    try {
        $pdo->prepare("INSERT IGNORE INTO phone_otp_challenges(phone) VALUES(?)")->execute([$phone]);
        $stmt = $pdo->prepare("SELECT * FROM phone_otp_challenges WHERE phone=? FOR UPDATE");
        $stmt->execute([$phone]);
        $challenge = $stmt->fetch();
        if ($challenge["last_sent_at"] && $now - strtotime($challenge["last_sent_at"]) < 60) {
            $pdo->rollBack();
            throw new RuntimeException("Please wait 60 seconds before requesting another code.");
        }

        $windowStart = $challenge["window_started_at"] ? strtotime($challenge["window_started_at"]) : 0;
        $sentCount = $windowStart < $now - 3600 ? 0 : (int)$challenge["sent_count"];
        if ($sentCount >= 5) {
            $pdo->rollBack();
            throw new RuntimeException("Too many codes have been requested. Try again in about an hour.");
        }

        $windowStartedAt = $sentCount === 0 ? date("Y-m-d H:i:s", $now) : $challenge["window_started_at"];
        $update = $pdo->prepare(
            "UPDATE phone_otp_challenges
             SET otp_hash=?, purpose=?, expires_at=?, last_sent_at=?, window_started_at=?,
                 sent_count=?, failed_attempts=0
             WHERE phone=?"
        );
        $update->execute([
            $hash,
            $purpose,
            date("Y-m-d H:i:s", $now + 300),
            date("Y-m-d H:i:s", $now),
            $windowStartedAt,
            $sentCount + 1,
            $phone,
        ]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    try {
        send_whatsapp_otp($phone, $code);
    } catch (RuntimeException $e) {
        $pdo->prepare("UPDATE phone_otp_challenges SET otp_hash=NULL, expires_at=NULL WHERE phone=?")->execute([$phone]);
        throw $e;
    }
}

function verify_phone_otp(PDO $pdo, string $phone, string $code, string $purpose): void
{
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM phone_otp_challenges WHERE phone=? FOR UPDATE");
        $stmt->execute([$phone]);
        $challenge = $stmt->fetch();
        if (!$challenge || $challenge["purpose"] !== $purpose || !$challenge["otp_hash"]
            || !$challenge["expires_at"] || strtotime($challenge["expires_at"]) < time()) {
            $pdo->rollBack();
            throw new RuntimeException("The code is incorrect or expired. Request a new code and try again.");
        }
        if ((int)$challenge["failed_attempts"] >= 5) {
            $pdo->rollBack();
            throw new RuntimeException("Too many incorrect codes. Request a new code and try again.");
        }
        if (!preg_match('/^[0-9]{6}$/', $code) || !password_verify($code, $challenge["otp_hash"])) {
            $attempts = (int)$challenge["failed_attempts"] + 1;
            $update = $pdo->prepare(
                "UPDATE phone_otp_challenges SET failed_attempts=?, otp_hash=IF(? >= 5, NULL, otp_hash) WHERE phone=?"
            );
            $update->execute([$attempts, $attempts, $phone]);
            $pdo->commit();
            throw new RuntimeException("The code is incorrect or expired. Request a new code and try again.");
        }
        $pdo->prepare("UPDATE phone_otp_challenges SET otp_hash=NULL, expires_at=NULL, failed_attempts=0 WHERE phone=?")
            ->execute([$phone]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function send_smtp_email(string $to, string $subject, string $body, ?string $replyTo = null): void
{
    $host = required_env("SMTP_HOST");
    $port = (int)(getenv("SMTP_PORT") ?: 587);
    $username = getenv("SMTP_USERNAME") ?: "";
    $password = getenv("SMTP_PASSWORD") ?: "";
    $fromEmail = required_env("SMTP_FROM_EMAIL");
    $fromName = getenv("SMTP_FROM_NAME") ?: "BookSwap";
    $encryption = strtolower(getenv("SMTP_ENCRYPTION") ?: "tls");

    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL)
        || ($replyTo && !filter_var($replyTo, FILTER_VALIDATE_EMAIL))
        || $port < 1 || $port > 65535 || !in_array($encryption, ["tls", "ssl", "none"], true)
        || (($username === "") !== ($password === ""))
        || ($username !== "" && $encryption === "none")) {
        throw new RuntimeException("The SMTP email configuration is invalid.");
    }
    if (preg_match('/[\r\n]/', $host . $username . $fromEmail . $fromName . $to . ($replyTo ?? ""))) {
        throw new RuntimeException("The SMTP email configuration contains invalid characters.");
    }

    $transport = $encryption === "ssl" ? "ssl" : "tcp";
    $context = stream_context_create(["ssl" => ["verify_peer" => true, "verify_peer_name" => true]]);
    $socket = @stream_socket_client(
        $transport . "://" . $host . ":" . $port,
        $errorNumber,
        $errorMessage,
        10,
        STREAM_CLIENT_CONNECT,
        $context
    );
    if (!$socket) {
        throw new RuntimeException("Could not connect to the configured SMTP server.");
    }
    stream_set_timeout($socket, 10);

    try {
        smtp_expect_response($socket, [220]);
        smtp_command($socket, "EHLO localhost", [250]);
        if ($encryption === "tls") {
            smtp_command($socket, "STARTTLS", [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException("Could not establish a secure SMTP connection.");
            }
            smtp_command($socket, "EHLO localhost", [250]);
        }
        if ($username !== "") {
            smtp_command($socket, "AUTH LOGIN", [334]);
            smtp_command($socket, base64_encode($username), [334]);
            smtp_command($socket, base64_encode($password), [235]);
        }
        smtp_command($socket, "MAIL FROM:<" . $fromEmail . ">", [250]);
        smtp_command($socket, "RCPT TO:<" . $to . ">", [250, 251]);
        smtp_command($socket, "DATA", [354]);

        $headers = [
            "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <" . $fromEmail . ">",
            "To: <" . $to . ">",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit",
            "Date: " . gmdate("D, d M Y H:i:s") . " +0000",
        ];
        if ($replyTo) {
            $headers[] = "Reply-To: <" . $replyTo . ">";
        }
        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= preg_replace('/\r\n|\r|\n/', "\r\n", $body);
        $message = preg_replace('/^\./m', '..', $message);
        smtp_write($socket, $message . "\r\n.\r\n");
        smtp_expect_response($socket, [250]);
        smtp_command($socket, "QUIT", [221]);
    } finally {
        fclose($socket);
    }
}

function smtp_command($socket, string $command, array $expectedCodes): string
{
    smtp_write($socket, $command . "\r\n");
    return smtp_expect_response($socket, $expectedCodes);
}

function smtp_write($socket, string $data): void
{
    while ($data !== "") {
        $written = fwrite($socket, $data);
        if ($written === false || $written === 0) {
            throw new RuntimeException("The SMTP server connection was interrupted.");
        }
        $data = substr($data, $written);
    }
}

function smtp_expect_response($socket, array $expectedCodes): string
{
    $response = "";
    do {
        $line = fgets($socket, 515);
        if ($line === false || !preg_match('/^([0-9]{3})([ -])/', $line, $matches)) {
            throw new RuntimeException("The SMTP server returned an invalid response.");
        }
        $response = $line;
        $continue = $matches[2] === "-";
    } while ($continue);

    if (!in_array((int)substr($response, 0, 3), $expectedCodes, true)) {
        throw new RuntimeException("The SMTP server rejected an email command (code " . (int)substr($response, 0, 3) . ").");
    }
    return $response;
}

function send_request_notification(PDO $pdo, int $requestId): void
{
    $stmt = $pdo->prepare(
        "SELECT b.title, b.author, r.request_type, r.message, requester.name AS requester_name,
                requester.email AS requester_email, requester.phone AS requester_phone,
                seller.email AS seller_email
         FROM book_requests r
         JOIN books b ON b.id = r.book_id
         JOIN users requester ON requester.id = r.requester_id
         JOIN users seller ON seller.id = b.user_id
         WHERE r.id = ?"
    );
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if (!$request) {
        throw new RuntimeException("Could not load the saved request for email notification.");
    }

    $baseUrl = rtrim(getenv("APP_BASE_URL") ?: "http://localhost/BookSwap", "/");
    if (preg_match('/[\r\n]/', $baseUrl)) {
        throw new RuntimeException("The site URL for email notifications is invalid.");
    }
    $body = "A student has sent you a BookSwap request.\n\n"
        . "Book: " . $request["title"] . " by " . $request["author"] . "\n"
        . "Request type: " . ucfirst($request["request_type"]) . "\n"
        . "From: " . $request["requester_name"] . "\n"
        . "Email: " . $request["requester_email"] . "\n"
        . "Phone: " . ($request["requester_phone"] ?: "Not provided") . "\n"
        . "Message: " . ($request["message"] ?: "No message added.") . "\n\n"
        . "View and respond to this request: " . $baseUrl . "/requests.php\n";
    send_smtp_email(
        $request["seller_email"],
        "New BookSwap request: " . $request["title"],
        $body,
        $request["requester_email"]
    );
}
