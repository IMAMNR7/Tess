<?php
require_once 'koneksi.php';

// Tabel users dengan kolom baru
$conn->query("DROP TABLE IF EXISTS absensi");
$conn->query("DROP TABLE IF EXISTS users");

$conn->query("CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    password_plain VARCHAR(100) NOT NULL DEFAULT '',
    role ENUM('admin','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
    nim VARCHAR(20) NULL,
    prodi VARCHAR(100) NULL,
    status ENUM('aktif','pending','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Tabel absensi: 1 record per hari per user (UNIQUE KEY)
$conn->query("CREATE TABLE absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal DATE NOT NULL,
    waktu TIME NOT NULL,
    latitude DOUBLE NOT NULL,
    longitude DOUBLE NOT NULL,
    jarak_meter DOUBLE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Hadir',
    keterangan VARCHAR(255) NULL,
    UNIQUE KEY unik_absen (user_id, tanggal),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$users = [
    ['Administrator LLDIKTI','admin','admin123','admin',null,null,'aktif'],
    ['Budi Santoso','budi','mhs123','mahasiswa','20210001','Teknik Informatika','aktif'],
    ['Siti Rahayu','siti','mhs123','mahasiswa','20210002','Sistem Informasi','aktif'],
];
$stmt = $conn->prepare("INSERT INTO users (nama,username,password,password_plain,role,nim,prodi,status) VALUES (?,?,?,?,?,?,?,?)");
foreach ($users as $u) {
    $hash = password_hash($u[2], PASSWORD_DEFAULT);
    $stmt->bind_param('ssssssss',$u[0],$u[1],$hash,$u[2],$u[3],$u[4],$u[5],$u[6]);
    $stmt->execute();
}
$stmt->close();
?>
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Setup</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="bg-light d-flex justify-content-center align-items-center" style="min-height:100vh">
<div class="card shadow p-4" style="max-width:480px;width:100%">
    <h4 class="text-success">✅ Setup Berhasil!</h4>
    <table class="table table-bordered table-sm mt-3">
        <thead class="table-dark"><tr><th>Role</th><th>Username</th><th>Password</th></tr></thead>
        <tbody>
            <tr><td>Admin</td><td>admin</td><td>admin123</td></tr>
            <tr><td>Mahasiswa</td><td>budi</td><td>mhs123</td></tr>
            <tr><td>Mahasiswa</td><td>siti</td><td>mhs123</td></tr>
        </tbody>
    </table>
    <div class="alert alert-warning">⚠️ Hapus <strong>setup.php</strong> setelah ini!</div>
    <a href="login.php" class="btn btn-primary w-100">Ke Halaman Login</a>
</div></body></html>
