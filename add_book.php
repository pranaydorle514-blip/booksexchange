<?php
require_once "config.php"; require_login();
$page_title="List a book"; $error="";
$form_data = $_SERVER["REQUEST_METHOD"] === "POST" ? $_POST : $_GET;
if($_SERVER["REQUEST_METHOD"]==="POST"){
    verify_csrf();
    $title=trim($_POST["title"]??""); $author=trim($_POST["author"]??""); $subject=trim($_POST["subject"]??"");
    $branch=trim($_POST["branch"]??""); $semester=trim($_POST["semester"]??""); $condition=$_POST["book_condition"]??"Good";
    $price=trim($_POST["price"]??""); $description=trim($_POST["description"]??""); $exchange=isset($_POST["exchange_available"])?1:0;
    if(!$title || !$author || !$subject || !$description || mb_strlen($description,"UTF-8")>600 || !in_array($condition,["Like New","Good","Used"],true) || ($price!=="" && (!is_numeric($price)||$price<0))) $error="Please complete the required fields and provide a short overview of up to 600 characters.";
    else {
        $stmt=$pdo->prepare("INSERT INTO books(user_id,title,author,subject,branch,semester,book_condition,price,description,exchange_available) VALUES(?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$_SESSION["user_id"],$title,$author,$subject,$branch,$semester,$condition,$price===""?null:(float)$price,$description,$exchange]);
        flash("Your book has been listed successfully."); header("Location: my_listings.php"); exit;
    }
}
require "header.php";
?>
<div class="page-intro"><div class="eyebrow">GIVE YOUR BOOK A NEW CHAPTER</div><h1>List a <span>book.</span></h1><p>Add a few details so another student can find exactly what they need.</p></div>
<div class="form-card wide-form"><?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="stack-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<p class="form-intro">Title, author and subject are filled from Open Library. Confirm them, then enter the condition and price of your actual copy.</p>
<div class="form-grid"><label>Book title *<input name="title" required value="<?= e($form_data["title"]??"") ?>" placeholder="e.g. Engineering Mathematics"></label><label>Author *<input name="author" required value="<?= e($form_data["author"]??"") ?>" placeholder="Author name"></label><label>Subject *<input name="subject" required value="<?= e($form_data["subject"]??"") ?>" placeholder="e.g. Mathematics"></label><label>Branch / Course<input name="branch" value="<?= e($form_data["branch"]??"") ?>" placeholder="e.g. Computer Engineering"></label><label>Semester<input name="semester" value="<?= e($form_data["semester"]??"") ?>" placeholder="e.g. Semester 1"></label><label>Book condition *<select name="book_condition"><option <?= ($form_data["book_condition"]??"Good")==="Like New"?"selected":"" ?>>Like New</option><option <?= ($form_data["book_condition"]??"Good")==="Good"?"selected":"" ?>>Good</option><option <?= ($form_data["book_condition"]??"Good")==="Used"?"selected":"" ?>>Used</option></select></label><label>Price in ₹ <span class="optional">(leave blank if free)</span><input type="number" name="price" min="0" step="0.01" value="<?= e($form_data["price"]??"") ?>" placeholder="e.g. 250"></label></div>
<label>Short overview for buyers *<textarea name="description" rows="4" maxlength="600" required placeholder="Write a brief summary in your own words. Include the edition or ISBN if known; do not paste book pages."><?= e($form_data["description"]??"") ?></textarea></label>
<label class="check-label"><input type="checkbox" name="exchange_available" checked> Open to exchanging this book</label>
<button class="btn btn-primary">Publish listing <span>→</span></button></form></div>
<?php require "footer.php"; ?>
