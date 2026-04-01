<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    echo json_encode(['success'=>false,'message'=>'Akses tidak sah.']); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Metode tidak valid.']); exit;
}

require_once 'koneksi.php';
$userId = (int)$_SESSION['user_id'];

// Cek sudah absen hadir hari ini?
$today = date('Y-m-d');
$cek = $conn->query("SELECT id, status FROM absensi WHERE user_id=$userId AND DATE(tanggal)='$today'");
if ($cek->num_rows > 0) {
    $existing = $cek->fetch_assoc();
    echo json_encode([
        'success' => false,
        'sudah_absen' => true,
        'status_sebelumnya' => $existing['status'],
        'message' => 'Anda sudah melakukan absensi hari ini dengan status: ' . $existing['status']
    ]);
    exit;
}

$lat = filter_input(INPUT_POST, 'latitude',  FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lng === false || $lat === null || $lng === null) {
    echo json_encode(['success'=>false,'message'=>'Koordinat tidak valid.']); exit;
}

// Haversine
$hasil = dalamRadius($lat, $lng);
$jarak = $hasil['jarak'];
$dalam = $hasil['dalam'];

$status     = $dalam ? 'Hadir' : 'Ditolak';
$keterangan = $dalam
    ? "Dalam radius LLDIKTI ({$jarak}m)"
    : "Di luar radius (jarak: {$jarak}m, batas: ".RADIUS_METER."m)";

$waktuSekarang = date('H:i:s');
$tanggalSekarang = date('Y-m-d H:i:s');

// INSERT dengan ON DUPLICATE KEY untuk keamanan ganda
$stmt = $conn->prepare("
    INSERT INTO absensi (user_id, tanggal, waktu, latitude, longitude, jarak_meter, status, keterangan)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        tanggal=VALUES(tanggal), waktu=VALUES(waktu), latitude=VALUES(latitude), longitude=VALUES(longitude),
        jarak_meter=VALUES(jarak_meter), status=VALUES(status), keterangan=VALUES(keterangan)
" );
$stmt->bind_param('issdddss', $userId, $tanggalSekarang, $waktuSekarang, $lat, $lng, $jarak, $status, $keterangan);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    echo json_encode(['success'=>false,'message'=>'Gagal menyimpan absensi.']); exit;
}

echo json_encode([
    'success' => true,
    'status'  => $status,
    'jarak'   => $jarak,
    'waktu'   => date('d M Y H:i:s'),
    'message' => $dalam
        ? "Absensi berhasil dicatat. Anda berada dalam radius LLDIKTI."
        : "Anda berada di luar area absensi LLDIKTI. Jarak Anda: {$jarak} meter (batas: ".RADIUS_METER." meter).",
]);
