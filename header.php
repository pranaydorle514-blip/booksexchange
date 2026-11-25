<?php require_once __DIR__ . "/config.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? e($page_title) . " | " : "" ?>BookSwap</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">B</span><span>Book<span class="brand-accent">Swap</span><small>READ • REUSE • REPEAT</small></span></a>
  <button class="menu-toggle" id="menuToggle" aria-label="Toggle navigation">☰</button>
  <nav class="nav-links" id="navLinks">
    <a href="index.php">Explore books</a>
    <?php if (logged_in()): ?>
      <a href="my_listings.php">My listings</a>
      <a href="requests.php">Requests</a>
      <a class="nav-cta" href="add_book.php">＋ Sell a book</a>
      <span class="nav-user">Hi, <?= e($_SESSION["user_name"] ?? "Student") ?></span>
      <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Log in</a>
      <a class="nav-cta" href="register.php">Join BookSwap</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
<?php if ($message = flash()): ?><div class="alert success"><?= e($message) ?></div><?php endif; ?>
