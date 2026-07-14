<?php
// ==========================================
// BACKEND: Handle the API Extraction Request
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Read the incoming JSON data from the fetch request
    $requestData = json_decode(file_get_contents('php://input'), true);
    $targetUrl = $requestData['url'] ?? '';

    if (empty($targetUrl)) {
        echo json_encode(["error" => "No URL provided"]);
        exit;
    }

    // Thunderbit API Configuration
    $apiKey = "Tb_cc55a9bd407a93cf5c55d56f59a0f7e1";
    $apiUrl = "https://api.thunderbit.com/v1/extract";

    $schema = [
        "type" => "object",
        "properties" => [
            "product_name" => ["type" => "string", "description" => "Product title"],
            "price" => ["type" => "string", "description" => "Current price"],
            "images" => [
                "type" => "array",
                "items" => ["type" => "string"],
                "description" => "Direct URLs of the product images"
            ],
            "details" => ["type" => "string", "description" => "Product specifications and description"]
        ]
    ];

    $payload = [
        "url" => $targetUrl,
        "schema" => $schema
    ];

    // Initialize and execute cURL
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
    curl_close($ch);

    // Return the response directly to the frontend
    if ($httpCode === 200) {
        echo $response;
    } else {
        echo json_encode(["error" => "API Request Failed", "details" => json_decode($response), "code" => $httpCode]);
    }
    
    // Stop execution so HTML is not sent in the API response
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Extractor</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            width: 100%;
            max-width: 500px;
            text-align: center;
            margin-bottom: 30px;
        }

        h2 { margin-top: 0; color: #2c3e50; }
        .input-group { display: flex; flex-direction: column; gap: 15px; }
        
        input[type="url"] {
            padding: 14px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 15px;
            outline: none;
        }

        button {
            padding: 14px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }

        /* Loading Animation */
        #loading-state { display: none; flex-direction: column; align-items: center; }
        .spinner {
            width: 45px; height: 45px;
            border: 4px solid rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .loading-text { margin-top: 20px; color: #34495e; font-weight: 500; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* Results Display */
        #result-display {
            display: none;
            width: 100%;
            max-width: 800px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .product-header { border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 20px; }
        .price-tag { color: #27ae60; font-size: 24px; font-weight: bold; }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .image-grid img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .details-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: left;
            line-height: 1.6;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>

    <div class="container" id="main-box">
        <div id="input-state">
            <h2>Product Extractor</h2>
            <p style="color:#7f8c8d;">Paste a Flipkart URL to fetch data.</p>
            <div class="input-group">
                <input type="url" id="product-url" placeholder="https://www.flipkart.com/..." required>
                <button onclick="startExtraction()">Extract Data</button>
            </div>
        </div>
        
        <div id="loading-state">
            <div class="spinner"></div>
            <div class="loading-text" id="status-text">Connecting to server...</div>
        </div>
    </div>

    <div id="result-display">
        <div class="product-header">
            <h2 id="res-title">Product Name</h2>
            <div class="price-tag" id="res-price">₹0.00</div>
        </div>
        
        <h3>Images</h3>
        <div class="image-grid" id="res-images"></div>
        
        <h3>Details</h3>
        <div class="details-box" id="res-details"></div>
    </div>

    <script>
        async function startExtraction() {
            const urlInput = document.getElementById('product-url').value;
            if (!urlInput) {
                alert("Please enter a valid URL.");
                return;
            }

            document.getElementById('input-state').style.display = 'none';
            document.getElementById('loading-state').style.display = 'flex';
            document.getElementById('result-display').style.display = 'none';
            const statusText = document.getElementById('status-text');

            try {
                statusText.innerText = "Extracting data from Thunderbit...";
                
                // Fetch points to the same file (index.php)
                const response = await fetch('/index.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: urlInput })
                });

                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                const extracted = data.product_name ? data : (data.data || data);

                document.getElementById('res-title').innerText = extracted.product_name || "Name not found";
                document.getElementById('res-price').innerText = extracted.price || "Price not found";
                document.getElementById('res-details').innerText = extracted.details || "No details available";

                const imageContainer = document.getElementById('res-images');
                imageContainer.innerHTML = ''; 
                
                if (extracted.images && Array.isArray(extracted.images)) {
                    extracted.images.forEach(imgUrl => {
                        const img = document.createElement('img');
                        img.src = imgUrl;
                        imageContainer.appendChild(img);
                    });
                } else {
                    imageContainer.innerHTML = "<p>No images found.</p>";
                }

                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('input-state').style.display = 'block';
                document.getElementById('result-display').style.display = 'block';
                
            } catch (error) {
                console.error(error);
                alert("Extraction failed. Check the console for details.");
                document.getElementById('loading-state').style.display = 'none';
                document.getElementById('input-state').style.display = 'block';
            }
        }
    </script>
</body>
</html>
