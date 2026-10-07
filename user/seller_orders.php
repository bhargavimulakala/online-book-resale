<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];

$statusFilter = sanitize($_GET['status'] ?? 'all');
$search       = sanitize($_GET['search'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$viewId       = (int)($_GET['id'] ?? 0);

// ----------------------------------------------------------------
// DETAIL VIEW — single order breakdown (seller's items only)
// ----------------------------------------------------------------
$orderDetail = null;
$sellerItems = [];

if ($viewId) {
    // Verify the order contains at least one book belonging to this seller
    $chk = $conn->prepare("
        SELECT o.*, u.name AS buyer_name, u.email AS buyer_email
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN books b        ON b.id = oi.book_id
        JOIN users u        ON u.id = o.user_id
        WHERE o.id = ? AND b.seller_id = ?
        LIMIT 1
    ");
    $chk->bind_param('ii', $viewId, $userId);
    $chk->execute();
    $orderDetail = $chk->get_result()->fetch_assoc();

    if (!$orderDetail) {
        setFlash('error', 'Order not found or it does not contain any of your books.');
        header('Location: ' . SITE_URL . '/user/seller_orders.php');
        exit;
    }

    // Fetch only this seller's items within that order
    $ois = $conn->prepare("
        SELECT oi.*, b.title, b.author,
               (SELECT image_name FROM book_images WHERE book_id = b.id LIMIT 1) AS img
        FROM order_items oi
        JOIN books b ON b.id = oi.book_id
        WHERE oi.order_id = ? AND b.seller_id = ?
    ");
    $ois->bind_param('ii', $viewId, $userId);
    $ois->execute();
    $sellerItems = $ois->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ----------------------------------------------------------------
// LIST VIEW — paginated orders that contain this seller's books
// ----------------------------------------------------------------
$where = ["b.seller_id = $userId"];
if ($statusFilter !== 'all') $where[] = "o.status = '" . addslashes($statusFilter) . "'";
if ($search) $where[] = "(o.order_number LIKE '%" . addslashes($search) . "%' OR u.name LIKE '%" . addslashes($search) . "%')";
$whereStr = implode(' AND ', $where);

$total = (int)$conn->query("
    SELECT COUNT(DISTINCT o.id) as c
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN books b        ON b.id = oi.book_id
    JOIN users u        ON u.id = o.user_id
    WHERE $whereStr
")->fetch_assoc()['c'];

$pag = paginate($total, ORDERS_PER_PAGE, $page);

$orders = $conn->query("
    SELECT DISTINCT o.*, u.name AS buyer_name
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.id
    JOIN books b        ON b.id = oi.book_id
    JOIN users u        ON u.id = o.user_id
    WHERE $whereStr
    ORDER BY o.created_at DESC
    LIMIT " . ORDERS_PER_PAGE . " OFFSET " . $pag['offset']
)->fetch_all(MYSQLI_ASSOC);

// Stats
$totalEarned = (float)$conn->query("
    SELECT COALESCE(SUM(oi.price * oi.quantity), 0) AS earned
    FROM order_items oi
    JOIN books b  ON b.id  = oi.book_id
    JOIN orders o ON o.id  = oi.order_id
    WHERE b.seller_id = $userId AND o.status != 'cancelled'
")->fetch_assoc()['earned'];

$totalItemsSold = (int)$conn->query("
    SELECT COALESCE(SUM(oi.quantity), 0) AS sold
    FROM order_items oi
    JOIN books b  ON b.id  = oi.book_id
    JOIN orders o ON o.id  = oi.order_id
    WHERE b.seller_id = $userId AND o.status = 'delivered'
")->fetch_assoc()['sold'];

// Map of allowed next-step transitions (seller side only)
$sellerTransitions = [
    'pending'   => ['next' => 'confirmed', 'label' => 'Confirm Order',   'icon' => 'bi-check-circle', 'color' => 'btn-success'],
    'confirmed' => ['next' => 'packed',    'label' => 'Mark as Packed',  'icon' => 'bi-box-seam',     'color' => 'btn-info'],
    'packed'    => ['next' => 'shipped',   'label' => 'Mark as Shipped', 'icon' => 'bi-truck',        'color' => 'btn-primary'],
];

$pageTitle = 'My Sales Orders';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <?= renderFlash() ?>

    <?php if ($orderDetail): ?>
    <!-- ============================================================ -->
    <!-- DETAIL VIEW                                                   -->
    <!-- ============================================================ -->
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <a href="<?= SITE_URL ?>/user/seller_orders.php" class="btn btn-outline-gold btn-sm">
            <i class="bi bi-arrow-left me-2"></i>Back to Sales
        </a>
        <h4 class="fw-bold mb-0" style="color:var(--text-primary)">Order: <?= sanitize($orderDetail['order_number']) ?></h4>
        <?= statusBadge($orderDetail['status']) ?>
    </div>

    <!-- Order Tracker -->
    <?php
    $steps      = ['pending', 'confirmed', 'packed', 'shipped', 'delivered'];
    $currentIdx = array_search($orderDetail['status'], $steps);
    if ($currentIdx === false) $currentIdx = -1;
    ?>
    <?php if ($orderDetail['status'] !== 'cancelled'): ?>
    <div class="glass-card p-4 mb-4">
        <h6 class="text-gold mb-4"><i class="bi bi-diagram-3 me-2"></i>Order Status</h6>
        <div class="order-tracker">
            <?php foreach ($steps as $si => $step): $done = $si <= $currentIdx; $active = $si === $currentIdx; ?>
            <div class="tracker-step <?= $done ? 'done' : '' ?> <?= $active ? 'active' : '' ?>">
                <div class="tracker-dot">
                    <i class="bi <?= ['bi-hourglass','bi-check2','bi-box-seam','bi-truck','bi-house-check'][$si] ?>"></i>
                </div>
                <div class="tracker-label"><?= ucfirst($step) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- SELLER STATUS UPDATE CARD                                     -->
    <!-- ============================================================ -->
    <?php
    $currentStatus = $orderDetail['status'];
    $canUpdate     = isset($sellerTransitions[$currentStatus]);
    ?>
    <?php if ($canUpdate): ?>
    <div class="glass-card p-4 mb-4" id="sellerActionCard">
        <h6 class="text-gold mb-3"><i class="bi bi-pencil-square me-2"></i>Update Order Status</h6>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <span class="text-muted small">Current status:</span>
                <?= statusBadge($currentStatus) ?>
                <i class="bi bi-arrow-right mx-2 text-gold"></i>
                <span class="text-muted small">Next:</span>
                <span class="fw-bold" style="color:var(--text-primary)"> <?= ucfirst($sellerTransitions[$currentStatus]['next']) ?></span>
            </div>
            <button id="sellerNextBtn"
                    class="btn <?= $sellerTransitions[$currentStatus]['color'] ?> px-4 fw-bold"
                    onclick="sellerUpdateStatus(<?= $viewId ?>, '<?= $sellerTransitions[$currentStatus]['next'] ?>')">
                <i class="bi <?= $sellerTransitions[$currentStatus]['icon'] ?> me-2"></i>
                <?= $sellerTransitions[$currentStatus]['label'] ?>
            </button>
        </div>
        <small class="text-muted d-block mt-2">
            <i class="bi bi-info-circle me-1"></i>
            Once you advance the status, it cannot be reversed. The buyer will be notified automatically.
        </small>
    </div>
    <?php elseif ($currentStatus === 'shipped'): ?>
    <div class="glass-card p-4 mb-4">
        <div class="d-flex align-items-center gap-3">
            <i class="bi bi-truck text-gold fs-3"></i>
            <div>
                <div class="fw-bold" style="color:var(--text-primary)">Order Shipped — Awaiting Buyer Confirmation</div>
                <small class="text-muted">The buyer will mark this order as received when it arrives. You'll be notified.</small>
            </div>
        </div>
    </div>
    <?php elseif ($currentStatus === 'delivered'): ?>
    <div class="glass-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-house-check-fill text-success fs-3"></i>
            <div>
                <div class="fw-bold text-success">Order Delivered Successfully</div>
                <small class="text-muted">The buyer confirmed receipt. Earnings have been credited.</small>
            </div>
        </div>
    </div>
    <?php elseif ($currentStatus === 'cancelled'): ?>
    <div class="glass-card p-4 mb-4">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-x-circle-fill text-danger fs-3"></i>
            <div>
                <div class="fw-bold text-danger">Order Cancelled</div>
                <?php if (!empty($orderDetail['cancel_reason'])): ?>
                <small class="text-muted">Reason: <?= sanitize($orderDetail['cancel_reason']) ?></small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Left: Items -->
        <div class="col-md-8">
            <div class="glass-card p-4 mb-4">
                <h6 class="text-gold mb-3"><i class="bi bi-bag me-2"></i>Your Items in This Order</h6>
                <?php foreach ($sellerItems as $item):
                    $imgUrl = getBookImageUrl($item['img']); ?>
                <div class="cart-item">
                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= sanitize($item['title']) ?>"
                         class="cart-item-img"
                         onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                    <div class="flex-grow-1">
                        <h6 class="fw-bold mb-1" style="color:var(--text-primary)"><?= sanitize($item['title']) ?></h6>
                        <small class="text-muted">by <?= sanitize($item['author']) ?></small><br>
                        <small class="text-muted">Qty: <?= (int)$item['quantity'] ?></small>
                    </div>
                    <div class="text-gold fw-bold"><?= formatPrice($item['price'] * $item['quantity']) ?></div>
                </div>
                <?php endforeach; ?>

                <?php $orderEarnings = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $sellerItems)); ?>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3" style="border-top:1px solid var(--border-subtle);">
                    <span class="text-muted">Your earnings from this order</span>
                    <span class="text-gold fw-bold fs-5"><?= formatPrice($orderEarnings) ?></span>
                </div>
            </div>
        </div>

        <!-- Right: Buyer + Order Info -->
        <div class="col-md-4">
            <div class="glass-card p-4 mb-4">
                <h6 class="text-gold mb-3"><i class="bi bi-person me-2"></i>Buyer Info</h6>
                <p class="fw-bold mb-1" style="color:var(--text-primary)"><?= sanitize($orderDetail['buyer_name']) ?></p>
                <p class="text-muted small mb-2"><?= sanitize($orderDetail['buyer_email']) ?></p>
                <hr class="divider my-2">
                <h6 class="text-gold mb-2 small">Shipping Address</h6>
                <p class="fw-bold mb-1" style="color:var(--text-primary)"><?= sanitize($orderDetail['shipping_name']) ?></p>
                <p class="text-muted small mb-1"><?= sanitize($orderDetail['shipping_address']) ?>, <?= sanitize($orderDetail['shipping_city']) ?>, <?= sanitize($orderDetail['shipping_state']) ?> — <?= sanitize($orderDetail['shipping_pincode']) ?></p>
                <p class="text-muted small mb-0"><i class="bi bi-telephone me-1"></i><?= sanitize($orderDetail['shipping_phone']) ?></p>
            </div>

            <div class="glass-card p-4 mb-4">
                <h6 class="text-gold mb-3"><i class="bi bi-receipt me-2"></i>Order Details</h6>
                <div class="d-flex justify-content-between text-muted mb-2">
                    <span>Order Date</span>
                    <span><?= date('d M Y', strtotime($orderDetail['created_at'])) ?></span>
                </div>
                <div class="d-flex justify-content-between text-muted mb-2">
                    <span>Payment</span>
                    <span><?= strtoupper($orderDetail['payment_method']) ?></span>
                </div>
                <div class="d-flex justify-content-between text-muted mb-0">
                    <span>Order Total</span>
                    <span class="text-gold fw-bold"><?= formatPrice($orderDetail['total']) ?></span>
                </div>
            </div>

            <?php if (!empty($orderDetail['delivery_instructions'])): ?>
            <div class="glass-card p-4 mb-4">
                <h6 class="text-gold mb-2 small"><i class="bi bi-info-circle me-1"></i>Delivery Note</h6>
                <p class="text-muted small mb-0"><?= sanitize($orderDetail['delivery_instructions']) ?></p>
            </div>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/pages/invoice.php?id=<?= $viewId ?>" target="_blank"
               class="btn btn-outline-gold w-100">
                <i class="bi bi-download me-2"></i>View Invoice
            </a>
        </div>
    </div>

    <?php else: ?>
    <!-- ============================================================ -->
    <!-- LIST VIEW                                                     -->
    <!-- ============================================================ -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1" style="color:var(--text-primary)"><i class="bi bi-shop me-2 text-gold"></i>My Sales Orders</h4>
            <p class="text-muted mb-0">Orders containing books you have listed.</p>
        </div>
        <a href="<?= SITE_URL ?>/user/mybooks.php" class="btn btn-outline-gold btn-sm">
            <i class="bi bi-journal-bookmarks me-1"></i>My Listings
        </a>
    </div>

    <!-- Summary stat bar -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['Total Sale Orders',     'bi-bag-check',      'gold',   $total],
            ['Items Delivered',       'bi-house-check',    'green',  $totalItemsSold],
            ['Total Earnings (est.)', 'bi-currency-rupee', 'purple', formatPrice($totalEarned)],
        ] as $s): ?>
        <div class="col-sm-4">
            <div class="stat-card">
                <div class="stat-icon <?= $s[2] ?>"><i class="bi <?= $s[1] ?>"></i></div>
                <div class="stat-number"><?= $s[3] ?></div>
                <div class="stat-label"><?= $s[0] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Status filter tabs -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <?php foreach (['all'=>'All','pending'=>'Pending','confirmed'=>'Confirmed','packed'=>'Packed','shipped'=>'Shipped','delivered'=>'Delivered','cancelled'=>'Cancelled'] as $val=>$lbl): ?>
        <a href="?status=<?= $val ?>" class="btn btn-sm <?= $statusFilter===$val?'btn-gold':'btn-outline-gold' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
    </div>

    <!-- Search -->
    <div class="glass-card p-3 mb-4">
        <form method="GET" class="d-flex gap-3 align-items-center flex-wrap">
            <input type="hidden" name="status" value="<?= $statusFilter ?>">
            <input type="text" name="search" class="form-control"
                   placeholder="Search by order number or buyer name…"
                   value="<?= sanitize($search) ?>" style="max-width:350px;">
            <button type="submit" class="btn btn-gold">Search</button>
            <a href="?status=<?= $statusFilter ?>" class="btn btn-outline-gold">Clear</a>
        </form>
    </div>

    <!-- Orders table -->
    <?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <i class="bi bi-shop" style="font-size:4rem;color:#d4af37;opacity:0.35;"></i>
        <h5 class="mt-4" style="color:var(--text-primary)">No sales orders yet</h5>
        <p class="text-muted mb-4">
            <?= ($statusFilter !== 'all' || $search) ? 'No orders match your current filter.' : 'When buyers purchase your listed books, those orders will appear here.' ?>
        </p>
        <?php if ($statusFilter !== 'all' || $search): ?>
        <a href="<?= SITE_URL ?>/user/seller_orders.php" class="btn btn-outline-gold me-2">Clear Filters</a>
        <?php endif; ?>
        <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-gold">Browse Books</a>
    </div>
    <?php else: ?>
    <div class="glass-card mb-4" style="overflow:hidden;">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Buyer</th>
                        <th>Your Items</th>
                        <th>Your Earnings</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o):
                        $sellerItemCount = (int)$conn->query("
                            SELECT COALESCE(SUM(oi.quantity), 0) AS c
                            FROM order_items oi JOIN books b ON b.id = oi.book_id
                            WHERE oi.order_id = {$o['id']} AND b.seller_id = $userId
                        ")->fetch_assoc()['c'];
                        $orderEarning = (float)$conn->query("
                            SELECT COALESCE(SUM(oi.price * oi.quantity), 0) AS e
                            FROM order_items oi JOIN books b ON b.id = oi.book_id
                            WHERE oi.order_id = {$o['id']} AND b.seller_id = $userId
                        ")->fetch_assoc()['e'];
                        // Show action badge: needs attention if pending
                        $needsAction = ($o['status'] === 'pending');
                    ?>
                    <tr>
                        <td>
                            <a href="?id=<?= $o['id'] ?>" class="text-gold fw-bold text-decoration-none">
                                <?= sanitize($o['order_number']) ?>
                            </a>
                            <?php if ($needsAction): ?>
                            <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;">Action needed</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:var(--text-primary)"><?= sanitize($o['buyer_name']) ?></td>
                        <td><span class="badge bg-primary"><?= $sellerItemCount ?></span></td>
                        <td class="text-gold fw-bold"><?= formatPrice($orderEarning) ?></td>
                        <td class="text-muted"><?= strtoupper($o['payment_method']) ?></td>
                        <td><?= statusBadge($o['status']) ?></td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        <td>
                            <a href="?id=<?= $o['id'] ?>" class="btn btn-outline-gold btn-sm" title="View & Update">
                                <?php if ($needsAction || isset($sellerTransitions[$o['status']])): ?>
                                <i class="bi bi-pencil-square"></i>
                                <?php else: ?>
                                <i class="bi bi-eye"></i>
                                <?php endif; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-2"><?= renderPagination($pag, SITE_URL . '/user/seller_orders.php?status=' . $statusFilter . '&search=' . urlencode($search)) ?></div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// ================================================================
// Seller Order Status Update — AJAX
// ================================================================

/**
 * Called when seller clicks Confirm / Pack / Ship button.
 * Verifies intent, sends AJAX to order_action.php, reloads on success.
 */
function sellerUpdateStatus(orderId, newStatus) {
    const labels = {
        confirmed: 'Confirm this order?',
        packed:    'Mark this order as Packed?',
        shipped:   'Mark this order as Shipped? The buyer will be notified and can mark it as received.',
    };
    const msg = labels[newStatus] || `Update status to "${newStatus}"?`;
    if (!confirm(msg)) return;

    const btn = document.getElementById('sellerNextBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating…';
    }

    fetch('/online-book-resale/ajax/order_action.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body:    `action=seller_update_status&order_id=${orderId}&new_status=${encodeURIComponent(newStatus)}`
    })
    .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
    })
    .then(data => {
        if (data.success) {
            if (typeof showToast === 'function') showToast(data.message, 'success');
            // Reload the page after brief delay to show the new status
            setTimeout(() => location.reload(), 800);
        } else {
            if (typeof showToast === 'function') showToast(data.message, 'error');
            else alert(data.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = btn.getAttribute('data-original') || 'Update';
            }
        }
    })
    .catch(err => {
        console.error('sellerUpdateStatus error:', err);
        if (typeof showToast === 'function') showToast('Connection error. Please try again.', 'error');
        else alert('Connection error. Please try again.');
        if (btn) btn.disabled = false;
    });
}
</script>
