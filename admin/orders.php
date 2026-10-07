<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireAdminLogin();

// AJAX
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = sanitize($_POST['action']);
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'update_status') {
        $newStatus = sanitize($_POST['status'] ?? '');
        $allowed = ['pending','confirmed','packed','shipped','delivered','cancelled'];
        if (!in_array($newStatus,$allowed)) { echo json_encode(['success'=>false,'message'=>'Invalid status.']); exit; }
        $conn->query("UPDATE orders SET status='$newStatus' WHERE id=$id");
        $order = $conn->query("SELECT user_id,order_number FROM orders WHERE id=$id")->fetch_assoc();
        if ($order) $conn->query("INSERT INTO notifications (user_id,title,message,type,link) VALUES ({$order['user_id']},'Order Update','Your order #{$order['order_number']} status updated to " . ucfirst($newStatus) . ".','info','/online-book-resale/user/orders.php?id=$id')");
        echo json_encode(['success'=>true,'message'=>'Order status updated to ' . ucfirst($newStatus)]);
    } else echo json_encode(['success'=>false,'message'=>'Invalid action.']);
    exit;
}

$statusFilter = sanitize($_GET['status'] ?? 'all');
$search = sanitize($_GET['search'] ?? '');
$page = max(1,(int)($_GET['page'] ?? 1));
$viewId = (int)($_GET['id'] ?? 0);

if ($viewId) {
    $order = $conn->query("SELECT o.*,u.name as user_name,u.email as user_email FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=$viewId")->fetch_assoc();
    if (!$order) { header('Location: ' . SITE_URL . '/admin/orders.php'); exit; }
    $items = $conn->query("SELECT oi.*,b.title,b.author FROM order_items oi JOIN books b ON oi.book_id=b.id WHERE oi.order_id=$viewId")->fetch_all(MYSQLI_ASSOC);
}

$where = ['1=1'];
if ($statusFilter !== 'all') $where[] = "o.status='$statusFilter'";
if ($search) $where[] = "(o.order_number LIKE '%".addslashes($search)."%' OR u.name LIKE '%".addslashes($search)."%')";
$whereStr = implode(' AND ', $where);

