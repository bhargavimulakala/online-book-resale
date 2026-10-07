<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireAdminLogin();

// Handle coupon actions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    header('Content-Type: application/json');
    $action = sanitize($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'add') {
        $code = strtoupper(sanitize($_POST['code'] ?? ''));
        $type = sanitize($_POST['discount_type'] ?? 'percent');
        $val  = (float)($_POST['discount_value'] ?? 0);
        $minO = (float)($_POST['min_order'] ?? 0);
        $maxD = (float)($_POST['max_discount'] ?? 0);
        $uses = (int)($_POST['uses_limit'] ?? 999);
        $exp  = sanitize($_POST['expires_at'] ?? '');
        $ins = $conn->prepare("INSERT INTO coupons (code,discount_type,discount_value,min_order,max_discount,uses_limit,expires_at) VALUES (?,?,?,?,?,?,?)");
        $expVal = $exp ?: null;
        $ins->bind_param('ssdddis',$code,$type,$val,$minO,$maxD,$uses,$expVal); $ins->execute();
        echo json_encode(['success'=>true,'message'=>'Coupon created!']);
    } elseif ($action === 'toggle') {
        $conn->query("UPDATE coupons SET is_active=NOT is_active WHERE id=$id");
        echo json_encode(['success'=>true,'message'=>'Status toggled.']);
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM coupons WHERE id=$id");
        echo json_encode(['success'=>true,'message'=>'Coupon deleted.']);
    }
    exit;
}

$coupons = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$payments = $conn->query("SELECT p.*,o.order_number,u.name as user_name FROM payments p JOIN orders o ON p.order_id=o.id JOIN users u ON p.user_id=u.id ORDER BY p.created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
$totalRevenue = (float)$conn->query("SELECT COALESCE(SUM(amount),0) as r FROM payments WHERE status='success'")->fetch_assoc()['r'];
$pageTitle = 'Payments & Coupons';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button><h6 class="text-white mb-0 fw-bold">Payments & Coupons</h6></div>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
    <div class="admin-page-content">
        <!-- Revenue Card -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="bi bi-currency-rupee"></i></div>
                    <div class="stat-number">â‚¹<?= number_format($totalRevenue,0) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="bi bi-ticket-perforated"></i></div>
                    <div class="stat-number"><?= count($coupons) ?></div>
                    <div class="stat-label">Active Coupons</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon green"><i class="bi bi-credit-card"></i></div>
                    <div class="stat-number"><?= count($payments) ?></div>
                    <div class="stat-label">Recent Transactions</div>
                </div>
            </div>
        </div>
        <div class="row g-4">
            <!-- Coupons -->
            <div class="col-lg-5">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white fw-bold mb-0">Discount Coupons</h5>
                    <button class="btn btn-gold btn-sm" data-bs-toggle="modal" data-bs-target="#addCouponModal"><i class="bi bi-plus me-1"></i>Add Coupon</button>
                </div>
                <div class="admin-table">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Code</th><th>Discount</th><th>Uses</th><th>Active</th><th>Exp.</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($coupons as $c): ?>
                            <tr id="couponRow_<?= $c['id'] ?>">
                                <td class="text-gold fw-bold"><?= sanitize($c['code']) ?></td>
                                <td class="text-white"><?= $c['discount_type']==='percent' ? $c['discount_value'].'%' : 'â‚¹'.$c['discount_value'] ?></td>
                                <td class="text-muted small"><?= $c['used_count'] ?>/<?= $c['uses_limit'] ?></td>
                                <td><?= $c['is_active'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                                <td class="text-muted small"><?= $c['expires_at'] ? date('d/m/y',strtotime($c['expires_at'])) : 'âˆž' ?></td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <button class="btn btn-sm btn-outline-gold" onclick="toggleCoupon(<?= $c['id'] ?>)" title="Toggle"><i class="bi bi-toggle-<?= $c['is_active']?'on':'off' ?>"></i></button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteCouponItem(<?= $c['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($coupons)): ?><tr><td colspan="6" class="text-center text-muted py-3">No coupons yet.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Payments -->
            <div class="col-lg-7">
                <h5 class="text-white fw-bold mb-3">Recent Transactions</h5>
                <div class="admin-table">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                            <tr>
                                <td><a href="<?= SITE_URL ?>/admin/orders.php?id=<?= $p['order_id'] ?>" class="text-gold text-decoration-none"><?= sanitize($p['order_number']) ?></a></td>
                                <td class="text-white"><?= sanitize($p['user_name']) ?></td>
                                <td class="text-gold fw-bold"><?= formatPrice($p['amount']) ?></td>
                                <td class="text-muted"><?= strtoupper($p['payment_method']) ?></td>
                                <td><?= $p['status']==='success' ? '<span class="badge bg-success">Success</span>' : '<span class="badge bg-warning text-dark">'.ucfirst($p['status']).'</span>' ?></td>
                                <td class="text-muted small"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- Add Coupon Modal -->
<div class="modal fade" id="addCouponModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title text-white"><i class="bi bi-ticket-perforated me-2 text-gold"></i>Create Coupon</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="addCouponForm"><div class="modal-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Coupon Code *</label><input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. SAVE20" required></div>
                <div class="col-md-6"><label class="form-label">Discount Type</label><select name="discount_type" class="form-select"><option value="percent">Percentage (%)</option><option value="fixed">Fixed Amount (â‚¹)</option></select></div>
                <div class="col-md-6"><label class="form-label">Discount Value *</label><input type="number" name="discount_value" class="form-control" min="1" step="0.01" required></div>
                <div class="col-md-6"><label class="form-label">Min. Order Amount (â‚¹)</label><input type="number" name="min_order" class="form-control" min="0" value="0"></div>
                <div class="col-md-6"><label class="form-label">Max. Discount (â‚¹)</label><input type="number" name="max_discount" class="form-control" min="0" value="0" placeholder="0 = no limit"></div>
                <div class="col-md-6"><label class="form-label">Total Uses Limit</label><input type="number" name="uses_limit" class="form-control" min="1" value="999"></div>
                <div class="col-12"><label class="form-label">Expiry Date</label><input type="date" name="expires_at" class="form-control"></div>
            </div>
        </div><div class="modal-footer"><button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-gold">Create Coupon</button></div></form>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
function toggleCoupon(id){ fetch('/online-book-resale/admin/payments.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=toggle&id=${id}`}).then(r=>r.json()).then(d=>{showToast(d.message,d.success?'success':'error');if(d.success)setTimeout(()=>location.reload(),800);}); }
function deleteCouponItem(id){ if(!confirm('Delete this coupon?')) return; fetch('/online-book-resale/admin/payments.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=delete&id=${id}`}).then(r=>r.json()).then(d=>{if(d.success){showToast(d.message,'success');document.getElementById('couponRow_'+id)?.remove();}else showToast(d.message,'error');}); }
document.getElementById('addCouponForm').addEventListener('submit',function(e){
    e.preventDefault(); const fd=new FormData(this); fd.append('action','add');
    fetch('/online-book-resale/admin/payments.php',{method:'POST',body:new URLSearchParams(fd)}).then(r=>r.json()).then(d=>{showToast(d.message,d.success?'success':'error');if(d.success){bootstrap.Modal.getInstance(document.getElementById('addCouponModal')).hide();setTimeout(()=>location.reload(),800);}});
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

