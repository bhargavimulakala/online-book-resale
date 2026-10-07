<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];

// Handle cancel
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cancel_order'])) {
    $oid = (int)$_POST['order_id'];
    $reason = sanitize($_POST['cancel_reason'] ?? 'Cancelled by user');
    $stmt = $conn->prepare("UPDATE orders SET status='cancelled',cancel_reason=? WHERE id=? AND user_id=? AND status IN ('pending','confirmed')");
    $stmt->bind_param('sii', $reason, $oid, $userId); $stmt->execute();
    setFlash('success','Order cancelled successfully.'); header('Location: ' . SITE_URL . '/user/orders.php'); exit;
}

// View single order
$viewId = (int)($_GET['id'] ?? 0);
$orderDetail = null;
$orderItems = [];
if ($viewId) {
    $os = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $os->bind_param('ii', $viewId, $userId); $os->execute();
    $orderDetail = $os->get_result()->fetch_assoc();
    if ($orderDetail) {
        $ois = $conn->prepare("SELECT oi.*, b.title, b.author, (SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as img FROM order_items oi JOIN books b ON oi.book_id=b.id WHERE oi.order_id=?");
        $ois->bind_param('i', $viewId); $ois->execute();
        $orderItems = $ois->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$page = max(1,(int)($_GET['page']??1));
$pag = paginate((int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$userId")->fetch_assoc()['c'], ORDERS_PER_PAGE, $page);
$orders = $conn->query("SELECT * FROM orders WHERE user_id=$userId ORDER BY created_at DESC LIMIT " . ORDERS_PER_PAGE . " OFFSET " . $pag['offset'])->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <?= renderFlash() ?>
    <?php if ($orderDetail): ?>
    <!-- Order Detail View -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="<?= SITE_URL ?>/user/orders.php" class="btn btn-outline-gold btn-sm"><i class="bi bi-arrow-left me-2"></i>Back to Orders</a>
        <h4 class="text-white fw-bold mb-0">Order: <?= sanitize($orderDetail['order_number']) ?></h4>
        <?= statusBadge($orderDetail['status']) ?>
    </div>
    <!-- Tracker -->
    <?php
    $steps = ['pending','confirmed','packed','shipped','delivered'];
    $currentIdx = array_search($orderDetail['status'], $steps);
    if ($currentIdx === false) $currentIdx = -1;
    ?>
    <?php if ($orderDetail['status'] !== 'cancelled'): ?>
    <div class="glass-card p-4 mb-4">
        <h6 class="text-gold mb-4">Order Tracking</h6>
        <div class="order-tracker">
            <?php foreach ($steps as $si => $step): $done=$si<=$currentIdx; $active=$si===$currentIdx; ?>
            <div class="tracker-step <?= $done?'done':'' ?> <?= $active?'active':'' ?>">
                <div class="tracker-dot"><i class="bi <?= ['bi-hourglass','bi-check2','bi-box-seam','bi-truck','bi-house-check'][$si] ?>"></i></div>
                <div class="tracker-label"><?= ucfirst($step) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-md-8">
            <div class="glass-card p-4 mb-4">
                <h6 class="text-gold mb-3">Items Ordered</h6>
                <?php foreach ($orderItems as $item):
                    $imgUrl = getBookImageUrl($item['img']); ?>
                <div class="cart-item">
                    <img src="<?= $imgUrl ?>" alt="<?= sanitize($item['title']) ?>" class="cart-item-img" onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                    <div class="flex-grow-1">
                        <h6 class="text-white fw-bold mb-1"><?= sanitize($item['title']) ?></h6>
                        <small class="text-muted">by <?= sanitize($item['author']) ?></small><br>
                        <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                    </div>
                    <div class="text-gold fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card p-4 mb-4">
                <h6 class="text-gold mb-3">Order Summary</h6>
                <div class="d-flex justify-content-between text-muted mb-2"><span>Subtotal</span><span><?= formatPrice($orderDetail['subtotal']) ?></span></div>
                <?php if ($orderDetail['discount'] > 0): ?><div class="d-flex justify-content-between text-success mb-2"><span>Discount (<?= sanitize($orderDetail['coupon_code']) ?>)</span><span>-<?= formatPrice($orderDetail['discount']) ?></span></div><?php endif; ?>
                <hr class="divider my-2">
                <div class="d-flex justify-content-between text-white fw-bold fs-5"><span>Total</span><span class="text-gold"><?= formatPrice($orderDetail['total']) ?></span></div>
                <div class="mt-3"><small class="text-muted">Payment: <?= strtoupper($orderDetail['payment_method']) ?></small></div>
            </div>
            <div class="glass-card p-4 mb-4">
                <h6 class="text-gold mb-3">Shipping Address</h6>
                <p class="text-white mb-1 fw-bold"><?= sanitize($orderDetail['shipping_name']) ?></p>
                <p class="text-muted small mb-0"><?= sanitize($orderDetail['shipping_address']) ?>, <?= sanitize($orderDetail['shipping_city']) ?>, <?= sanitize($orderDetail['shipping_state']) ?> - <?= sanitize($orderDetail['shipping_pincode']) ?></p>
                <p class="text-muted small"><i class="bi bi-telephone me-1"></i><?= sanitize($orderDetail['shipping_phone']) ?></p>
            </div>
            <div class="d-flex flex-column gap-2">
                <a href="<?= SITE_URL ?>/pages/invoice.php?id=<?= $orderDetail['id'] ?>" target="_blank" class="btn btn-outline-gold btn-sm"><i class="bi bi-download me-2"></i>Download Invoice</a>
                <?php if (in_array($orderDetail['status'],['pending','confirmed'])): ?>
                <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cancelModal">
                    <i class="bi bi-x-circle me-2"></i>Cancel Order
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title text-white">Cancel Order</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST"><div class="modal-body">
                <input type="hidden" name="cancel_order" value="1">
                <input type="hidden" name="order_id" value="<?= $orderDetail['id'] ?>">
                <div class="mb-3"><label class="form-label">Reason for cancellation</label><textarea name="cancel_reason" class="form-control" rows="3" placeholder="Tell us why you're cancelling..." required></textarea></div>
            </div><div class="modal-footer"><button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Keep Order</button><button type="submit" class="btn btn-danger">Cancel Order</button></div></form>
        </div></div>
    </div>
    <?php else: ?>
    <!-- Orders List -->
    <h4 class="text-white fw-bold mb-4"><i class="bi bi-bag-check me-2 text-gold"></i>My Orders</h4>
    <?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <i class="bi bi-bag-x" style="font-size:4rem;color:#d4af37;opacity:0.4;"></i>
        <h4 class="text-white mt-4">No orders yet</h4>
        <p class="text-muted mb-4">Start shopping to see your orders here.</p>
        <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-gold">Browse Books</a>
    </div>
    <?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($orders as $o):
            $itemCount = (int)$conn->query("SELECT SUM(quantity) as c FROM order_items WHERE order_id={$o['id']}")->fetch_assoc()['c']; ?>
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="text-white fw-bold"><?= sanitize($o['order_number']) ?></span>
                        <?= statusBadge($o['status']) ?>
                    </div>
                    <small class="text-muted"><?= date('d M Y, h:i A', strtotime($o['created_at'])) ?></small><br>
                    <small class="text-muted"><?= $itemCount ?> item<?= $itemCount!=1?'s':'' ?> &bull; <?= strtoupper($o['payment_method']) ?></small>
                </div>
                <div class="text-end">
                    <div class="text-gold fw-bold fs-5"><?= formatPrice($o['total']) ?></div>
                    <a href="<?= SITE_URL ?>/user/orders.php?id=<?= $o['id'] ?>" class="btn btn-outline-gold btn-sm mt-2">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-4"><?= renderPagination($pag, SITE_URL . '/user/orders.php') ?></div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

