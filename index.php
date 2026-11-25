<?php
require_once "config.php";
$page_title = "Explore books";
$q = trim($_GET["q"] ?? "");
$subject = trim($_GET["subject"] ?? "");
$condition = trim($_GET["condition"] ?? "");
$max_price = trim($_GET["max_price"] ?? "");
$sql = "SELECT b.*, u.name AS seller_name, u.college FROM books b JOIN users u ON b.user_id=u.id WHERE b.status='available'";
$params = [];
if ($q !== "") {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.subject LIKE ? OR b.description LIKE ?)";
    $like = "%$q%"; array_push($params, $like, $like, $like, $like);
}
if ($subject !== "") { $sql .= " AND b.subject = ?"; $params[] = $subject; }
if ($condition !== "") { $sql .= " AND b.book_condition = ?"; $params[] = $condition; }
if ($max_price !== "" && is_numeric($max_price)) { $sql .= " AND (b.price IS NULL OR b.price <= ?)"; $params[] = (float)$max_price; }
$sql .= " ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params); $books = $stmt->fetchAll();
$subjects = $pdo->query("SELECT DISTINCT subject FROM books WHERE status='available' AND subject<>'' ORDER BY subject")->fetchAll(PDO::FETCH_COLUMN);
require "header.php";
?>
<section class="hero">
  <div class="hero-copy">
    <div class="eyebrow"><span class="eyebrow-dot"></span> THE STUDENT BOOK MARKETPLACE</div>
    <h1>Every book deserves<br>a <span>second chapter.</span></h1>
    <p>Find affordable textbooks, pass on the ones you’ve finished, and help knowledge travel further.</p>
    <div class="hero-actions"><a class="btn btn-primary" href="#browse">Explore books <span>↗</span></a><a class="btn btn-light" href="<?= logged_in() ? "add_book.php" : "register.php" ?>">＋ List your book</a></div>
    <div class="hero-trust"><span>✓ Student-friendly</span><span>✓ Affordable learning</span><span>✓ Reuse more</span></div>
  </div>
  <div class="hero-art">
    <div class="art-orbit orbit-one"></div><div class="art-orbit orbit-two"></div>
    <div class="book-stack"><div class="book book-a"><b>ENGINEERING<br>MATHEMATICS</b><small>SECOND EDITION</small></div><div class="book book-b"><b>THE ART OF<br>LEARNING</b><small>STUDY SMART</small></div><div class="book book-c"><b>DATA<br>STRUCTURES</b><small>CORE CONCEPTS</small></div></div>
    <div class="floating-note note-top">↗ Give books a new home</div><div class="floating-note note-bottom"><strong>Save more.</strong><br><span>Learn without limits.</span></div>
  </div>
</section>
<section class="stats-strip">
  <div><strong><?= number_format((int)$pdo->query("SELECT COUNT(*) FROM books WHERE status='available'")->fetchColumn()) ?>+</strong><span>Books listed</span></div>
  <div><strong><?= number_format((int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn()) ?>+</strong><span>Student members</span></div>
  <div><strong>One campus</strong><span>One sharing community</span></div>
</section>
<section class="browse-section" id="browse">
  <div class="section-heading"><div><div class="eyebrow">YOUR NEXT GREAT FIND</div><h2>Explore the bookshelf<span>.</span></h2><p>Useful reads, friendly prices, and books ready for their next owner.</p></div><div class="results-count"><?= count($books) ?> books found</div></div>
  <form class="filter-panel" method="get" action="index.php">
    <div class="search-field"><span>⌕</span><input type="search" name="q" placeholder="Search title, author or subject..." value="<?= e($q) ?>"></div>
    <select name="subject"><option value="">All subjects</option><?php foreach($subjects as $s): ?><option value="<?= e($s) ?>" <?= $subject===$s?"selected":"" ?>><?= e($s) ?></option><?php endforeach; ?></select>
    <select name="condition"><option value="">Any condition</option><?php foreach(["Like New","Good","Used"] as $c): ?><option <?= $condition===$c?"selected":"" ?>><?= e($c) ?></option><?php endforeach; ?></select>
    <input class="price-filter" type="number" min="0" name="max_price" placeholder="Max ₹" value="<?= e($max_price) ?>">
    <button class="btn btn-dark" type="submit">Search</button>
    <?php if ($q || $subject || $condition || $max_price !== ""): ?><a class="clear-filter" href="index.php">Clear</a><?php endif; ?>
  </form>
  <?php if (!$books): ?>
    <div class="empty-state"><div class="empty-icon">⌕</div><h3>No books found</h3><p>Try a different search or be the first to list this book.</p><a class="btn btn-primary" href="<?= logged_in()?"add_book.php":"register.php" ?>">List a book</a></div>
  <?php else: ?>
    <div class="book-grid">
    <?php foreach($books as $book): ?>
      <article class="book-card">
        <a class="cover-wrap" href="book.php?id=<?= (int)$book["id"] ?>">
          <div class="cover-placeholder cover-<?= (int)$book["id"]%5 ?>"><span class="cover-label"><?= e($book["subject"] ?: "STUDENT EDITION") ?></span><strong><?= e($book["title"]) ?></strong><small><?= e($book["author"]) ?></small><i>BOOKSWAP</i></div>
          <span class="condition-tag"><?= e($book["book_condition"]) ?></span>
        </a>
        <div class="book-info"><div class="book-subject"><?= e($book["subject"] ?: "Academic book") ?></div><h3><a href="book.php?id=<?= (int)$book["id"] ?>"><?= e($book["title"]) ?></a></h3><p class="book-author">by <?= e($book["author"]) ?></p>
          <div class="book-meta"><span class="book-price"><?= money($book["price"]) ?></span><span class="book-seller">by <?= e($book["seller_name"]) ?></span></div>
          <a class="card-link" href="book.php?id=<?= (int)$book["id"] ?>">View details <span>→</span></a>
        </div>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
<section class="browse-section open-library-section" id="open-library">
  <div class="section-heading">
    <div><div class="eyebrow">OPEN LIBRARY CATALOG</div><h2>Discover more books<span>.</span></h2><p>Browse up to 1,000 catalog books with author names. Choose a title to prefill a listing for your own physical copy.</p></div>
    <div class="results-count" id="openLibraryCount" aria-live="polite">Loading books…</div>
  </div>
  <form class="filter-panel open-library-search" id="openLibrarySearch">
    <label class="search-field"><span>⌕</span><input id="openLibraryQuery" type="search" placeholder="Search Open Library by title, author or subject..." value="education" aria-label="Search Open Library"></label>
    <button class="btn btn-dark" type="submit">Search catalog</button>
  </form>
  <div id="openLibraryMessage" class="empty-inline" role="status">Loading books from Open Library…</div>
  <div class="book-grid" id="openLibraryResults" aria-live="polite"></div>
  <div class="open-library-load-more-wrap"><button class="btn btn-light" id="openLibraryLoadMore" type="button" hidden>Load more books</button></div>
</section>
<section class="how-section"><div class="eyebrow">SIMPLE BY DESIGN</div><h2>Pass it on in <span>three steps.</span></h2><div class="steps-grid"><div class="step"><b>01</b><div class="step-icon">⌕</div><h3>Find your book</h3><p>Search by subject, title, author or condition.</p></div><div class="step"><b>02</b><div class="step-icon">↗</div><h3>Connect with a student</h3><p>View listing details and contact the seller directly.</p></div><div class="step"><b>03</b><div class="step-icon">↻</div><h3>Give it a new chapter</h3><p>Arrange a safe campus handover or exchange.</p></div></div></section>
<?php require "footer.php"; ?>
