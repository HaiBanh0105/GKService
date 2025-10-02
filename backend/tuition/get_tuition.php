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

$studentId = isset($_GET['studentId']) ? trim($_GET['studentId']) : '';
if ($studentId === '') {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã số sinh viên']);
    exit;
}

try {
    //lấy học phí theo mã số sinh viên
    $stmt = $tuitionPdo->prepare("SELECT StudentID, StudentName, Amount, DueDate, Status FROM TuitionFee WHERE StudentID = :sid LIMIT 1");
    $stmt->execute([':sid' => $studentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy học phí']);
        exit;
    }
    echo json_encode(['status' => 'success', 'tuition' => $row]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi server']);
}
