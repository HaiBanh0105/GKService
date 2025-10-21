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

// Đọc dữ liệu JSON
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

$userId = (int)($data['userId'] ?? 0);
$amount = (float)($data['amount'] ?? 0);

if ($userId <= 0 || $amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu dữ liệu']);
    exit;
}

try {
    $authPdo->beginTransaction();

    // Khóa dòng và kiểm tra số dư
    $stmt = $authPdo->prepare("SELECT AvailableBalance FROM User WHERE UserID = :uid FOR UPDATE");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        throw new Exception('Không tìm thấy người dùng');
    }

    $currentBal = (float)$row['AvailableBalance'];
    if ($currentBal < $amount) {
        throw new Exception('Số dư không đủ');
    }

    // Trừ tiền
    $newBal = $currentBal - $amount;
    $upd = $authPdo->prepare("UPDATE User SET AvailableBalance = :b WHERE UserID = :uid");
    $upd->execute([':b' => $newBal, ':uid' => $userId]);

    $authPdo->commit();

    echo json_encode([
        'status' => 'success',
        'newBalance' => $newBal
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi server']);
    // error_log("Lỗi nội bộ deduct_balance: " . $e->getMessage());
    // if (isset($authPdo) && $authPdo->inTransaction()) {
    //     $authPdo->rollBack();
    // }
    // http_response_code(500);
    // echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
