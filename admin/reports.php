<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireAdminLogin();

// Stats for reports
$period = sanitize($_GET['period'] ?? '30');

// Revenue over time
$revenueData = $conn->query("SELECT DATE_FORMAT(created_at,'%d %b') as day, SUM(total) as revenue, COUNT(*) as orders FROM orders WHERE status!='cancelled' AND created_at >= DATE_SUB(NOW(),INTERVAL $period DAY) GROUP BY DATE(created_at) ORDER BY created_at ASC")->fetch_all(MYSQLI_ASSOC);

// Top selling books
$topBooks = $conn->query("SELECT b.title, b.author, SUM(oi.quantity) as sold, SUM(oi.quantity*oi.price) as revenue FROM order_items oi JOIN books b ON oi.book_id=b.id JOIN orders o ON oi.order_id=o.id WHERE o.status!='cancelled' GROUP BY oi.book_id ORDER BY sold DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Top sellers
$topSellers = $conn->query("SELECT u.name, u.email, COUNT(DISTINCT b.id) as books_listed, SUM(oi.quantity) as books_sold, SUM(oi.quantity*oi.price) as revenue FROM order_items oi JOIN books b ON oi.book_id=b.id JOIN users u ON b.seller_id=u.id JOIN orders o ON oi.order_id=o.id WHERE o.status!='cancelled' GROUP BY u.id ORDER BY revenue DESC LIMIT 10")->fetch_all(MYSQLI_ASSOC);

// Category breakdown
$catRevenue = $conn->query("SELECT c.name, COUNT(oi.id) as sales, SUM(oi.quantity*oi.price) as revenue FROM order_items oi JOIN books b ON oi.book_id=b.id JOIN categories c ON b.category_id=c.id JOIN orders o ON oi.order_id=o.id WHERE o.status!='cancelled' GROUP BY c.id ORDER BY revenue DESC")->fetch_all(MYSQLI_ASSOC);

$totalRev = array_sum(array_column($catRevenue,'revenue'));
$pageTitle = 'Reports & Analytics';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button><h6 class="text-white mb-0 fw-bold">Reports & Analytics</h6></div>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
    <div class="admin-page-content">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div class="admin-page-header mb-0"><h2>Reports & Analytics</h2><p>Sales trends, top books, and seller performance.</p></div>
            <div class="d-flex gap-2 align-items-center">
                <span class="text-muted small">Period:</span>
                <?php foreach (['7'=>'7 Days','30'=>'30 Days','90'=>'90 Days','365'=>'1 Year'] as $val=>$lbl): ?>
                <a href="?period=<?= $val ?>" class="btn btn-sm <?= $period===$val?'btn-gold':'btn-outline-gold' ?>"><?= $lbl ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="glass-card p-4 mb-4">
            <h6 class="text-white fw-bold mb-4">Revenue Trend (Last <?= $period ?> Days)</h6>
            <canvas id="revenueChart" height="80"></canvas>
        </div>

        <div class="row g-4 mb-4">
            <!-- Top Selling Books -->
            <div class="col-lg-6">
                <div class="admin-table">
                    <div class="p-3 border-bottom" style="border-color:rgba(212,175,55,0.1)!important;">
                        <h6 class="text-white fw-bold mb-0"><i class="bi bi-trophy me-2 text-gold"></i>Top Selling Books</h6>
                    </div>
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>#</th><th>Book</th><th>Sold</th><th>Revenue</th></tr></thead>
                        <tbody>
                            <?php foreach ($topBooks as $i=>$b): ?>
                            <tr>
                                <td class="text-muted"><?= $i+1 ?></td>
                                <td><div class="text-white fw-bold small"><?= sanitize($b['title']) ?></div><small class="text-muted"><?= sanitize($b['author']) ?></small></td>
                                <td><span class="badge bg-primary"><?= $b['sold'] ?></span></td>
                                <td class="text-gold fw-bold"><?= formatPrice($b['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topBooks)): ?><tr><td colspan="4" class="text-center text-muted py-3">No sales data yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Top Sellers -->
            <div class="col-lg-6">
                <div class="admin-table">
                    <div class="p-3 border-bottom" style="border-color:rgba(212,175,55,0.1)!important;">
                        <h6 class="text-white fw-bold mb-0"><i class="bi bi-person-badge me-2 text-gold"></i>Top Sellers</h6>
                    </div>
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>#</th><th>Seller</th><th>Listed</th><th>Sold</th><th>Revenue</th></tr></thead>
                        <tbody>
                            <?php foreach ($topSellers as $i=>$s): ?>
                            <tr>
                                <td class="text-muted"><?= $i+1 ?></td>
                                <td><div class="text-white fw-bold small"><?= sanitize($s['name']) ?></div><small class="text-muted"><?= sanitize($s['email']) ?></small></td>
                                <td class="text-muted"><?= $s['books_listed'] ?></td>
                                <td><span class="badge bg-success"><?= $s['books_sold'] ?></span></td>
                                <td class="text-gold fw-bold"><?= formatPrice($s['revenue']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topSellers)): ?><tr><td colspan="5" class="text-center text-muted py-3">No data yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Category Revenue Breakdown -->
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="glass-card p-4 h-100">
                    <h6 class="text-white fw-bold mb-4">Revenue by Category</h6>
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="admin-table">
                    <div class="p-3 border-bottom" style="border-color:rgba(212,175,55,0.1)!important;">
                        <h6 class="text-white fw-bold mb-0">Category Performance</h6>
                    </div>
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Category</th><th>Sales</th><th>Revenue</th><th>% Share</th></tr></thead>
                        <tbody>
                            <?php foreach ($catRevenue as $c): ?>
                            <tr>
                                <td class="text-white fw-bold"><?= sanitize($c['name']) ?></td>
                                <td><span class="badge bg-info"><?= $c['sales'] ?></span></td>
                                <td class="text-gold fw-bold"><?= formatPrice($c['revenue']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px;">
                                            <div class="progress-bar bg-gold" style="width:<?= $totalRev>0 ? round($c['revenue']/$totalRev*100) : 0 ?>%;background:#d4af37!important;"></div>
                                        </div>
                                        <small class="text-muted"><?= $totalRev>0 ? round($c['revenue']/$totalRev*100) : 0 ?>%</small>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($catRevenue)): ?><tr><td colspan="4" class="text-center text-muted py-3">No data yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
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
const revLabels = <?= json_encode(array_column($revenueData,'day')) ?>;
const revData = <?= json_encode(array_map(fn($r)=>(float)$r['revenue'],$revenueData)) ?>;
const catLabels = <?= json_encode(array_column($catRevenue,'name')) ?>;
const catData = <?= json_encode(array_column($catRevenue,'revenue')) ?>;
document.addEventListener('DOMContentLoaded',()=>{
    initRevenueChart(revLabels.length?revLabels:['No Data'],revData.length?revData:[0]);
    initCategoryChart(catLabels.length?catLabels:['No Data'],catData.length?catData:[0]);
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

