<?php
/**
 * ============================================================
 *  CONFIG  â€”  config.php
 * ============================================================
 *  YAHAN SIRF APNA GITHUB PAT TOKEN + USERNAME DAALO.
 *  Ye ek hi jagah hai jahan token badalna hai.
 *
 *  githubflow.js (Node backend) is file se hi token padhta hai,
 *  toh do jagah likhne ki zaroorat nahi.
 * ============================================================
 */

// â¬‡â¬‡â¬‡ APNA GITHUB PERSONAL ACCESS TOKEN (classic ya fine-grained, repo scope) â¬‡â¬‡â¬‡
define('GITHUB_PAT', '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');

// â¬‡ Apna GitHub username (jiske account me repos banengi)
define('GITHUB_USERNAME', 'naitikyt0123-blip');

// â¬‡ UI login (browser me app kholne ke liye) â€” ise zaroor badlo
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123');

// â¬‡ Session secret (koi bhi random string)
define('APP_SECRET', 'change-this-random-secret-string-123');

/* ------------------------------------------------------------
   Neeche kuch mat chhedo â€” ye values Node backend (githubflow.js)
   ke liye JSON me expose hoti hain jab is file ko as text padha jata hai.
   ------------------------------------------------------------ */
if (php_sapi_name() !== 'cli' && isset($_GET['__ping'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}