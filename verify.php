<?php
// verify.php
error_reporting(0); // Faltu PHP warnings ko chup rakhne ke liye
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

// Oxylabs API Payload
$params = array(
    'source' => 'universal_ecommerce',
    'url' => $targetUrl,
    'parse' => true,
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://realtime.oxylabs.io/v1/queries");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 120); // 120 seconds timeout kyunki Oxylabs thoda time le sakta hai bypass karne me
curl_setopt($ch, CURLOPT_USERPWD, "spadexnaitik_k77Ay:Naitikff+123");

$headers = array("Content-Type: application/json");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Network error check
if ($result === false) {
    echo json_encode(["success" => false, "error" => "Network error: " . $curlError]);
    exit;
}

// Oxylabs error check
if ($httpCode !== 200) {
    echo json_encode([
        "success" => false,
        "error" => "Oxylabs API block ho gayi ya fail hui. Status Code: " . $httpCode,
        "details" => json_decode($result, true)
    ]);
    exit;
}

// Result Decode aur Clean Data Mapping
$oxylabsData = json_decode($result, true);

if (isset($oxylabsData['results'][0]['content'])) {
    $content = $oxylabsData['results'][0]['content'];
    
    $title = $content['title'] ?? "Title not found";
    $price = isset($content['price']) ? ($content['currency'] ?? "") . " " . $content['price'] : "Price not found";
    $image = (isset($content['images']) && count($content['images']) > 0) ? $content['images'][0] : "";
    
    // Exact format jo tumhare index.php ko chahiye
    echo json_encode([
        "success" => true,
        "data" => [
            "title" => trim($title),
            "price" => trim($price),
            "image" => $image
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Oxylabs ne request accept kar li, par Flipkart ka data parse nahi kar paya."
    ]);
}
exit;
?>
