<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireAdminLogin();
$admin = getCurrentAdmin($conn);

// Stats
$totalUsers   = (int)$conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalBooks   = (int)$conn->query("SELECT COUNT(*) as c FROM books WHERE status='approved'")->fetch_assoc()['c'];
$pendingBooks = (int)$conn->query("SELECT COUNT(*) as c FROM books WHERE status='pending'")->fetch_assoc()['c'];
$totalOrders  = (int)$conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$totalRevenue = (float)$conn->query("SELECT COALESCE(SUM(total),0) as r FROM orders WHERE status!='cancelled'")->fetch_assoc()['r'];
$booksSold    = (int)$conn->query("SELECT COALESCE(SUM(quantity),0) as c FROM order_items")->fetch_assoc()['c'];
$pendingOrders= (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE status='pending'")->fetch_assoc()['c'];
$newUsers     = (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 30 DAY)")->fetch_assoc()['c'];

// Monthly revenue - last 6 months
$monthlyData = $conn->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') as month, SUM(total) as revenue, COUNT(*) as orders
    FROM orders WHERE status!='cancelled' AND created_at >= DATE_SUB(NOW(),INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY created_at ASC")->fetch_all(MYSQLI_ASSOC);

// Orders by status
$orderStats = $conn->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$orderStatusData = array_fill_keys(['pending','confirmed','packed','shipped','delivered','cancelled'], 0);
foreach ($orderStats as $row) $orderStatusData[$row['status']] = (int)$row['cnt'];

// Books by category
$catStats = $conn->query("SELECT c.name, COUNT(b.id) as cnt FROM categories c LEFT JOIN books b ON b.category_id=c.id AND b.status='approved' GROUP BY c.id ORDER BY cnt DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recentOrders = $conn->query("SELECT o.*,u.name as user_name FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 7")->fetch_all(MYSQLI_ASSOC);

// Recent users
$recentUsers = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <!-- Topbar -->
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button>
            <h6 class="text-white mb-0 fw-bold">Dashboard</h6>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted small">Welcome, <span class="text-gold fw-bold"><?= sanitize($admin['name']) ?></span></span>
            <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
        </div>
    </div>
    <div class="admin-page-content">
        <div class="admin-page-header">
            <h2>Dashboard Overview</h2>
            <p>Welcome back! Here's what's happening today.</p>
        </div>

        <!-- Stat Cards Row 1 -->
        <div class="row g-3 mb-4">
            <?php $stats=[
                ['Total Users','bi-people','gold',$totalUsers,'+' . $newUsers . ' this month'],
                ['Total Books','bi-book','purple',$totalBooks,$pendingBooks . ' pending approval'],
                ['Total Orders','bi-bag-check','green',$totalOrders,$pendingOrders . ' pending'],
                ['Revenue','bi-currency-rupee','red','â‚¹' . number_format($totalRevenue,0),$booksSold . ' books sold'],
            ]; foreach ($stats as $s): ?>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-icon <?= $s[2] ?>"><i class="bi <?= $s[1] ?>"></i></div>
                        <small class="text-muted"><?= $s[4] ?></small>
                    </div>
                    <div class="stat-number"><?= $s[3] ?></div>
                    <div class="stat-label"><?= $s[0] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <div class="col-lg-8">
                <div class="glass-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="text-white fw-bold mb-0">Monthly Revenue (Last 6 Months)</h6>
                        <span class="badge bg-gold text-dark">â‚¹</span>
                    </div>
                    <canvas id="revenueChart" height="90"></canvas>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="glass-card p-4 h-100">
                    <h6 class="text-white fw-bold mb-4">Orders by Status</h6>
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Category Chart + Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="glass-card p-4">
                    <h6 class="text-white fw-bold mb-4">Books by Category</h6>
                    <canvas id="categoryChart" height="180"></canvas>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="glass-card p-4">
                    <h6 class="text-white fw-bold mb-4">Quick Actions</h6>
                    <div class="row g-2">
                        <?php $actions=[
                            ['Approve Books','bi-check-circle','btn-gold','/admin/books.php?status=pending'],
                            ['Manage Orders','bi-bag','btn-outline-gold','/admin/orders.php'],
                            ['View Users','bi-people','btn-outline-gold','/admin/users.php'],
                            ['Categories','bi-tags','btn-outline-gold','/admin/categories.php'],
                            ['Reports','bi-bar-chart','btn-outline-gold','/admin/reports.php'],
                            ['Messages','bi-envelope','btn-outline-gold','/admin/messages.php'],
                        ]; foreach ($actions as $a): ?>
                        <div class="col-6"><a href="<?= SITE_URL . $a[3] ?>" class="btn <?= $a[1]?'':'' ?> <?= $a[2] ?> w-100 text-start py-2"><i class="bi <?= $a[1] ?> me-2"></i><?= $a[0] ?></a></div>
                        <?php endforeach; ?>
                    </div>
                    <!-- Pending alerts -->
                    <?php if ($pendingBooks > 0): ?>
                    <div class="alert alert-warning mt-3 py-2 mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong><?= $pendingBooks ?></strong> book<?= $pendingBooks!=1?'s':'' ?> waiting for approval.
                        <a href="<?= SITE_URL ?>/admin/books.php?status=pending" class="alert-link ms-2">Review Now â†’</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Orders + Users -->
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="admin-table">
                    <div class="p-3 border-bottom" style="border-color:rgba(212,175,55,0.15)!important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="text-white fw-bold mb-0">Recent Orders</h6>
                            <a href="<?= SITE_URL ?>/admin/orders.php" class="btn btn-outline-gold btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Order #</th><th>User</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($recentOrders as $o): ?>
                                <tr>
                                    <td><a href="<?= SITE_URL ?>/admin/orders.php?id=<?= $o['id'] ?>" class="text-gold fw-bold text-decoration-none"><?= sanitize($o['order_number']) ?></a></td>
                                    <td class="text-white"><?= sanitize($o['user_name']) ?></td>
                                    <td class="text-gold">â‚¹<?= number_format($o['total'],0) ?></td>
                                    <td><?= statusBadge($o['status']) ?></td>
                                    <td class="text-muted small"><?= date('d M', strtotime($o['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="admin-table">
                    <div class="p-3 border-bottom" style="border-color:rgba(212,175,55,0.15)!important;">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="text-white fw-bold mb-0">New Users</h6>
                            <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-outline-gold btn-sm">View All</a>
                        </div>
                    </div>
                    <div class="p-3">
                        <?php foreach ($recentUsers as $u): ?>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <i class="bi bi-person-circle fs-4 text-gold"></i>
                            <div class="flex-grow-1">
                                <div class="text-white fw-bold small"><?= sanitize($u['name']) ?></div>
                                <small class="text-muted"><?= sanitize($u['email']) ?></small>
                            </div>
                            <small class="text-muted"><?= timeAgo($u['created_at']) ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
<script>
const monthLabels = <?= json_encode(array_column($monthlyData,'month')) ?>;
const monthRevenue = <?= json_encode(array_map(fn($r)=>(float)$r['revenue'], $monthlyData)) ?>;
const orderStatusData = <?= json_encode(array_values($orderStatusData)) ?>;
const catLabels = <?= json_encode(array_column($catStats,'name')) ?>;
const catData = <?= json_encode(array_column($catStats,'cnt')) ?>;

document.addEventListener('DOMContentLoaded',()=>{
    initRevenueChart(monthLabels.length ? monthLabels : ['No Data'], monthRevenue.length ? monthRevenue : [0]);
    initOrdersChart(orderStatusData);
    initCategoryChart(catLabels, catData);
    initTableSearch('orderSearch','ordersTable');
});
</script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

