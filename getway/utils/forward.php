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
// function forward($url, $method = 'POST', $data = [])
// {
//     $ch = curl_init($url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

//     if ($method === 'POST') {
//         curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
//     }

//     $response = curl_exec($ch);
//     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//     // Ghi log mã HTTP trả về
//     error_log("HTTP code từ service: " . $httpCode);

//     // Ghi log lỗi nếu có
//     if ($response === false) {
//         $curlError = curl_error($ch);
//         error_log("CURL error: " . $curlError);
//         curl_close($ch);
//         return json_encode(['status' => 'error', 'message' => 'Lỗi CURL: ' . $curlError]);
//     }

//     curl_close($ch);

//     // Nếu mã HTTP là 2xx thì trả về phản hồi thật
//     if ($httpCode >= 200 && $httpCode < 300) {
//         return $response;
//     }

//     // Nếu mã HTTP là lỗi, trả về thông báo rõ ràng
//     return json_encode([
//         'status' => 'error',
//         'message' => 'Service trả về mã lỗi ' . $httpCode,
//         'raw' => $response
//     ]);
// }
