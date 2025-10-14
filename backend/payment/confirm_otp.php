<?php
date_default_timezone_set('Asia/Ho_Chi_Minh');

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
require __DIR__ . '/../common/PHPmailer.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);
if (!is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Payload JSON không hợp lệ']);
    exit;
}

$paymentId = (int)($data['paymentId'] ?? 0);
$code = trim($data['otp'] ?? '');

if ($paymentId <= 0 || $code === '') {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu dữ liệu đầu vào']);
    exit;
}

try {
    // lấy thông tin OTP
    $stmt = $paymentPdo->prepare("SELECT O.OtpID, O.IsUsed, O.ExpiredAt, P.UserID, P.StudentID, P.Amount FROM OTPs O JOIN Payment P ON P.PaymentID = O.PaymentID WHERE O.PaymentID = :pid AND O.Code = :code LIMIT 1");
    $stmt->execute([':pid' => $paymentId, ':code' => $code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        echo json_encode(['status' => 'error', 'message' => 'OTP không hợp lệ']);
        exit;
    }
    if ((int)$row['IsUsed'] === 1) {
        echo json_encode(['status' => 'error', 'message' => 'OTP đã được sử dụng']);
        exit;
    }
    if (!is_null($row['ExpiredAt']) && strtotime($row['ExpiredAt']) < time()) {
        echo json_encode(['status' => 'error', 'message' => 'OTP đã hết hạn, vui lòng tạo OTP mới']);
        exit;
    }

    $userId = (int)$row['UserID'];
    $studentId = $row['StudentID'];
    $amount = (float)$row['Amount'];


    // Bắt đầu giao dịch
    $paymentPdo->beginTransaction();

    // Đánh dấu OTP là đã sử dụng
    $updOtp = $paymentPdo->prepare("UPDATE OTPs SET IsUsed = 1 WHERE OtpID = :id");
    $updOtp->execute([':id' => (int)$row['OtpID']]);


    // lấy thông tin người dùng từ service user
    $infoUrl = "http://localhost/GKService/getway/auth/get_user_info?userId=" . urlencode($userId);
    $ch = curl_init($infoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $userInfo = json_decode($response, true);
    if (!is_array($userInfo) || $userInfo['status'] !== 'success') {
        throw new Exception('Không lấy được thông tin người dùng');
    }
    $balRow = $userInfo['user'];

    $deductUrl = "http://localhost/GKService/getway/auth/deduct_balance";
    $payload = json_encode(['userId' => $userId, 'amount' => $amount]);

    $ch = curl_init($deductUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    if (!is_array($result) || $result['status'] !== 'success') {
        throw new Exception($result['message'] ?? 'Không trừ được số dư');
    }

    $newBal = (float)$result['newBalance'];


    // lấy thông tin sinh viên từ service học phí
    $infoUrl = "http://localhost/GKService/getway/tuition/get?studentId=" . urlencode($studentId);
    $ch = curl_init($infoUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $tInfo = json_decode($response, true);
    if (!is_array($tInfo) || $tInfo['status'] !== 'success') {
        throw new Exception('Không lấy được thông tin sinh viên');
    }
    $studentName = $tInfo['tuition']['StudentName'] ?? '';


    // // thêm lịch sử giao dịch
    $logUrl = "http://localhost/GKService/getway/auth/add_transactions";
    $logPayload = json_encode([
        'userId' => $userId,
        'studentId' => $studentId,
        'studentName' => $studentName,
        'amount' => $amount
    ]);

    $ch = curl_init($logUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $logPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $logResponse = curl_exec($ch);
    curl_close($ch);

    $logResult = json_decode($logResponse, true);
    if (!is_array($logResult) || $logResult['status'] !== 'success') {
        error_log("Không ghi được lịch sử giao dịch: " . ($logResult['message'] ?? ''));
    }


    // Cập nhật trạng thái học phí sang "Completed"
    $updateUrl = "http://localhost/GKService/getway/tuition/update_status";
    $updatePayload = json_encode([
        'studentId' => $studentId,
        'newStatus' => 'Completed'
    ]);

    $ch = curl_init($updateUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $updatePayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $updateResponse = curl_exec($ch);
    curl_close($ch);

    $updateResult = json_decode($updateResponse, true);
    if (!is_array($updateResult) || $updateResult['status'] !== 'success') {
        echo json_encode(['status' => 'error', 'message' => 'Không cập nhật được trạng thái học phí']);
        exit;
    }

    $paymentPdo->commit();

    // Gửi email xác nhận thành công
    $subject = 'Xác nhận giao dịch học phí thành công';
    $body = '<p>Giao dịch thanh toán học phí đã hoàn tất.</p>' .
        '<p>MSSV: <strong>' . htmlspecialchars($studentId) . '</strong><br/>' .
        'Sinh viên: <strong>' . htmlspecialchars($tInfo['StudentName'] ?? '') . '</strong><br/>' .
        'Số tiền: <strong>' . number_format($amount, 0, ',', '.') . " VND</strong></p>" .
        '<p>Số dư mới: <strong>' . number_format($newBal, 0, ',', '.') . ' VND</strong></p>';
    if (!empty($balRow['Email'])) {
        sendEmail($balRow['Email'], $subject, $body);
    }

    $updatedUser = [
        'id' => (int)$balRow['UserID'],
        'username' => $balRow['Username'],
        'fullname' => $balRow['FullName'],
        'phone' => $balRow['Phone'],
        'email' => $balRow['Email'],
        'balance' => (float)$newBal
    ];

    echo json_encode(['status' => 'success', 'message' => 'Xác nhận giao dịch thành công', 'user' => $updatedUser]);
} catch (Throwable $e) {
    if ($paymentPdo->inTransaction()) {
        $paymentPdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage() ?: 'Lỗi server']);
}
