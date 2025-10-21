<?php
$host = "localhost";
$dbname = "user";
$username = "root";
$password = "";

try {
    $authPdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $authPdo->exec("SET time_zone = '+07:00'");
    $authPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Auth DB connect failed']);
    exit;
}
