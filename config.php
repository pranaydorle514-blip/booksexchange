<?php
// Database connection and shared helpers
if (session_status() === PHP_SESSION_NONE) session_start();

$host = "localhost";
$dbname = "bookswap";
$dbuser = "root";
$dbpass = ""; // Default XAMPP MySQL password is empty

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $dbuser,
        $dbpass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die("Database connection failed. Import database.sql in phpMyAdmin and check config.php. Details: " . htmlspecialchars($e->getMessage()));
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
