<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require __DIR__ . '/db.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

$userId = (int)($data['userId'] ?? 0);
$studentId = trim($data['studentId'] ?? '');
$studentName = trim($data['studentName'] ?? '');
$amount = (float)($data['amount'] ?? 0);

if ($userId <= 0 || $studentId === '' || $studentName === '' || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu dữ liệu']);
    exit;
}

try {
    $stmt = $authPdo->prepare("INSERT INTO TransactionHistory(UserID, StudentID, StudentName, Amount) VALUES (:uid, :sid, :sname, :amt)");
    $stmt->execute([
        ':uid' => $userId,
        ':sid' => $studentId,
        ':sname' => $studentName,
        ':amt' => $amount
    ]);

    echo json_encode(['status' => 'success', 'message' => 'Đã ghi lịch sử giao dịch']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
