<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireAdminLogin();

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = sanitize($_POST['action']);
    $id = (int)($_POST['id'] ?? 0);
    if ($action === 'block') {
        $conn->query("UPDATE users SET is_blocked=1 WHERE id=$id");
        echo json_encode(['success'=>true,'message'=>'User blocked.']);
    } elseif ($action === 'unblock') {
        $conn->query("UPDATE users SET is_blocked=0 WHERE id=$id");
        echo json_encode(['success'=>true,'message'=>'User unblocked.']);
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM users WHERE id=$id");
        echo json_encode(['success'=>true,'message'=>'User deleted.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Invalid action.']);
    }
    exit;
}

$search = sanitize($_GET['search'] ?? '');
$filter = sanitize($_GET['filter'] ?? 'all');
$page   = max(1,(int)($_GET['page'] ?? 1));

$where = ['1=1'];
if ($search) $where[] = "(name LIKE '%".addslashes($search)."%' OR email LIKE '%".addslashes($search)."%')";
if ($filter === 'blocked') $where[] = 'is_blocked=1';
if ($filter === 'active') $where[] = 'is_blocked=0';
$whereStr = implode(' AND ', $where);

$total = (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE $whereStr")->fetch_assoc()['c'];
$pag = paginate($total, USERS_PER_PAGE, $page);
$users = $conn->query("SELECT u.*, (SELECT COUNT(*) FROM books WHERE seller_id=u.id) as books_count, (SELECT COUNT(*) FROM orders WHERE user_id=u.id) as orders_count FROM users u WHERE $whereStr ORDER BY u.created_at DESC LIMIT " . USERS_PER_PAGE . " OFFSET " . $pag['offset'])->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button><h6 class="text-white mb-0 fw-bold">Manage Users</h6></div>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
    <div class="admin-page-content">
        <div class="admin-page-header">
            <h2>Users Management</h2>
            <p>View, block, and manage registered users. Total: <?= number_format($total) ?></p>
        </div>
        <!-- Filters -->
        <div class="glass-card p-3 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">Search Users</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Name or email..." value="<?= sanitize($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter</label>
                    <select name="filter" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?= $filter==='all'?'selected':'' ?>>All Users</option>
                        <option value="active" <?= $filter==='active'?'selected':'' ?>>Active Users</option>
                        <option value="blocked" <?= $filter==='blocked'?'selected':'' ?>>Blocked Users</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-gold w-100"><i class="bi bi-search me-1"></i>Search</button></div>
                <div class="col-md-2"><a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-outline-gold w-100">Clear</a></div>
            </form>
        </div>
        <!-- Table -->
        <div class="admin-table">
            <table class="table table-hover align-middle mb-0" id="usersTable">
                <thead><tr>
                    <th>#</th><th>User</th><th>Phone</th><th>Books</th><th>Orders</th><th>Status</th><th>Joined</th><th>Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($users as $i => $u): ?>
                    <tr id="userRow_<?= $u['id'] ?>">
                        <td class="text-muted"><?= $pag['offset']+$i+1 ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-person-circle fs-4 text-gold"></i>
                                <div>
                                    <div class="text-white fw-bold small"><?= sanitize($u['name']) ?></div>
                                    <small class="text-muted"><?= sanitize($u['email']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted"><?= sanitize($u['phone'] ?? '-') ?></td>
                        <td><span class="badge bg-primary"><?= $u['books_count'] ?></span></td>
                        <td><span class="badge bg-info"><?= $u['orders_count'] ?></span></td>
                        <td>
                            <?php if ($u['is_blocked']): ?>
                            <span class="badge bg-danger">Blocked</span>
                            <?php else: ?>
                            <span class="badge bg-success">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <?php if ($u['is_blocked']): ?>
                                <button class="btn btn-success btn-sm" onclick="toggleUserBlock(<?= $u['id'] ?>,false)" title="Unblock"><i class="bi bi-unlock"></i></button>
                                <?php else: ?>
                                <button class="btn btn-warning btn-sm" onclick="toggleUserBlock(<?= $u['id'] ?>,true)" title="Block"><i class="bi bi-slash-circle"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-danger btn-sm" onclick="deleteUser(<?= $u['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><?= renderPagination($pag, SITE_URL . '/admin/users.php?search=' . urlencode($search) . '&filter=' . $filter) ?></div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
function deleteUser(id){
    if(!confirm('Permanently delete this user and all their data?')) return;
    fetch('/online-book-resale/admin/users.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=delete&id=${id}`})
    .then(r=>r.json()).then(d=>{if(d.success){showToast(d.message,'success');document.getElementById('userRow_'+id)?.remove();}else showToast(d.message,'error');});
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

