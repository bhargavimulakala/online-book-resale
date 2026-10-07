<?php
// ============================================================
// Application Configuration
// ============================================================

// Prevent multiple inclusions
if (defined('SITE_NAME')) {
    return;
}

// Site Settings
define('SITE_NAME', 'BookResale');
define('SITE_URL', 'http://localhost/online-book-resale');
define('BASE_URL', '/online-book-resale');

// Currency
define('CURRENCY', '₹');

// Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('UPLOAD_URL', SITE_URL . '/assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

define('ALLOWED_IMAGE_EXTENSIONS', ['jpg','jpeg','png','gif','webp']);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg','image/png','image/gif','image/webp']);

define('DEFAULT_BOOK_IMAGE', 'default_book.png');
define('DEFAULT_USER_IMAGE', 'default_user.png');

// Pagination
define('BOOKS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);
define('USERS_PER_PAGE', 15);

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);