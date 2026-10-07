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
    if ($action === 'add') {
        $name = sanitize($_POST['name'] ?? '');
        $desc = sanitize($_POST['description'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'bi-book');
        if (!$name) { echo json_encode(['success'=>false,'message'=>'Name required.']); exit; }
        $stmt = $conn->prepare("INSERT INTO categories (name,description,icon) VALUES (?,?,?)");
        $stmt->bind_param('sss',$name,$desc,$icon); $stmt->execute();
        echo json_encode(['success'=>true,'message'=>'Category added!','id'=>$conn->insert_id]);
    } elseif ($action === 'edit') {
        $name = sanitize($_POST['name'] ?? '');
        $desc = sanitize($_POST['description'] ?? '');
        $icon = sanitize($_POST['icon'] ?? 'bi-book');
        $stmt = $conn->prepare("UPDATE categories SET name=?,description=?,icon=? WHERE id=?");
        $stmt->bind_param('sssi',$name,$desc,$icon,$id); $stmt->execute();
        echo json_encode(['success'=>true,'message'=>'Category updated!']);
    } elseif ($action === 'delete') {
        $cnt = (int)$conn->query("SELECT COUNT(*) as c FROM books WHERE category_id=$id")->fetch_assoc()['c'];
        if ($cnt>0) { echo json_encode(['success'=>false,'message'=>"Cannot delete: $cnt books use this category."]); exit; }
        $conn->query("DELETE FROM categories WHERE id=$id");
        echo json_encode(['success'=>true,'message'=>'Category deleted.']);
    } else echo json_encode(['success'=>false,'message'=>'Invalid action.']);
    exit;
}

$categories = $conn->query("SELECT c.*, COUNT(b.id) as book_count FROM categories c LEFT JOIN books b ON b.category_id=c.id GROUP BY c.id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Manage Categories';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button><h6 class="text-white mb-0 fw-bold">Categories</h6></div>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
    <div class="admin-page-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="admin-page-header mb-0"><h2>Book Categories</h2><p>Manage all book categories and genres.</p></div>
            <button class="btn btn-gold" data-bs-toggle="modal" data-bs-target="#addCatModal"><i class="bi bi-plus me-2"></i>Add Category</button>
        </div>
        <div class="admin-table">
            <table class="table table-hover align-middle mb-0" id="categoriesTable">
                <thead><tr><th>#</th><th>Icon</th><th>Category Name</th><th>Description</th><th>Books</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($categories as $i => $cat): ?>
                    <tr id="catRow_<?= $cat['id'] ?>">
                        <td class="text-muted"><?= $i+1 ?></td>
                        <td><i class="bi <?= sanitize($cat['icon']) ?> fs-4 text-gold"></i></td>
                        <td class="text-white fw-bold"><?= sanitize($cat['name']) ?></td>
                        <td class="text-muted small"><?= sanitize($cat['description'] ?? '-') ?></td>
                        <td><span class="badge bg-primary"><?= $cat['book_count'] ?></span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-gold" onclick='openEditCat(<?= json_encode($cat) ?>)' title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?= $cat['id'] ?>)" title="Delete"><i class="bi bi-trash"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title text-white"><i class="bi bi-tags me-2 text-gold"></i>Add Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="addCatForm"><div class="modal-body">
            <div class="mb-3"><label class="form-label">Category Name *</label><input type="text" name="name" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Icon (Bootstrap Icon class)</label>
                <input type="text" name="icon" class="form-control" placeholder="e.g. bi-book" value="bi-book">
                <small class="text-muted">Browse icons at <a href="https://icons.getbootstrap.com/" target="_blank" class="text-gold">icons.getbootstrap.com</a></small>
            </div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
        </div><div class="modal-footer"><button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-gold">Add Category</button></div></form>
    </div></div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCatModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title text-white"><i class="bi bi-pencil me-2 text-gold"></i>Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <form id="editCatForm"><div class="modal-body">
            <input type="hidden" name="id" id="editCatId">
            <div class="mb-3"><label class="form-label">Category Name *</label><input type="text" name="name" id="editCatName" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">Icon</label><input type="text" name="icon" id="editCatIcon" class="form-control"></div>
            <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="editCatDesc" class="form-control" rows="2"></textarea></div>
        </div><div class="modal-footer"><button type="button" class="btn btn-outline-gold" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-gold">Save Changes</button></div></form>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<script>
function openEditCat(cat) {
    document.getElementById('editCatId').value = cat.id;
    document.getElementById('editCatName').value = cat.name;
    document.getElementById('editCatIcon').value = cat.icon || 'bi-book';
    document.getElementById('editCatDesc').value = cat.description || '';
    new bootstrap.Modal(document.getElementById('editCatModal')).show();
}
document.getElementById('addCatForm').addEventListener('submit',function(e){
    e.preventDefault();
    const fd=new FormData(this);
    fd.append('action','add');
    fetch('/online-book-resale/admin/categories.php',{method:'POST',body:new URLSearchParams(fd)})
    .then(r=>r.json()).then(d=>{showToast(d.message,d.success?'success':'error');if(d.success){bootstrap.Modal.getInstance(document.getElementById('addCatModal')).hide();setTimeout(()=>location.reload(),800);}});
});
document.getElementById('editCatForm').addEventListener('submit',function(e){
    e.preventDefault();
    const fd=new FormData(this);
    fd.append('action','edit');
    fetch('/online-book-resale/admin/categories.php',{method:'POST',body:new URLSearchParams(fd)})
    .then(r=>r.json()).then(d=>{showToast(d.message,d.success?'success':'error');if(d.success){bootstrap.Modal.getInstance(document.getElementById('editCatModal')).hide();setTimeout(()=>location.reload(),800);}});
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

