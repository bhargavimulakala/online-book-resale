<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
defined('BASE_URL') || define('BASE_URL', '/online-book-resale');
requireUserLogin();
$orderId = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$stmt->bind_param('ii',$orderId,$userId); $stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
if (!$order) { header('Location: ' . SITE_URL . '/user/orders.php'); exit; }
$ois = $conn->prepare("SELECT oi.*,b.title,b.author,b.isbn,b.publisher,(SELECT image_name FROM book_images WHERE book_id=b.id LIMIT 1) as img FROM order_items oi JOIN books b ON oi.book_id=b.id WHERE oi.order_id=?");
$ois->bind_param('i',$orderId); $ois->execute();
$items = $ois->get_result()->fetch_all(MYSQLI_ASSOC);
$user = getCurrentUser($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><title>Invoice - <?= sanitize($order['order_number']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{font-family:'Segoe UI',sans-serif;background:#f0f0f0;padding:20px;}
.invoice-wrapper{background:white;max-width:800px;margin:0 auto;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);}
.invoice-header{display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid #d4af37;padding-bottom:20px;margin-bottom:24px;}
.brand{font-family:Georgia,serif;font-size:2rem;font-weight:800;color:#0f0a1e;}
.brand span{color:#d4af37;}
.invoice-title{text-align:right;}
.invoice-title h2{font-size:1.8rem;color:#0f0a1e;margin:0;}
.invoice-title p{color:#666;margin:4px 0 0;}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;}
.info-box{background:#f8f9ff;padding:16px;border-radius:8px;border-left:4px solid #d4af37;}
.info-box h6{color:#d4af37;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;}
.info-box p{margin:3px 0;color:#333;font-size:0.9rem;}
table{width:100%;border-collapse:collapse;margin-bottom:24px;}
th{background:#0f0a1e;color:white;padding:12px 16px;text-align:left;font-size:0.85rem;}
td{padding:12px 16px;border-bottom:1px solid #eee;font-size:0.9rem;}
.total-box{margin-left:auto;width:280px;}
.total-row{display:flex;justify-content:space-between;padding:6px 0;color:#666;}
.total-row.final{border-top:2px solid #d4af37;padding-top:12px;font-weight:800;font-size:1.1rem;color:#0f0a1e;}
.badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:0.75rem;font-weight:600;}
.badge-success{background:#d1fae5;color:#065f46;}
.badge-warning{background:#fef3c7;color:#92400e;}
.footer{text-align:center;color:#999;font-size:0.8rem;border-top:1px solid #eee;padding-top:20px;margin-top:20px;}
@media print{body{background:white;padding:0;}.no-print{display:none!important;}.invoice-wrapper{box-shadow:none;padding:20px;}}
</style>
</head>
<body>
<div class="no-print" style="text-align:center;margin-bottom:20px;">
    <button onclick="window.print()" style="background:#d4af37;color:#0f0a1e;border:none;padding:10px 30px;border-radius:8px;font-weight:700;cursor:pointer;font-size:1rem;"><span>ðŸ–¨</span> Print / Download PDF</button>
    <a href="<?= SITE_URL ?>/user/orders.php?id=<?= $orderId ?>" style="display:inline-block;margin-left:10px;color:#0f0a1e;text-decoration:none;padding:10px 20px;border:2px solid #d4af37;border-radius:8px;font-weight:700;">â† Back to Order</a>
</div>
<div class="invoice-wrapper">
    <div class="invoice-header">
        <div class="brand"><span>ðŸ“š</span> <span>Book</span>Resale</div>
        <div class="invoice-title">
            <h2>INVOICE</h2>
            <p><?= sanitize($order['order_number']) ?></p>
            <p><?= date('d M Y', strtotime($order['created_at'])) ?></p>
        </div>
    </div>
    <div class="info-grid">
        <div class="info-box">
            <h6>Bill To</h6>
            <p><strong><?= sanitize($order['shipping_name']) ?></strong></p>
            <p><?= sanitize($order['shipping_email']) ?></p>
            <p><?= sanitize($order['shipping_phone']) ?></p>
            <p><?= sanitize($order['shipping_address']) ?></p>
            <p><?= sanitize($order['shipping_city']) ?>, <?= sanitize($order['shipping_state']) ?> - <?= sanitize($order['shipping_pincode']) ?></p>
        </div>
        <div class="info-box">
            <h6>Invoice Details</h6>
            <p><strong>Order:</strong> <?= sanitize($order['order_number']) ?></p>
            <p><strong>Date:</strong> <?= date('d M Y', strtotime($order['created_at'])) ?></p>
            <p><strong>Payment:</strong> <?= strtoupper($order['payment_method']) ?></p>
            <p><strong>Status:</strong> <span class="badge badge-<?= $order['status']==='delivered'?'success':'warning' ?>"><?= ucfirst($order['status']) ?></span></p>
        </div>
    </div>
    <table>
        <thead><tr><th>#</th><th>Book</th><th>Author</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr></thead>
        <tbody>
            <?php foreach ($items as $i=>$item): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><strong><?= sanitize($item['title']) ?></strong><?php if($item['isbn']): ?><br><small style="color:#999;">ISBN: <?= sanitize($item['isbn']) ?></small><?php endif; ?></td>
                <td><?= sanitize($item['author']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>â‚¹<?= number_format($item['price'],2) ?></td>
                <td><strong>â‚¹<?= number_format($item['price']*$item['quantity'],2) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="total-box">
        <div class="total-row"><span>Subtotal</span><span>â‚¹<?= number_format($order['subtotal'],2) ?></span></div>
        <?php if ($order['discount']>0): ?><div class="total-row" style="color:#10b981;"><span>Discount (<?= sanitize($order['coupon_code']) ?>)</span><span>-â‚¹<?= number_format($order['discount'],2) ?></span></div><?php endif; ?>
        <div class="total-row"><span>Delivery</span><span>FREE</span></div>
        <div class="total-row final"><span>Grand Total</span><span>â‚¹<?= number_format($order['total'],2) ?></span></div>
    </div>
    <?php if ($order['delivery_instructions']): ?><p style="color:#666;font-size:0.85rem;"><strong>Delivery Note:</strong> <?= sanitize($order['delivery_instructions']) ?></p><?php endif; ?>
    <div class="footer">
        <p><strong>BookResale</strong> â€” India's Trusted Book Marketplace</p>
        <p>ðŸ“§ admin@bookresale.com | ðŸ“ž +91 98765 43210</p>
        <p>Thank you for your purchase! This is a computer-generated invoice and does not require a signature.</p>
    </div>
</div>
</body>
</html>

