<?php
session_start();

// ---------- Error handling (Railway safe) ----------
ini_set('display_errors', 0);      // production: errors hide
error_reporting(E_ALL);

// ---------- Data directory ----------
define('DATA_DIR', __DIR__ . '/data');
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0777, true);
}

// ---------- File paths ----------
$FILES = [
    'settings' => DATA_DIR . '/settings.json',
    'packs'    => DATA_DIR . '/packs.json',
    'stats'    => DATA_DIR . '/stats.json',
];

// ---------- Create default files on first run ----------
if (!file_exists($FILES['settings'])) {
    @file_put_contents($FILES['settings'], json_encode([
        'admin_user'   => 'zerospade',
        'admin_pass'   => 'spadewebs',
        'upi_id'       => 'yourupi@okhdfcbank',
        'upi_name'     => 'FF TopUp Store',
        'payment_mode' => 'all_upi',   // all_upi | razorpay
        'razorpay_key' => ''
    ], JSON_PRETTY_PRINT));
}

if (!file_exists($FILES['packs'])) {
    @file_put_contents($FILES['packs'], json_encode([
        ['id' => 1, 'diamonds' => 100,  'original' => 100,  'offer' => 52],
        ['id' => 2, 'diamonds' => 310,  'original' => 300,  'offer' => 155],
        ['id' => 3, 'diamonds' => 520,  'original' => 500,  'offer' => 260],
        ['id' => 4, 'diamonds' => 1060, 'original' => 1000, 'offer' => 520],
        ['id' => 5, 'diamonds' => 2180, 'original' => 2000, 'offer' => 1040],
        ['id' => 6, 'diamonds' => 5600, 'original' => 5000, 'offer' => 2600],
    ], JSON_PRETTY_PRINT));
}

if (!file_exists($FILES['stats'])) {
    @file_put_contents($FILES['stats'], json_encode([
        'clicks'    => 0,
        'checkouts' => 0
    ], JSON_PRETTY_PRINT));
}

// ---------- Read a data file ----------
function get_data($key) {
    global $FILES;
    if (!isset($FILES[$key]) || !file_exists($FILES[$key])) return [];
    $raw = file_get_contents($FILES[$key]);
    $d = json_decode($raw, true);
    return is_array($d) ? $d : [];
}

// ---------- Save a data file ----------
function save_data($key, $data) {
    global $FILES;
    if (!isset($FILES[$key])) return false;
    return @file_put_contents($FILES[$key], json_encode($data, JSON_PRETTY_PRINT));
}

// ---------- Increment a stat field ----------
function bump_stat($field) {
    $s = get_data('stats');
    $s[$field] = ($s[$field] ?? 0) + 1;
    save_data('stats', $s);
}

// ---------- Count a website visit (once per session) ----------
function count_click() {
    if (empty($_SESSION['counted_click'])) {
        bump_stat('clicks');
        $_SESSION['counted_click'] = true;
    }
}

// ---------- Count a checkout (once per session) ----------
function count_checkout() {
    if (empty($_SESSION['counted_checkout'])) {
        bump_stat('checkouts');
        $_SESSION['counted_checkout'] = true;
    }
}
?>
