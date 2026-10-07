<?php
// ============================================================
// Global Helper Functions
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Sanitize input
function sanitize(string $data): string {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Format price
function formatPrice(float $amount): string {
    return CURRENCY . number_format($amount, 2);
}

// Generate unique order number
function generateOrderNumber(): string {
    return 'ORD-' . strtoupper(uniqid()) . '-' . date('Ymd');
}

// Generate random token
function generateToken(int $length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

// Slugify string
function slugify(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^\w\s-]/', '', $text);
    $text = preg_replace('/[\s_-]+/', '-', $text);
    return trim($text, '-');
}

// Upload image
function uploadImage(array $file, string $folder = 'books'): ?string {
    $uploadPath = UPLOAD_DIR . $folder . '/';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    if ($file['size'] > MAX_FILE_SIZE) return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_IMAGE_EXTENSIONS)) return null;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_IMAGE_TYPES)) return null;
    $newName = uniqid('img_', true) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadPath . $newName)) {
        return $folder . '/' . $newName;
    }
    return null;
}

// Get paginated data with total count
function paginate(int $total, int $perPage, int $page): array {
    $totalPages = max(1, ceil($total / $perPage));
    $currentPage = max(1, min($page, $totalPages));
    $offset = ($currentPage - 1) * $perPage;
    return ['total' => $total, 'per_page' => $perPage, 'current' => $currentPage,
            'total_pages' => $totalPages, 'offset' => $offset];
}

// Render pagination HTML
function renderPagination(array $pag, string $baseUrl): string {
    if ($pag['total_pages'] <= 1) return '';
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    for ($i = 1; $i <= $pag['total_pages']; $i++) {
        $active = $i === $pag['current'] ? ' active' : '';
        $html .= "<li class='page-item{$active}'><a class='page-link' href='{$baseUrl}&page={$i}'>{$i}</a></li>";
    }
    $html .= '</ul></nav>';
    return $html;
}

// Get full URL for a book image
function getBookImageUrl(?string $imageName): string {
    if (empty($imageName)) {
        return UPLOAD_URL . DEFAULT_BOOK_IMAGE;
    }
    if (preg_match('/^https?:\/\//i', $imageName)) {
        return $imageName;
    }
    if (strpos($imageName, 'books/') === 0 || strpos($imageName, 'profiles/') === 0) {
        return UPLOAD_URL . $imageName;
    }
    if (file_exists(UPLOAD_DIR . $imageName)) {
        return UPLOAD_URL . $imageName;
    }
    if (file_exists(UPLOAD_DIR . 'books/' . $imageName)) {
        return UPLOAD_URL . 'books/' . $imageName;
    }
    return UPLOAD_URL . 'books/' . ltrim($imageName, '/');
}

// Get book primary image
function getBookImage(int $bookId, mysqli $conn): string {
    $stmt = $conn->prepare("SELECT image_name FROM book_images WHERE book_id=? AND is_primary=1 LIMIT 1");
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    if ($r) return getBookImageUrl($r['image_name']);
    // fallback: any image
    $stmt2 = $conn->prepare("SELECT image_name FROM book_images WHERE book_id=? LIMIT 1");
    $stmt2->bind_param('i', $bookId);
    $stmt2->execute();
    $r2 = $stmt2->get_result()->fetch_assoc();
    return $r2 ? getBookImageUrl($r2['image_name']) : getBookImageUrl(null);
}

// Get average rating for a book
function getAvgRating(int $bookId, mysqli $conn): array {
    $stmt = $conn->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM reviews WHERE book_id=?");
    $stmt->bind_param('i', $bookId);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    return ['avg' => round($r['avg_r'] ?? 0, 1), 'count' => (int)($r['cnt'] ?? 0)];
}

// Add notification
function addNotification(int $userId, string $title, string $message, string $type='info', string $link='', mysqli $conn = null): void {
    global $conn;
    $db = $conn;
    $stmt = $db->prepare("INSERT INTO notifications (user_id,title,message,type,link) VALUES (?,?,?,?,?)");
    $stmt->bind_param('issss', $userId, $title, $message, $type, $link);
    $stmt->execute();
}

// Get unread notification count for user
function getUnreadNotifications(int $userId, mysqli $conn): int {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id=? AND is_read=0");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['cnt'];
}

// Get cart count
function getCartCount(int $userId, mysqli $conn): int {
    $stmt = $conn->prepare("SELECT COALESCE(SUM(quantity),0) as cnt FROM cart WHERE user_id=?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    return (int)$stmt->get_result()->fetch_assoc()['cnt'];
}

// Render star rating HTML
function renderStars(float $rating): string {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) $html .= '<i class="bi bi-star-fill text-warning"></i>';
        elseif ($i - 0.5 <= $rating) $html .= '<i class="bi bi-star-half text-warning"></i>';
        else $html .= '<i class="bi bi-star text-muted"></i>';
    }
    return $html;
}

// Time ago
function timeAgo(string $datetime): string {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . ' mins ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    return date('d M Y', $time);
}

// Condition badge
function conditionBadge(string $cond): string {
    $map = [
        'New'       => 'bg-success',
        'Like New'  => 'bg-info',
        'Good'      => 'bg-primary',
        'Acceptable'=> 'bg-warning text-dark',
        'Old'       => 'bg-secondary',
    ];
    $cls = $map[$cond] ?? 'bg-secondary';
    return "<span class='badge {$cls}'>{$cond}</span>";
}

// Status badge
function statusBadge(string $status): string {
    $map = [
        'pending'   => 'bg-warning text-dark',
        'approved'  => 'bg-success',
        'rejected'  => 'bg-danger',
        'sold'      => 'bg-secondary',
        'confirmed' => 'bg-info',
        'packed'    => 'bg-primary',
        'shipped'   => 'bg-purple',
        'delivered' => 'bg-success',
        'cancelled' => 'bg-danger',
    ];
    $cls = $map[$status] ?? 'bg-secondary';
    return "<span class='badge {$cls}'>" . ucfirst($status) . "</span>";
}

// Flash message
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function renderFlash(): string {
    $f = getFlash();
    if (!$f) return '';
    $map = ['success'=>'success','error'=>'danger','info'=>'info','warning'=>'warning'];
    $cls = $map[$f['type']] ?? 'info';
    return "<div class='alert alert-{$cls} alert-dismissible fade show' role='alert'>
        " . sanitize($f['msg']) . "
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
}
