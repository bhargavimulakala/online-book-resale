<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
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
        $error = 'Please fill all required fields (Title, Author, Category, Price).';
    } else {
        $stmt = $conn->prepare("INSERT INTO books (seller_id,category_id,title,author,isbn,publisher,edition,language,pages,condition_type,description,original_price,selling_price,is_negotiable,quantity) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('iissssssissddii', $userId,$catId,$title,$author,$isbn,$publisher,$edition,$language,$pages,$cond,$desc,$origPrice,$sellPrice,$negotiable,$qty);
        if ($stmt->execute()) {
            $bookId = $conn->insert_id;
            // Upload images
            $uploadedAny = false;
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['name'] as $k => $name) {
                    if ($_FILES['images']['error'][$k] !== UPLOAD_ERR_OK) continue;
                    $file = ['name'=>$name,'type'=>$_FILES['images']['type'][$k],'tmp_name'=>$_FILES['images']['tmp_name'][$k],'error'=>$_FILES['images']['error'][$k],'size'=>$_FILES['images']['size'][$k]];
                    $imgPath = uploadImage($file, 'books');
                    if ($imgPath) {
                        $isPrimary = !$uploadedAny ? 1 : 0;
                        $iStmt = $conn->prepare("INSERT INTO book_images (book_id,image_name,is_primary) VALUES (?,?,?)");
                        $iStmt->bind_param('isi', $bookId, $imgPath, $isPrimary); $iStmt->execute();
                        $uploadedAny = true;
                    }
                }
            }
            setFlash('success', 'Book listed successfully! It will be visible after admin approval.');
            header('Location: ' . SITE_URL . '/user/mybooks.php'); exit;
        } else {
            $error = 'Failed to list book. Please try again.';
        }
    }
}

$pageTitle = 'Sell a Book';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5" style="max-width:800px;">
    <div class="mb-4">
        <h2 class="text-white fw-bold"><i class="bi bi-plus-circle me-2 text-gold"></i>List a Book for Sale</h2>
        <p class="text-muted">Fill in the details to list your book. It will go live after admin approval.</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?= renderFlash() ?>
    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        <!-- Basic Info -->
        <div class="glass-card p-4 mb-4">
            <h5 class="text-white fw-bold mb-4"><i class="bi bi-book me-2 text-gold"></i>Book Information</h5>
            <div class="row g-3">
                <div class="col-12"><label class="form-label">Book Title *</label><input type="text" name="title" class="form-control" placeholder="Full title of the book" required value="<?= isset($_POST['title'])?sanitize($_POST['title']):'' ?>"></div>
                <div class="col-md-6"><label class="form-label">Author *</label><input type="text" name="author" class="form-control" placeholder="Author name(s)" required value="<?= isset($_POST['author'])?sanitize($_POST['author']):'' ?>"></div>
                <div class="col-md-6"><label class="form-label">ISBN</label><input type="text" name="isbn" class="form-control" placeholder="978-..." value="<?= isset($_POST['isbn'])?sanitize($_POST['isbn']):'' ?>"></div>
                <div class="col-md-6"><label class="form-label">Category *</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (isset($_POST['category_id'])&&$_POST['category_id']==$c['id'])?'selected':'' ?>><?= sanitize($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Publisher</label><input type="text" name="publisher" class="form-control" value="<?= isset($_POST['publisher'])?sanitize($_POST['publisher']):'' ?>"></div>
                <div class="col-md-4"><label class="form-label">Edition</label><input type="text" name="edition" class="form-control" placeholder="e.g. 3rd" value="<?= isset($_POST['edition'])?sanitize($_POST['edition']):'' ?>"></div>
                <div class="col-md-4"><label class="form-label">Language</label>
                    <select name="language" class="form-select">
                        <?php foreach (['English','Hindi','Marathi','Tamil','Telugu','Bengali','Gujarati','Kannada','Malayalam','Punjabi'] as $l): ?>
                        <option value="<?= $l ?>" <?= (isset($_POST['language'])&&$_POST['language']===$l)?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">No. of Pages</label><input type="number" name="pages" class="form-control" min="1" value="<?= isset($_POST['pages'])?(int)$_POST['pages']:'' ?>"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="4" placeholder="Describe the book condition, highlights, notes, etc."><?= isset($_POST['description'])?sanitize($_POST['description']):'' ?></textarea></div>
            </div>
        </div>
        <!-- Condition & Pricing -->
        <div class="glass-card p-4 mb-4">
            <h5 class="text-white fw-bold mb-4"><i class="bi bi-tag me-2 text-gold"></i>Condition & Pricing</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Book Condition *</label>
                    <select name="condition_type" class="form-select" required>
                        <?php foreach (['New','Like New','Good','Acceptable','Old'] as $c): ?>
                        <option value="<?= $c ?>" <?= (isset($_POST['condition_type'])&&$_POST['condition_type']===$c)?'selected':($c==='Good'?'selected':'') ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Quantity Available</label><input type="number" name="quantity" class="form-control" min="1" max="99" value="<?= isset($_POST['quantity'])?(int)$_POST['quantity']:1 ?>"></div>
                <div class="col-md-6"><label class="form-label">Original/MRP Price (₹)</label><div class="input-group"><span class="input-group-text">₹</span><input type="number" name="original_price" class="form-control" min="0" step="0.01" placeholder="0.00" value="<?= isset($_POST['original_price'])?(float)$_POST['original_price']:'' ?>"></div></div>
                <div class="col-md-6"><label class="form-label">Your Selling Price (₹) *</label><div class="input-group"><span class="input-group-text">₹</span><input type="number" name="selling_price" class="form-control" min="1" step="0.01" placeholder="0.00" required value="<?= isset($_POST['selling_price'])?(float)$_POST['selling_price']:'' ?>"></div></div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_negotiable" id="negotiable" <?= isset($_POST['is_negotiable'])?'checked':'' ?>><label class="form-check-label text-muted" for="negotiable"><i class="bi bi-chat-dots me-2 text-gold"></i>Price is negotiable (buyers can contact you)</label></div></div>
            </div>
        </div>
        <!-- Images -->
        <div class="glass-card p-4 mb-4">
            <h5 class="text-white fw-bold mb-4"><i class="bi bi-images me-2 text-gold"></i>Book Images</h5>
            <div class="mb-3">
                <label class="form-label">Upload Images (max 5, JPG/PNG, max 5MB each)</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple data-preview="imagePreviewContainer">
                <small class="text-muted">First image will be used as the primary/cover image</small>
            </div>
            <div id="imagePreviewContainer" class="d-flex flex-wrap gap-2"></div>
        </div>
        <div class="d-flex gap-3">
            <button type="submit" class="btn btn-gold px-5 py-2 fw-bold"><i class="bi bi-check-circle me-2"></i>Submit for Review</button>
            <a href="<?= SITE_URL ?>/user/mybooks.php" class="btn btn-outline-gold px-4">Cancel</a>
        </div>
        <p class="text-muted small mt-2"><i class="bi bi-info-circle me-1 text-gold"></i>Your listing will be reviewed by admin before going live.</p>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

