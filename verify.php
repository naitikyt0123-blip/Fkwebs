<?php
// verify.php
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "error" => "Invalid request method."]);
    exit;
}

$input = file_get_contents('php://input');
$requestData = json_decode($input, true);
$targetUrl = $requestData['url'] ?? '';

if (empty($targetUrl)) {
    echo json_encode(["success" => false, "error" => "Product URL is missing."]);
    exit;
}

// 1. Extract the Flipkart Product ID (PID) directly from the URL
$pid = "";
if (preg_match('/pid=([a-zA-Z0-9]+)/', $targetUrl, $matches)) {
    $pid = $matches[1];
}

if (empty($pid)) {
    echo json_encode(["success" => false, "error" => "Could not find the Product ID (pid) in the Flipkart URL."]);
    exit;
}

// 2. Hit Buyhatke's Hidden API directly using the extracted PID
$buyhatkeApiUrl = "https://buyhatke.com/api/productData?pos=2&pid=" . $pid;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $buyhatkeApiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
// Add headers so Buyhatke thinks we are a normal browser
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    'Accept: application/json',
    'Referer: https://buyhatke.com/',
    'Origin: https://buyhatke.com'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    echo json_encode(["success" => false, "error" => "Failed to connect to Buyhatke API. Status: " . $httpCode]);
    exit;
}

// 3. Parse Buyhatke's JSON response
$data = json_decode($response, true);

// Note: Because I cannot see the EXACT structure of Buyhatke's JSON from the screenshot, 
// I am dumping the raw data to the frontend if it succeeds, so we can map it perfectly next.
// For now, let's try to guess their standard keys or send the raw array to inspect.

if ($data) {
    echo json_encode([
        "success" => true,
        "buyhatke_raw_data" => $data // Sending the raw data to see where the images are hidden!
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Buyhatke returned empty or invalid JSON."]);
}
exit;
?>
