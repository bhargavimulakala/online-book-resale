<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Please login first.','redirect'=>'/online-book-resale/auth/login.php']); exit; }
$userId = (int)$_SESSION['user_id'];
$bookId = (int)($_POST['book_id'] ?? 0);
if (!$bookId) { echo json_encode(['success'=>false,'message'=>'Invalid book.']); exit; }
$chk = $conn->prepare("SELECT id FROM wishlist WHERE user_id=? AND book_id=?");
$chk->bind_param('ii',$userId,$bookId); $chk->execute();
$exists = $chk->get_result()->fetch_assoc();
if ($exists) {
    $conn->prepare("DELETE FROM wishlist WHERE user_id=? AND book_id=?")->execute() or $conn->query("DELETE FROM wishlist WHERE user_id=$userId AND book_id=$bookId");
    $del = $conn->prepare("DELETE FROM wishlist WHERE id=?"); $del->bind_param('i',$exists['id']); $del->execute();
    echo json_encode(['success'=>true,'added'=>false,'message'=>'Removed from wishlist.']);
} else {
    $ins = $conn->prepare("INSERT INTO wishlist (user_id,book_id) VALUES (?,?)");
    $ins->bind_param('ii',$userId,$bookId); $ins->execute();
    echo json_encode(['success'=>true,'added'=>true,'message'=>'Added to wishlist!']);
}
