<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];

$bookId = (int)($_GET['id'] ?? 0);
if (!$bookId) { header('Location: ' . SITE_URL . '/user/mybooks.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM books WHERE id=? AND seller_id=?");
$stmt->bind_param('ii', $bookId, $userId); $stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
if (!$book) { setFlash('error','Book not found or access denied.'); header('Location: ' . SITE_URL . '/user/mybooks.php'); exit; }

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$images = $conn->query("SELECT * FROM book_images WHERE book_id=$bookId ORDER BY is_primary DESC")->fetch_all(MYSQLI_ASSOC);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = sanitize($_POST['title'] ?? '');
    $author    = sanitize($_POST['author'] ?? '');
    $isbn      = sanitize($_POST['isbn'] ?? '');
    $catId     = (int)($_POST['category_id'] ?? 0);
    $publisher = sanitize($_POST['publisher'] ?? '');
    $edition   = sanitize($_POST['edition'] ?? '');
    $language  = sanitize($_POST['language'] ?? 'English');
    $pages     = (int)($_POST['pages'] ?? 0);
    $cond      = sanitize($_POST['condition_type'] ?? 'Good');
    $desc      = sanitize($_POST['description'] ?? '');
    $origPrice = (float)($_POST['original_price'] ?? 0);
    $sellPrice = (float)($_POST['selling_price'] ?? 0);
    $negotiable= isset($_POST['is_negotiable']) ? 1 : 0;
    $qty       = max(1, (int)($_POST['quantity'] ?? 1));

    if (!$title || !$author || !$catId || !$sellPrice) {
        $error = 'Please fill all required fields.';
    } else {
        $upd = $conn->prepare("
UPDATE books
SET
    category_id=?,
    title=?,
    author=?,
    isbn=?,
    publisher=?,
    edition=?,
    language=?,
    pages=?,
    condition_type=?,
    description=?,
    original_price=?,
    selling_price=?,
    is_negotiable=?,
    quantity=?,
    status='pending'
WHERE id=? AND seller_id=?
");

$upd->bind_param(
    "isssssssissdiiii",
    $catId,
    $title,
    $author,
    $isbn,
    $publisher,
    $edition,
    $language,
    $pages,
    $cond,
    $desc,
    $origPrice,
    $sellPrice,
    $negotiable,
    $qty,
    $bookId,
    $userId
);

$upd->execute();
        $upd->execute();
        // New images
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $k => $name) {
                if ($_FILES['images']['error'][$k] !== UPLOAD_ERR_OK) continue;
                $file = ['name'=>$name,'type'=>$_FILES['images']['type'][$k],'tmp_name'=>$_FILES['images']['tmp_name'][$k],'error'=>$_FILES['images']['error'][$k],'size'=>$_FILES['images']['size'][$k]];
                $imgPath = uploadImage($file, 'books');
                if ($imgPath) {
                    $hasPrimary = !empty($images);
                    $ip = $hasPrimary ? 0 : 1;
                    $iStmt = $conn->prepare("INSERT INTO book_images (book_id,image_name,is_primary) VALUES (?,?,?)");
                    $iStmt->bind_param('isi', $bookId, $imgPath, $ip); $iStmt->execute();
                }
            }
        }
        // Delete images
        if (!empty($_POST['delete_images'])) {
            foreach ($_POST['delete_images'] as $imgId) {
                $iDel = $conn->prepare("SELECT image_name FROM book_images WHERE id=? AND book_id=?");
                $iDel->bind_param('ii',(int)$imgId,$bookId); $iDel->execute();
                $row = $iDel->get_result()->fetch_assoc();
                if ($row) { @unlink(UPLOAD_DIR . $row['image_name']); $conn->query("DELETE FROM book_images WHERE id=" . (int)$imgId); }
            }
        }
        setFlash('success', 'Book updated! Submitted for re-approval.');
        header('Location: ' . SITE_URL . '/user/mybooks.php'); exit;
    }
}

