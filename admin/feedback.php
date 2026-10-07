<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireAdminLogin();

$reviews = $conn->query("
    SELECT r.*, b.title as book_title, u.name as reviewer_name, u.email as reviewer_email
    FROM reviews r
    JOIN books b ON r.book_id = b.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 100
")->fetch_all(MYSQLI_ASSOC);

// Handle delete review via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    $rid = (int)$_POST['review_id'];
    $conn->query("DELETE FROM reviews WHERE id=$rid");
    setFlash('success', 'Review deleted.');
    header('Location: ' . SITE_URL . '/admin/feedback.php'); exit;
}

$avgRating = $conn->query("SELECT AVG(rating) as avg_r, COUNT(*) as total FROM reviews")->fetch_assoc();
$ratingDist = $conn->query("SELECT rating, COUNT(*) as cnt FROM reviews GROUP BY rating ORDER BY rating DESC")->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Reviews & Feedback';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="admin-wrapper">
<?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
<div class="admin-content">
    <div class="admin-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm" id="sidebarToggle" style="color:#d4af37;"><i class="bi bi-list fs-4"></i></button>
            <h6 class="text-white mb-0 fw-bold">Reviews & Feedback</h6>
        </div>
        <a href="<?= SITE_URL ?>/admin/logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
    <div class="admin-page-content">
        <div class="admin-page-header">
            <h2>Reviews & Ratings</h2>
            <p>Monitor all user reviews across book listings.</p>
        </div>
        <?= renderFlash() ?>

        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="bi bi-star-fill"></i></div>
                    <div class="stat-number"><?= number_format((float)($avgRating['avg_r'] ?? 0), 1) ?></div>
                    <div class="stat-label">Average Rating</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="bi bi-chat-square-text"></i></div>
                    <div class="stat-number"><?= (int)($avgRating['total'] ?? 0) ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="glass-card p-3 h-100">
                    <h6 class="text-gold mb-3 small">Rating Distribution</h6>
                    <?php for ($s = 5; $s >= 1; $s--):
                        $cnt = 0;
                        foreach ($ratingDist as $rd) { if ((int)$rd['rating'] === $s) { $cnt = (int)$rd['cnt']; break; } }
                        $pct = $avgRating['total'] > 0 ? round($cnt / $avgRating['total'] * 100) : 0;
                    ?>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <small class="text-muted" style="width:20px;"><?= $s ?>â˜…</small>
                        <div class="progress flex-grow-1" style="height:8px;">
                            <div class="progress-bar" style="width:<?= $pct ?>%;background:#d4af37;"></div>
                        </div>
                        <small class="text-muted" style="width:30px;"><?= $cnt ?></small>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>

        <!-- Reviews Table -->
        <div class="admin-table">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th>Reviewer</th>
                    <th>Book</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($reviews as $rev): ?>
                    <tr id="revRow_<?= $rev['id'] ?>">
                        <td>
                            <div class="text-white fw-bold small"><?= sanitize($rev['reviewer_name']) ?></div>
                            <small class="text-muted"><?= sanitize($rev['reviewer_email']) ?></small>
                        </td>
                        <td>
                            <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $rev['book_id'] ?>" target="_blank" class="text-gold text-decoration-none small">
                                <?= sanitize(substr($rev['book_title'], 0, 40)) . (strlen($rev['book_title']) > 40 ? '...' : '') ?>
                            </a>
                        </td>
                        <td><?= renderStars($rev['rating']) ?></td>
                        <td class="text-muted small" style="max-width:250px;">
                            <?= $rev['review_text'] ? sanitize(substr($rev['review_text'], 0, 100)) . (strlen($rev['review_text']) > 100 ? '...' : '') : '<em>No text</em>' ?>
                        </td>
                        <td class="text-muted small"><?= timeAgo($rev['created_at']) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this review?')">
                                <input type="hidden" name="delete_review" value="1">
                                <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($reviews)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No reviews yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/admin.js"></script>
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

