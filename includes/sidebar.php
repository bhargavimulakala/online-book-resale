<?php // Admin Sidebar ?>
<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <a href="<?= SITE_URL ?>/admin/dashboard.php">
            <i class="bi bi-book-half"></i>
            <span><?= SITE_NAME ?> Admin</span>
        </a>
    </div>
    <nav class="sidebar-nav">
        <ul class="list-unstyled">
            <li class="nav-label">Main</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            </li>
            <li class="nav-label">Management</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/users.php"><i class="bi bi-people"></i> Users</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'books.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/books.php"><i class="bi bi-journal-bookmarks"></i> Books</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'categories.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/categories.php"><i class="bi bi-tags"></i> Categories</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/orders.php"><i class="bi bi-bag-check"></i> Orders</a>
            </li>
            <li class="nav-label">Finance</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'payments.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/payments.php"><i class="bi bi-credit-card"></i> Payments</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/reports.php"><i class="bi bi-bar-chart-line"></i> Reports</a>
            </li>
            <li class="nav-label">Communication</li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/messages.php"><i class="bi bi-envelope"></i> Contact Messages</a>
            </li>
            <li class="<?= basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : '' ?>">
                <a href="<?= SITE_URL ?>/admin/feedback.php"><i class="bi bi-star"></i> Reviews & Ratings</a>
            </li>
        </ul>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm w-100">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