$pageTitle = 'Edit Book';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5" style="max-width:800px;">
    <div class="mb-4">
        <h2 class="text-white fw-bold"><i class="bi bi-pencil me-2 text-gold"></i>Edit Book Listing</h2>
        <p class="text-muted">Update your listing. It will be re-submitted for approval.</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="glass-card p-4 mb-4">
            <h5 class="text-white fw-bold mb-4"><i class="bi bi-book me-2 text-gold"></i>Book Information</h5>
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Book Title *</label><input type="text" name="title" class="form-control" required value="<?= sanitize($book['title']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Author *</label><input type="text" name="author" class="form-control" required value="<?= sanitize($book['author']) ?>"></div>
                <div class="col-md-6"><label class="form-label">ISBN</label><input type="text" name="isbn" class="form-control" value="<?= sanitize($book['isbn'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Category *</label>
                    <select name="category_id" class="form-select" required>
                        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $book['category_id']==$c['id']?'selected':'' ?>><?= sanitize($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Publisher</label><input type="text" name="publisher" class="form-control" value="<?= sanitize($book['publisher'] ?? '') ?>"></div>
                <div class="col-md-4"><label class="form-label">Edition</label><input type="text" name="edition" class="form-control" value="<?= sanitize($book['edition'] ?? '') ?>"></div>
                <div class="col-md-4"><label class="form-label">Language</label>
                    <select name="language" class="form-select">
                        <?php foreach (['English','Hindi','Marathi','Tamil','Telugu','Bengali'] as $l): ?>
                        <option value="<?= $l ?>" <?= $book['language']===$l?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Pages</label><input type="number" name="pages" class="form-control" min="1" value="<?= $book['pages'] ?? '' ?>"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4"><?= sanitize($book['description'] ?? '') ?></textarea></div>
            </div>
        </div>
        <div class="glass-card p-4 mb-4">
            <h5 class="text-white fw-bold mb-4"><i class="bi bi-tag me-2 text-gold"></i>Condition & Pricing</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Book Condition *</label>
                    <select name="condition_type" class="form-select" required>
                        <?php foreach (['New','Like New','Good','Acceptable','Old'] as $c): ?><option value="<?= $c ?>" <?= $book['condition_type']===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Quantity</label><input type="number" name="quantity" class="form-control" min="1" value="<?= $book['quantity'] ?>"></div>
                <div class="col-md-6"><label class="form-label">Original Price (â‚¹)</label><div class="input-group"><span class="input-group-text">â‚¹</span><input type="number" name="original_price" class="form-control" step="0.01" value="<?= $book['original_price'] ?>"></div></div>
                <div class="col-md-6"><label class="form-label">Selling Price (â‚¹) *</label><div class="input-group"><span class="input-group-text">â‚¹</span><input type="number" name="selling_price" class="form-control" step="0.01" required value="<?= $book['selling_price'] ?>"></div></div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_negotiable" id="negotiable" <?= $book['is_negotiable']?'checked':'' ?>><label class="form-check-label text-muted" for="negotiable">Price is negotiable</label></div></div>
            </div>
        </div>
        <div class="glass-card p-4 mb-4">
            <h5 class="text-white fw-bold mb-4"><i class="bi bi-images me-2 text-gold"></i>Book Images</h5>
            <?php if (!empty($images)): ?>
            <div class="d-flex flex-wrap gap-3 mb-3">
                <?php foreach ($images as $img): ?>
                <div class="position-relative">
                    <img src="<?= getBookImageUrl($img['image_name']) ?>"
                         style="width:90px;height:100px;object-fit:cover;border-radius:8px;"
                         onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                    <?php if ($img['is_primary']): ?><span class="position-absolute top-0 start-0 badge bg-gold text-dark" style="font-size:0.65rem;">Primary</span><?php endif; ?>
                    <div class="form-check position-absolute bottom-0 start-0 m-1">
                        <input class="form-check-input" type="checkbox" name="delete_images[]" value="<?= $img['id'] ?>" id="del_<?= $img['id'] ?>" style="background:rgba(239,68,68,0.3);border-color:#ef4444;">
                        <label class="form-check-label text-danger" for="del_<?= $img['id'] ?>" style="font-size:0.7rem;">Del</label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <div class="mb-2"><label class="form-label">Add New Images</label><input type="file" name="images[]" class="form-control" accept="image/*" multiple data-preview="newPreview"></div>
            <div id="newPreview" class="d-flex flex-wrap gap-2"></div>
        </div>
        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-gold px-5 py-2 fw-bold"><i class="bi bi-save me-2"></i>Save Changes</button>
            <a href="<?= SITE_URL ?>/user/mybooks.php" class="btn btn-outline-gold px-4">Cancel</a>
        </div>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

