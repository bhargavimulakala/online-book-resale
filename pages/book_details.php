<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

$bookId = (int)($_GET['id'] ?? 0);
if (!$bookId) { header('Location: ' . SITE_URL . '/pages/books.php'); exit; }

$stmt = $conn->prepare("SELECT b.*, c.name as category_name, u.name as seller_name, u.id as seller_user_id, u.email as seller_email, u.phone as seller_phone, u.created_at as seller_since FROM books b JOIN categories c ON b.category_id=c.id JOIN users u ON b.seller_id=u.id WHERE b.id=? AND b.status='approved'");
$stmt->bind_param('i', $bookId); $stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) { header('Location: ' . SITE_URL . '/pages/books.php'); exit; }

$conn->query("UPDATE books SET views=views+1 WHERE id=$bookId");

$imgs = $conn->prepare("SELECT * FROM book_images WHERE book_id=? ORDER BY is_primary DESC, id ASC");
$imgs->bind_param('i', $bookId); $imgs->execute();
$images = $imgs->get_result()->fetch_all(MYSQLI_ASSOC);

$rating = getAvgRating($bookId, $conn);

$revStmt = $conn->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.book_id=? ORDER BY r.created_at DESC");
$revStmt->bind_param('i', $bookId); $revStmt->execute();
$reviews = $revStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$canReview = false; $existingReview = null; $purchase = null;
if (isLoggedIn()) {
    $userId = (int)$_SESSION['user_id'];
    $pchk = $conn->prepare("SELECT oi.id, o.id as order_id FROM order_items oi JOIN orders o ON oi.order_id=o.id WHERE oi.book_id=? AND o.user_id=? AND o.status='delivered' LIMIT 1");
    $pchk->bind_param('ii', $bookId, $userId); $pchk->execute();
    $purchase = $pchk->get_result()->fetch_assoc();
    if ($purchase) {
        $canReview = true;
        $rchk = $conn->prepare("SELECT * FROM reviews WHERE book_id=? AND user_id=?");
        $rchk->bind_param('ii', $bookId, $userId); $rchk->execute();
        $existingReview = $rchk->get_result()->fetch_assoc();
    }
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['submit_review']) && $canReview) {
        $rText = sanitize($_POST['review_text'] ?? '');
        $rRating = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        if ($existingReview) {
            $upd = $conn->prepare("UPDATE reviews SET rating=?,review_text=?,updated_at=NOW() WHERE id=?");
            $upd->bind_param('isi', $rRating, $rText, $existingReview['id']); $upd->execute();
        } else {
            $ins = $conn->prepare("INSERT INTO reviews (book_id,user_id,order_id,rating,review_text) VALUES (?,?,?,?,?)");
            $oid = (int)$purchase['order_id'];
            $ins->bind_param('iiiis', $bookId, $userId, $oid, $rRating, $rText); $ins->execute();
        }
        header('Location: ' . SITE_URL . '/pages/book_details.php?id=' . $bookId . '#reviews'); exit;
    }
}

$sellerCount = (int)$conn->query("SELECT COUNT(*) as c FROM books WHERE seller_id={$book['seller_user_id']} AND status='approved'")->fetch_assoc()['c'];
$relStmt = $conn->prepare("SELECT b.*,(SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as any_image FROM books b WHERE b.category_id=? AND b.id!=? AND b.status='approved' LIMIT 4");
$relStmt->bind_param('ii', $book['category_id'], $bookId); $relStmt->execute();
$relatedBooks = $relStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$primaryImg = !empty($images)
    ? getBookImageUrl($images[0]['image_name'])
    : getBookImageUrl(null);
