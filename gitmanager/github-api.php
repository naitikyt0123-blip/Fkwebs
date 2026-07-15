<?php
// github-api.php
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
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

switch ($action) {
    case 'list_repos':
        $res = makeGitHubRequest('/user/repos?sort=updated&per_page=100');
        echo json_encode($res);
        break;

    case 'get_repo_contents':
        $repo = $_GET['repo'];
        $path = $_GET['path'] ?? '';
        $res = makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/$repo/contents/$path");
        echo json_encode($res);
        break;

    case 'get_file':
        $repo = $_GET['repo'];
        $path = $_GET['path'];
        $res = makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/$repo/contents/$path");
        // GitHub returns base64 content
        if(isset($res['body']['content'])) {
            $res['body']['decoded_content'] = base64_decode($res['body']['content']);
        }
        echo json_encode($res);
        break;

    case 'save_file':
        $data = json_decode(file_get_contents('php://input'), true);
        $repo = $data['repo'];
        $path = $data['path'];
        $content = base64_encode($data['content']);
        $message = $data['message'] ?? "Update $path via Web Manager";
        $sha = $data['sha']; // Required by GitHub to update existing files

        $payload = [
            'message' => $message,
            'content' => $content,
            'sha' => $sha
        ];
        
        $res = makeGitHubRequest("/repos/" . GITHUB_USERNAME . "/$repo/contents/$path", 'PUT', $payload);
        echo json_encode($res);
        break;

    case 'upload_zip':
        // ZIP extraction aur Tree API commit logic yahan aayega
        // 1. ZIP upload accept karein
        // 2. Temp folder me extract karein
        // 3. GitHub Tree API use karke multi-file commit banayein
        echo json_encode(['status' => 'error', 'message' => 'ZIP upload logic implementation pending based on Tree API constraints.']);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
