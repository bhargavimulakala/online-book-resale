<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Login required.']); exit; }
$userId = (int)$_SESSION['user_id'];
$bookId = (int)($_POST['book_id'] ?? 0);
$receiverId = (int)($_POST['receiver_id'] ?? 0);
$message = sanitize($_POST['message'] ?? '');
if (!$bookId || !$receiverId || !$message) { echo json_encode(['success'=>false,'message'=>'All fields are required.']); exit; }
if ($userId === $receiverId) { echo json_encode(['success'=>false,'message'=>'Cannot message yourself.']); exit; }
$stmt = $conn->prepare("INSERT INTO messages (book_id,sender_id,receiver_id,message) VALUES (?,?,?,?)");
$stmt->bind_param('iiis',$bookId,$userId,$receiverId,$message);
if ($stmt->execute()) { echo json_encode(['success'=>true,'message'=>'Message sent successfully!']); }
else { echo json_encode(['success'=>false,'message'=>'Failed to send message.']); }
