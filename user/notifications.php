<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];

// Handle mark all read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$userId");
    header('Location: ' . SITE_URL . '/user/notifications.php'); exit;
}

$filter = sanitize($_GET['filter'] ?? 'all');
$where = "user_id=$userId";
if ($filter === 'unread') $where .= " AND is_read=0";

$notifs = $conn->query("SELECT * FROM notifications WHERE $where ORDER BY created_at DESC LIMIT 50")->fetch_all(MYSQLI_ASSOC);
$unreadCount = getUnreadNotifications($userId, $conn);

$pageTitle = 'Notifications';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5" style="max-width:800px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="text-white fw-bold mb-0">
                <i class="bi bi-bell me-2 text-gold"></i>Notifications
                <?php if ($unreadCount > 0): ?><span class="badge bg-danger ms-2"><?= $unreadCount ?></span><?php endif; ?>
            </h4>
        </div>
        <div class="d-flex gap-2">
            <a href="?filter=all" class="btn btn-sm <?= $filter==='all'?'btn-gold':'btn-outline-gold' ?>">All</a>
            <a href="?filter=unread" class="btn btn-sm <?= $filter==='unread'?'btn-gold':'btn-outline-gold' ?>">Unread</a>
            <?php if ($unreadCount > 0): ?>
            <form method="POST">
                <button type="submit" name="mark_all_read" class="btn btn-sm btn-outline-gold">
                    <i class="bi bi-check-all me-1"></i>Mark All Read
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?= renderFlash() ?>

    <?php if (empty($notifs)): ?>
    <div class="text-center py-5">
        <i class="bi bi-bell-slash" style="font-size:5rem;color:#d4af37;opacity:0.3;"></i>
        <h5 class="text-white mt-4">No notifications</h5>
        <p class="text-muted">You're all caught up!</p>
    </div>
    <?php else: ?>
    <div class="d-flex flex-column gap-2">
        <?php
        $typeIcons = ['success'=>'bi-check-circle-fill','danger'=>'bi-x-circle-fill','warning'=>'bi-exclamation-triangle-fill','info'=>'bi-info-circle-fill'];
        $typeColors = ['success'=>'#10b981','danger'=>'#ef4444','warning'=>'#f59e0b','info'=>'#3b82f6'];
        foreach ($notifs as $n):
            $icon  = $typeIcons[$n['type']] ?? 'bi-info-circle-fill';
            $color = $typeColors[$n['type']] ?? '#3b82f6';
        ?>
        <div class="glass-card p-4 d-flex align-items-start gap-3 <?= !$n['is_read'] ? 'notif-unread' : '' ?>"
             id="notif_<?= $n['id'] ?>"
             style="<?= !$n['is_read'] ? 'border-left:3px solid #d4af37;' : '' ?>">
            <div class="flex-shrink-0" style="width:42px;height:42px;background:<?= $color ?>22;border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:1.1rem;"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="text-white fw-bold mb-1"><?= sanitize($n['title']) ?></h6>
                    <small class="text-muted"><?= timeAgo($n['created_at']) ?></small>
                </div>
                <p class="text-muted small mb-0"><?= sanitize($n['message']) ?></p>
                <?php if ($n['link']): ?>
                <a href="<?= SITE_URL . sanitize($n['link']) ?>" class="btn btn-sm btn-outline-gold mt-2 py-1 px-3" onclick="markRead(<?= $n['id'] ?>)">
                    View Details â†’
                </a>
                <?php endif; ?>
            </div>
            <?php if (!$n['is_read']): ?>
            <span class="flex-shrink-0" style="width:8px;height:8px;background:#d4af37;border-radius:50%;margin-top:6px;"></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function markRead(id) {
    fetch('/online-book-resale/ajax/notification_action.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `action=mark_read&id=${id}`
    });
    const el = document.getElementById('notif_' + id);
    if (el) el.style.borderLeft = '';
    const dot = el?.querySelector('[style*="border-radius:50%"]');
    if (dot) dot.remove();
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

