<?php
require_once "config.php";
if (logged_in()) { header("Location: index.php"); exit; }

$page_title = "Log in";
$error = "";
$emailInput = strtolower(trim($_POST["email"] ?? ""));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $password = $_POST["password"] ?? "";
    $stmt = $pdo->prepare("SELECT id, name, password FROM users WHERE email=?");
    $stmt->execute([$emailInput]);
    $user = $stmt->fetch();
    if ($user && $user["password"] && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = (int)$user["id"];
        $_SESSION["user_name"] = $user["name"];
        flash("Welcome back, " . $user["name"] . "!");
        header("Location: index.php");
        exit;
    }
    $error = "Incorrect email or password.";
}

require "header.php";
?>
<div class="auth-layout"><div class="auth-aside"><div class="eyebrow">WELCOME BACK</div><h1>Your next read<br>is <span>waiting.</span></h1><p>Log in to manage your listings and connect with other students.</p><div class="auth-quote">Buy less new. Share more knowledge.</div></div><div class="form-card"><div class="eyebrow">STUDENT ACCESS</div><h2>Log in to BookSwap</h2><p class="form-intro">Good to see you again.</p>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if(isset($_GET["message"])): ?><div class="alert"><?= e($_GET["message"]) ?></div><?php endif; ?>
<form method="post" class="stack-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Email address<input type="email" name="email" required value="<?= e($emailInput) ?>" placeholder="you@example.com"></label><label>Password<input type="password" name="password" required placeholder="Your password"></label><button class="btn btn-primary btn-full">Log in <span>→</span></button></form><p class="switch-auth">New to BookSwap? <a href="register.php">Create an account</a></p></div></div>
<?php require "footer.php"; ?>
