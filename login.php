<?php
require_once "config.php";
require_once "services.php";
if (logged_in()) { header("Location: index.php"); exit; }

$page_title = "Log in";
$error = "";
$notice = "";
$phoneInput = trim($_POST["phone"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $phone = normalize_phone($phoneInput);
    if (!$phone) {
        $error = "Enter a valid phone number with country code (or a 10-digit Indian number).";
    } else {
        try {
            $action = $_POST["action"] ?? "";
            if ($action === "send_otp") {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone=?");
                $stmt->execute([$phone]);
                if (!$stmt->fetch()) {
                    $error = "No account is registered with that phone number.";
                } else {
                    issue_phone_otp($pdo, $phone, "login");
                    $notice = "A sign-in code was sent to your WhatsApp.";
                }
            } elseif ($action === "verify_otp") {
                verify_phone_otp($pdo, $phone, trim($_POST["otp"] ?? ""), "login");
                $stmt = $pdo->prepare("SELECT id,name FROM users WHERE phone=?");
                $stmt->execute([$phone]);
                $user = $stmt->fetch();
                if (!$user) {
                    throw new RuntimeException("No account is registered with that phone number.");
                }
                session_regenerate_id(true);
                $_SESSION["user_id"] = (int)$user["id"];
                $_SESSION["user_name"] = $user["name"];
                flash("Welcome back, " . $user["name"] . "!");
                header("Location: index.php");
                exit;
            } else {
                $error = "Choose whether to send or verify your sign-in code.";
            }
        } catch (RuntimeException $e) {
            $error = $e->getMessage();
        }
    }
}

require "header.php";
?>
<div class="auth-layout"><div class="auth-aside"><div class="eyebrow">WELCOME BACK</div><h1>Your next read<br>is <span>waiting.</span></h1><p>Log in to manage your listings and connect with other students.</p><div class="auth-quote">Buy less new. Share more knowledge.</div></div><div class="form-card"><div class="eyebrow">STUDENT ACCESS</div><h2>Log in to BookSwap</h2><p class="form-intro">We’ll send a one-time sign-in code to your WhatsApp.</p>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if($notice): ?><div class="alert success"><?= e($notice) ?></div><?php endif; ?>
<?php if(isset($_GET["message"])): ?><div class="alert"><?= e($_GET["message"]) ?></div><?php endif; ?>
<form method="post" class="stack-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>WhatsApp phone number<input type="tel" name="phone" required value="<?= e($phoneInput) ?>" placeholder="+919876543210"></label><label>One-time code<input type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" value="<?= e($_POST["otp"]??"") ?>" placeholder="6-digit code"></label><button class="btn btn-light btn-full" name="action" value="send_otp">Send WhatsApp code</button><button class="btn btn-primary btn-full" name="action" value="verify_otp">Verify and log in <span>→</span></button></form><p class="switch-auth">New to BookSwap? <a href="register.php">Create an account</a></p></div></div>
<?php require "footer.php"; ?>
