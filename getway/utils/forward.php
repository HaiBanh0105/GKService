<?php
function forward($url, $method = 'POST', $data = [])
{
    // Nếu là GET, gắn dữ liệu vào query string
    if ($method === 'GET' && !empty($data)) {
        $query = http_build_query($data);
        $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    // Nếu là POST hoặc PUT, gửi dữ liệu JSON
    if (in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    if ($response === false) {
        error_log("Lỗi cURL: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log("HTTP code từ service: " . $httpCode);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return trim($response);
    }

    // return json_encode(['status' => 'error', 'message' => 'Không có phản hồi từ service']);
    // Nếu có phản hồi JSON, vẫn trả về để frontend xử lý
    return $response ?: json_encode([
        'status' => 'error',
        'message' => 'Không có phản hồi từ service'
    ]);
}
