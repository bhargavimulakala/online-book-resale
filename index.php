<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

$pageTitle = 'Buy & Sell Books Online';
$pageDesc = 'BookResale - India\'s trusted platform for buying and selling new & used books at affordable prices for students and readers.';

// Featured books (approved, sorted by views)
$featuredBooks = $conn->query("
    SELECT b.*, c.name as category_name, u.name as seller_name,
           (SELECT image_name FROM book_images WHERE book_id=b.id AND is_primary=1 LIMIT 1) as primary_image,
           (SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as any_image
    FROM books b
    JOIN categories c ON b.category_id = c.id
    JOIN users u ON b.seller_id = u.id
    WHERE b.status='approved' AND b.quantity > 0
    ORDER BY b.views DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Latest books
$latestBooks = $conn->query("
    SELECT b.*, c.name as category_name,
           (SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as any_image
    FROM books b
    JOIN categories c ON b.category_id = c.id
    WHERE b.status='approved' AND b.quantity > 0
    ORDER BY b.created_at DESC LIMIT 4
")->fetch_all(MYSQLI_ASSOC);

// Categories with book count
$categories = $conn->query("
    SELECT c.*, COUNT(b.id) as book_count
    FROM categories c
    LEFT JOIN books b ON b.category_id = c.id AND b.status='approved'
    GROUP BY c.id
    ORDER BY book_count DESC LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

// Stats
$totalBooks = $conn->query("SELECT COUNT(*) as c FROM books WHERE status='approved'")->fetch_assoc()['c'];
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$totalSold = $conn->query("SELECT COUNT(*) as c FROM order_items")->fetch_assoc()['c'];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ============ HERO SECTION ============ -->
<section class="hero-section">
    <div class="container position-relative" style="z-index:2;">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-badge">
                    <i class="bi bi-stars"></i>
                    <span>India's #1 Book Resale Platform</span>
                </div>
                <h1 class="hero-title">
                    Buy & Sell Books<br>
                    at <span class="highlight">Best Prices</span>
                </h1>
                <p class="hero-subtitle">
                    Discover thousands of new and used books. Save big on textbooks, novels, and study material. Connect with sellers directly.
                </p>
                <div class="hero-buttons d-flex flex-wrap gap-3">
                    <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-gold btn-lg px-4">
                        <i class="bi bi-search me-2"></i>Browse Books
                    </a>
                    <a href="<?= SITE_URL ?>/seller/add_book.php" class="btn btn-outline-gold btn-lg px-4">
                        <i class="bi bi-plus-circle me-2"></i>Sell a Book
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="number"><?= number_format($totalBooks) ?>+</div>
                        <div class="label">Books Listed</div>
                    </div>
                    <div class="hero-stat">
                        <div class="number"><?= number_format($totalUsers) ?>+</div>
                        <div class="label">Happy Users</div>
                    </div>
                    <div class="hero-stat">
                        <div class="number"><?= number_format($totalSold) ?>+</div>
                        <div class="label">Books Sold</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block">
                <div class="hero-image-container text-center">
                    <div style="width:320px;height:360px;margin:0 auto;background:linear-gradient(135deg,rgba(212,175,55,0.15),rgba(124,58,237,0.15));border-radius:30px;border:1px solid rgba(212,175,55,0.3);display:flex;align-items:center;justify-content:center;flex-direction:column;gap:20px;backdrop-filter:blur(10px);">
                        <i class="bi bi-book-half" style="font-size:8rem;color:#d4af37;opacity:0.9;"></i>
                        <p style="color:rgba(255,255,255,0.7);font-size:1rem;font-weight:600;">Find Your Next Book</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Quick Search -->
        <div class="hero-search-box mt-4">
            <form action="<?= SITE_URL ?>/pages/books.php" method="GET">
                <div class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted mb-1">Search Books</label>
                        <input type="text" name="q" class="form-control" placeholder="Title, Author, or ISBN...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Category</label>
                        <select name="cat" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= sanitize($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Condition</label>
                        <select name="condition" class="form-select">
                            <option value="">Any</option>
                            <option>New</option><option>Like New</option>
                            <option>Good</option><option>Acceptable</option><option>Old</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-gold w-100 py-2">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ============ CATEGORIES ============ -->
<section class="section-pad section-darker">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">Browse by <span class="text-gold">Category</span></h2>
            <p class="text-muted mt-2">Explore books across all subjects and genres</p>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-4 col-lg-2 animate-on-scroll">
                <a href="<?= SITE_URL ?>/pages/books.php?cat=<?= $cat['id'] ?>" class="category-card">
                    <i class="bi <?= sanitize($cat['icon']) ?> category-icon"></i>
                    <h6><?= sanitize($cat['name']) ?></h6>
                    <small><?= $cat['book_count'] ?> books</small>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ FEATURED BOOKS ============ -->
<section class="section-pad section-dark">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="section-title">Featured <span class="text-gold">Books</span></h2>
                <p class="text-muted mt-1">Most popular books this week</p>
            </div>
            <a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-outline-gold">View All <i class="bi bi-arrow-right ms-2"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredBooks as $book):
                $img = $book['primary_image'] ?? $book['any_image'];
                $imgUrl = getBookImageUrl($img);
                $discount = $book['original_price'] > 0 ? round((($book['original_price'] - $book['selling_price']) / $book['original_price']) * 100) : 0;
                $rating = getAvgRating($book['id'], $conn);
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3 animate-on-scroll">
                <div class="book-card">
                    <div class="book-card-img-wrapper">
                        <?php if ($discount >= 10): ?>
                        <div class="book-card-badge"><span class="discount-badge"><?= $discount ?>% OFF</span></div>
                        <?php endif; ?>
                        <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>">
                            <img src="<?= $imgUrl ?>" alt="<?= sanitize($book['title']) ?>" class="book-card-img" loading="lazy"
                                 onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                        </a>
                        <div style="position:absolute;top:12px;right:12px;">
                            <?php echo conditionBadge($book['condition_type']); ?>
                        </div>
                    </div>
                    <div class="book-card-body">
                        <p class="book-card-author"><i class="bi bi-person me-1"></i><?= sanitize($book['author']) ?></p>
                        <h3 class="book-card-title">
                            <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>" class="text-decoration-none" style="color:inherit;">
                                <?= sanitize($book['title']) ?>
                            </a>
                        </h3>
                        <div class="d-flex align-items-center gap-2 my-2">
                            <?= renderStars($rating['avg']) ?>
                            <small class="text-muted">(<?= $rating['count'] ?>)</small>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="book-card-price"><?= formatPrice($book['selling_price']) ?></span>
                            <?php if ($book['original_price'] > 0): ?>
                            <span class="book-card-original"><?= formatPrice($book['original_price']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($book['is_negotiable']): ?>
                        <small class="text-success"><i class="bi bi-chat-dots me-1"></i>Negotiable</small>
                        <?php endif; ?>
                        <div class="book-card-actions">
                            <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>" class="btn btn-gold btn-sm flex-grow-1">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            <button class="wishlist-btn <?= isLoggedIn() ? '' : '' ?>"
                                    onclick="toggleWishlist(<?= $book['id'] ?>, this)"
                                    title="Add to Wishlist">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="section-pad section-glass">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">How It <span class="text-gold">Works</span></h2>
            <p class="text-muted mt-2">Simple steps to buy or sell books</p>
        </div>
        <div class="row g-4 text-center">
            <?php
            $steps = [
                ['bi-person-plus','Register','Create your free account in seconds. No hidden fees.','1'],
                ['bi-search','Browse & Search','Search for books by title, author, ISBN or category.','2'],
                ['bi-cart-check','Add to Cart','Add books to cart and apply discount coupons.','3'],
                ['bi-bag-check','Place Order','Choose payment method and get books delivered.','4'],
            ];
            foreach ($steps as $s): ?>
            <div class="col-md-6 col-lg-3 animate-on-scroll">
                <div class="glass-card p-4 h-100">
                    <div class="mb-3" style="position:relative;display:inline-block;">
                        <div class="stat-icon gold mx-auto" style="width:70px;height:70px;font-size:1.8rem;">
                            <i class="bi <?= $s[0] ?>"></i>
                        </div>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-gold text-dark fw-bold"><?= $s[3] ?></span>
                    </div>
                    <h5 class="text-white fw-700 mb-2"><?= $s[1] ?></h5>
                    <p class="text-muted small mb-0"><?= $s[2] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ LATEST BOOKS ============ -->
<section class="section-pad section-darker">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="section-title">Latest <span class="text-gold">Listings</span></h2>
                <p class="text-muted mt-1">Freshly added books</p>
            </div>
            <a href="<?= SITE_URL ?>/pages/books.php?sort=latest" class="btn btn-outline-gold">See All</a>
        </div>
        <div class="row g-4">
            <?php foreach ($latestBooks as $book):
                $imgUrl = getBookImageUrl($book['any_image']);
            ?>
            <div class="col-md-6 col-lg-3 animate-on-scroll">
                <div class="book-card">
                    <div class="book-card-img-wrapper">
                        <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>">
                            <img src="<?= $imgUrl ?>" alt="<?= sanitize($book['title']) ?>" class="book-card-img" loading="lazy"
                                 onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                        </a>
                        <div class="book-card-badge"><span class="badge bg-success">New</span></div>
                    </div>
                    <div class="book-card-body">
                        <p class="book-card-author"><?= sanitize($book['author']) ?></p>
                        <h3 class="book-card-title">
                            <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>" class="text-decoration-none" style="color:inherit;">
                                <?= sanitize($book['title']) ?>
                            </a>
                        </h3>
                        <small class="text-muted"><?= sanitize($book['category_name']) ?></small>
                        <div class="book-card-actions mt-2">
                            <span class="book-card-price flex-grow-1"><?= formatPrice($book['selling_price']) ?></span>
                            <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>" class="btn btn-gold btn-sm">View</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ TESTIMONIALS ============ -->
<section class="section-pad section-dark">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title">What Students <span class="text-gold">Say</span></h2>
        </div>
        <div class="row g-4">
            <?php
            $testimonials = [
                ['Aarav Sharma','MCA Student, Mumbai','Saved over â‚¹3,000 on my semester textbooks! Great platform with genuine sellers.','5','bi-person-circle'],
                ['Priya Singh','BCA Student, Delhi','Sold 5 of my old books within a week. Very smooth experience and quick payment!','5','bi-person-circle'],
                ['Rohit Kumar','B.Tech Student, Bangalore','Found rare engineering books that weren\'t available locally. Excellent service!','4','bi-person-circle'],
            ];
            foreach ($testimonials as $t): ?>
            <div class="col-md-4 animate-on-scroll">
                <div class="testimonial-card">
                    <div class="d-flex mb-3">
                        <?php for ($i=0; $i<5; $i++): ?>
                        <i class="bi bi-star-fill <?= $i < $t[3] ? 'text-warning' : 'text-muted' ?> me-1"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-muted mb-4">"<?= $t[2] ?>"</p>
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi <?= $t[4] ?> fs-2 text-gold"></i>
                        <div>
                            <h6 class="text-white mb-0"><?= $t[0] ?></h6>
                            <small class="text-muted"><?= $t[1] ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ CTA BANNER ============ -->
<section class="section-pad" style="background:linear-gradient(135deg,#1a0a2e 0%,#0d1a3a 50%,#1a0a2e 100%);border-top:1px solid rgba(212,175,55,0.2);border-bottom:1px solid rgba(212,175,55,0.2);">
    <div class="container text-center">
        <h2 class="section-title mx-auto d-inline-block">Have Books to <span class="text-gold">Sell?</span></h2>
        <p class="text-muted mt-3 mb-4 fs-5">List your books in minutes and reach thousands of buyers across India.</p>
        <a href="<?= SITE_URL ?>/seller/add_book.php" class="btn btn-gold btn-lg px-5 py-3">
            <i class="bi bi-plus-circle me-2"></i>Start Selling Today
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

