<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];

// Get cart items with book info
$cartItems = $conn->query("SELECT c.*, b.title, b.author, b.selling_price, b.quantity as stock, b.condition_type, b.seller_id,
    (SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as img
    FROM cart c JOIN books b ON c.book_id=b.id WHERE c.user_id=$userId AND b.status='approved'")->fetch_all(MYSQLI_ASSOC);

// Applied coupon from session
$couponDiscount = $_SESSION['coupon_discount'] ?? 0;
$couponCode = $_SESSION['coupon_code'] ?? '';
$subtotal = array_sum(array_map(fn($i) => $i['selling_price'] * $i['quantity'], $cartItems));
$total = max(0, $subtotal - $couponDiscount);

$pageTitle = 'My Cart';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <h4 class="text-white fw-bold mb-4"><i class="bi bi-cart3 me-2 text-gold"></i>Shopping Cart <span class="badge bg-gold text-dark ms-2"><?= count($cartItems) ?></span></h4>
    <?= renderFlash() ?>
    <?php if (empty($cartItems)): ?>
    <div class="text-center py-5">
        <i class="bi bi-cart-x" style="font-size:5rem;color:#d4af37;opacity:0.4;"></i>
        <h4 class="text-white mt-4">Your cart is empty</h4>
        <p class="text-muted mb-4">Add some books to get started!</p>
        <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-gold btn-lg">Browse Books</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <?php foreach ($cartItems as $item):
                $imgUrl = getBookImageUrl($item['img']); ?>
            <div class="cart-item" id="cartItem_<?= $item['id'] ?>">
                <img src="<?= $imgUrl ?>" alt="<?= sanitize($item['title']) ?>" class="cart-item-img" onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                <div class="flex-grow-1">
                    <h6 class="text-white fw-bold mb-1">
                        <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $item['book_id'] ?>" class="text-white text-decoration-none"><?= sanitize($item['title']) ?></a>
                    </h6>
                    <small class="text-muted">by <?= sanitize($item['author']) ?></small><br>
                    <small class="text-muted"><?= conditionBadge($item['condition_type']) ?></small>
                    <div class="mt-2 qty-control">
                        <button class="qty-btn qty-minus" onclick="changeQty(<?= $item['id'] ?>,-1,<?= $item['stock'] ?>)"><i class="bi bi-dash"></i></button>
                        <input type="number" class="qty-input" id="qty_<?= $item['id'] ?>" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" readonly style="width:45px;text-align:center;">
                        <button class="qty-btn qty-plus" onclick="changeQty(<?= $item['id'] ?>,1,<?= $item['stock'] ?>)"><i class="bi bi-plus"></i></button>
                    </div>
                </div>
                <div class="text-end">
                    <div class="text-gold fw-bold fs-5 mb-2" id="itemTotal_<?= $item['id'] ?>"><?= formatPrice($item['selling_price'] * $item['quantity']) ?></div>
                    <small class="text-muted d-block mb-2"><?= formatPrice($item['selling_price']) ?> each</small>
                    <button class="btn btn-outline-danger btn-sm" onclick="removeItem(<?= $item['id'] ?>)"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <?php endforeach; ?>
            <div class="d-flex justify-content-between mt-3">
                <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-outline-gold btn-sm"><i class="bi bi-arrow-left me-2"></i>Continue Shopping</a>
            </div>
        </div>
        <div class="col-lg-4">
            <!-- Coupon -->
            <div class="glass-card p-4 mb-3">
                <h6 class="text-white fw-bold mb-3"><i class="bi bi-ticket-perforated me-2 text-gold"></i>Coupon Code</h6>
                <?php if ($couponCode): ?>
                <div class="alert alert-success py-2 mb-2">
                    <i class="bi bi-check-circle me-2"></i>Coupon <strong><?= sanitize($couponCode) ?></strong> applied! Saved <?= formatPrice($couponDiscount) ?>
                </div>
                <button class="btn btn-outline-danger btn-sm" onclick="removeCoupon()"><i class="bi bi-x me-1"></i>Remove Coupon</button>
                <?php else: ?>
                <div class="input-group">
                    <input type="text" class="form-control" id="couponInput" placeholder="Enter coupon code">
                    <button class="btn btn-gold" id="applyCouponBtn">Apply</button>
                </div>
                <small class="text-muted mt-1 d-block">Try: BOOK10, FIRST50, STUDENT20</small>
                <?php endif; ?>
            </div>
            <!-- Order Summary -->
            <div class="glass-card p-4">
                <h6 class="text-white fw-bold mb-4"><i class="bi bi-receipt me-2 text-gold"></i>Order Summary</h6>
                <div class="d-flex justify-content-between text-muted mb-2"><span>Subtotal (<?= count($cartItems) ?> items)</span><span id="cartSubtotal"><?= formatPrice($subtotal) ?></span></div>
                <div class="d-flex justify-content-between text-muted mb-2"><span>Delivery</span><span class="text-success">FREE</span></div>
                <?php if ($couponDiscount > 0): ?>
                <div class="d-flex justify-content-between text-success mb-2"><span>Discount</span><span>-<?= formatPrice($couponDiscount) ?></span></div>
                <?php endif; ?>
                <hr class="divider my-3">
                <div class="d-flex justify-content-between mb-4">
                    <span class="text-white fw-bold fs-5">Total</span>
                    <span class="text-gold fw-bold fs-5" id="cartGrandTotal"><?= formatPrice($total) ?></span>
                </div>
                <a href="<?= SITE_URL ?>/pages/checkout.php" class="btn btn-gold w-100 py-2 fw-bold">
                    <i class="bi bi-bag-check me-2"></i>Proceed to Checkout
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<script>
function changeQty(cartId, delta, maxStock) {
    const inp = document.getElementById('qty_' + cartId);
    let newQty = parseInt(inp.value) + delta;
    if (newQty < 1 || newQty > maxStock) return;
    inp.value = newQty;
    const totalEl = document.getElementById('itemTotal_' + cartId);
    updateCartQty(cartId, newQty, totalEl);
}
function removeItem(cartId) {
    if (!confirm('Remove this item from cart?')) return;
    removeFromCart(cartId, data => {
        const el = document.getElementById('cartItem_' + cartId);
        if (el) el.remove();
        if (data.cart_count === 0) location.reload();
    });
}
function removeCoupon() {
    fetch('/online-book-resale/ajax/cart_action.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=remove_coupon'})
    .then(r=>r.json()).then(d=>{ showToast(d.message,'info'); setTimeout(()=>location.reload(),800); });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

