<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Please login to continue.','redirect'=>'/online-book-resale/auth/login.php']); exit; }
$userId = (int)$_SESSION['user_id'];
$action = sanitize($_POST['action'] ?? '');

if ($action === 'add') {
    $bookId = (int)($_POST['book_id'] ?? 0);
    $qty = max(1,(int)($_POST['qty'] ?? 1));
    if (!$bookId) { echo json_encode(['success'=>false,'message'=>'Invalid book.']); exit; }
    $bStmt = $conn->prepare("SELECT * FROM books WHERE id=? AND status='approved' AND quantity>0");
    $bStmt->bind_param('i',$bookId); $bStmt->execute();
    $book = $bStmt->get_result()->fetch_assoc();
    if (!$book) { echo json_encode(['success'=>false,'message'=>'Book not available.']); exit; }
    if ((int)$book['seller_id'] === $userId) { echo json_encode(['success'=>false,'message'=>'You cannot buy your own book.']); exit; }
    $check = $conn->prepare("SELECT id,quantity FROM cart WHERE user_id=? AND book_id=?");
    $check->bind_param('ii',$userId,$bookId); $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    if ($existing) {
        $newQty = min($existing['quantity']+$qty, $book['quantity']);
        $upd = $conn->prepare("UPDATE cart SET quantity=? WHERE id=?");
        $upd->bind_param('ii',$newQty,$existing['id']); $upd->execute();
        $msg = 'Cart updated!';
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id,book_id,quantity) VALUES (?,?,?)");
        $ins->bind_param('iii',$userId,$bookId,$qty); $ins->execute();
        $msg = 'Added to cart!';
    }
    echo json_encode(['success'=>true,'message'=>$msg,'cart_count'=>getCartCount($userId,$conn)]);
}
elseif ($action === 'remove') {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
    $stmt->bind_param('ii',$cartId,$userId); $stmt->execute();
    echo json_encode(['success'=>true,'message'=>'Removed from cart.','cart_count'=>getCartCount($userId,$conn)]);
}
elseif ($action === 'update') {
    $cartId = (int)($_POST['cart_id'] ?? 0);
    $qty = max(1,(int)($_POST['qty'] ?? 1));
    $stmt = $conn->prepare("SELECT c.id, b.selling_price, b.quantity as stock FROM cart c JOIN books b ON c.book_id=b.id WHERE c.id=? AND c.user_id=?");
    $stmt->bind_param('ii',$cartId,$userId); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) { echo json_encode(['success'=>false,'message'=>'Item not found.']); exit; }
    $newQty = min($qty, $row['stock']);
    $upd = $conn->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
    $upd->bind_param('iii',$newQty,$cartId,$userId); $upd->execute();
    $items = $conn->query("SELECT c.quantity, b.selling_price FROM cart c JOIN books b ON c.book_id=b.id WHERE c.user_id=$userId")->fetch_all(MYSQLI_ASSOC);
    $grandTotal = array_sum(array_map(fn($i)=>$i['selling_price']*$i['quantity'],$items));
    $couponDisc = $_SESSION['coupon_discount'] ?? 0;
    echo json_encode(['success'=>true,'cart_count'=>getCartCount($userId,$conn),'item_total'=>$row['selling_price']*$newQty,'grand_total'=>max(0,$grandTotal-$couponDisc)]);
}
elseif ($action === 'apply_coupon') {
    $code = strtoupper(sanitize($_POST['code'] ?? ''));
    $items = $conn->query("SELECT SUM(c.quantity * b.selling_price) as total FROM cart c JOIN books b ON c.book_id=b.id WHERE c.user_id=$userId")->fetch_assoc();
    $subtotal = (float)($items['total'] ?? 0);
    $stmt = $conn->prepare("SELECT * FROM coupons WHERE code=? AND is_active=1 AND (expires_at IS NULL OR expires_at >= CURDATE()) AND used_count < uses_limit");
    $stmt->bind_param('s',$code); $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();
    if (!$coupon) { echo json_encode(['success'=>false,'message'=>'Invalid or expired coupon code.']); exit; }
    if ($subtotal < $coupon['min_order']) { echo json_encode(['success'=>false,'message'=>'Minimum order of ' . formatPrice($coupon['min_order']) . ' required for this coupon.']); exit; }
    $discount = $coupon['discount_type']==='percent' ? ($subtotal * $coupon['discount_value'] / 100) : $coupon['discount_value'];
    if ($coupon['max_discount'] && $discount > $coupon['max_discount']) $discount = $coupon['max_discount'];
    $_SESSION['coupon_code'] = $code;
    $_SESSION['coupon_discount'] = round($discount, 2);
    echo json_encode(['success'=>true,'message'=>'Coupon applied! You save ' . formatPrice($discount),'discount'=>$discount]);
}
elseif ($action === 'remove_coupon') {
    unset($_SESSION['coupon_code'],$_SESSION['coupon_discount']);
    echo json_encode(['success'=>true,'message'=>'Coupon removed.']);
}
else { echo json_encode(['success'=>false,'message'=>'Invalid action.']); }
