<?php
require_once "config.php";require_login();$page_title="My listings";
if($_SERVER["REQUEST_METHOD"]==="POST"){verify_csrf();$id=(int)($_POST["book_id"]??0);$action=$_POST["action"]??"";
 if($action==="delete")$stmt=$pdo->prepare("DELETE FROM books WHERE id=? AND user_id=?");
 else $stmt=$pdo->prepare("UPDATE books SET status=? WHERE id=? AND user_id=?");
 if($action==="delete")$stmt->execute([$id,$_SESSION["user_id"]]);
 elseif(in_array($action,["available","sold"],true))$stmt->execute([$action,$id,$_SESSION["user_id"]]);
 flash("Listing updated.");header("Location: my_listings.php");exit;
}
$stmt=$pdo->prepare("SELECT * FROM books WHERE user_id=? ORDER BY created_at DESC");$stmt->execute([$_SESSION["user_id"]]);$books=$stmt->fetchAll();require "header.php";
?>
<div class="page-intro page-intro-row"><div><div class="eyebrow">YOUR BOOKSHELF</div><h1>My <span>listings.</span></h1><p>Manage the books you’ve shared with the community.</p></div><a class="btn btn-primary" href="add_book.php">＋ Add a book</a></div>
<?php if(!$books): ?><div class="empty-state"><div class="empty-icon">＋</div><h3>Your bookshelf is waiting</h3><p>List your first book and help another student find it.</p><a class="btn btn-primary" href="add_book.php">List your first book</a></div><?php else: ?>
<div class="listing-table"><div class="listing-head"><span>BOOK</span><span>PRICE</span><span>STATUS</span><span>ACTIONS</span></div>
<?php foreach($books as $b): ?><div class="listing-row"><div><strong><?= e($b["title"]) ?></strong><small><?= e($b["author"]) ?> · <?= e($b["subject"]) ?></small></div><b><?= money($b["price"]) ?></b><span class="status status-<?= e($b["status"]) ?>"><?= e(ucfirst($b["status"])) ?></span><div class="listing-actions"><a href="book.php?id=<?= (int)$b["id"] ?>">View</a><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="book_id" value="<?= (int)$b["id"] ?>"><?php if($b["status"]!=="sold"): ?><button name="action" value="sold">Mark sold</button><?php else: ?><button name="action" value="available">Relist</button><?php endif; ?><button name="action" value="delete" class="delete-link" onclick="return confirm('Delete this listing?')">Delete</button></form></div></div><?php endforeach; ?></div><?php endif; ?>
<?php require "footer.php"; ?>
