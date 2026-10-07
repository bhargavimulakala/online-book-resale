<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];

$items = $conn->query("SELECT w.*, b.title, b.author, b.selling_price, b.condition_type, b.quantity as stock, b.status,
    (SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as img
    FROM wishlist w JOIN books b ON w.book_id=b.id WHERE w.user_id=$userId ORDER BY w.added_at DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Wishlist';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <h4 class="text-white fw-bold mb-4"><i class="bi bi-heart me-2 text-gold"></i>My Wishlist <span class="badge bg-gold text-dark ms-2"><?= count($items) ?></span></h4>
    <?= renderFlash() ?>
    <?php if (empty($items)): ?>
    <div class="text-center py-5">
        <i class="bi bi-heart" style="font-size:5rem;color:#d4af37;opacity:0.4;"></i>
        <h4 class="text-white mt-4">Your wishlist is empty</h4>
        <p class="text-muted mb-4">Save books you love to buy them later.</p>
        <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-gold btn-lg">Browse Books</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($items as $item):
            $imgUrl = getBookImageUrl($item['img']); ?>
        <div class="col-md-6 col-lg-4" id="wishItem_<?= $item['book_id'] ?>">
            <div class="wishlist-card">
                <div class="position-relative overflow-hidden" style="height:200px;">
                    <img src="<?= $imgUrl ?>" alt="<?= sanitize($item['title']) ?>" style="width:100%;height:100%;object-fit:cover;" onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                    <div style="position:absolute;top:10px;right:10px;"><?= conditionBadge($item['condition_type']) ?></div>
                    <?php if ($item['status']!=='approved' || $item['stock']<=0): ?>
                    <div style="position:absolute;inset:0;background:rgba(0,0,0,0.6);display:flex;align-items:center;justify-content:center;"><span class="badge bg-danger fs-6">Unavailable</span></div>
                    <?php endif; ?>
                </div>
                <div class="p-3">
                    <h6 class="text-white fw-bold mb-1"><a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $item['book_id'] ?>" class="text-white text-decoration-none"><?= sanitize($item['title']) ?></a></h6>
                    <small class="text-muted">by <?= sanitize($item['author']) ?></small>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="text-gold fw-bold fs-5"><?= formatPrice($item['selling_price']) ?></span>
                        <small class="text-muted"><?= timeAgo($item['added_at']) ?></small>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <?php if ($item['status']==='approved' && $item['stock']>0): ?>
                        <button class="btn btn-gold btn-sm flex-grow-1" onclick="moveToCart(<?= $item['book_id'] ?>,<?= $item['id'] ?>)"><i class="bi bi-cart-plus me-1"></i>Add to Cart</button>
                        <?php endif; ?>
                        <button class="btn btn-outline-danger btn-sm" onclick="removeWish(<?= $item['book_id'] ?>,<?= $item['id'] ?>)"><i class="bi bi-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<script>
function moveToCart(bookId, wishId) {
    addToCart(bookId, 1);
    setTimeout(() => removeWish(bookId, wishId), 600);
}
function removeWish(bookId, wishId) {
    fetch('/online-book-resale/ajax/wishlist_action.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`book_id=${bookId}`})
    .then(r=>r.json()).then(d=>{
        if(d.success){showToast('Removed from wishlist','info');document.getElementById('wishItem_'+bookId)?.remove();}
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

