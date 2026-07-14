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
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_USERPWD, "spadexnaitik_k77Ay:Naitikff+123");

$headers = array("Content-Type: application/json");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($result === false) {
    echo json_encode(["success" => false, "error" => "Network error."]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode([
        "success" => false,
        "error" => "Oxylabs API status code: " . $httpCode
    ]);
    exit;
}

$oxylabsData = json_decode($result, true);

if (isset($oxylabsData['results'][0]['content'])) {
    $content = $oxylabsData['results'][0]['content'];
    
    $title = $content['title'] ?? "Title not found";
    $currency = $content['currency'] ?? "₹";
    
    // 1. Selling Price (Offer Price)
    $price = isset($content['price']) ? $currency . " " . $content['price'] : "Price not found";
    
    // 2. Original Price (MRP) Extraction
    $originalPrice = "MRP not available";
    if (isset($content['mrp'])) {
        $originalPrice = $currency . " " . $content['mrp'];
    } elseif (isset($content['original_price'])) {
        $originalPrice = $currency . " " . $content['original_price'];
    }
    
    // 3. Image Extraction with multiple fallback keys
    $image = "";
    if (isset($content['images']) && is_array($content['images']) && count($content['images']) > 0) {
        $image = $content['images'][0];
    } elseif (isset($content['image'])) {
        $image = is_array($content['image']) ? $content['image'][0] : $content['image'];
    } elseif (isset($content['main_image'])) {
        $image = $content['main_image'];
    }
    
    echo json_encode([
        "success" => true,
        "data" => [
            "title" => trim($title),
            "price" => trim($price),
            "original_price" => trim($originalPrice),
            "image" => $image
        ]
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Parsing failed."]);
}
exit;
?>
