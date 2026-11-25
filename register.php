<?php
require_once "config.php";
require_once "services.php";
if (logged_in()) { header("Location: index.php"); exit; }

$page_title = "Create account";
$error = "";
$name = trim($_POST["name"] ?? "");
$email = strtolower(trim($_POST["email"] ?? ""));
$college = trim($_POST["college"] ?? "");
$phoneInput = trim($_POST["phone"] ?? "");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    verify_csrf();
    $phone = $phoneInput === "" ? null : normalize_phone($phoneInput);
    $password = $_POST["password"] ?? "";
    if (!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8
        || ($phoneInput !== "" && !$phone)) {
        $error = "Enter your name, a valid email, a password with at least 8 characters, and a valid phone number if provided.";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email=? OR phone=?");
        $check->execute([$email, $phone]);
        if ($check->fetch()) {
            $error = "An account already uses this email address or phone number.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users(name,email,password,college,phone) VALUES(?,?,?,?,?)");
                $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $college ?: null, $phone]);
                session_regenerate_id(true);
                $_SESSION["user_id"] = (int)$pdo->lastInsertId();
                $_SESSION["user_name"] = $name;
                flash("Welcome to BookSwap! Your account is ready.");
                header("Location: index.php");
                exit;
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
<div class="auth-layout"><div class="auth-aside"><div class="eyebrow">JOIN THE COMMUNITY</div><h1>Make room for<br><span>new stories.</span></h1><p>Pass on the books you’ve finished. Find the ones you need next.</p><div class="auth-quote">“A book is a gift you can open again and again.”</div></div><div class="form-card"><div class="eyebrow">CREATE YOUR ACCOUNT</div><h2>Join BookSwap</h2><p class="form-intro">A few details and you’re in.</p>
<?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="stack-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Full name<input name="name" required value="<?= e($name) ?>" placeholder="Your name"></label><label>Email address<input type="email" name="email" required value="<?= e($email) ?>" placeholder="you@example.com"></label><label>College / Institute <span class="optional">(optional)</span><input name="college" value="<?= e($college) ?>" placeholder="Your college"></label><label>Phone number <span class="optional">(optional)</span><input type="tel" name="phone" value="<?= e($phoneInput) ?>" placeholder="For buyer-seller coordination"></label><label>Password<input type="password" name="password" minlength="8" required placeholder="At least 8 characters"></label><button class="btn btn-primary btn-full">Create account <span>→</span></button></form><p class="switch-auth">Already a member? <a href="login.php">Log in</a></p></div></div>
<?php require "footer.php"; ?>
