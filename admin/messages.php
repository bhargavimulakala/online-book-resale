<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireAdminLogin();

$messages = $conn->query("SELECT m.*, b.title as book_title, s.name as sender_name, r.name as receiver_name FROM messages m JOIN books b ON m.book_id=b.id JOIN users s ON m.sender_id=s.id JOIN users r ON m.receiver_id=r.id ORDER BY m.created_at DESC LIMIT 100")->fetch_all(MYSQLI_ASSOC);
$pageTitle = 'Messages';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3"><button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button><h6 class="text-white mb-0 fw-bold">Messages</h6></div>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
    <div class="admin-page-content">
        <div class="admin-page-header"><h2>User Messages</h2><p>View buyer-seller communication. <?= count($messages) ?> messages total.</p></div>
        <div class="admin-table">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>From</th><th>To</th><th>Book</th><th>Message</th><th>Date</th></tr></thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                    <tr>
                        <td><div class="text-white fw-bold small"><?= sanitize($m['sender_name']) ?></div></td>
                        <td><div class="text-white small"><?= sanitize($m['receiver_name']) ?></div></td>
                        <td><span class="badge" style="background:rgba(212,175,55,0.15);color:#d4af37;border:1px solid rgba(212,175,55,0.3);"><?= sanitize($m['book_title']) ?></span></td>
                        <td class="text-muted" style="max-width:300px;"><?= sanitize(substr($m['message'],0,100)) . (strlen($m['message'])>100?'...':'') ?></td>
                        <td class="text-muted small"><?= timeAgo($m['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($messages)): ?><tr><td colspan="5" class="text-center text-muted py-4">No messages yet.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

