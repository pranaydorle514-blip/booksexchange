<?php
// Database connection and shared helpers
if (session_status() === PHP_SESSION_NONE) session_start();

$host = getenv("DB_HOST") ?: "localhost";
$dbname = getenv("DB_NAME") ?: "bookswap";
$dbuser = getenv("DB_USER") ?: "root";
$dbpass = getenv("DB_PASSWORD");
$dbport = filter_var(
    getenv("DB_PORT") ?: "3306",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1, "max_range" => 65535]]
);
if ($dbpass === false) $dbpass = "";
if ($dbport === false) {
    http_response_code(500);
    exit("Database configuration error: DB_PORT must be between 1 and 65535.");
}

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$dbport;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    error_log("BookSwap database connection failed: " . $e->getMessage());
    http_response_code(500);
    die("Database connection failed. Check the DB_* settings and ensure database.sql has been imported.");
}

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function logged_in() { return isset($_SESSION["user_id"]); }
function require_login() {
    if (!logged_in()) {
        header("Location: login.php?message=" . urlencode("Please log in to continue."));
        exit;
    }
}
function flash($message = null) {
    if ($message !== null) { $_SESSION["flash"] = $message; return; }
    $value = $_SESSION["flash"] ?? "";
    unset($_SESSION["flash"]);
    return $value;
}
function csrf_token() {
    if (empty($_SESSION["csrf"])) $_SESSION["csrf"] = bin2hex(random_bytes(32));
    return $_SESSION["csrf"];
}
function verify_csrf() {
    if ($_SERVER["REQUEST_METHOD"] === "POST" &&
        !hash_equals($_SESSION["csrf"] ?? "", $_POST["csrf"] ?? "")) {
        http_response_code(403); exit("Invalid form token. Please go back and try again.");
    }
}
function money($amount) {
    return $amount === null ? "Free" : "₹" . number_format((float)$amount, 2);
}
?>
