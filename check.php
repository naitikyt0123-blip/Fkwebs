<?php
header('Content-Type: application/json; charset=utf-8');

$uid = trim($_GET['uid'] ?? '');
$uid = preg_replace('/\D/', '', $uid);

if (empty($uid)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid UID"
    ]);
    exit;
}

$api = "https://f-fuid2-info.vercel.app/api/check?uid=" . urlencode($uid);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "User-Agent: Mozilla/5.0"
    ]
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode([
        "Success" => false,
        "message" => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

http_response_code($http ?: 200);

echo $response;
