<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/functions.php';
$cartCount = 0;
$notifCount = 0;
if (isLoggedIn()) {
    $cartCount = getCartCount((int)$_SESSION['user_id'], $conn);
    $notifCount = getUnreadNotifications((int)$_SESSION['user_id'], $conn);
}
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top site-navbar" id="mainNavbar">
    <div class="container">
        <a class="navbar-brand" href="<?= SITE_URL ?>">
            <i class="bi bi-book-half"></i>
            <span class="brand-text"><?= SITE_NAME ?></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Search Form -->
            <form class="d-flex mx-auto nav-search" action="<?= SITE_URL ?>/pages/books.php" method="GET">
                <div class="input-group">
                    <input class="form-control nav-search-input" type="search" name="q"
                        placeholder="Search books, authors, ISBN..."
                        value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>"
                        id="navSearchInput" autocomplete="off">
                    <button class="btn btn-gold" type="submit"><i class="bi bi-search"></i></button>
                </div>
                <div class="search-suggestions" id="searchSuggestions"></div>
            </form>
            <ul class="navbar-nav ms-auto align-items-center gap-1">
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/pages/books.php">
                        <i class="bi bi-grid"></i> <span>Books</span>
                    </a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= SITE_URL ?>/user/cart.php">
                        <i class="bi bi-cart3"></i> <span>Cart</span>
                        <?php if ($cartCount > 0): ?>
                        <span class="badge bg-gold position-absolute top-0 start-100 translate-middle rounded-pill"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($notifCount > 0): ?>
                        <span class="badge bg-danger position-absolute top-0 start-100 translate-middle rounded-pill"><?= $notifCount ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end notif-dropdown">
                        <h6 class="dropdown-header">Notifications</h6>
                        <?php
                        $ns = $conn->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
                        $ns->bind_param('i', $_SESSION['user_id']);
                        $ns->execute();
                        $notifs = $ns->get_result()->fetch_all(MYSQLI_ASSOC);
                        if ($notifs): foreach ($notifs as $n): ?>
                        <a class="dropdown-item <?= $n['is_read'] ? '' : 'unread' ?>" href="<?= $n['link'] ?: '#' ?>">
                            <small class="d-block fw-semibold"><?= sanitize($n['title']) ?></small>
                            <small class="text-muted"><?= sanitize($n['message']) ?></small>
                        </a>
                        <?php endforeach; else: ?>
                        <p class="dropdown-item text-muted small">No notifications yet.</p>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center small" href="<?= SITE_URL ?>/user/dashboard.php">View All</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" data-bs-toggle="dropdown">
                        <img src="<?= UPLOAD_URL . DEFAULT_USER_IMAGE ?>" alt="User" class="nav-avatar rounded-circle">
                        <span><?= sanitize(explode(' ', $_SESSION['user_name'])[0]) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/user/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/user/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/user/orders.php"><i class="bi bi-bag me-2"></i>My Orders</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/seller/add_book.php"><i class="bi bi-plus-circle me-2"></i>Sell a Book</a></li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/user/wishlist.php"><i class="bi bi-heart me-2"></i>Wishlist</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/auth/login.php"><i class="bi bi-person"></i> Login</a>
                </li>
                <li class="nav-item">
                    <a class="btn btn-gold ms-2" href="<?= SITE_URL ?>/auth/register.php">Register</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
