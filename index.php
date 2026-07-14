<?php
// Error reporting on kar rahe hain taaki debugging asaan ho
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ==========================================
// BACKEND: API Extraction Handling
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    
    if (!isset($requestData['url'])) {
        http_response_code(400);
        echo json_encode(["error" => "URL missing"]);
        exit;
    }

    $apiKey = "Tb_cc55a9bd407a93cf5c55d56f59a0f7e1";
    $apiUrl = "https://api.thunderbit.com/v1/extract";

    $payload = [
        "url" => $requestData['url'],
        "schema" => [
            "type" => "object",
            "properties" => [
                "product_name" => ["type" => "string"],
                "price" => ["type" => "string"],
                "images" => ["type" => "array", "items" => ["type" => "string"]],
                "details" => ["type" => "string"]
            ]
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $apiKey,
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo json_encode(["error" => "CURL Error: " . curl_error($ch)]);
    } else {
        http_response_code($httpCode);
        echo $response;
    }
    curl_close($ch);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Thunderbit Scraper</title>
    <style>
        body { font-family: sans-serif; padding: 20px; max-width: 600px; margin: auto; }
        #res { margin-top: 20px; border: 1px solid #ccc; padding: 10px; display: none; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Product Extractor</h2>
    <input type="url" id="url" placeholder="Paste Flipkart URL" style="width: 100%; padding: 10px;">
    <button onclick="extract()" style="margin-top: 10px; padding: 10px;">Extract Data</button>

    <div id="status"></div>
    <div id="res"></div>

    <script>
        async function extract() {
            const url = document.getElementById('url').value;
            const status = document.getElementById('status');
            const resDiv = document.getElementById('res');
            
            status.innerText = "Checking...";
            resDiv.style.display = 'none';

            try {
                // '/' path ka use karein taaki root se call ho
                const response = await fetch(window.location.href, { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: url })
                });

                const data = await response.json();
                
                if (data.error) throw new Error(data.error);

                status.innerText = "Success!";
                resDiv.style.display = 'block';
                resDiv.innerHTML = `<pre>${JSON.stringify(data, null, 2)}</pre>`;
            } catch (err) {
                status.innerHTML = `<span class="error">Error: ${err.message}</span>`;
            }
        }
    </script>
</body>
</html>
