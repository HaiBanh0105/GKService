<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'Method not allowed']); exit; }

require __DIR__ . '/db.php';
require __DIR__ . '/../common/mailer.php';

$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);
if (!is_array($data)) { echo json_encode(['status'=>'error','message'=>'Payload JSON không hợp lệ']); exit; }

$paymentId = (int)($data['paymentId'] ?? 0);
$code = trim($data['otp'] ?? '');

if ($paymentId <= 0 || $code === '') {
    echo json_encode(['status'=>'error','message'=>'Thiếu dữ liệu đầu vào']);
    exit;
}

try {
    // Verify OTP
    $stmt = $paymentPdo->prepare("SELECT O.OtpID, O.IsUsed, O.ExpiredAt, P.UserID, P.StudentID, P.Amount FROM OTPs O JOIN Payment P ON P.PaymentID = O.PaymentID WHERE O.PaymentID = :pid AND O.Code = :code LIMIT 1");
    $stmt->execute([':pid'=>$paymentId, ':code'=>$code]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo json_encode(['status'=>'error','message'=>'OTP không hợp lệ']); exit; }
    if ((int)$row['IsUsed'] === 1) { echo json_encode(['status'=>'error','message'=>'OTP đã được sử dụng']); exit; }
    if (!is_null($row['ExpiredAt']) && strtotime($row['ExpiredAt']) < time()) { echo json_encode(['status'=>'error','message'=>'OTP đã hết hạn']); exit; }

    $userId = (int)$row['UserID'];
    $studentId = $row['StudentID'];
    $amount = (float)$row['Amount'];

    // Open other DBs
    $userPdo = new PDO('mysql:host=localhost;dbname=user;charset=utf8', 'root', '');
    $userPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tuitionPdo = new PDO('mysql:host=localhost;dbname=TuitionFee;charset=utf8', 'root', '');
    $tuitionPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Begin distributed operations (best-effort, each within its DB tx)
    $paymentPdo->beginTransaction();

    // Mark OTP used
    $updOtp = $paymentPdo->prepare("UPDATE OTPs SET IsUsed = 1 WHERE OtpID = :id");
    $updOtp->execute([':id'=>(int)$row['OtpID']]);

    // Deduct balance and insert transaction history
    $userPdo->beginTransaction();
    $balanceStmt = $userPdo->prepare("SELECT UserID, Username, FullName, Phone, Email, AvailableBalance FROM User WHERE UserID = :uid FOR UPDATE");
    $balanceStmt->execute([':uid'=>$userId]);
    $balRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);
    if (!$balRow) { throw new Exception('User not found'); }
    $currentBal = (float)$balRow['AvailableBalance'];
    if ($currentBal < $amount) { throw new Exception('Số dư không đủ'); }
    $newBal = $currentBal - $amount;
    $updBal = $userPdo->prepare("UPDATE User SET AvailableBalance = :b WHERE UserID = :uid");
    $updBal->execute([':b'=>$newBal, ':uid'=>$userId]);

    // Get tuition info for history
    $tStmt = $tuitionPdo->prepare("SELECT StudentName FROM TuitionFee WHERE StudentID = :sid");
    $tStmt->execute([':sid'=>$studentId]);
    $tInfo = $tStmt->fetch(PDO::FETCH_ASSOC);

    $insHist = $userPdo->prepare("INSERT INTO TransactionHistory(UserID, StudentID, StudentName, Amount) VALUES (:uid,:sid,:sname,:amt)");
    $insHist->execute([':uid'=>$userId, ':sid'=>$studentId, ':sname'=>($tInfo['StudentName'] ?? ''), ':amt'=>$amount]);
    $userPdo->commit();

    // Mark tuition as Completed
    $tuitionPdo->beginTransaction();
    $updTu = $tuitionPdo->prepare("UPDATE TuitionFee SET Status = 'Completed' WHERE StudentID = :sid");
    $updTu->execute([':sid'=>$studentId]);
    $tuitionPdo->commit();

    $paymentPdo->commit();

    // Send success email
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

    echo json_encode(['status'=>'success','message'=>'Xác nhận giao dịch thành công','user'=>$updatedUser]);
} catch (Throwable $e) {
    if ($paymentPdo->inTransaction()) { $paymentPdo->rollBack(); }
    if (isset($userPdo) && $userPdo->inTransaction()) { $userPdo->rollBack(); }
    if (isset($tuitionPdo) && $tuitionPdo->inTransaction()) { $tuitionPdo->rollBack(); }
    http_response_code(500);
    echo json_encode(['status'=>'error','message'=>$e->getMessage() ?: 'Lỗi server']);
}
