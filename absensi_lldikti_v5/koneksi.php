<?php
// ============================================================
// koneksi.php - Koneksi Database MySQL
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'absensi_lldikti');

// Koordinat LLDIKTI Wilayah II Palembang
// Jl. Srijaya No.883, Srijaya, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30153
// Koordinat manual berdasarkan Google Maps — titik pusat gedung LLDIKTI
define('LLDIKTI_LAT', -2.964400);
define('LLDIKTI_LNG', 104.717600);
define('RADIUS_METER', 2000); // radius 2000 meter

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]));
}

$conn->set_charset('utf8mb4');

// ============================================================
// Fungsi Haversine: Hitung jarak antara dua koordinat (meter)
// ============================================================
function hitungJarak($lat1, $lng1, $lat2, $lng2) {
    $R = 6371000; // Radius bumi dalam meter
    $phi1 = deg2rad($lat1);
    $phi2 = deg2rad($lat2);
    $deltaPhi = deg2rad($lat2 - $lat1);
    $deltaLambda = deg2rad($lng2 - $lng1);

    $a = sin($deltaPhi / 2) * sin($deltaPhi / 2) +
         cos($phi1) * cos($phi2) *
         sin($deltaLambda / 2) * sin($deltaLambda / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $jarak = $R * $c;

    return round($jarak, 2); // meter
}

// ============================================================
// Helper: Cek apakah dalam radius LLDIKTI
// ============================================================
function dalamRadius($lat, $lng) {
    $jarak = hitungJarak($lat, $lng, LLDIKTI_LAT, LLDIKTI_LNG);
    return ['dalam' => ($jarak <= RADIUS_METER), 'jarak' => $jarak];
}
