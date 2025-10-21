<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit; }

require __DIR__ . '/db.php';

$userId = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;
if ($userId <= 0) { echo json_encode(['status'=>'error','message'=>'Thiếu userId']); exit; }

try {
    $stmt = $authPdo->prepare("SELECT TransactionID, StudentID, StudentName, Amount, CreatedAt FROM TransactionHistory WHERE UserID = :uid ORDER BY CreatedAt DESC LIMIT 100");
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'success','transactions'=>$rows]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Lỗi server']);
}
