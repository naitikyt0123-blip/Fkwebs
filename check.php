<?php
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");

// UID get karna aur sirf numbers allow karna
$uid = trim($_GET['uid'] ?? '');
$uid = preg_replace('/\D/', '', $uid);

if (empty($uid)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid UID"
    ]);
    exit;
}

// Target API URL
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
        "User-Agent: Mozilla/5.0"
    ]
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

// Agar connection fail ho jaye
if ($error || empty($response)) {
    echo json_encode([
        "success" => false,
        "message" => "API connection failed"
    ]);
    exit;
}

$data = json_decode($response, true);

// Check karna ki API ne 'Success' aur 'Player_Details' return kiya hai
if (isset($data['Status']) && $data['Status'] === 'Success' && isset($data['Player_Details'])) {
    
    $details = $data['Player_Details'];
    
    // Yahan hum response ko ekdum tumhari index.php ke format me bana rahe hain
    echo json_encode([
        "success" => true,
        "data" => [
            "Name" => $details['Name'] ?? 'Unknown',
            "UID" => $uid,
            "Level" => $details['Level'] ?? 'N/A',
            "Likes" => $details['Likes'] ?? '0',
            "Region" => $details['Region'] ?? 'IND'
        ]
    ]);

} else {
    // Agar UID galat ho
    echo json_encode([
        "success" => false,
        "message" => "UID Not Found"
    ]);
}
?>
