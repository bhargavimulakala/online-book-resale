<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['success'=>false]); exit; }
$userId = (int)$_SESSION['user_id'];
$action = sanitize($_POST['action'] ?? '');
if ($action === 'mark_all_read') {
    $conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$userId");
    echo json_encode(['success'=>true]);
} elseif ($action === 'mark_read' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
    $stmt->bind_param('ii',$id,$userId); $stmt->execute();
    echo json_encode(['success'=>true]);
} else { echo json_encode(['success'=>false,'message'=>'Invalid action.']); }
