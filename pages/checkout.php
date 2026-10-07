<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$userId = (int)$_SESSION['user_id'];
$user = getCurrentUser($conn);

// Buy Now mode
$buyNowId = (int)($_GET['buy_now'] ?? 0);
$cartItems = [];
if ($buyNowId) {
    $bStmt = $conn->prepare("SELECT b.*,(SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as img FROM books b WHERE b.id=? AND b.status='approved' AND b.quantity>0");
    $bStmt->bind_param('i',$buyNowId); $bStmt->execute();
    $bk = $bStmt->get_result()->fetch_assoc();
    if ($bk) $cartItems = [['id'=>0,'book_id'=>$bk['id'],'title'=>$bk['title'],'author'=>$bk['author'],'selling_price'=>$bk['selling_price'],'quantity'=>1,'img'=>$bk['img'],'stock'=>$bk['quantity'],'condition_type'=>$bk['condition_type']]];
} else {
    $cartItems = $conn->query("SELECT c.*, b.title, b.author, b.selling_price, b.quantity as stock, b.condition_type, (SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as img FROM cart c JOIN books b ON c.book_id=b.id WHERE c.user_id=$userId AND b.status='approved' AND b.quantity>0")->fetch_all(MYSQLI_ASSOC);
}
if (empty($cartItems)) { setFlash('error','Your cart is empty.'); header('Location: ' . SITE_URL . '/pages/books.php'); exit; }

$subtotal = array_sum(array_map(fn($i)=>$i['selling_price']*$i['quantity'],$cartItems));
$couponDiscount = !$buyNowId ? ($_SESSION['coupon_discount'] ?? 0) : 0;
$couponCode = !$buyNowId ? ($_SESSION['coupon_code'] ?? '') : '';
$total = max(0, $subtotal - $couponDiscount);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipName  = sanitize($_POST['shipping_name'] ?? '');
    $shipEmail = sanitize($_POST['shipping_email'] ?? '');
    $shipPhone = sanitize($_POST['shipping_phone'] ?? '');
    $shipAddr  = sanitize($_POST['shipping_address'] ?? '');
    $shipCity  = sanitize($_POST['shipping_city'] ?? '');
    $shipState = sanitize($_POST['shipping_state'] ?? '');
    $shipPin   = sanitize($_POST['shipping_pincode'] ?? '');
    $delivery  = sanitize($_POST['delivery_instructions'] ?? '');
    $payMethod = sanitize($_POST['payment_method'] ?? 'cod');

    if (!$shipName||!$shipEmail||!$shipPhone||!$shipAddr||!$shipCity||!$shipState||!$shipPin) {
        $error = 'Please fill all shipping details.';
    } else {
        $orderNum = generateOrderNumber();
        $stmt = $conn->prepare("INSERT INTO orders (order_number,user_id,shipping_name,shipping_email,shipping_phone,shipping_address,shipping_city,shipping_state,shipping_pincode,delivery_instructions,subtotal,discount,coupon_code,total,payment_method) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sissssssssddsds',$orderNum,$userId,$shipName,$shipEmail,$shipPhone,$shipAddr,$shipCity,$shipState,$shipPin,$delivery,$subtotal,$couponDiscount,$couponCode,$total,$payMethod);
        if ($stmt->execute()) {
            $orderId = $conn->insert_id;
            foreach ($cartItems as $item) {
                $ois = $conn->prepare("INSERT INTO order_items (order_id,book_id,quantity,price) VALUES (?,?,?,?)");
                $ois->bind_param('iiid',$orderId,$item['book_id'],$item['quantity'],$item['selling_price']); $ois->execute();
                $conn->query("UPDATE books SET quantity=quantity-{$item['quantity']} WHERE id={$item['book_id']}");
                $conn->query("UPDATE books SET status='sold' WHERE id={$item['book_id']} AND quantity<=0");
            }
            // Payment
            $txnId = 'TXN' . strtoupper(uniqid());
            $payStatus = ($payMethod === 'cod') ? 'pending' : 'success';
            $pStmt = $conn->prepare("INSERT INTO payments (order_id,user_id,amount,payment_method,transaction_id,status) VALUES (?,?,?,?,?,?)");
            $pStmt->bind_param('iidsss',$orderId,$userId,$total,$payMethod,$txnId,$payStatus); $pStmt->execute();
            // Clear cart
            if (!$buyNowId) {
                $conn->query("DELETE FROM cart WHERE user_id=$userId");
                unset($_SESSION['coupon_code'],$_SESSION['coupon_discount']);
            }
            // Notification
            $conn->query("INSERT INTO notifications (user_id,title,message,type,link) VALUES ($userId,'Order Placed!','Your order $orderNum has been placed successfully.','success','/online-book-resale/user/orders.php?id=$orderId')");
            // Coupon usage count
            if ($couponCode) $conn->query("UPDATE coupons SET used_count=used_count+1 WHERE code='" . $conn->real_escape_string($couponCode) . "'");
            header('Location: ' . SITE_URL . '/pages/order_confirmation.php?id=' . $orderId); exit;
        } else { $error = 'Order placement failed. Please try again.'; }
    }
}

