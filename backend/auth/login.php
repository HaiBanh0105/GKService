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
if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Payload không hợp lệ (JSON)']);
    exit;
}

$username = trim($data['username'] ?? '');
$password = (string)($data['password'] ?? '');
if ($username === '' || $password === '') {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập đủ thông tin']);
    exit;
}

try {
    $stmt = $authPdo->prepare("SELECT UserID, Username, Password, FullName, Phone, Email, AvailableBalance FROM `User` WHERE Username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Tên đăng nhập không tồn tại']);
        exit;
    }
    if (!hash_equals((string)$user['Password'], $password)) {
        echo json_encode(['status' => 'error', 'message' => 'Mật khẩu không chính xác']);
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
