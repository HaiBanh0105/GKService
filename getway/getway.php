<?php
$config = require __DIR__ . '/getway-config.php';
require_once __DIR__ . '/utils/forward.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Phân tích URI để lấy route sau /getway/
$uri = $_SERVER['REQUEST_URI'];
$base = '/GKService/getway/';
$route = substr($uri, strpos($uri, $base) + strlen($base));
$route = '/' . trim($route, '/');


$method = $_SERVER['REQUEST_METHOD'];
$body = file_get_contents('php://input');

// Kiểm tra route có tồn tại trong config
if (!isset($config['routes'][$route])) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Route không tồn tại']);
    exit;
}

$serviceInfo = $config['routes'][$route];
$serviceName = $serviceInfo['service'];
$dir = $serviceInfo['dir'] ?? $serviceName;

// Nếu bạn đang chạy XAMPP trên port 8080 và thư mục gốc là GKService
$targetUrl = "http://localhost:8080/GKService/backend/$dir/{$serviceInfo['path']}";

// Gửi request đến service đích
$response = forward($targetUrl, $method, json_decode($body, true));
echo $response;
