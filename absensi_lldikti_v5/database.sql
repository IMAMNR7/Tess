-- ============================================================
-- Database: absensi_lldikti
-- Sistem Informasi Absensi Mahasiswa Magang
-- LLDIKTI Wilayah II Palembang
-- ============================================================

CREATE DATABASE IF NOT EXISTS absensi_lldikti CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_lldikti;

-- Tabel users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'mahasiswa') NOT NULL DEFAULT 'mahasiswa',
    nim VARCHAR(20) NULL,
    prodi VARCHAR(100) NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel absensi
CREATE TABLE IF NOT EXISTS absensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    latitude DOUBLE NOT NULL,
    longitude DOUBLE NOT NULL,
    jarak_meter DOUBLE NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Hadir',
    keterangan VARCHAR(255) NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- Data awal: Admin & Mahasiswa contoh
-- Password admin: admin123
-- Password mahasiswa: mhs123
-- ============================================================

INSERT INTO users (nama, username, password, role, nim, prodi) VALUES
(
    'Administrator LLDIKTI',
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin',
    NULL,
    NULL
),
(
    'Budi Santoso',
    'budi',
    '$2y$10$TKh8H1.PkziVdsk5x9C8/eU.VWsWe5FEvL0Ku3sOdvBzqOtAhHCi', -- password: mhs123
    'mahasiswa',
    '20210001',
    'Teknik Informatika'
),
(
    'Siti Rahayu',
    'siti',
    '$2y$10$TKh8H1.PkziVdsk5x9C8/eU.VWsWe5FEvL0Ku3sOdvBzqOtAhHCi', -- password: mhs123
    'mahasiswa',
    '20210002',
    'Sistem Informasi'
);

-- Catatan: Jalankan script PHP berikut sekali untuk generate password yang benar:
-- <?php
-- echo password_hash('admin123', PASSWORD_DEFAULT); // untuk admin
-- echo password_hash('mhs123', PASSWORD_DEFAULT);   // untuk mahasiswa
-- ?>
-- Lalu update tabel users dengan hash yang dihasilkan.
-- Atau gunakan insert_users.php yang disertakan.
