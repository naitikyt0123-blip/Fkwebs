<?php
require_once 'config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

function makeGitHubRequest($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    $headers = [
        'Accept: application/vnd.github.v3+json',
        'Authorization: token ' . GITHUB_PAT,
        'User-Agent: ' . GITHUB_USER_AGENT
    ];

    curl_setopt($ch, CURLOPT_URL, "https://api.github.com" . $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

switch ($action) {
    case 'list_repos':
        echo json_encode(makeGitHubRequest('/user/repos?sort=updated&per_page=100'));
        break;

    case 'create_repo':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(makeGitHubRequest('/user/repos', 'POST', [
            'name' => $data['name'],
            'private' => $data['private'] ?? true,
            'auto_init' => true // Initializes with a README so it's not empty
        ]));
        break;

    case 'delete_repo':
        $data = json_decode(file_get_contents('php://input'), true);
        echo json_encode(makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/{$data['repo']}", 'DELETE'));
        break;

    case 'get_repo_contents':
        $repo = $_GET['repo'];
        $path = ltrim($_GET['path'] ?? '', '/');
        echo json_encode(makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/$repo/contents/$path"));
        break;

    case 'get_file':
        $repo = $_GET['repo'];
        $path = ltrim($_GET['path'], '/');
        $res = makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/$repo/contents/$path");
        if(isset($res['body']['content'])) {
            $res['body']['decoded_content'] = base64_decode($res['body']['content']);
        }
        echo json_encode($res);
        break;

    case 'save_file':
        $data = json_decode(file_get_contents('php://input'), true);
        $repo = $data['repo'];
        $path = ltrim($data['path'], '/');
        
        $payload = [
            'message' => $data['message'] ?? "Update $path",
            'content' => base64_encode($data['content'])
        ];
        if (!empty($data['sha'])) $payload['sha'] = $data['sha'];
        
        echo json_encode(makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/$repo/contents/$path", 'PUT', $payload));
        break;

    case 'delete_file':
        $data = json_decode(file_get_contents('php://input'), true);
        $repo = $data['repo'];
        $path = ltrim($data['path'], '/');
        
        $payload = [
            'message' => "Delete $path",
            'sha' => $data['sha']
        ];
        echo json_encode(makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/$repo/contents/$path", 'DELETE', $payload));
        break;

    case 'upload_zip':
        if (!isset($_FILES['zipfile']) || !isset($_POST['repo'])) {
            echo json_encode(['error' => 'Missing file or repo']);
            exit;
        }

        $repo = $_POST['repo'];
        $basePath = isset($_POST['path']) ? trim($_POST['path'], '/') . '/' : '';
        if ($basePath === '/') $basePath = '';

        $zipPath = $_FILES['zipfile']['tmp_name'];
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === TRUE) {
            $tempDir = sys_get_temp_dir() . '/' . uniqid('repo_');
            mkdir($tempDir);
            $zip->extractTo($tempDir);
            $zip->close();

            // Iterate through extracted files
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir));
            $results = [];

            foreach ($iterator as $file) {
                if ($file->isDir()) continue;
                
                $filePath = $file->getPathname();
                $relativePath = str_replace($tempDir . '/', '', $filePath);
                
                // Ignore system files
                if (strpos($relativePath, '__MACOSX') !== false || strpos($relativePath, '.DS_Store') !== false) continue;

                $content = base64_encode(file_get_contents($filePath));
                $targetPath = ltrim($basePath . $relativePath, '/');

                // Commit each file to GitHub
                $res = makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/$repo/contents/$targetPath", 'PUT', [
                    'message' => "Extracted from ZIP: $relativePath",
                    'content' => $content
                ]);
                $results[] = ['file' => $targetPath, 'status' => $res['code']];
            }

            // Cleanup temp dir
            $dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($dirIterator as $fileInfo) {
                $todo = ($fileInfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileInfo->getRealPath());
            }
            rmdir($tempDir);

            echo json_encode(['code' => 200, 'message' => 'ZIP Extracted and Uploaded', 'details' => $results]);
        } else {
            echo json_encode(['code' => 500, 'error' => 'Failed to open ZIP file']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
