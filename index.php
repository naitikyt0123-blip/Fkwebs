<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flipkart Product Extractor</title>
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .card {
            background: white;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
            box-sizing: border-box;
            margin-bottom: 20px;
        }
        h2 { margin: 0 0 10px 0; color: #1e293b; }
        p.subtitle { color: #64748b; font-size: 14px; margin-bottom: 25px; }
        .input-group { display: flex; flex-direction: column; gap: 12px; }
        input[type="url"] {
            padding: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
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
        #loading-ui { display: none; flex-direction: column; align-items: center; padding: 20px 0; }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #f1f5f9;
            border-left-color: #2563eb;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .status-text { margin-top: 15px; color: #475569; font-weight: 500; font-size: 15px; }
        .result-card { display: none; text-align: left; }
        .product-title { font-size: 20px; color: #0f172a; font-weight: 700; margin: 0 0 12px 0; }
        .price-container { display: flex; align-items: baseline; gap: 12px; margin-bottom: 20px; }
        .product-price { font-size: 26px; color: #16a34a; font-weight: bold; }
        .product-mrp { font-size: 16px; color: #94a3b8; text-decoration: line-through; }
        .image-container { width: 100%; text-align: center; margin-top: 15px; }
        .product-image {
            max-width: 100%;
            max-height: 350px;
            height: auto;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            display: none;
            object-fit: contain;
        }
        .error-card {
            display: none;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 20px;
            border-radius: 12px;
            text-align: left;
            font-size: 14px;
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div class="card" id="form-card">
        <h2>Flipkart Extractor</h2>
        <p class="subtitle">Enter a product URL to retrieve data securely.</p>
        <div class="input-group">
            <input type="url" id="product-url" placeholder="https://www.flipkart.com/..." required>
            <button onclick="startExtraction()">Extract Information</button>
        </div>
    </div>

    <div class="card" id="loading-ui">
        <div class="spinner"></div>
        <div class="status-text">Fetching live data via Oxylabs...</div>
    </div>

    <div class="card error-card" id="error-ui"></div>

    <div class="card result-card" id="result-ui">
        <h3 id="res-title" class="product-title">Product Name</h3>
        <div class="price-container">
            <span id="res-price" class="product-price">₹0.00</span>
            <span id="res-mrp" class="product-mrp">₹0.00</span>
        </div>
        <div class="image-container">
            <img id="res-image" class="product-image" src="" alt="Product Output">
        </div>
    </div>

    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const autoUrl = urlParams.get('url');
            if (autoUrl) {
                document.getElementById('product-url').value = autoUrl;
                startExtraction();
            }
        };

        async function startExtraction() {
            const urlInput = document.getElementById('product-url').value.trim();
            const formCard = document.getElementById('form-card');
            const loadingUI = document.getElementById('loading-ui');
            const errorUI = document.getElementById('error-ui');
            const resultUI = document.getElementById('result-ui');

            if (!urlInput) {
                alert("Please enter a URL.");
                return;
            }

            errorUI.style.display = 'none';
            resultUI.style.display = 'none';
            formCard.style.display = 'none';
            loadingUI.style.display = 'flex';
            document.getElementById('res-image').style.display = 'none';

            try {
                const response = await fetch('verify.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: urlInput })
                });

                const rawText = await response.text();
                let result;
                try {
                    result = JSON.parse(rawText);
                } catch(e) {
                    throw new Error("Invalid format. Raw:\n" + rawText.substring(0, 150));
                }

                if (result.success === false) {
                    throw new Error(result.error || "Failed to process.");
                }

                const data = result.data;
                document.getElementById('res-title').innerText = data.title;
                document.getElementById('res-price').innerText = data.price;
                document.getElementById('res-mrp').innerText = data.original_price;
                
                const img = document.getElementById('res-image');
                if (data.image && data.image.trim() !== "") {
                    img.src = data.image;
                    img.style.display = 'inline-block';
                } else {
                    img.style.display = 'none';
                }

                resultUI.style.display = 'block';
            } catch (err) {
                errorUI.innerText = "Error Occurred:\n\n" + err.message;
                errorUI.style.display = 'block';
            } finally {
                loadingUI.style.display = 'none';
                formCard.style.display = 'block';
            }
        }
    </script>
</body>
</html>
