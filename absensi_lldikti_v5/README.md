# Sistem Informasi Absensi Mahasiswa Magang
## LLDIKTI Wilayah II Palembang
### Berbasis Web dengan Geolocation & GPS Tracking

---

## 📁 Struktur Folder

```
absensi_lldikti/
├── koneksi.php              ← Koneksi DB + fungsi Haversine + konstanta koordinat
├── login.php                ← Halaman login (admin & mahasiswa)
├── logout.php               ← Proses logout
├── setup.php                ← Inisialisasi database & user awal (jalankan sekali)
├── dashboard_admin.php      ← Dashboard admin (lihat absensi, kelola mahasiswa)
├── dashboard_mahasiswa.php  ← Dashboard mahasiswa + tombol absen GPS
├── proses_absen.php         ← Backend: validasi jarak & simpan absensi (JSON)
├── database.sql             ← Script SQL manual (opsional)
└── README.md                ← Dokumentasi ini
```

---

## ⚙️ Cara Instalasi

### 1. Persyaratan
- XAMPP / Laragon (PHP 7.4+ & MySQL)
- Browser dengan dukungan Geolocation API (Chrome, Firefox, Edge)
- Koneksi HTTPS atau localhost (GPS hanya berfungsi di HTTPS atau localhost)

### 2. Langkah Setup

**a. Copy folder ke htdocs / www**
```
XAMPP  → C:\xampp\htdocs\absensi_lldikti\
Laragon → C:\laragon\www\absensi_lldikti\
```

**b. Buat database di phpMyAdmin**
- Buka phpMyAdmin: http://localhost/phpmyadmin
- Buat database baru bernama: `absensi_lldikti`

**c. Jalankan Setup**
- Buka browser: http://localhost/absensi_lldikti/setup.php
- Script akan membuat tabel dan user otomatis
- **Hapus setup.php setelah selesai!**

---

## 🔐 Akun Default

| Role       | Username | Password |
|------------|----------|----------|
| Admin      | admin    | admin123 |
| Mahasiswa  | budi     | mhs123   |
| Mahasiswa  | siti     | mhs123   |

---

## 🗺️ Koordinat LLDIKTI

- **Latitude:**  -2.990934
- **Longitude:** 104.756554
- **Radius:**    100 meter

Untuk mengubah koordinat/radius, edit file `koneksi.php`:
```php
define('LLDIKTI_LAT', -2.990934);
define('LLDIKTI_LNG', 104.756554);
define('RADIUS_METER', 100);
```

---

## 🧮 Formula Haversine

Digunakan di `koneksi.php` → fungsi `hitungJarak()`:

```
a = sin²(Δlat/2) + cos(lat1) × cos(lat2) × sin²(Δlon/2)
c = 2 × atan2(√a, √(1−a))
d = R × c   (R = 6,371,000 meter)
```

---

## 📱 Fitur

### Admin
- ✅ Login dengan session
- ✅ Lihat semua data absensi + filter (mahasiswa, status, tanggal)
- ✅ Tambah mahasiswa baru
- ✅ Hapus mahasiswa
- ✅ Statistik (total mahasiswa, hadir, ditolak)
- ✅ Link Google Maps dari koordinat absensi

### Mahasiswa
- ✅ Login dengan session
- ✅ Tombol "Absen Hadir" dengan GPS
- ✅ Validasi radius 100m dengan Haversine
- ✅ Preview peta mini lokasi
- ✅ Riwayat absensi hari ini & 7 hari terakhir
- ✅ Deteksi sudah absen hari ini

---

## 🔧 Konfigurasi Database (`koneksi.php`)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // sesuaikan password MySQL Anda
define('DB_NAME', 'absensi_lldikti');
```

---

## ⚠️ Catatan Penting

1. **GPS di localhost** → Berfungsi normal di Chrome/Firefox
2. **GPS di server** → Wajib menggunakan HTTPS
3. **Hapus setup.php** setelah inisialisasi untuk keamanan
4. Password di-hash dengan `password_hash()` (bcrypt)
5. Semua input di-sanitasi dengan prepared statements
