<?php
// =========================================================================
// 1. BACKEND: Handle API Request (Sirf POST request aane par chalega)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Return format hamesha JSON hoga
    header('Content-Type: application/json');
    
    // Frontend se aaya hua URL read karein
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    $targetUrl = $requestData['url'] ?? '';

    if (empty($targetUrl)) {
        echo json_encode(["error" => "Oops! Product URL missing hai."]);
        exit;
    }

    // Thunderbit API Credentials (As per documentation)
    $apiKey = "Tb_cc55a9bd407a93cf5c55d56f59a0f7e1";
    $apiUrl = "https://openapi.thunderbit.com/openapi/v1/extract";

    // AI Schema Definition
    $schema = [
        "type" => "object",
        "properties" => [
            "product_name" => ["type" => "string", "description" => "Product title or name"],
            "price" => ["type" => "string", "description" => "Current price of the product"],
            "images" => [
                "type" => "array",
                "items" => ["type" => "string"],
                "description" => "Direct URLs of all product images"
            ],
            "details" => ["type" => "string", "description" => "Product specifications and features summary"]
        ]
    ];

    $payload = [
        "url" => $targetUrl,
        "schema" => $schema
    ];

    // cURL Setup
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
        // Agar Thunderbit ne error diya toh usko handle karein
        if ($httpCode !== 200) {
            echo json_encode([
                "error" => "Thunderbit API failed with status code: " . $httpCode,
                "details" => json_decode($response, true)
            ]);
        } else {
            echo $response; // Success response send karein
        }
    }
    
    curl_close($ch);
    exit; // HTML content ko target response me mix hone se rokne ke liye
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thunderbit Live Product Extractor</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f3f4f6;
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
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 550px;
            text-align: center;
            box-sizing: border-box;
        }

        h2 { margin: 0 0 10px 0; color: #1f2937; font-size: 24px; }
        p.sub { color: #6b7280; font-size: 14px; margin-bottom: 25px; }

        .input-box {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        input[type="url"] {
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
            transition: all 0.2s ease;
        }

        input[type="url"]:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }

        button {
            padding: 14px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover { background-color: #2563eb; }

        /* Live Checking Animation Layer */
        #loading-state {
            display: none;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
        }
        .spinner {
            width: 48px; height: 48px;
            border: 4px solid #f3f4f6;
            border-left-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .status-text { margin-top: 15px; color: #4b5563; font-weight: 500; font-size: 15px; }

        /* Dynamic Result & Error Container Below */
        .result-container {
            display: none;
            width: 100%;
            max-width: 750px;
            background: white;
            margin-top: 25px;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            box-sizing: border-box;
        }
        
        .error-box {
            display: none;
            width: 100%;
            max-width: 750px;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #b91c1c;
            margin-top: 25px;
            padding: 20px;
            border-radius: 12px;
            text-align: left;
            box-sizing: border-box;
            font-size: 14px;
            white-space: pre-wrap;
        }

        .product-title { font-size: 22px; color: #111827; margin: 0 0 8px 0; text-align: left; }
        .product-price { font-size: 20px; color: #059669; font-weight: 700; margin-bottom: 20px; text-align: left; }
        
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            margin-bottom: 25px;
        }
        .image-gallery img {
            width: 100%;
            height: 140px;
            object-fit: contain;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #fafafa;
        }

        .details-heading { font-size: 16px; color: #374151; font-weight: 600; margin: 0 0 8px 0; text-align: left; }
        .details-content {
            background: #f9fafb;
            padding: 15px;
            border-radius: 10px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.6;
            text-align: left;
            white-space: pre-wrap;
            border: 1px solid #f3f4f6;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div class="main-card">
        <h2>Thunderbit Extractor</h2>
        <p class="sub">Paste any Flipkart or E-commerce URL to fetch live details.</p>
        
        <div id="input-state" class="input-box">
            <input type="url" id="product-url" placeholder="https://www.flipkart.com/product-url..." required>
            <button onclick="runExtraction()">Extract Data</button>
        </div>

        <div id="loading-state">
            <div class="spinner"></div>
            <div id="live-status" class="status-text">Initializing Request...</div>
        </div>
    </div>

    <div id="error-output" class="error-box"></div>

    <div id="result-output" class="result-container">
        <h3 id="view-title" class="product-title">Product Title</h3>
        <div id="view-price" class="product-price">₹0.00</div>
        
        <div class="details-heading">Product Images</div>
        <div id="view-images" class="image-gallery"></div>
        
        <div class="details-heading" style="margin-top: 20px;">Product Specifications / Details</div>
        <div id="view-details" class="details-content"></div>
    </div>

    <script>
        async function runExtraction() {
            const urlInput = document.getElementById('product-url').value.trim();
            const inputState = document.getElementById('input-state');
            const loadingState = document.getElementById('loading-state');
            const liveStatus = document.getElementById('live-status');
            const errorOutput = document.getElementById('error-output');
            const resultOutput = document.getElementById('result-output');

            if (!urlInput) {
                alert("Please enter a valid product URL first!");
                return;
            }

            // UI Refresh: Hide previous logs, show loader
            errorOutput.style.display = 'none';
            resultOutput.style.display = 'none';
            inputState.style.display = 'none';
            loadingState.style.display = 'flex';

            // Live checking steps simulation text
            liveStatus.innerText = "Connecting to Railway backend...";
            
            setTimeout(() => { if(loadingState.style.display === 'flex') liveStatus.innerText = "Routing request through Thunderbit proxies..."; }, 1500);
            setTimeout(() => { if(loadingState.style.display === 'flex') liveStatus.innerText = "Parsing DOM & rendering JavaScript elements..."; }, 3500);
            setTimeout(() => { if(loadingState.style.display === 'flex') liveStatus.innerText = "Extracting matching JSON Schema items..."; }, 6000);

            try {
                // Current absolute URL par hit karega index.php ko target karte hue
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: urlInput })
                });

                // Raw text capture taaki unexpected characters easily display ho sakein
                const rawText = await response.text();
                
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch(e) {
                    throw new Error("Server returned invalid response structure. Raw Text: \n" + rawText);
                }

                if (data.error) {
                    let detailedMsg = data.error;
                    if(data.details) detailedMsg += "\nDetails: " + JSON.stringify(data.details, null, 2);
                    throw new Error(detailedMsg);
                }

                // Standard Data mapping from Thunderbit response
                const outputData = data.product_name ? data : (data.data || data);

                // Populate Fields
                document.getElementById('view-title').innerText = outputData.product_name || "N/A";
                document.getElementById('view-price').innerText = outputData.price || "N/A";
                document.getElementById('view-details').innerText = outputData.details || "No specification details returned.";

                // Handle Images Grid
                const imgContainer = document.getElementById('view-images');
                imgContainer.innerHTML = '';
                if (outputData.images && Array.isArray(outputData.images) && outputData.images.length > 0) {
                    outputData.images.forEach(url => {
                        const img = document.createElement('img');
                        img.src = url;
                        img.alt = "Product Image";
                        imgContainer.appendChild(img);
                    });
                } else {
                    imgContainer.innerHTML = '<p style="color:#9ca3af; font-size:14px; margin:0;">No product images found.</p>';
                }

                // Show Success Content Box
                resultOutput.style.display = 'block';

            } catch (err) {
                // Render exact descriptive error directly in the box below
                errorOutput.innerText = "Extraction Failed!\n\n" + err.message;
                errorOutput.style.display = 'block';
            } finally {
                // UI Reset: Stop loading loader, bring back input form
                loadingState.style.display = 'none';
                inputState.style.display = 'flex';
            }
        }
    </script>
</body>
</html>
