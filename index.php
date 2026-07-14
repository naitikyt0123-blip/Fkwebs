<?php
// =========================================================================
// 1. BACKEND: Handle Oxylabs API Request
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    $targetUrl = $requestData['url'] ?? '';

    if (empty($targetUrl)) {
        echo json_encode(["success" => false, "error" => "Product URL is missing."]);
        exit;
    }

    // Oxylabs Payload Configuration for E-commerce
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
    
    // Using your provided Oxylabs credentials
    curl_setopt($ch, CURLOPT_USERPWD, "spadexnaitik_k77Ay:Naitikff+123");
    
    $headers = array("Content-Type: application/json");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        echo json_encode(["success" => false, "error" => "CURL Error: " . curl_error($ch)]);
    } else {
        if ($httpCode !== 200) {
            echo json_encode([
                "success" => false,
                "error" => "Oxylabs API failed with status code: " . $httpCode,
                "details" => json_decode($result, true)
            ]);
        } else {
            // Forward the raw Oxylabs response directly to the frontend
            echo $result;
        }
    }
    
    curl_close($ch);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oxylabs Flipkart Extractor</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 30px 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .main-card {
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
            box-sizing: border-box;
        }

        h2 { margin: 0 0 10px 0; color: #1e293b; font-size: 24px; }
        p.sub { color: #64748b; font-size: 14px; margin-bottom: 25px; }

        .input-box { display: flex; flex-direction: column; gap: 12px; }

        input[type="url"] {
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
            width: 100%;
            box-sizing: border-box;
        }

        button {
            padding: 14px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background-color: #1d4ed8; }

        #loading-state { display: none; flex-direction: column; align-items: center; padding: 20px 0; }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #f1f5f9;
            border-left-color: #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .status-text { margin-top: 15px; color: #475569; font-weight: 500; font-size: 15px; }

        .result-wrapper {
            display: none;
            width: 100%;
            max-width: 600px;
            margin-top: 25px;
            flex-direction: column;
            gap: 20px;
        }

        .meta-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: left;
        }

        .meta-title { font-size: 20px; color: #0f172a; font-weight: 700; margin: 0 0 10px 0; }
        .meta-price { font-size: 22px; color: #16a34a; font-weight: bold; margin: 0 0 15px 0; }
        
        .screenshot-container { margin-top: 15px; }
        .screenshot-container img { max-width: 100%; height: auto; border-radius: 8px; border: 1px solid #e2e8f0; }

        .error-box {
            display: none;
            width: 100%;
            max-width: 600px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            margin-top: 25px;
            padding: 20px;
            border-radius: 12px;
            text-align: left;
            white-space: pre-wrap;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <div class="main-card">
        <h2>Oxylabs Product Scraper</h2>
        <p class="sub">Extract details directly from Flipkart URLs.</p>
        
        <div id="input-state" class="input-box">
            <input type="url" id="product-url" placeholder="https://www.flipkart.com/..." required>
            <button onclick="runExtraction()">Extract Data</button>
        </div>

        <div id="loading-state">
            <div class="spinner"></div>
            <div id="live-status" class="status-text">Routing via Oxylabs Proxies...</div>
        </div>
    </div>

    <div id="error-output" class="error-box"></div>

    <div id="result-wrapper" class="result-wrapper hidden">
        <div class="meta-card">
            <h3 id="res-title" class="meta-title">Product Name</h3>
            <div id="res-price" class="meta-price">₹0.00</div>
            
            <div class="screenshot-container">
                <img id="res-image" src="" alt="Product Image" style="display: none;">
            </div>
        </div>
    </div>

    <script>
        // Auto-run logic for Chrome URL pasting
        window.onload = function() {
            const currentHref = window.location.href;
            if (currentHref.includes('?url=')) {
                const extractedParam = currentHref.split('?url=')[1];
                if (extractedParam) {
                    document.getElementById('product-url').value = decodeURIComponent(extractedParam);
                    runExtraction();
                }
            }
        };

        async function runExtraction() {
            const urlInput = document.getElementById('product-url').value.trim();
            const inputState = document.getElementById('input-state');
            const loadingState = document.getElementById('loading-state');
            const errorOutput = document.getElementById('error-output');
            const resultWrapper = document.getElementById('result-wrapper');

            if (!urlInput) return;

            // UI Reset
            errorOutput.style.display = 'none';
            resultWrapper.classList.add('hidden');
            inputState.style.display = 'none';
            loadingState.style.display = 'flex';
            document.getElementById('res-image').style.display = 'none';

            try {
                const targetEndpoint = window.location.origin + window.location.pathname;
                const response = await fetch(targetEndpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: urlInput })
                });

                const rawText = await response.text();
                let result;
                
                try {
                    result = JSON.parse(rawText);
                } catch(e) {
                    throw new Error("Response is not valid JSON. Raw output:\n" + rawText);
                }

                if (result.error || result.success === false) {
                    throw new Error(result.error || "Oxylabs tracking failure.");
                }

                // Oxylabs returns data in an array called "results"
                const content = result.results?.[0]?.content || {};

                // Map Content to the UI
                document.getElementById('res-title').innerText = content.title || "Title Not Found";
                
                // Formatting price if it exists
                let priceText = "Price Not Found";
                if (content.price) {
                    priceText = (content.currency || "") + " " + content.price;
                }
                document.getElementById('res-price').innerText = priceText;

                // Handle Image
                const imgElement = document.getElementById('res-image');
                if (content.images && content.images.length > 0) {
                    imgElement.src = content.images[0];
                    imgElement.style.display = 'block';
                }

                resultWrapper.classList.remove('hidden');

            } catch (err) {
                errorOutput.innerText = "Extraction Failed!\n\n" + err.message;
                errorOutput.style.display = 'block';
            } finally {
                loadingState.style.display = 'none';
                inputState.style.display = 'flex';
            }
        }
    </script>
</body>
</html>
