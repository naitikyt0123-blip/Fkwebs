<?php
// =========================================================================
// 1. BACKEND: Handle /distill API Request
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true);
    $targetUrl = $requestData['url'] ?? '';

    if (empty($targetUrl)) {
        echo json_encode(["error" => "Oops! Product URL is missing."]);
        exit;
    }

    // Replace this with your actual secret key from the playground!
    $apiKey = "tb-your secret"; 
    $apiUrl = "https://openapi.thunderbit.com/openapi/v1/distill";

    // Notice there is no schema here, just the URL
    $payload = [
        "url" => $targetUrl
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
        if ($httpCode !== 200) {
            echo json_encode([
                "error" => "Thunderbit API failed with status code: " . $httpCode,
                "details" => json_decode($response, true)
            ]);
        } else {
            echo $response;
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
    <title>Thunderbit Distill</title>
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

        .input-box { display: flex; flex-direction: column; gap: 12px; }

        input[type="url"] {
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
        }

        button {
            padding: 14px;
            background-color: #10b981;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        #loading-state {
            display: none;
            flex-direction: column;
            align-items: center;
            padding: 20px 0;
        }
        
        .spinner {
            width: 48px; height: 48px;
            border: 4px solid #f3f4f6;
            border-left-color: #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .status-text { margin-top: 15px; color: #4b5563; font-weight: 500; font-size: 15px; }

        .result-container, .error-box {
            display: none;
            width: 100%;
            max-width: 800px;
            margin-top: 25px;
            padding: 30px;
            border-radius: 16px;
            box-sizing: border-box;
        }
        
        .error-box { background: #fef2f2; border: 1px solid #fee2e2; color: #b91c1c; }
        .result-container { background: #1e1e1e; color: #d4d4d4; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        
        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            line-height: 1.5;
            text-align: left;
            margin: 0;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

    <div class="main-card">
        <h2>Thunderbit Distiller</h2>
        <p class="sub">Extract clean Markdown text from any URL.</p>
        
        <div id="input-state" class="input-box">
            <input type="url" id="product-url" placeholder="https://www.flipkart.com/..." required>
            <button onclick="runDistill()">Distill Page</button>
        </div>

        <div id="loading-state">
            <div class="spinner"></div>
            <div id="live-status" class="status-text">Distilling page to Markdown...</div>
        </div>
    </div>

    <div id="error-output" class="error-box"></div>

    <div id="result-output" class="result-container">
        <pre id="markdown-content"></pre>
    </div>

    <script>
        async function runDistill() {
            const urlInput = document.getElementById('product-url').value.trim();
            const inputState = document.getElementById('input-state');
            const loadingState = document.getElementById('loading-state');
            const errorOutput = document.getElementById('error-output');
            const resultOutput = document.getElementById('result-output');
            const markdownContent = document.getElementById('markdown-content');

            if (!urlInput) {
                alert("Please enter a valid URL!");
                return;
            }

            errorOutput.style.display = 'none';
            resultOutput.style.display = 'none';
            inputState.style.display = 'none';
            loadingState.style.display = 'flex';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: urlInput })
                });

                const rawText = await response.text();
                let data;
                
                try {
                    data = JSON.parse(rawText);
                } catch(e) {
                    throw new Error("Invalid response format. Raw Text: \n" + rawText);
                }

                if (data.error) {
                    let detailedMsg = data.error;
                    if(data.details) detailedMsg += "\nDetails: " + JSON.stringify(data.details, null, 2);
                    throw new Error(detailedMsg);
                }

                // Display the raw JSON or markdown returned by distill
                markdownContent.innerText = JSON.stringify(data, null, 2);
                resultOutput.style.display = 'block';

            } catch (err) {
                errorOutput.innerText = "Distill Failed!\n\n" + err.message;
                errorOutput.style.display = 'block';
            } finally {
                loadingState.style.display = 'none';
                inputState.style.display = 'flex';
            }
        }
    </script>
</body>
</html>