$pageTitle = sanitize($book['title']);
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb breadcrumb-dark">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/pages/books.php">Books</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/pages/books.php?cat=<?= $book['category_id'] ?>"><?= sanitize($book['category_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= sanitize($book['title']) ?></li>
        </ol>
    </nav>
    <div class="row g-5">
        <!-- Gallery -->
        <div class="col-lg-5">
            <img id="galleryMain" src="<?= $primaryImg ?>" alt="<?= sanitize($book['title']) ?>" class="gallery-main-img" onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
            <?php if (count($images) > 1): ?>
            <div class="gallery-thumbs mt-3">
                <?php foreach ($images as $i => $img): ?>
                <img src="<?= getBookImageUrl($img['image_name']) ?>" alt="thumb" class="gallery-thumb <?= $i===0?'active':'' ?>"
                     onclick="switchGalleryImage('<?= getBookImageUrl($img['image_name']) ?>',this)" loading="lazy">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <!-- Info -->
        <div class="col-lg-7">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-gold text-dark"><?= sanitize($book['category_name']) ?></span>
                <?= conditionBadge($book['condition_type']) ?>
                <?php if ($book['is_negotiable']): ?>
                <span class="badge" style="background:rgba(16,185,129,0.2);color:#10b981;border:1px solid rgba(16,185,129,0.3);"><i class="bi bi-chat-dots me-1"></i>Negotiable</span>
                <?php endif; ?>
            </div>
            <h1 style="font-family:'Playfair Display',serif;font-size:1.9rem;color:white;font-weight:800;line-height:1.2;"><?= sanitize($book['title']) ?></h1>
            <p class="mt-2 mb-1 text-muted">by <strong class="text-gold fs-5"><?= sanitize($book['author']) ?></strong></p>
            <div class="d-flex align-items-center gap-3 my-3">
                <?= renderStars($rating['avg']) ?>
                <span class="text-white fw-bold"><?= $rating['avg'] ?>/5</span>
                <span class="text-muted">(<?= $rating['count'] ?> review<?= $rating['count']!=1?'s':'' ?>)</span>
                <span class="text-muted">|</span>
                <small class="text-muted"><i class="bi bi-eye me-1"></i><?= number_format($book['views']) ?> views</small>
            </div>
            <!-- Price Box -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <span style="font-size:2.2rem;font-weight:800;color:#d4af37;"><?= formatPrice($book['selling_price']) ?></span>
                    <?php if ($book['original_price'] > 0 && $book['original_price'] > $book['selling_price']): ?>
                    <span class="text-muted text-decoration-line-through fs-5"><?= formatPrice($book['original_price']) ?></span>
                    <span class="badge bg-danger"><?= round((($book['original_price']-$book['selling_price'])/$book['original_price'])*100) ?>% OFF</span>
                    <?php endif; ?>
                </div>
                <?php if ($book['quantity'] > 0): ?>
                <p class="text-success mb-0"><i class="bi bi-check-circle-fill me-2"></i>In Stock (<?= $book['quantity'] ?> available)</p>
                <?php else: ?><p class="text-danger mb-0"><i class="bi bi-x-circle-fill me-2"></i>Out of Stock</p><?php endif; ?>
            </div>
            <!-- Actions -->
            <?php if ($book['quantity'] > 0): ?>
            <div class="d-flex flex-wrap gap-3 mb-4">
                <?php if (!isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/auth/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-gold px-4 py-2"><i class="bi bi-box-arrow-in-right me-2"></i>Login to Buy</a>
                <?php elseif ((int)$_SESSION['user_id'] === (int)$book['seller_user_id']): ?>
                <div class="badge py-3 px-4" style="background:rgba(212,175,55,0.15);color:#d4af37;border:1px solid rgba(212,175,55,0.3);font-size:0.9rem;"><i class="bi bi-info-circle me-2"></i>This is your listing</div>
                <a href="<?= SITE_URL ?>/seller/edit_book.php?id=<?= $book['id'] ?>" class="btn btn-outline-gold"><i class="bi bi-pencil me-2"></i>Edit Listing</a>
                <?php else: ?>
                <button class="btn btn-gold px-4 py-2 fw-bold" onclick="addToCart(<?= $book['id'] ?>)"><i class="bi bi-cart-plus me-2"></i>Add to Cart</button>
                <a href="<?= SITE_URL ?>/pages/checkout.php?buy_now=<?= $book['id'] ?>" class="btn btn-purple px-4 py-2 fw-bold"><i class="bi bi-lightning-fill me-2"></i>Buy Now</a>
                <button class="btn btn-outline-gold px-3" onclick="toggleWishlist(<?= $book['id'] ?>,this)" id="wishlistBtn"><i class="bi bi-heart me-1"></i>Wishlist</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <!-- Details -->
            <div class="glass-card p-3 mb-4">
                <h6 class="text-gold mb-3"><i class="bi bi-info-circle me-2"></i>Book Details</h6>
                <div class="row g-2">
                    <?php $details=['ISBN'=>$book['isbn'],'Publisher'=>$book['publisher'],'Edition'=>$book['edition'],'Language'=>$book['language'],'Pages'=>($book['pages']?number_format($book['pages']):null),'Category'=>$book['category_name']];
                    foreach ($details as $lbl=>$val): if (!$val) continue; ?>
                    <div class="col-6">
                        <small class="text-muted d-block"><?= $lbl ?></small>
                        <span class="text-white small fw-500"><?= sanitize((string)$val) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Seller Info -->
            <div class="glass-card p-3">
                <h6 class="text-gold mb-3"><i class="bi bi-person-badge me-2"></i>Seller Information</h6>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="bi bi-person-circle fs-1 text-gold"></i>
                    <div>
                        <h6 class="text-white mb-0 fw-bold"><?= sanitize($book['seller_name']) ?></h6>
                        <small class="text-muted">Member since <?= date('M Y', strtotime($book['seller_since'])) ?></small><br>
                        <small class="text-muted"><?= $sellerCount ?> book<?= $sellerCount!=1?'s':'' ?> listed</small>
                    </div>
                </div>
                <?php if (isLoggedIn() && (int)$_SESSION['user_id'] !== (int)$book['seller_user_id']): ?>
                <button class="btn btn-outline-gold btn-sm" data-bs-toggle="modal" data-bs-target="#contactModal">
                    <i class="bi bi-chat-text me-2"></i>Contact Seller
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Description -->
    <?php if ($book['description']): ?>
    <div class="glass-card p-4 mt-5">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-file-text me-2 text-gold"></i>Description</h5>
        <p class="text-muted" style="line-height:1.9;"><?= nl2br(sanitize($book['description'])) ?></p>
    </div>
    <?php endif; ?>
    <!-- Reviews -->
    <div class="mt-5" id="reviews">
        <h4 class="text-white fw-bold mb-4"><i class="bi bi-star me-2 text-gold"></i>Reviews & Ratings</h4>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="glass-card p-4 text-center">
                    <div style="font-size:3.5rem;font-weight:800;color:#d4af37;line-height:1;"><?= $rating['avg'] ?: '-' ?></div>
                    <div class="my-2"><?= renderStars($rating['avg']) ?></div>
                    <small class="text-muted"><?= $rating['count'] ?> review<?= $rating['count']!=1?'s':'' ?></small>
                </div>
            </div>
            <div class="col-md-9">
                <?php if ($canReview): ?>
                <div class="glass-card p-4 mb-4">
                    <h6 class="text-white mb-3"><?= $existingReview ? 'Update Your Review' : 'Write a Review' ?></h6>
                    <form method="POST">
                        <div class="mb-3">
                            <div class="star-rating-input d-flex gap-2" style="font-size:1.6rem;cursor:pointer;">
                                <?php for ($s=1;$s<=5;$s++): ?>
                                <i class="bi <?= ($existingReview && $existingReview['rating']>=$s)?'bi-star-fill text-warning':'bi-star text-muted' ?>" data-selected="<?= ($existingReview && $existingReview['rating']>=$s)?'1':'0' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="rating" id="ratingValue" value="<?= $existingReview['rating'] ?? 5 ?>">
                        </div>
                        <div class="mb-3">
                            <textarea name="review_text" class="form-control" rows="3" placeholder="Share your experience..."><?= $existingReview ? sanitize($existingReview['review_text']) : '' ?></textarea>
                        </div>
                        <button type="submit" name="submit_review" class="btn btn-gold btn-sm px-4">
                            <i class="bi bi-send me-2"></i><?= $existingReview ? 'Update Review' : 'Submit Review' ?>
                        </button>
                    </form>
                </div>
                <?php elseif (!isLoggedIn()): ?>
                <div class="glass-card p-3 mb-4 text-center">
                    <p class="text-muted mb-2">Login to write a review</p>
                    <a href="<?= SITE_URL ?>/auth/login.php" class="btn btn-outline-gold btn-sm">Login</a>
                </div>
                <?php endif; ?>
                <?php if (empty($reviews)): ?>
                <div class="glass-card p-4 text-center"><i class="bi bi-chat-square-text fs-2 text-muted mb-3 d-block"></i><p class="text-muted mb-0">No reviews yet. Purchase this book to write the first review!</p></div>
                <?php else: foreach ($reviews as $rev): ?>
                <div class="glass-card p-3 mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-circle text-gold fs-5"></i>
                            <strong class="text-white"><?= sanitize($rev['reviewer_name']) ?></strong>
                        </div>
                        <small class="text-muted"><?= timeAgo($rev['created_at']) ?></small>
                    </div>
                    <div class="mb-2"><?= renderStars($rev['rating']) ?></div>
                    <?php if ($rev['review_text']): ?><p class="text-muted mb-0"><?= sanitize($rev['review_text']) ?></p><?php endif; ?>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <!-- Related -->
    <?php if (!empty($relatedBooks)): ?>
    <div class="mt-5">
        <h4 class="text-white fw-bold mb-4">You May Also Like</h4>
        <div class="row g-4">
            <?php foreach ($relatedBooks as $rb):
                $rImg = getBookImageUrl($rb['any_image']); ?>
            <div class="col-6 col-md-3">
                <div class="book-card">
                    <div class="book-card-img-wrapper"><a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $rb['id'] ?>"><img src="<?= $rImg ?>" alt="<?= sanitize($rb['title']) ?>" class="book-card-img" loading="lazy" onerror="this.src='<?= UPLOAD_URL ?>default_book.png'"></a></div>
                    <div class="book-card-body">
                        <h3 class="book-card-title"><a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $rb['id'] ?>" class="text-decoration-none" style="color:inherit;"><?= sanitize($rb['title']) ?></a></h3>
                        <p class="book-card-author"><?= sanitize($rb['author']) ?></p>
                        <div class="book-card-actions"><span class="book-card-price flex-grow-1"><?= formatPrice($rb['selling_price']) ?></span><a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $rb['id'] ?>" class="btn btn-gold btn-sm">View</a></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<!-- Contact Seller Modal -->
<div class="modal fade" id="contactModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title text-white"><i class="bi bi-chat-text me-2 text-gold"></i>Contact Seller</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <form id="msgForm">
                <input type="hidden" name="book_id" value="<?= $bookId ?>">
                <input type="hidden" name="receiver_id" value="<?= $book['seller_user_id'] ?>">
                <div class="mb-3"><label class="form-label">Your Message</label><textarea name="message" class="form-control" rows="4" placeholder="Ask about the book, condition, or negotiate the price..." required></textarea></div>
                <button type="submit" class="btn btn-gold w-100"><i class="bi bi-send me-2"></i>Send Message</button>
            </form>
        </div>
    </div></div>
</div>
<script>
document.getElementById('msgForm')?.addEventListener('submit',function(e){
    e.preventDefault();
    const fd=new FormData(this);
    fetch('/online-book-resale/ajax/message_action.php',{method:'POST',body:new URLSearchParams(fd)})
    .then(r=>r.json()).then(d=>{showToast(d.message,d.success?'success':'error');if(d.success)bootstrap.Modal.getInstance(document.getElementById('contactModal')).hide();});
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

