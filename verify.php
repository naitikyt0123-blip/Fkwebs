<?php
// verify.php
error_reporting(0); // PHP warnings ko screen par aane se roko
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

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
    'Accept-Language: en-US,en;q=0.5'
]);

$htmlResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$htmlResponse) {
    echo json_encode(["success" => false, "error" => "Network error ya server unreachable hai."]);
    exit;
}

// Agar HTML me Captcha ya Block page aaya hai, toh usko handle karo
if (stripos($htmlResponse, 'captcha') !== false || $httpCode == 403 || $httpCode == 503) {
    echo json_encode([
        "success" => false, 
        "error" => "Flipkart ne block kar diya! Status Code: " . $httpCode . "\nServer proxy ya API ki zaroorat hai."
    ]);
    exit;
}

libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($htmlResponse);
libxml_clear_errors();
$xpath = new DOMXPath($dom);

$titleNode = $xpath->query('//span[contains(@class, "VU-ZEz")]');
$title = $titleNode->length > 0 ? $titleNode->item(0)->nodeValue : "Title not found";

$priceNode = $xpath->query('//div[contains(@class, "Nx9bqj")]');
$price = $priceNode->length > 0 ? $priceNode->item(0)->nodeValue : "Price not found";

$imageNode = $xpath->query('//img[contains(@class, "DByoR4") or contains(@class, "J1ZGlC")]');
$image = $imageNode->length > 0 ? $imageNode->item(0)->getAttribute('src') : "";

echo json_encode([
    "success" => true,
    "data" => [
        "title" => trim($title),
        "price" => trim($price),
        "image" => $image
    ]
]);
exit;
?>
