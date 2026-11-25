<?php
require_once "config.php";
require_once "services.php";
if (logged_in()) { header("Location: index.php"); exit; }

$page_title = "Create account";
$error = "";
$notice = "";
$name = trim($_POST["name"] ?? "");
$email = strtolower(trim($_POST["email"] ?? ""));
$college = trim($_POST["college"] ?? "");
$phoneInput = trim($_POST["phone"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $phone = normalize_phone($phoneInput);
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$phone) {
        $error = "Enter your name, a valid email, and a valid phone number with country code (or a 10-digit Indian number).";
    } else {
        $duplicate = $pdo->prepare("SELECT id FROM users WHERE email=? OR phone=?");
        $duplicate->execute([$email, $phone]);
        if ($duplicate->fetch()) {
            $error = "An account already uses this email address or phone number.";
        } else {
            try {
                $action = $_POST["action"] ?? "";
                if ($action === "send_otp") {
                    issue_phone_otp($pdo, $phone, "register");
                    $notice = "A verification code was sent to your WhatsApp. Enter it below to finish creating your account.";
                } elseif ($action === "verify_registration") {
                    verify_phone_otp($pdo, $phone, trim($_POST["otp"] ?? ""), "register");
                    $stmt = $pdo->prepare("INSERT INTO users(name,email,password,college,phone) VALUES(?,?,NULL,?,?)");
                    $stmt->execute([$name, $email, $college ?: null, $phone]);
                    session_regenerate_id(true);
                    $_SESSION["user_id"] = (int)$pdo->lastInsertId();
                    $_SESSION["user_name"] = $name;
                    flash("Welcome to BookSwap! Your account is ready.");
                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Choose whether to send a verification code or create your account.";
                }
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            } catch (PDOException $e) {
                if ($e->getCode() === "23000") {
                    $error = "An account already uses this email address or phone number.";
                } else {
                    throw $e;
                }
            }
        }
    }
}

require "header.php";
?>
<div class="auth-layout"><div class="auth-aside"><div class="eyebrow">JOIN THE COMMUNITY</div><h1>Make room for<br><span>new stories.</span></h1><p>Pass on the books you’ve finished. Find the ones you need next.</p><div class="auth-quote">“A book is a gift you can open again and again.”</div></div><div class="form-card"><div class="eyebrow">CREATE YOUR ACCOUNT</div><h2>Join BookSwap</h2><p class="form-intro">Verify your WhatsApp number to create your account.</p>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<?php if($notice): ?><div class="alert success"><?= e($notice) ?></div><?php endif; ?>
<form method="post" class="stack-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Full name<input name="name" required value="<?= e($name) ?>" placeholder="Your name"></label><label>Email address<input type="email" name="email" required value="<?= e($email) ?>" placeholder="you@example.com"></label><label>College / Institute <span class="optional">(optional)</span><input name="college" value="<?= e($college) ?>" placeholder="Your college"></label><label>WhatsApp phone number<input type="tel" name="phone" required value="<?= e($phoneInput) ?>" placeholder="+919876543210"><span class="optional">Use country code. 10-digit Indian numbers are also accepted.</span></label><label>One-time code<input type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" value="<?= e($_POST["otp"]??"") ?>" placeholder="6-digit code"></label><button class="btn btn-light btn-full" name="action" value="send_otp">Send WhatsApp verification code</button><button class="btn btn-primary btn-full" name="action" value="verify_registration">Verify and create account <span>→</span></button></form><p class="switch-auth">Already a member? <a href="login.php">Log in</a></p></div></div>
<?php require "footer.php"; ?>
