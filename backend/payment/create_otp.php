<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit; }

require __DIR__ . '/db.php';
require __DIR__ . '/../common/PHPmailer.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);
if (!is_array($data)) { echo json_encode(['status'=>'error','message'=>'Payload JSON không hợp lệ']); exit; }

$userId = (int)($data['userId'] ?? 0);
$studentId = trim($data['studentId'] ?? '');
$amount = (float)($data['amount'] ?? 0);
$userEmail = trim($data['userEmail'] ?? '');

if ($userId <= 0 || $studentId === '' || $amount <= 0 || $userEmail === '') {
    echo json_encode(['status'=>'error','message'=>'Thiếu dữ liệu đầu vào']);
    exit;
}

try {
    // Kết nối đến DB học phí để kiểm tra trạng thái học phí
    $tuitionPdo = new PDO('mysql:host=localhost;dbname=TuitionFee;charset=utf8', 'root', '');
    $tuitionPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Kiểm tra trạng thái học phí
    $checkStmt = $tuitionPdo->prepare("SELECT Status FROM TuitionFee WHERE StudentID = :sid LIMIT 1");
    $checkStmt->execute([':sid' => $studentId]);
    $tuitionRow = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if (!$tuitionRow) {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy học phí']);
        exit;
    }
    if ($tuitionRow['Status'] !== 'Unpaid') {
        echo json_encode(['status' => 'error', 'message' => 'Chỉ cho phép tạo OTP khi học phí ở trạng thái Chưa nộp']);
        exit;
    }

    // Tạo bản ghi thanh toán và OTP
    $paymentPdo->beginTransaction();
    $stmt = $paymentPdo->prepare("INSERT INTO Payment(UserID, StudentID, Amount) VALUES (:uid, :sid, :amt)");
    $stmt->execute([':uid'=>$userId, ':sid'=>$studentId, ':amt'=>$amount]);
    $paymentId = (int)$paymentPdo->lastInsertId();

    $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $stmt2 = $paymentPdo->prepare("INSERT INTO OTPs(PaymentID, Code, ExpiredAt) VALUES (:pid, :code, DATE_ADD(NOW(), INTERVAL 5 MINUTE))");
    $stmt2->execute([':pid'=>$paymentId, ':code'=>$otp]);

    $paymentPdo->commit();

    // Cập nhật trạng thái học phí sang Processing
    $tuitionPdo->beginTransaction();
    $upd = $tuitionPdo->prepare("UPDATE TuitionFee SET Status = 'Processing' WHERE StudentID = :sid");
    $upd->execute([':sid' => $studentId]);
    $tuitionPdo->commit();

    // Gửi email OTP
    $subject = 'Mã OTP xác nhận thanh toán học phí';
    $body = '<p>Mã OTP của bạn là: <strong>' . htmlspecialchars($otp) . '</strong></p><p>OTP có hiệu lực trong 5 phút.</p>';
    sendEmail($userEmail, $subject, $body);

    echo json_encode(['status'=>'success','paymentId'=>$paymentId]);
} catch (Throwable $e) {
    if ($paymentPdo->inTransaction()) { $paymentPdo->rollBack(); }
    if (isset($tuitionPdo) && $tuitionPdo->inTransaction()) { $tuitionPdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>'Lỗi server']);
}
