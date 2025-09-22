<?php
$host = "localhost";
$dbname = "TuitionFee";
$username = "root";
$password = "";

try {
    $tuitionPdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $tuitionPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Tuition DB connect failed']);
    exit;
}
