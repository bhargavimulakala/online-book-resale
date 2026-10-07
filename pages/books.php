<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

$q      = sanitize($_GET['q'] ?? '');
$catId  = (int)($_GET['cat'] ?? 0);
$cond   = sanitize($_GET['condition'] ?? '');
$lang   = sanitize($_GET['language'] ?? '');
$minP   = (float)($_GET['min_price'] ?? 0);
$maxP   = (float)($_GET['max_price'] ?? 99999);
$sort   = sanitize($_GET['sort'] ?? 'latest');
$page   = max(1, (int)($_GET['page'] ?? 1));

$where = ["b.status='approved'", 'b.quantity > 0'];
$params = []; $types = '';
if ($q) { $where[] = '(b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? OR b.publisher LIKE ?)'; $lq='%'.$q.'%'; $params[]=$lq;$params[]=$lq;$params[]=$lq;$params[]=$lq; $types.='ssss'; }
if ($catId) { $where[] = 'b.category_id=?'; $params[]=$catId; $types.='i'; }
if ($cond)  { $where[] = 'b.condition_type=?'; $params[]=$cond; $types.='s'; }
if ($lang)  { $where[] = 'b.language=?'; $params[]=$lang; $types.='s'; }
if ($minP > 0) { $where[] = 'b.selling_price >= ?'; $params[]=$minP; $types.='d'; }
if ($maxP < 99999) { $where[] = 'b.selling_price <= ?'; $params[]=$maxP; $types.='d'; }
$whereStr = implode(' AND ', $where);
$orderMap = ['latest'=>'b.created_at DESC','price_asc'=>'b.selling_price ASC','price_desc'=>'b.selling_price DESC','popular'=>'b.views DESC'];
$orderBy = $orderMap[$sort] ?? 'b.created_at DESC';

$cntStmt = $conn->prepare("SELECT COUNT(*) as c FROM books b JOIN categories c ON b.category_id=c.id JOIN users u ON b.seller_id=u.id WHERE $whereStr");
if ($types) $cntStmt->bind_param($types, ...$params);
$cntStmt->execute();
$total = (int)$cntStmt->get_result()->fetch_assoc()['c'];
$pag = paginate($total, BOOKS_PER_PAGE, $page);