$total = (int)$conn->query("SELECT COUNT(*) as c FROM orders o JOIN users u ON o.user_id=u.id WHERE $whereStr")->fetch_assoc()['c'];
$pag = paginate($total, ORDERS_PER_PAGE, $page);
$orders = $conn->query("SELECT o.*,u.name as user_name FROM orders o JOIN users u ON o.user_id=u.id WHERE $whereStr ORDER BY o.created_at DESC LIMIT " . ORDERS_PER_PAGE . " OFFSET " . $pag['offset'])->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Orders';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button><h6 class="text-white mb-0 fw-bold">Orders Management</h6></div>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
    <div class="admin-page-content">
        <?php if ($viewId && isset($order)): ?>
        <!-- Order Detail View -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-outline-gold btn-sm"><i class="bi bi-arrow-left me-2"></i>Back</a>
            <h4 class="text-white fw-bold mb-0">Order: <?= sanitize($order['order_number']) ?></h4>
            <?= statusBadge($order['status']) ?>
        </div>
        <div class="row g-4">
            <div class="col-md-8">
                <div class="glass-card p-4 mb-4">
                    <h6 class="text-gold mb-3">Items</h6>
                    <?php foreach ($items as $item): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3" style="border-bottom:1px solid rgba(255,255,255,0.08);">
                        <div>
                            <div class="text-white fw-bold"><?= sanitize($item['title']) ?></div>
                            <small class="text-muted">by <?= sanitize($item['author']) ?> &bull; Qty: <?= $item['quantity'] ?></small>
                        </div>
                        <span class="text-gold fw-bold"><?= formatPrice($item['price']*$item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="glass-card p-4">
                    <h6 class="text-gold mb-3">Update Status</h6>
                    <div class="d-flex gap-2 flex-wrap" id="statusButtons">
                        <?php foreach (['confirmed','packed','shipped','delivered','cancelled'] as $s): ?>
                        <button class="btn btn-sm <?= $order['status']===$s?'btn-gold':'btn-outline-gold' ?>" onclick="adminUpdateOrderStatus(<?= $viewId ?>,'<?= $s ?>',this)">
                            <?= ucfirst($s) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="glass-card p-4 mb-4">
                    <h6 class="text-gold mb-3">Summary</h6>
                    <div class="d-flex justify-content-between text-muted mb-2"><span>Subtotal</span><span><?= formatPrice($order['subtotal']) ?></span></div>
                    <?php if ($order['discount']>0): ?><div class="d-flex justify-content-between text-success mb-2"><span>Discount</span><span>-<?= formatPrice($order['discount']) ?></span></div><?php endif; ?>
                    <hr class="divider my-2">
                    <div class="d-flex justify-content-between text-white fw-bold fs-5"><span>Total</span><span class="text-gold"><?= formatPrice($order['total']) ?></span></div>
                    <div class="mt-2 pt-2" style="border-top:1px solid rgba(255,255,255,0.08);">
                        <small class="text-muted">Payment: <?= strtoupper($order['payment_method']) ?></small>
                    </div>
                </div>
                <div class="glass-card p-4 mb-4">
                    <h6 class="text-gold mb-3">Customer</h6>
                    <p class="text-white fw-bold mb-1"><?= sanitize($order['user_name']) ?></p>
                    <p class="text-muted small mb-1"><?= sanitize($order['user_email']) ?></p>
                    <hr class="divider my-2">
                    <p class="text-white fw-bold mb-1"><?= sanitize($order['shipping_name']) ?></p>
                    <p class="text-muted small mb-0"><?= sanitize($order['shipping_address']) ?>, <?= sanitize($order['shipping_city']) ?>, <?= sanitize($order['shipping_state']) ?> - <?= sanitize($order['shipping_pincode']) ?></p>
                    <p class="text-muted small"><i class="bi bi-telephone me-1"></i><?= sanitize($order['shipping_phone']) ?></p>
                </div>
                <a href="<?= SITE_URL ?>/pages/invoice.php?id=<?= $viewId ?>" target="_blank" class="btn btn-outline-gold w-100"><i class="bi bi-download me-2"></i>View Invoice</a>
            </div>
        </div>
        <?php else: ?>
        <!-- Orders List -->
        <div class="admin-page-header"><h2>Orders Management</h2><p>Monitor and update all orders. Total: <?= number_format($total) ?></p></div>
        <!-- Status Tabs -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <?php foreach (['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','packed'=>'Packed','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $val=>$lbl): ?>
            <a href="?status=<?= $val ?>" class="btn btn-sm <?= $statusFilter===$val?'btn-gold':'btn-outline-gold' ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
        <!-- Search -->
        <div class="glass-card p-3 mb-4">
            <form method="GET" class="d-flex gap-3 align-items-center">
                <input type="hidden" name="status" value="<?= $statusFilter ?>">
                <input type="text" name="search" class="form-control" placeholder="Search by order number or user name..." value="<?= sanitize($search) ?>" style="max-width:350px;">
                <button type="submit" class="btn btn-gold">Search</button>
                <a href="?status=<?= $statusFilter ?>" class="btn btn-outline-gold">Clear</a>
            </form>
        </div>
        <div class="admin-table">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Order #</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $o):
                        $itemCount = (int)$conn->query("SELECT COALESCE(SUM(quantity),0) as c FROM order_items WHERE order_id={$o['id']}")->fetch_assoc()['c']; ?>
                    <tr>
                        <td><a href="?id=<?= $o['id'] ?>" class="text-gold fw-bold text-decoration-none"><?= sanitize($o['order_number']) ?></a></td>
                        <td class="text-white"><?= sanitize($o['user_name']) ?></td>
                        <td><span class="badge bg-primary"><?= $itemCount ?></span></td>
                        <td class="text-gold fw-bold"><?= formatPrice($o['total']) ?></td>
                        <td class="text-muted"><?= strtoupper($o['payment_method']) ?></td>
                        <td><?= statusBadge($o['status']) ?></td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="?id=<?= $o['id'] ?>" class="btn btn-outline-gold btn-sm" title="View"><i class="bi bi-eye"></i></a>
                                <a href="<?= SITE_URL ?>/pages/invoice.php?id=<?= $o['id'] ?>" target="_blank" class="btn btn-outline-gold btn-sm" title="Invoice"><i class="bi bi-receipt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?><tr><td colspan="8" class="text-center text-muted py-4">No orders found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><?= renderPagination($pag, SITE_URL . '/admin/orders.php?status=' . $statusFilter . '&search=' . urlencode($search)) ?></div>
        <?php endif; ?>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

