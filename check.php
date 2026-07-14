<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

$uid = trim($_GET['uid'] ?? '');
// Sirf numbers allow karne ke liye
$uid = preg_replace('/\D/', '', $uid);

if (empty($uid)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid UID"
    ]);
    exit;
}

// Tumhari Nayi API ka URL
$api = "https://f-fuid2-info.vercel.app/api/check?uid=" . urlencode($uid);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        "Accept: application/json",
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
    ]
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Agar Curl me koi error aaye
if ($error) {
    echo json_encode([
        "success" => false,
        "message" => "Server connection failed"
    ]);
    exit;
}

// Nayi API ke JSON response ko decode karna
$data = json_decode($response, true);

// Check karna ki API ne 'Success' aur 'Name' return kiya hai ya nahi
if (isset($data['Status']) && $data['Status'] === 'Success' && isset($data['Player_Details']['Name'])) {
    
    // Yahan hum response ko modify kar rahe hain taaki tumhari HTML (frontend) isko samajh sake
    echo json_encode([
        "success" => true,
        "nickname" => $data['Player_Details']['Name'],
        "uid" => $uid
    ]);

} else {
    // Agar UID galat ho ya API failed ho jaye
    echo json_encode([
        "success" => false,
        "message" => "UID Not Found"
    ]);
}
?>