$sql = "SELECT b.*, c.name as category_name, u.name as seller_name,
        (SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as any_image
        FROM books b JOIN categories c ON b.category_id=c.id JOIN users u ON b.seller_id=u.id
        WHERE $whereStr ORDER BY $orderBy LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$allParams = array_merge($params, [BOOKS_PER_PAGE, $pag['offset']]);
$allTypes  = $types . 'ii';
$stmt->bind_param($allTypes, ...$allParams);
$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$allCats = $conn->query("SELECT c.*,COUNT(b.id) as cnt FROM categories c LEFT JOIN books b ON b.category_id=c.id AND b.status='approved' GROUP BY c.id ORDER BY c.name")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Browse Books' . ($q ? " - $q" : '');
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb breadcrumb-dark">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Browse Books</li>
        </ol>
    </nav>
    <?= renderFlash() ?>
    <div class="row g-4">
        <!-- Filter Sidebar -->
        <div class="col-lg-3">
            <div class="filter-card">
                <form method="GET" id="filterForm">
                    <?php if ($q): ?><input type="hidden" name="q" value="<?= sanitize($q) ?>"><?php endif; ?>
                    <h5 class="text-white fw-bold mb-4"><i class="bi bi-funnel me-2 text-gold"></i>Filters</h5>
                    <div class="mb-4">
                        <div class="filter-title">Category</div>
                        <div style="max-height:220px;overflow-y:auto;padding-right:4px;">
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="cat" value="" id="catAll" <?= !$catId ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="form-check-label text-muted" for="catAll">All Categories</label>
                            </div>
                            <?php foreach ($allCats as $c): ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="radio" name="cat" value="<?= $c['id'] ?>" id="cat<?= $c['id'] ?>" <?= $catId==$c['id'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                <label class="form-check-label text-muted" for="cat<?= $c['id'] ?>"><?= sanitize($c['name']) ?> <span class="text-gold">(<?= $c['cnt'] ?>)</span></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="filter-title">Condition</div>
                        <?php foreach ([''=>'Any Condition','New'=>'New','Like New'=>'Like New','Good'=>'Good','Acceptable'=>'Acceptable','Old'=>'Old'] as $val=>$label): ?>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="condition" value="<?= $val ?>" id="cond_<?= str_replace(' ','_',$val) ?>" <?= $cond===$val ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label text-muted" for="cond_<?= str_replace(' ','_',$val) ?>"><?= $label ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-4">
                        <div class="filter-title">Price Range (â‚¹)</div>
                        <div class="d-flex gap-2 mb-2">
                            <input type="number" name="min_price" class="form-control form-control-sm" placeholder="Min â‚¹" value="<?= $minP > 0 ? (int)$minP : '' ?>">
                            <input type="number" name="max_price" class="form-control form-control-sm" placeholder="Max â‚¹" value="<?= $maxP < 99999 ? (int)$maxP : '' ?>">
                        </div>
                        <button type="submit" class="btn btn-gold btn-sm w-100">Apply Price</button>
                    </div>
                    <div class="mb-4">
                        <div class="filter-title">Language</div>
                        <?php foreach ([''=>'Any','English'=>'English','Hindi'=>'Hindi','Marathi'=>'Marathi','Tamil'=>'Tamil','Telugu'=>'Telugu','Bengali'=>'Bengali'] as $val=>$label): ?>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="radio" name="language" value="<?= $val ?>" <?= $lang===$val ? 'checked' : '' ?> onchange="this.form.submit()">
                            <label class="form-check-label text-muted"><?= $label ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?= SITE_URL ?>/pages/books.php<?= $q ? '?q='.urlencode($q) : '' ?>" class="btn btn-outline-gold btn-sm w-100"><i class="bi bi-x-circle me-1"></i>Clear All Filters</a>
                </form>
            </div>
        </div>
        <!-- Books Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h4 class="text-white mb-0"><?= $q ? 'Results for "' . sanitize($q) . '"' : ($catId ? sanitize($allCats[array_search($catId, array_column($allCats,'id'))]['name'] ?? 'Books') : 'All Books') ?></h4>
                    <small class="text-muted"><?= number_format($total) ?> book<?= $total != 1 ? 's' : '' ?> found</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Sort by:</span>
                    <select class="form-select form-select-sm" style="width:170px;" onchange="const u=new URL(window.location.href);u.searchParams.set('sort',this.value);u.searchParams.delete('page');window.location.href=u.toString();">
                        <option value="latest" <?= $sort==='latest'?'selected':'' ?>>Latest First</option>
                        <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High to Low</option>
                        <option value="popular" <?= $sort==='popular'?'selected':'' ?>>Most Popular</option>
                    </select>
                </div>
            </div>
            <?php if (empty($books)): ?>
            <div class="text-center py-5">
                <i class="bi bi-search" style="font-size:4rem;color:#d4af37;opacity:0.4;"></i>
                <h4 class="text-white mt-4">No books found</h4>
                <p class="text-muted mb-4">Try adjusting your search terms or filters.</p>
                <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-gold">Browse All Books</a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($books as $book):
                    $imgUrl = getBookImageUrl($book['any_image']);
                    $rating = getAvgRating($book['id'], $conn);
                    $disc = $book['original_price'] > 0 ? round((($book['original_price']-$book['selling_price'])/$book['original_price'])*100) : 0;
                ?>
                <div class="col-sm-6 col-xl-4">
                    <div class="book-card">
                        <div class="book-card-img-wrapper">
                            <?php if ($disc >= 10): ?><div class="book-card-badge"><span class="discount-badge"><?= $disc ?>% OFF</span></div><?php endif; ?>
                            <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>">
                                <img src="<?= $imgUrl ?>" alt="<?= sanitize($book['title']) ?>" class="book-card-img" loading="lazy" onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                            </a>
                            <div style="position:absolute;top:10px;right:10px;"><?= conditionBadge($book['condition_type']) ?></div>
                        </div>
                        <div class="book-card-body">
                            <small class="text-gold"><?= sanitize($book['category_name']) ?></small>
                            <h3 class="book-card-title mt-1"><a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>" class="text-decoration-none" style="color:inherit;"><?= sanitize($book['title']) ?></a></h3>
                            <p class="book-card-author mb-1"><?= sanitize($book['author']) ?></p>
                            <div class="d-flex align-items-center gap-1 mb-2"><?= renderStars($rating['avg']) ?><small class="text-muted ms-1">(<?= $rating['count'] ?>)</small></div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="book-card-price"><?= formatPrice($book['selling_price']) ?></span>
                                <?php if ($book['original_price'] > 0): ?><span class="book-card-original"><?= formatPrice($book['original_price']) ?></span><?php endif; ?>
                            </div>
                            <?php if ($book['is_negotiable']): ?><small class="text-success"><i class="bi bi-chat-dots me-1"></i>Negotiable</small><?php endif; ?>
                            <div class="book-card-actions">
                                <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>" class="btn btn-gold btn-sm flex-grow-1"><i class="bi bi-eye me-1"></i>View Details</a>
                                <button class="wishlist-btn" onclick="toggleWishlist(<?= $book['id'] ?>,this)" title="Add to Wishlist"><i class="bi bi-heart"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-5">
                <?php
                $qs = http_build_query(array_filter(['q'=>$q,'cat'=>$catId?:null,'condition'=>$cond,'language'=>$lang,'min_price'=>$minP?:null,'max_price'=>$maxP<99999?$maxP:null,'sort'=>$sort!='latest'?$sort:null]));
                echo renderPagination($pag, SITE_URL . '/pages/books.php?' . $qs);
                ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

