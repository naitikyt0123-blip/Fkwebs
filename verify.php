<?php
// Function to fetch the Thunderbit record
function fetchThunderbitRecord() {
    $url = 'https://api.thunderbit.com/api/v1/public_tools/980b1f0d-4fdf-48f3-8173-c68a82427a9c/records/a1adf2e3-e233-49b2-bfea-8e11695d046d';
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Mobile Safari/537.36',
        'Accept: application/json',
        'Referer: https://thunderbit.com/',
        'Origin: https://thunderbit.com/'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $response;
}

// Agar ye file direct call ho rahi hai toh result print karo
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo fetchThunderbitRecord();
}
?>
