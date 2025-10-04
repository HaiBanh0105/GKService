<?php
function forward($url, $method = 'POST', $data = [])
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    // curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));



    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    error_log("HTTP code từ service: " . $httpCode);

    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        return $response;
    }
    
    return json_encode(['status' => 'error', 'message' => 'Không có phản hồi từ service']);
}


