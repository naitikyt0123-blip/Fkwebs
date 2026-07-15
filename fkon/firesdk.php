<?php
/* ============================================================
   firesdk.php  â€”  SETTINGS FILE (isi file ko har store ke liye badlo)
   ------------------------------------------------------------
   Har naye user/store ke liye:
   1) COLLECTION_NAME badlo  (jaise: "fkiphone", "fksofa")
   2) Firebase config wahi rehne do (ek hi Firebase sabke liye)

   Ye file config ko JSON me deti hai. index.html isse fetch karta hai.
   Isliye Firebase ki keys HTML me nahi, sirf yahan hain.
   ============================================================ */

/* ---------- 1. YAHAN COLLECTION KA NAAM BADLO ---------- */
$COLLECTION_NAME = "products";   // <-- e.g. "fkiphone" ya "fksofa"

/* ---------- 2. FIREBASE CONFIG (ek hi rahega sabke liye) ---------- */
$FIREBASE_CONFIG = [
    "apiKey"            => "AIzaSyBPh60LuPqcH58delO2xGSQN7k48wW-6YM",
    "authDomain"        => "lavinrent.firebaseapp.com",
    "databaseURL"       => "https://lavinrent-default-rtdb.firebaseio.com",
    "projectId"         => "lavinrent",
    "storageBucket"     => "lavinrent.firebasestorage.app",
    "messagingSenderId" => "1070171538977",
    "appId"             => "1:1070171538977:web:a5f026d387328ebcfec560",
];

/* ============================================================
   Neeche kuch mat chhedna â€” ye config ko browser tak bhejta hai
   ============================================================ */
header("Content-Type: application/json");
header("Cache-Control: public, max-age=3600"); // config ko cache karo -> fast load

echo json_encode([
    "collection" => $COLLECTION_NAME,
    "firebase"   => $FIREBASE_CONFIG,
]);