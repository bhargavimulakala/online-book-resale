<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

defined('BASE_URL') || define('BASE_URL', '/online-book-resale');

requireUserLogin();

$userId = (int)$_SESSION['user_id'];

/* Fetch all books uploaded by this seller */
$sql = "
SELECT
    b.*,
    (
        SELECT image_name
        FROM book_images bi
        WHERE bi.book_id = b.id
        LIMIT 1
    ) AS img
FROM books b
WHERE b.seller_id = ?
ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$books = $result->fetch_all(MYSQLI_ASSOC);

/* Delete Book */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {

    $bid = (int)$_POST['book_id'];

    $stmt = $conn->prepare("SELECT status FROM books WHERE id=? AND seller_id=?");
    $stmt->bind_param("ii", $bid, $userId);
    $stmt->execute();

    $book = $stmt->get_result()->fetch_assoc();

    if ($book) {

        if ($book['status'] != 'sold') {

            // Delete images first
            $stmt = $conn->prepare("DELETE FROM book_images WHERE book_id=?");
            $stmt->bind_param("i", $bid);
            $stmt->execute();

            // Delete book
            $stmt = $conn->prepare("DELETE FROM books WHERE id=? AND seller_id=?");
            $stmt->bind_param("ii", $bid, $userId);
            $stmt->execute();

            setFlash('success', 'Book deleted successfully.');

        } else {

            setFlash('error', 'Sold books cannot be deleted.');

        }

    } else {

        setFlash('error', 'Book not found.');

    }

    header("Location: " . SITE_URL . "/user/mybooks.php");
    exit;
}

$pageTitle = "My Listings";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h3 class="text-white">
            <i class="bi bi-book me-2 text-warning"></i>
            My Book Listings
        </h3>

        <a href="<?= SITE_URL ?>/seller/add_book.php" class="btn btn-warning">
            <i class="bi bi-plus-circle"></i>
            Add Book
        </a>

    </div>

    <?= renderFlash(); ?>

    <?php if(empty($books)): ?>

        <div class="text-center mt-5">

            <i class="bi bi-journal-x text-warning" style="font-size:70px;"></i>

            <h4 class="text-white mt-3">
                You haven't listed any books yet.
            </h4>

            <a href="<?= SITE_URL ?>/seller/add_book.php" class="btn btn-warning mt-3">
                List Your First Book
            </a>

        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table table-dark table-hover align-middle">

                <thead>

                <tr>

                    <th>Book</th>
                    <th>Price</th>
                    <th>Condition</th>
                    <th>Qty</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>

                </tr>

                </thead>

                <tbody>

                <?php foreach($books as $book): ?>

                    <?php
                    $image = getBookImageUrl($book['img']);
                    ?>

                    <tr>

                        <td>

                            <div class="d-flex align-items-center">

                                <img src="<?= $image ?>"
                                     width="60"
                                     height="75"
                                     class="rounded me-3"
                                     style="object-fit:cover;">

                                <div>

                                    <strong><?= sanitize($book['title']) ?></strong>

                                    <br>

                                    <small class="text-secondary">

                                        <?= sanitize($book['author']) ?>

                                    </small>

                                </div>

                            </div>

                        </td>

                        <td><?= formatPrice($book['selling_price']) ?></td>

                        <td><?= conditionBadge($book['condition_type']) ?></td>

                        <td><?= $book['quantity'] ?></td>

                        <td><?= statusBadge($book['status']) ?></td>

                        <td><?= date("d M Y", strtotime($book['created_at'])) ?></td>

                        <td>

                            <a href="<?= SITE_URL ?>/pages/book_details.php?id=<?= $book['id'] ?>"
                               class="btn btn-info btn-sm">

                                <i class="bi bi-eye"></i>

                            </a>

                            <?php if($book['status'] != 'sold'): ?>

                                <a href="<?= SITE_URL ?>/seller/edit_book.php?id=<?= $book['id'] ?>"
                                   class="btn btn-warning btn-sm">

                                    <i class="bi bi-pencil"></i>

                                </a>

                                <form method="POST"
                                      style="display:inline;"
                                      onsubmit="return confirm('Delete this book?');">

                                    <input type="hidden" name="delete_book" value="1">

                                    <input type="hidden"
                                           name="book_id"
                                           value="<?= $book['id'] ?>">

                                    <button class="btn btn-danger btn-sm">

                                        <i class="bi bi-trash"></i>

                                    </button>

                                </form>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>