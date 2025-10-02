<?php
$host = "localhost";
$dbname = "Payment";
$username = "root";
$password = "";

try {
    $paymentPdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $paymentPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Payment DB connect failed']);
    exit;
}
