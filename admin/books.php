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
    if ($action === 'approve') {
        $conn->query("UPDATE books SET status='approved',rejection_reason=NULL WHERE id=$id");
        // Notify seller
        $book = $conn->query("SELECT seller_id,title FROM books WHERE id=$id")->fetch_assoc();
        if ($book) $conn->query("INSERT INTO notifications (user_id,title,message,type,link) VALUES ({$book['seller_id']},'Book Approved!','Your book \"{$conn->real_escape_string($book['title'])}\" has been approved and is now live.','success','/online-book-resale/pages/book_details.php?id=$id')");
        echo json_encode(['success'=>true,'message'=>'Book approved and listed!']);
    } elseif ($action === 'reject') {
        $reason = sanitize($_POST['reason'] ?? 'Does not meet our guidelines');
        $conn->query("UPDATE books SET status='rejected',rejection_reason='{$conn->real_escape_string($reason)}' WHERE id=$id");
        $book = $conn->query("SELECT seller_id,title FROM books WHERE id=$id")->fetch_assoc();
        if ($book) $conn->query("INSERT INTO notifications (user_id,title,message,type,link) VALUES ({$book['seller_id']},'Book Rejected','Your book \"{$conn->real_escape_string($book['title'])}\" was rejected. Reason: {$conn->real_escape_string($reason)}','danger','/online-book-resale/user/mybooks.php')");
        echo json_encode(['success'=>true,'message'=>'Book rejected.']);
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM books WHERE id=$id");
        echo json_encode(['success'=>true,'message'=>'Book deleted.']);
    } else echo json_encode(['success'=>false,'message'=>'Invalid action.']);
    exit;
}

$status = sanitize($_GET['status'] ?? 'all');
$search = sanitize($_GET['search'] ?? '');
$catId  = (int)($_GET['cat'] ?? 0);
$page   = max(1,(int)($_GET['page'] ?? 1));

$where = ['1=1'];
if ($status !== 'all') $where[] = "b.status='$status'";
if ($search) $where[] = "(b.title LIKE '%".addslashes($search)."%' OR b.author LIKE '%".addslashes($search)."%')";
if ($catId) $where[] = "b.category_id=$catId";
$whereStr = implode(' AND ', $where);

$total = (int)$conn->query("SELECT COUNT(*) as c FROM books b WHERE $whereStr")->fetch_assoc()['c'];
$pag = paginate($total, BOOKS_PER_PAGE, $page);
$books = $conn->query("SELECT b.*,c.name as cat_name,u.name as seller_name,(SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as img FROM books b JOIN categories c ON b.category_id=c.id JOIN users u ON b.seller_id=u.id WHERE $whereStr ORDER BY b.created_at DESC LIMIT " . BOOKS_PER_PAGE . " OFFSET " . $pag['offset'])->fetch_all(MYSQLI_ASSOC);
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$pendingCount = (int)$conn->query("SELECT COUNT(*) as c FROM books WHERE status='pending'")->fetch_assoc()['c'];

$pageTitle = 'Manage Books';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button><h6 class="text-white mb-0 fw-bold">Manage Books</h6></div>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
    <div class="admin-page-content">
        <div class="admin-page-header">
            <h2>Books Management</h2>
            <p>Approve, reject or delete book listings. <?php if($pendingCount): ?><span class="badge bg-warning text-dark ms-1"><?= $pendingCount ?> pending</span><?php endif; ?></p>
        </div>
        <!-- Status Tabs -->
        <div class="d-flex gap-2 mb-4 flex-wrap">
            <?php foreach (['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','sold'=>'Sold'] as $val=>$lbl): ?>
            <a href="?status=<?= $val ?>" class="btn btn-sm <?= $status===$val?'btn-gold':'btn-outline-gold' ?>"><?= $lbl ?></a>
            <?php endforeach; ?>
        </div>
        <!-- Search -->
        <div class="glass-card p-3 mb-4">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="status" value="<?= $status ?>">
                <div class="col-md-5"><input type="text" name="search" class="form-control" placeholder="Search by title or author..." value="<?= sanitize($search) ?>"></div>
                <div class="col-md-3">
                    <select name="cat" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $catId==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-gold w-100">Search</button></div>
                <div class="col-md-2"><a href="?status=<?= $status ?>" class="btn btn-outline-gold w-100">Clear</a></div>
            </form>
        </div>
        <!-- Books Table -->
        <div class="admin-table">
            <table class="table table-hover align-middle mb-0" id="booksTable">
                <thead><tr><th>Book</th><th>Seller</th><th>Category</th><th>Price</th><th>Condition</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($books as $b):
                        $imgUrl = getBookImageUrl($b['img']); ?>
                    <tr id="bookRow_<?= $b['id'] ?>">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= $imgUrl ?>" style="width:44px;height:52px;object-fit:cover;border-radius:6px;" onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                                <div>
                                    <div class="text-white fw-bold small truncate-2" style="max-width:180px;"><?= sanitize($b['title']) ?></div>
                                    <small class="text-muted"><?= sanitize($b['author']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-white small"><?= sanitize($b['seller_name']) ?></td>
                        <td><span class="badge" style="background:rgba(124,58,237,0.2);color:#9f63f5;border:1px solid rgba(124,58,237,0.3);"><?= sanitize($b['cat_name']) ?></span></td>
                        <td class="text-gold fw-bold"><?= formatPrice($b['selling_price']) ?></td>
                        <td><?= conditionBadge($b['condition_type']) ?></td>
                        <td><?= statusBadge($b['status']) ?></td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($b['created_at'])) ?></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                                <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-sm btn-outline-gold" title="View"><i class="bi bi-eye"></i></a>
                                <?php if ($b['status']==='pending'): ?>
                                <button class="btn btn-sm btn-success" onclick="approveBook(<?= $b['id'] ?>,'approve')" title="Approve"><i class="bi bi-check-lg"></i></button>
                                <button class="btn btn-sm btn-warning" onclick="approveBook(<?= $b['id'] ?>,'reject')" title="Reject"><i class="bi bi-x-lg"></i></button>
                                <?php elseif ($b['status']==='approved'): ?>
                                <button class="btn btn-sm btn-warning" onclick="approveBook(<?= $b['id'] ?>,'reject')" title="Reject"><i class="bi bi-slash-circle"></i></button>
                                <?php elseif ($b['status']==='rejected'): ?>
                                <button class="btn btn-sm btn-success" onclick="approveBook(<?= $b['id'] ?>,'approve')" title="Approve"><i class="bi bi-check-lg"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteBook(<?= $b['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($books)): ?><tr><td colspan="8" class="text-center text-muted py-4">No books found.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><?= renderPagination($pag, SITE_URL . '/admin/books.php?status=' . $status . '&search=' . urlencode($search) . '&cat=' . $catId) ?></div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
function deleteBook(id){
    if(!confirm('Delete this book listing permanently?')) return;
    fetch('/online-book-resale/admin/books.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:`action=delete&id=${id}`})
    .then(r=>r.json()).then(d=>{if(d.success){showToast(d.message,'success');document.getElementById('bookRow_'+id)?.remove();}else showToast(d.message,'error');});
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

