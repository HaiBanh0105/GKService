<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require __DIR__ . '/db.php';

$userId = (int)($_GET['userId'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu hoặc sai userId']);
    exit;
}

try {
    $stmt = $authPdo->prepare("SELECT UserID, Username, FullName, Phone, Email, AvailableBalance FROM User WHERE UserID = :uid");
    $stmt->execute([':uid' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng']);
        exit;
    }

    echo json_encode(['status' => 'success', 'user' => $row]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
