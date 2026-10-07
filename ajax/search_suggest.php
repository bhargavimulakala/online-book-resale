<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
$q = sanitize($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['results'=>[]]); exit; }
$lq = '%' . $q . '%';
$stmt = $conn->prepare("SELECT id, title, author FROM books WHERE (title LIKE ? OR author LIKE ?) AND status='approved' LIMIT 8");
$stmt->bind_param('ss',$lq,$lq); $stmt->execute();
$results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode(['results'=>$results]);
