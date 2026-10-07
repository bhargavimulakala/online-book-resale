<?php
/**
 * order_action.php — AJAX endpoint for order workflow actions.
 *
 * Handles two actions:
 *   1. seller_update_status — Seller advances order through Confirmed → Packed → Shipped
 *   2. buyer_received       — Buyer marks a Shipped order as Delivered
 *
 * Security:
 *   - seller_update_status: verifies books.seller_id = session user via SQL JOIN
 *   - buyer_received:        verifies orders.user_id  = session user
 *   Neither action can be triggered for orders the requester does not own.
 */
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

// ---- Helper ----
function jsonOut(array $data): never {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---- Must be a logged-in user (not admin) ----
if (!isLoggedIn()) {
    jsonOut(['success' => false, 'message' => 'Please login to continue.', 'redirect' => BASE_URL . '/auth/login.php']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['success' => false, 'message' => 'Invalid request method.']);
}

$userId  = (int)$_SESSION['user_id'];
$action  = trim($_POST['action'] ?? '');
$orderId = (int)($_POST['order_id'] ?? 0);

if (!$orderId) {
    jsonOut(['success' => false, 'message' => 'Invalid order ID.']);
}

// ============================================================
// ACTION 1: Seller advances order status
// ============================================================
if ($action === 'seller_update_status') {

    $newStatus = trim($_POST['new_status'] ?? '');

    // Only these three transitions are ever allowed from the seller side
    $allowedTransitions = [
        'pending'   => 'confirmed',
        'confirmed' => 'packed',
        'packed'    => 'shipped',
    ];

    // ---- Security: fetch order with seller ownership check ----
    // The JOIN ensures the order contains at least one book belonging to this seller.
    $stmt = $conn->prepare("
        SELECT o.id, o.status, o.order_number, o.user_id AS buyer_id
        FROM orders o
        JOIN order_items oi ON oi.order_id = o.id
        JOIN books b        ON b.id = oi.book_id
        WHERE o.id = ? AND b.seller_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        jsonOut(['success' => false, 'message' => 'Order not found or access denied.']);
    }

    $currentStatus = $order['status'];

    // ---- Validate the transition is legal ----
    if (!isset($allowedTransitions[$currentStatus])) {
        jsonOut(['success' => false, 'message' => 'This order cannot be updated further by the seller.']);
    }

    $expectedNext = $allowedTransitions[$currentStatus];
    if ($newStatus !== $expectedNext) {
        jsonOut(['success' => false, 'message' => "Invalid transition. Expected: $expectedNext."]);
    }

    // ---- Perform the update ----
    $upd = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $upd->bind_param('si', $newStatus, $orderId);
    if (!$upd->execute()) {
        jsonOut(['success' => false, 'message' => 'Database error. Please try again.']);
    }

    // ---- Notify the buyer ----
    $notifMessages = [
        'confirmed' => [
            'title'   => 'Order Confirmed ✓',
            'message' => "Your order #{$order['order_number']} has been confirmed by the seller and is being prepared.",
            'type'    => 'success',
        ],
        'packed' => [
            'title'   => 'Order Packed 📦',
            'message' => "Your order #{$order['order_number']} has been packed and is ready to ship.",
            'type'    => 'info',
        ],
        'shipped' => [
            'title'   => 'Order Shipped 🚚',
            'message' => "Your order #{$order['order_number']} is on the way! You can mark it as received once it arrives.",
            'type'    => 'success',
        ],
    ];

    if (isset($notifMessages[$newStatus])) {
        $n = $notifMessages[$newStatus];
        addNotification(
            $order['buyer_id'],
            $n['title'],
            $n['message'],
            $n['type'],
            BASE_URL . '/user/orders.php?id=' . $orderId
        );
    }

    jsonOut([
        'success'    => true,
        'message'    => 'Order status updated to ' . ucfirst($newStatus) . '.',
        'new_status' => $newStatus,
    ]);
}

// ============================================================
// ACTION 2: Buyer marks order as Delivered (Mark as Received)
// ============================================================
if ($action === 'buyer_received') {

    // ---- Security: buyer must own the order ----
    $stmt = $conn->prepare("
        SELECT id, status, order_number, user_id
        FROM orders
        WHERE id = ? AND user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        jsonOut(['success' => false, 'message' => 'Order not found or access denied.']);
    }

    if ($order['status'] !== 'shipped') {
        jsonOut(['success' => false, 'message' => 'Only shipped orders can be marked as received.']);
    }

    // ---- Mark as delivered ----
    $delivered = 'delivered';
    $upd = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $upd->bind_param('si', $delivered, $orderId);
    if (!$upd->execute()) {
        jsonOut(['success' => false, 'message' => 'Database error. Please try again.']);
    }

    // ---- Notify the buyer (confirmation) ----
    addNotification(
        $userId,
        'Order Delivered ✓',
        "Your order #{$order['order_number']} has been delivered. Thank you for shopping with BookResale!",
        'success',
        BASE_URL . '/user/orders.php?id=' . $orderId
    );

    // ---- Notify the seller(s) whose books were in this order ----
    $sellerStmt = $conn->prepare("
        SELECT DISTINCT b.seller_id
        FROM order_items oi
        JOIN books b ON b.id = oi.book_id
        WHERE oi.order_id = ?
    ");
    $sellerStmt->bind_param('i', $orderId);
    $sellerStmt->execute();
    $sellers = $sellerStmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($sellers as $seller) {
        addNotification(
            (int)$seller['seller_id'],
            'Order Delivered 🎉',
            "Order #{$order['order_number']} has been marked as received by the buyer. Payment will be processed shortly.",
            'success',
            BASE_URL . '/user/seller_orders.php?id=' . $orderId
        );
    }

    jsonOut([
        'success'    => true,
        'message'    => 'Order marked as received. Thank you!',
        'new_status' => 'delivered',
    ]);
}

// ---- Unknown action ----
jsonOut(['success' => false, 'message' => 'Unknown action.']);
