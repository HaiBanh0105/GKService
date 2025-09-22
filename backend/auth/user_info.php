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

$userId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$username = isset($_GET['username']) ? trim($_GET['username']) : null;

if (!$userId && !$username) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu tham số id hoặc username']);
    exit;
}

try {
    if ($userId) {
        $stmt = $authPdo->prepare("SELECT UserID, Username, FullName, Phone, Email, AvailableBalance FROM `User` WHERE UserID = :id LIMIT 1");
        $stmt->execute([':id' => $userId]);
    } else {
        $stmt = $authPdo->prepare("SELECT UserID, Username, FullName, Phone, Email, AvailableBalance FROM `User` WHERE Username = :u LIMIT 1");
        $stmt->execute([':u' => $username]);
    }
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy user']);
        exit;
    }

    $safeUser = [
        'id' => (int)$user['UserID'],
        'username' => $user['Username'],
        'fullname' => $user['FullName'],
        'phone' => $user['Phone'],
        'email' => $user['Email'],
        'balance' => (float)$user['AvailableBalance']
    ];

    echo json_encode(['status' => 'success', 'user' => $safeUser]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi server']);
}
