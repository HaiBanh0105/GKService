<?php
require __DIR__ . '/db.php';
header('Content-Type: application/json; charset=utf-8');

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

$studentId = trim($data['studentId'] ?? '');
$newStatus = trim($data['newStatus'] ?? '');

if ($studentId === '' || $newStatus === '') {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu dữ liệu']);
    exit;
}

try {
    if (strtolower($newStatus) === 'completed') {
        // Nếu trạng thái là Completed thì gạch nợ luôn
        $stmt = $tuitionPdo->prepare("UPDATE TuitionFee SET Status = :status, Amount = 0 WHERE StudentID = :sid");
    } else {
        // Các trạng thái khác chỉ cập nhật trạng thái
        $stmt = $tuitionPdo->prepare("UPDATE TuitionFee SET Status = :status WHERE StudentID = :sid");
    }

    $stmt->execute([':status' => $newStatus, ':sid' => $studentId]);

    echo json_encode(['status' => 'success']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật trạng thái']);
}
