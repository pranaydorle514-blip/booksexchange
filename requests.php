<?php
require_once "config.php";require_login();$page_title="Requests";
if($_SERVER["REQUEST_METHOD"]==="POST"){verify_csrf();$rid=(int)($_POST["request_id"]??0);$action=$_POST["action"]??"";
 $stmt=$pdo->prepare("SELECT r.*,b.user_id AS seller_id,b.id AS book_id FROM book_requests r JOIN books b ON r.book_id=b.id WHERE r.id=?");$stmt->execute([$rid]);$r=$stmt->fetch();
 if($r && (int)$r["seller_id"]===(int)$_SESSION["user_id"] && in_array($action,["accepted","declined"],true)){
  $pdo->prepare("UPDATE book_requests SET status=? WHERE id=?")->execute([$action,$rid]);
  if($action==="accepted"){$pdo->prepare("UPDATE books SET status='reserved' WHERE id=?")->execute([$r["book_id"]]);}
  flash("Request ".$action.".");}
 header("Location: requests.php");exit;
}
$incoming=$pdo->prepare("SELECT r.*,b.title,b.author,b.price,u.name AS requester_name,u.email AS requester_email,u.phone AS requester_phone FROM book_requests r JOIN books b ON r.book_id=b.id JOIN users u ON r.requester_id=u.id WHERE b.user_id=? ORDER BY r.created_at DESC");$incoming->execute([$_SESSION["user_id"]]);
$outgoing=$pdo->prepare("SELECT r.*,b.title,b.author,b.price,u.name AS seller_name,u.email AS seller_email,u.phone AS seller_phone FROM book_requests r JOIN books b ON r.book_id=b.id JOIN users u ON b.user_id=u.id WHERE r.requester_id=? ORDER BY r.created_at DESC");$outgoing->execute([$_SESSION["user_id"]]);
require "header.php";
?>
<div class="page-intro"><div class="eyebrow">YOUR BOOKSWAP ACTIVITY</div><h1>Book <span>requests.</span></h1><p>Keep track of the books you want and the requests sent for your listings.</p></div>
<h2 class="subsection-title">Incoming requests</h2><div class="request-list">
<?php $in=$incoming->fetchAll();if(!$in): ?><div class="empty-inline">No one has requested your books yet.</div><?php endif; ?>
<?php foreach($in as $r): ?><article class="request-item"><div class="request-main"><div class="book-subject"><?= e(strtoupper($r["request_type"])) ?> REQUEST</div><h3><?= e($r["title"]) ?></h3><p>From <?= e($r["requester_name"]) ?> · <?= e($r["requester_email"]) ?> <?= $r["requester_phone"]?"· ".e($r["requester_phone"]):"" ?></p><p class="request-message"><?= e($r["message"] ?: "No message added.") ?></p></div><div class="request-actions"><span class="status status-<?= e($r["status"]) ?>"><?= e(ucfirst($r["status"])) ?></span><?php if($r["status"]==="pending"): ?><form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="request_id" value="<?= (int)$r["id"] ?>"><button name="action" value="accepted" class="btn btn-primary btn-small">Accept</button><button name="action" value="declined" class="btn btn-light btn-small">Decline</button></form><?php endif; ?></div></article><?php endforeach; ?></div>
<h2 class="subsection-title outgoing-title">Requests you sent</h2><div class="request-list">
<?php $out=$outgoing->fetchAll();if(!$out): ?><div class="empty-inline">You haven't requested any books yet.</div><?php endif; ?>
<?php foreach($out as $r): ?><article class="request-item"><div class="request-main"><div class="book-subject"><?= e(strtoupper($r["request_type"])) ?> REQUEST</div><h3><?= e($r["title"]) ?></h3><p>Seller: <?= e($r["seller_name"]) ?> · <?= e($r["seller_email"]) ?> <?= $r["seller_phone"]?"· ".e($r["seller_phone"]):"" ?></p><p class="request-message"><?= e($r["message"] ?: "No message added.") ?></p></div><div class="request-actions"><span class="status status-<?= e($r["status"]) ?>"><?= e(ucfirst($r["status"])) ?></span></div></article><?php endforeach; ?></div>
<?php require "footer.php"; ?>
