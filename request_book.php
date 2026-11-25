<?php
require_once "config.php"; require_once "services.php"; require_login();
$id=(int)($_GET["id"]??0); $stmt=$pdo->prepare("SELECT * FROM books WHERE id=? AND status='available'");$stmt->execute([$id]);$book=$stmt->fetch();
if(!$book){flash("This book is not available anymore.");header("Location: index.php");exit;}
if((int)$book["user_id"]===(int)$_SESSION["user_id"]){flash("You cannot request your own book.");header("Location: book.php?id=$id");exit;}
$error="";
if($_SERVER["REQUEST_METHOD"]==="POST"){
 verify_csrf();$type=$_POST["request_type"]??"buy";$message=trim($_POST["message"]??"");
 if(!in_array($type,["buy","exchange"],true))$error="Choose a valid request type.";
 elseif($type==="exchange" && !$book["exchange_available"])$error="The seller has not enabled exchanges.";
 else{
  $check=$pdo->prepare("SELECT id FROM book_requests WHERE book_id=? AND requester_id=? AND status='pending'");$check->execute([$id,$_SESSION["user_id"]]);
  if($check->fetch())$error="You already have a pending request for this book.";
  else{$ins=$pdo->prepare("INSERT INTO book_requests(book_id,requester_id,request_type,message) VALUES(?,?,?,?)");$ins->execute([$id,$_SESSION["user_id"],$type,$message]);$requestId=(int)$pdo->lastInsertId();try{send_request_notification($pdo,$requestId);flash("Your request was sent and the seller was notified by email.");}catch(RuntimeException $e){error_log("BookSwap request email failed for request ".$requestId.": ".$e->getMessage());flash("Your request was saved, but the seller email could not be sent. Please contact them through Requests.");}header("Location: requests.php");exit;}
 }
}
$page_title="Request book";require "header.php";
?>
<div class="page-intro"><div class="eyebrow">CONNECT WITH THE SELLER</div><h1>Request this <span>book.</span></h1><p><?= e($book["title"]) ?> by <?= e($book["author"]) ?></p></div>
<div class="form-card request-card"><?php if($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?><form method="post" class="stack-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label>Request type<select name="request_type"><option value="buy">Buy · <?= money($book["price"]) ?></option><?php if($book["exchange_available"]): ?><option value="exchange">Exchange · Offer another book</option><?php endif; ?></select></label><label>Message to seller<textarea name="message" rows="5" placeholder="Hi! I’m interested in this book. Can we arrange a pickup on campus?"></textarea></label><button class="btn btn-primary">Send request <span>→</span></button></form></div>
<?php require "footer.php"; ?>
