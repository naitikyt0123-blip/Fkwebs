<?php
header('Content-Type: application/json');

$uid = preg_replace('/[^0-9]/', '', $_GET['uid'] ?? '');
if (strlen($uid) < 5) {
    echo json_encode(["success" => false, "message" => "Invalid UID"]);
    exit;
}

$api = "https://ffuid2info.up.railway.app/api/check?uid=" . urlencode($uid);

if (function_exists('curl_init')) {
    $ch = curl_init($api);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($response === false || $httpCode >= 400) {
        echo json_encode(["success" => false, "message" => "API unreachable"]);
        exit;
    }
    echo $response;
} else {
    $ctx = stream_context_create([
        'http' => ['timeout' => 20, 'header' => "User-Agent: Mozilla/5.0\r\n"],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    $response = @file_get_contents($api, false, $ctx);
    if ($response === false) {
        echo json_encode(["success" => false, "message" => "API unreachable"]);
        exit;
    }
    echo $response;
}
?>