$pageTitle = 'Checkout';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
    <h4 class="text-white fw-bold mb-4"><i class="bi bi-bag-check me-2 text-gold"></i>Checkout</h4>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <form method="POST" class="needs-validation" novalidate>
        <div class="row g-4">
            <div class="col-lg-7">
                <!-- Shipping -->
                <div class="checkout-step">
                    <h5 class="text-white fw-bold mb-4"><i class="bi bi-geo-alt me-2 text-gold"></i>Shipping Address</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" name="shipping_name" class="form-control" required value="<?= sanitize($user['name'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="shipping_email" class="form-control" required value="<?= sanitize($user['email'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Phone *</label><input type="tel" name="shipping_phone" class="form-control" required value="<?= sanitize($user['phone'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">City *</label><input type="text" name="shipping_city" class="form-control" required value="<?= sanitize($user['city'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label">Full Address *</label><textarea name="shipping_address" class="form-control" rows="2" required><?= sanitize($user['address'] ?? '') ?></textarea></div>
                        <div class="col-md-6"><label class="form-label">State *</label><input type="text" name="shipping_state" class="form-control" required value="<?= sanitize($user['state'] ?? '') ?>"></div>
                        <div class="col-md-6"><label class="form-label">Pincode *</label><input type="text" name="shipping_pincode" class="form-control" pattern="[0-9]{6}" required value="<?= sanitize($user['pincode'] ?? '') ?>"></div>
                        <div class="col-12"><label class="form-label">Delivery Instructions (optional)</label><input type="text" name="delivery_instructions" class="form-control" placeholder="e.g. Leave at door, call before delivery"></div>
                    </div>
                </div>
                <!-- Payment -->
                <div class="checkout-step">
                    <h5 class="text-white fw-bold mb-4"><i class="bi bi-credit-card me-2 text-gold"></i>Payment Method</h5>
                    <div class="row g-3">
                        <?php $methods=[['cod','bi-cash-coin','Cash on Delivery','Pay when your books arrive'],['upi','bi-phone','UPI Payment','Google Pay, PhonePe, Paytm'],['credit_card','bi-credit-card','Credit Card','Visa, Mastercard (Demo)'],['debit_card','bi-credit-card-2-front','Debit Card','All major banks (Demo)']]; ?>
                        <?php foreach ($methods as $i=>[$val,$icon,$label,$sub]): ?>
                        <div class="col-md-6">
                            <label class="payment-method-card w-100 <?= $i===0?'selected':'' ?>" onclick="selectPayment(this,'<?= $val ?>')">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bi <?= $icon ?> fs-3 text-gold"></i>
                                    <div>
                                        <div class="text-white fw-bold"><?= $label ?></div>
                                        <small class="text-muted"><?= $sub ?></small>
                                    </div>
                                    <input type="radio" name="payment_method" value="<?= $val ?>" class="ms-auto" <?= $i===0?'checked':'' ?>>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <!-- UPI details (hidden by default) -->
                    <div id="upiDetails" class="mt-3 d-none">
                        <div class="glass-card p-3">
                            <label class="form-label">UPI ID</label>
                            <input type="text" class="form-control" placeholder="yourname@upi" id="upiInput">
                            <small class="text-muted">Enter your UPI ID to simulate payment</small>
                        </div>
                    </div>
                    <!-- Card details (hidden) -->
                    <div id="cardDetails" class="mt-3 d-none">
                        <div class="glass-card p-3">
                            <div class="alert alert-info py-2"><i class="bi bi-info-circle me-2"></i>Demo mode - no real payment processed</div>
                            <div class="row g-2">
                                <div class="col-12"><input type="text" class="form-control" placeholder="Card Number: 4111 1111 1111 1111" maxlength="19"></div>
                                <div class="col-6"><input type="text" class="form-control" placeholder="MM/YY"></div>
                                <div class="col-6"><input type="text" class="form-control" placeholder="CVV"></div>
                                <div class="col-12"><input type="text" class="form-control" placeholder="Cardholder Name"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Order Summary -->
            <div class="col-lg-5">
                <div class="glass-card p-4 position-sticky" style="top:90px;">
                    <h5 class="text-white fw-bold mb-4"><i class="bi bi-receipt me-2 text-gold"></i>Order Summary</h5>
                    <?php foreach ($cartItems as $item):
                        $imgUrl = getBookImageUrl($item['img']); ?>
                    <div class="d-flex gap-3 mb-3 align-items-center">
                        <img src="<?= $imgUrl ?>" style="width:50px;height:60px;object-fit:cover;border-radius:6px;" onerror="this.src='<?= UPLOAD_URL ?>default_book.png'">
                        <div class="flex-grow-1">
                            <div class="text-white small fw-bold"><?= sanitize($item['title']) ?></div>
                            <small class="text-muted">Qty: <?= $item['quantity'] ?></small>
                        </div>
                        <span class="text-gold fw-bold"><?= formatPrice($item['selling_price']*$item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <hr class="divider">
                    <div class="d-flex justify-content-between text-muted mb-2"><span>Subtotal</span><span><?= formatPrice($subtotal) ?></span></div>
                    <div class="d-flex justify-content-between text-success mb-2"><span>Delivery</span><span>FREE</span></div>
                    <?php if ($couponDiscount > 0): ?><div class="d-flex justify-content-between text-success mb-2"><span>Coupon (<?= sanitize($couponCode) ?>)</span><span>-<?= formatPrice($couponDiscount) ?></span></div><?php endif; ?>
                    <hr class="divider">
                    <div class="d-flex justify-content-between mb-4"><span class="text-white fw-bold fs-5">Total</span><span class="text-gold fw-bold fs-5"><?= formatPrice($total) ?></span></div>
                    <button type="submit" class="btn btn-gold w-100 py-3 fw-bold fs-6"><i class="bi bi-bag-check me-2"></i>Place Order (<?= formatPrice($total) ?>)</button>
                    <p class="text-muted small text-center mt-2 mb-0"><i class="bi bi-shield-check me-1 text-gold"></i>Safe & Secure Checkout</p>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
function selectPayment(card, value) {
    document.querySelectorAll('.payment-method-card').forEach(c=>c.classList.remove('selected'));
    card.classList.add('selected');
    document.querySelectorAll('[name="payment_method"]').forEach(r=>r.checked = r.value===value);
    document.getElementById('upiDetails').classList.toggle('d-none', value!=='upi');
    document.getElementById('cardDetails').classList.toggle('d-none', !['credit_card','debit_card'].includes(value));
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>

