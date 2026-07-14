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
        echo json_encode(["success" => false, "error" => "Oops! Product URL is missing."]);
        exit;
    }

    // Yahan apna asli secret key daalein
    $apiKey = "tb-your secret"; 
    $apiUrl = "https://openapi.thunderbit.com/openapi/v1/distill";

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
        echo json_encode(["success" => false, "error" => "CURL Error: " . curl_error($ch)]);
    } else {
        if ($httpCode !== 200) {
            echo json_encode([
                "success" => false,
                "error" => "Thunderbit API failed with status code: " . $httpCode,
                "details" => json_decode($response, true)
            ]);
        } else {
            // Thunderbit ka direct success response (jo aapne share kiya) aage pass kar rahe hain
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
    <title>Thunderbit Distill Output</title>
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
            transition: border-color 0.2s;
        }
        input[type="url"]:focus { border-color: #3b82f6; }

        button {
            padding: 14px;
            background-color: #8b5cf6;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover { background-color: #7c3aed; }

        #loading-state { display: none; flex-direction: column; align-items: center; padding: 20px 0; }
        .spinner {
            width: 40px; height: 40px;
            border: 4px solid #f1f5f9;
            border-left-color: #8b5cf6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .status-text { margin-top: 15px; color: #475569; font-weight: 500; font-size: 15px; }

        /* Structured Result Containers */
        .result-wrapper {
            display: none;
            width: 100%;
            max-width: 800px;
            margin-top: 25px;
            display: flex;
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

        .meta-title { font-size: 20px; color: #0f172a; font-weight: 700; margin: 0 0 8px 0; }
        .meta-desc { font-size: 14px; color: #64748b; margin: 0 0 15px 0; line-height: 1.5; }
        .meta-url { font-size: 12px; color: #3b82f6; word-break: break-all; text-decoration: none; }

        .screenshot-container { display: none; margin-top: 15px; }
        .screenshot-container img { max-width: 100%; border-radius: 8px; border: 1px solid #e2e8f0; }

        .markdown-card {
            background: #1e293b;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: left;
        }
        
        .markdown-header { color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 1px; }

        pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            color: #f8fafc;
            margin: 0;
            max-height: 500px;
            overflow-y: auto;
        }

        .error-box {
            display: none;
            width: 100%;
            max-width: 800px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            margin-top: 25px;
            padding: 20px;
            border-radius: 12px;
            text-align: left;
            font-size: 14px;
            white-space: pre-wrap;
        }

        @keyframes spin { 100% { transform: rotate(360deg); } }
        
        /* Utility class to hide element */
        .hidden { display: none !important; }
    </style>
</head>
<body>

    <div class="main-card">
        <h2>Web Page Distiller</h2>
        <p class="sub">URL paste kijiye aur clean Markdown data extract kijiye.</p>
        
        <div id="input-state" class="input-box">
            <input type="url" id="product-url" placeholder="https://example.com/article" required>
            <button onclick="runDistill()">Extract Data</button>
        </div>

        <div id="loading-state">
            <div class="spinner"></div>
            <div id="live-status" class="status-text">Fetching data from Thunderbit...</div>
        </div>
    </div>

    <div id="error-output" class="error-box"></div>

    <div id="result-wrapper" class="result-wrapper hidden">
        
        <div class="meta-card">
            <h3 id="res-title" class="meta-title">Page Title</h3>
            <p id="res-desc" class="meta-desc">Page description will appear here...</p>
            <a id="res-url" href="#" target="_blank" class="meta-url">Source URL</a>
            
            <div id="res-screenshot-container" class="screenshot-container">
                <p style="font-size: 12px; color: #64748b; margin-bottom: 5px;">Screenshot:</p>
                <img id="res-screenshot" src="" alt="Page Screenshot">
            </div>
        </div>

        <div class="markdown-card">
            <div class="markdown-header">Extracted Markdown</div>
            <pre id="res-markdown"></pre>
        </div>
        
    </div>

    <script>
        async function runDistill() {
            const urlInput = document.getElementById('product-url').value.trim();
            const inputState = document.getElementById('input-state');
            const loadingState = document.getElementById('loading-state');
            const errorOutput = document.getElementById('error-output');
            const resultWrapper = document.getElementById('result-wrapper');

            if (!urlInput) {
                alert("Please enter a valid URL!");
                return;
            }

            // Reset UI
            errorOutput.style.display = 'none';
            resultWrapper.classList.add('hidden');
            inputState.style.display = 'none';
            loadingState.style.display = 'flex';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ url: urlInput })
                });

                const rawText = await response.text();
                let result;
                
                try {
                    result = JSON.parse(rawText);
                } catch(e) {
                    throw new Error("Invalid response format. Raw Text: \n" + rawText);
                }

                // Check for backend PHP errors or API errors
                if (result.error || result.success === false) {
                    let detailedMsg = result.error || "Unknown error occurred.";
                    if(result.details) detailedMsg += "\nDetails: " + JSON.stringify(result.details, null, 2);
                    throw new Error(detailedMsg);
                }

                // Extract data based on the specific JSON schema provided
                const distillData = result.data || {};
                const meta = distillData.metadata || {};

                // Map Metadata
                document.getElementById('res-title').innerText = meta.title || "No Title Found";
                document.getElementById('res-desc').innerText = meta.description || "No description available.";
                
                const urlElem = document.getElementById('res-url');
                urlElem.innerText = meta.sourceURL || urlInput;
                urlElem.href = meta.sourceURL || urlInput;

                // Map Screenshot
                const screenshotContainer = document.getElementById('res-screenshot-container');
                if (distillData.screenshot && distillData.screenshot.trim() !== "") {
                    document.getElementById('res-screenshot').src = distillData.screenshot;
                    screenshotContainer.style.display = 'block';
                } else {
                    screenshotContainer.style.display = 'none';
                }

                // Map Markdown Content
                document.getElementById('res-markdown').innerText = distillData.markdown || "No content extracted.";

                // Show Results
                resultWrapper.classList.remove('hidden');

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
