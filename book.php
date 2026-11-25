<?php
require_once "config.php";
$id=(int)($_GET["id"]??0);
$stmt=$pdo->prepare("SELECT b.*,u.name AS seller_name,u.email AS seller_email,u.phone AS seller_phone,u.college FROM books b JOIN users u ON b.user_id=u.id WHERE b.id=?");
$stmt->execute([$id]); $book=$stmt->fetch();
if(!$book){http_response_code(404);$page_title="Book not found";require "header.php";echo '<div class="empty-state"><h2>Book not found</h2><a href="index.php">Back to books</a></div>';require "footer.php";exit;}
$preview_error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_preview"])) {
    require_login();
    verify_csrf();
    if ((int)$book["user_id"] !== (int)$_SESSION["user_id"]) {
        http_response_code(403);
        exit("You can only update your own book preview.");
    }
    $submitted_preview = trim($_POST["description"] ?? "");
    if ($submitted_preview === "" || mb_strlen($submitted_preview, "UTF-8") > 600) {
        $preview_error = "Enter a short overview of up to 600 characters.";
    } else {
        $update = $pdo->prepare("UPDATE books SET description=? WHERE id=? AND user_id=?");
        $update->execute([$submitted_preview, $id, $_SESSION["user_id"]]);
        flash("Book overview updated.");
        header("Location: book.php?id=" . $id);
        exit;
    }
}
$preview_form_value = $preview_error !== "" ? ($_POST["description"] ?? "") : ($book["description"] ?? "");
$book_preview = trim($book["description"] ?? "");
if (mb_strlen($book_preview, "UTF-8") > 600) $book_preview = mb_substr($book_preview, 0, 600, "UTF-8");
$page_title=$book["title"]; require "header.php";
?>
<div class="detail-back"><a href="index.php">← Back to explore</a></div>
<div class="detail-layout">
  <div class="detail-cover cover-<?= (int)$book["id"]%5 ?>"><span class="cover-label"><?= e($book["subject"]) ?></span><strong><?= e($book["title"]) ?></strong><small><?= e($book["author"]) ?></small><i>BOOKSWAP</i></div>
  <div class="detail-content">
    <div class="eyebrow"><?= e($book["subject"]) ?> · <?= e($book["branch"] ?: "Academic book") ?></div>
    <h1><?= e($book["title"]) ?></h1>
    <p class="detail-author">by <?= e($book["author"]) ?></p>
    <div class="detail-price"><?= money($book["price"]) ?></div>
    <div class="detail-tags"><span><?= e($book["book_condition"]) ?> condition</span><span><?= $book["exchange_available"]?"Exchange possible":"Sale listing" ?></span><span><?= e($book["status"]) ?></span></div>
    <section class="book-preview-panel" id="bookPreview" data-title="<?= e($book["title"]) ?>" data-author="<?= e($book["author"]) ?>">
      <div class="eyebrow">BUYER PREVIEW</div>
      <h3>Short overview</h3>
      <p class="detail-description"><?= $book_preview !== "" ? nl2br(e($book_preview)) : "The seller has not added a short overview yet. Ask them to describe the book before requesting it." ?></p>
      <p class="preview-note">This is a brief seller-written summary. Full book text is not shown.</p>
      <?php if($preview_error): ?><div class="alert error"><?= e($preview_error) ?></div><?php endif; ?>
      <?php if(logged_in() && (int)$book["user_id"]===(int)$_SESSION["user_id"]): ?>
      <form method="post" class="preview-edit-form">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="bookOverview">Update your short overview</label>
        <textarea id="bookOverview" name="description" rows="3" maxlength="600" required><?= e($preview_form_value) ?></textarea>
        <button class="btn btn-light btn-small" type="submit" name="update_preview" value="1">Save overview</button>
      </form>
      <?php endif; ?>
      <div class="edition-reference">
        <h3>Edition reference</h3>
        <p class="preview-note">Open Library details can help identify the title. Editions may differ, so compare the ISBN with the seller’s physical copy.</p>
        <div id="openLibraryEditionFacts" class="preview-facts" role="status" aria-live="polite">Loading edition details…</div>
      </div>
    </section>
    <div class="seller-card"><div class="seller-avatar"><?= e(strtoupper(substr($book["seller_name"],0,1))) ?></div><div><strong><?= e($book["seller_name"]) ?></strong><span><?= e($book["college"] ?: "BookSwap student member") ?></span></div></div>
<?php if($book["status"]==="available"): ?>
<?php if(logged_in() && (int)$book["user_id"]===(int)$_SESSION["user_id"]): ?><a class="btn btn-dark" href="my_listings.php">Manage your listing</a>
<?php elseif(logged_in()): ?><a class="btn btn-primary" href="request_book.php?id=<?= (int)$book["id"] ?>">Request this book <span>→</span></a>
<?php else: ?><a class="btn btn-primary" href="login.php">Log in to request this book <span>→</span></a><?php endif; ?>
<?php else: ?><div class="alert">This book is no longer available.</div><?php endif; ?>
<p class="safety-note">Safety tip: Meet in a public place on campus and check the book before completing the exchange.</p>
</div></div>
<?php require "footer.php"; ?>
