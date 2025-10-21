<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/phpmailer-master/src/Exception.php';
require __DIR__ . '/phpmailer-master/src/PHPMailer.php';
require __DIR__ . '/phpmailer-master/src/SMTP.php';

function sendEmail($to, $subject, $bodyHtml)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';   // Gmail SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tonlegiabao@gmail.com';   // Gmail của bạn
        $mail->Password   = 'itdf agvx ehmc rtup';     // App Password đã tạo
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet = "UTF-8";
        $mail->Encoding = "base64";

        // Recipients
        $mail->setFrom('tonlegiabao@gmail.com', 'Ibanking');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
