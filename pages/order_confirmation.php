<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$orderId = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param('ii',$orderId,$userId); $stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { header('Location: ' . SITE_URL . '/user/orders.php'); exit; }
$pageTitle = 'Order Confirmed';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <div class="text-center py-5">
        <div class="mb-4" style="width:100px;height:100px;background:rgba(16,185,129,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;border:3px solid #10b981;">
            <i class="bi bi-check-lg" style="font-size:3rem;color:#10b981;"></i>
        </div>
        <h2 class="text-white fw-bold mb-2">Order Placed Successfully!</h2>
        <p class="text-muted fs-5 mb-1">Thank you for your purchase, <strong class="text-gold"><?= sanitize($_SESSION['user_name']) ?></strong>!</p>
        <p class="text-muted mb-4">Order number: <span class="text-gold fw-bold"><?= sanitize($order['order_number']) ?></span></p>
        <div class="glass-card p-4 mx-auto mb-4" style="max-width:480px;">
            <div class="row g-3 text-start">
                <div class="col-6"><small class="text-muted d-block">Order Total</small><span class="text-gold fw-bold fs-5"><?= formatPrice($order['total']) ?></span></div>
                <div class="col-6"><small class="text-muted d-block">Payment Method</small><span class="text-white"><?= strtoupper($order['payment_method']) ?></span></div>
                <div class="col-6"><small class="text-muted d-block">Status</small><?= statusBadge($order['status']) ?></div>
                <div class="col-6"><small class="text-muted d-block">Date</small><span class="text-white"><?= date('d M Y', strtotime($order['created_at'])) ?></span></div>
            </div>
        </div>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <a href="<?= SITE_URL ?>/pages/invoice.php?id=<?= $orderId ?>" target="_blank" class="btn btn-outline-gold"><i class="bi bi-download me-2"></i>Download Invoice</a>
            <a href="<?= SITE_URL ?>/user/orders.php?id=<?= $orderId ?>" class="btn btn-gold"><i class="bi bi-bag me-2"></i>Track Order</a>
            <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-outline-gold"><i class="bi bi-shop me-2"></i>Continue Shopping</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

