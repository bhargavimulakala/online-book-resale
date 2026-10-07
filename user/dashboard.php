<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser($conn);
if (!$user) { header('Location: ' . SITE_URL . '/auth/logout.php'); exit; }

// Stats
$ordersCount = (int)$conn->query("SELECT COUNT(*) as c FROM orders WHERE user_id=$userId")->fetch_assoc()['c'];
$booksCount  = (int)$conn->query("SELECT COUNT(*) as c FROM books WHERE seller_id=$userId")->fetch_assoc()['c'];
$wishCount   = (int)$conn->query("SELECT COUNT(*) as c FROM wishlist WHERE user_id=$userId")->fetch_assoc()['c'];
$cartCount   = getCartCount($userId, $conn);
$unread      = getUnreadNotifications($userId, $conn);

// Recent orders
$recentOrders = $conn->query("SELECT * FROM orders WHERE user_id=$userId ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// My active listings
$myListings = $conn->query("SELECT * FROM books WHERE seller_id=$userId ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Notifications
$notifs = $conn->query("SELECT * FROM notifications WHERE user_id=$userId ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'My Dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <?= renderFlash() ?>
    <div class="row g-4">
        <!-- Sidebar Nav -->
        <div class="col-lg-3">
            <div class="glass-card p-0 overflow-hidden">
                <div class="text-center p-4" style="background:linear-gradient(135deg,rgba(212,175,55,0.1),rgba(124,58,237,0.1));">
                    <img src="<?= UPLOAD_URL ?>profiles/<?= sanitize($user['profile_photo']) ?>" alt="Profile"
                         class="profile-avatar mb-3" onerror="this.src='<?= UPLOAD_URL ?>default_user.png'">
                    <h6 class="text-white fw-bold mb-0"><?= sanitize($user['name']) ?></h6>
                    <small class="text-muted"><?= sanitize($user['email']) ?></small>
                </div>
                <ul class="list-unstyled p-2 mb-0">
                    <?php $links=[['dashboard.php','bi-speedometer2','Dashboard'],['profile.php','bi-person','My Profile'],['orders.php','bi-bag-check','My Orders'],['wishlist.php','bi-heart','Wishlist'],['cart.php','bi-cart3','Cart'],['mybooks.php','bi-journal-bookmarks','My Listings']]; ?>
                    <?php foreach ($links as $l): ?>
                    <li><a href="<?= SITE_URL ?>/user/<?= $l[0] ?>" class="dropdown-item rounded-3 py-2 px-3 mb-1 <?= basename($_SERVER['PHP_SELF'])===$l[0]?'bg-gold text-dark fw-bold':'' ?>"><i class="bi <?= $l[1] ?> me-2"></i><?= $l[2] ?></a></li>
                    <?php endforeach; ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a href="<?= SITE_URL ?>/seller/add_book.php" class="dropdown-item rounded-3 py-2 px-3 mb-1 text-success"><i class="bi bi-plus-circle me-2"></i>Sell a Book</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/logout.php" class="dropdown-item rounded-3 py-2 px-3 mb-1 text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
        <!-- Content -->
        <div class="col-lg-9">
            <div class="mb-4">
                <h2 class="text-white fw-bold">Welcome back, <span class="text-gold"><?= sanitize(explode(' ',$user['name'])[0]) ?></span>! </h2>
                <p class="text-muted">Manage your books, orders, and account from here.</p>
            </div>
            <!-- Stat Cards -->
            <div class="row g-3 mb-4">
                <?php $stats=[['Orders','bi-bag-check','gold',$ordersCount],['Listed Books','bi-journal-bookmarks','purple',$booksCount],['Wishlist','bi-heart','green',$wishCount],['Cart Items','bi-cart3','red',$cartCount]]; ?>
                <?php foreach ($stats as $s): ?>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="stat-icon <?= $s[2] ?>"><i class="bi <?= $s[1] ?>"></i></div>
                        <div class="stat-number"><?= $s[3] ?></div>
                        <div class="stat-label"><?= $s[0] ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Recent Orders -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white fw-bold mb-0"><i class="bi bi-bag me-2 text-gold"></i>Recent Orders</h5>
                    <a href="<?= SITE_URL ?>/user/orders.php" class="btn btn-outline-gold btn-sm">View All</a>
                </div>
                <?php if (empty($recentOrders)): ?>
                <div class="text-center py-4"><i class="bi bi-bag-x fs-2 text-muted mb-2 d-block"></i><p class="text-muted mb-2">No orders yet</p><a href="<?= SITE_URL ?>/pages/books.php" class="btn btn-gold btn-sm">Browse Books</a></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th class="text-gold">Order #</th><th class="text-gold">Date</th><th class="text-gold">Total</th><th class="text-gold">Status</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td class="text-white fw-bold"><?= sanitize($o['order_number']) ?></td>
                                <td class="text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                <td class="text-gold fw-bold"><?= formatPrice($o['total']) ?></td>
                                <td><?= statusBadge($o['status']) ?></td>
                                <td><a href="<?= SITE_URL ?>/user/orders.php?id=<?= $o['id'] ?>" class="btn btn-outline-gold btn-sm">View</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <!-- My Listings -->
            <div class="glass-card p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="text-white fw-bold mb-0"><i class="bi bi-journal-bookmarks me-2 text-gold"></i>My Book Listings</h5>
                    <div class="d-flex gap-2"><a href="<?= SITE_URL ?>/seller/add_book.php" class="btn btn-gold btn-sm"><i class="bi bi-plus me-1"></i>Add</a><a href="<?= SITE_URL ?>/user/mybooks.php" class="btn btn-outline-gold btn-sm">View All</a></div>
                </div>
                <?php if (empty($myListings)): ?>
                <div class="text-center py-4"><i class="bi bi-book-x fs-2 text-muted mb-2 d-block"></i><p class="text-muted mb-2">No books listed yet</p><a href="<?= SITE_URL ?>/seller/add_book.php" class="btn btn-gold btn-sm">List a Book</a></div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead><tr><th class="text-gold">Title</th><th class="text-gold">Price</th><th class="text-gold">Status</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach ($myListings as $b): ?>
                            <tr>
                                <td class="text-white"><?= sanitize($b['title']) ?></td>
                                <td class="text-gold"><?= formatPrice($b['selling_price']) ?></td>
                                <td><?= statusBadge($b['status']) ?></td>
                                <td><a href="<?= SITE_URL ?>/seller/edit_book.php?id=<?= $b['id'] ?>" class="btn btn-outline-gold btn-sm">Edit</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <!-- Notifications -->
            <?php if (!empty($notifs)): ?>
            <div class="glass-card p-4">
                <h5 class="text-white fw-bold mb-3"><i class="bi bi-bell me-2 text-gold"></i>Recent Notifications</h5>
                <?php foreach ($notifs as $n): ?>
                <div class="d-flex align-items-start gap-3 p-2 mb-2 rounded-3 <?= !$n['is_read'] ? 'bg-gold-10' : '' ?>" style="<?= !$n['is_read']?'background:rgba(212,175,55,0.08);':'' ?>">
                    <i class="bi bi-bell-fill text-gold mt-1"></i>
                    <div>
                        <div class="text-white fw-600 small"><?= sanitize($n['title']) ?></div>
                        <div class="text-muted small"><?= sanitize($n['message']) ?></div>
                        <small class="text-muted"><?= timeAgo($n['created_at']) ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

