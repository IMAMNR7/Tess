<?php
// ============================================================
// dashboard_mahasiswa.php
// ============================================================
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mahasiswa') {
    header('Location: login.php'); exit;
}
// Pastikan perhitungan waktu sinkron dengan zona waktu lokal Jakarta
date_default_timezone_set('Asia/Jakarta');
require_once 'koneksi.php';

$userId = $_SESSION['user_id'];

// Info mahasiswa
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Riwayat absensi hari ini
$today = date('Y-m-d');
$riwayatHariIni = $conn->query("
    SELECT * FROM absensi
    WHERE user_id = $userId AND DATE(tanggal) = '$today'
    ORDER BY tanggal DESC
");

// Riwayat 7 hari terakhir
$riwayat7 = $conn->query("
    SELECT * FROM absensi
    WHERE user_id = $userId AND tanggal >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY tanggal DESC
    LIMIT 20
");

// Cek sudah absen hadir hari ini
$cekHadir = $conn->query("
    SELECT id FROM absensi WHERE user_id = $userId AND DATE(tanggal) = '$today' AND status = 'Hadir'
")->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa — Absensi LLDIKTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary:#1a3c6e; --accent:#e8b84b; }
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f0f4f9; }

        .topbar {
            background: linear-gradient(135deg, #0d2347, #1a3c6e);
            padding: 14px 28px;
            display: flex; align-items: center; justify-content: space-between;
            color: #fff;
            box-shadow: 0 2px 12px rgba(0,0,0,.2);
        }
        .topbar .brand { font-weight: 800; font-size: 1.05rem; display: flex; align-items: center; gap: 10px; }
        .topbar .brand-icon { background: var(--accent); border-radius: 10px; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; }

        .wrapper { max-width: 880px; margin: 0 auto; padding: 28px 16px; }

        /* Profile card */
        .profile-card {
            background: linear-gradient(135deg, #1a3c6e, #2a5ca8);
            border-radius: 20px; color: #fff;
            padding: 28px 30px;
            display: flex; align-items: center; gap: 22px;
            margin-bottom: 24px;
            box-shadow: 0 8px 28px rgba(26,60,110,.28);
        }
        .profile-avatar {
            width: 70px; height: 70px;
            background: rgba(255,255,255,.15);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; flex-shrink: 0;
            border: 3px solid rgba(255,255,255,.25);
        }
        .profile-name { font-size: 1.2rem; font-weight: 800; }
        .profile-meta { font-size: .82rem; opacity: .75; margin-top: 3px; }
        .badge-role   { background: var(--accent); color: #fff; border-radius: 8px; padding: 3px 10px; font-size: .75rem; font-weight: 700; margin-top: 8px; display: inline-block; }

        /* Absen card */
        .absen-card {
            background: #fff; border-radius: 20px;
            padding: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,.07);
            text-align: center;
            margin-bottom: 24px;
        }
        .absen-title { font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 6px; }
        .absen-date  { font-size: .85rem; color: #888; margin-bottom: 24px; }
        .absen-loc   { font-size: .8rem; color: #666; margin-top: 12px; }

        /* GPS Button */
        .btn-absen {
            background: linear-gradient(135deg, #059669, #10b981);
            border: none; color: #fff;
            border-radius: 50px;
            padding: 16px 48px;
            font-size: 1rem; font-weight: 700;
            letter-spacing: .3px;
            box-shadow: 0 8px 24px rgba(5,150,105,.35);
            transition: transform .2s, box-shadow .2s;
            cursor: pointer;
        }
        .btn-absen:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(5,150,105,.4); }
        .btn-absen:disabled { background: #9ca3af; box-shadow: none; cursor: not-allowed; }

        /* Status overlay */
        #status-box { margin-top: 20px; }
        .status-loading { color: #888; font-size: .9rem; }

        /* Map preview */
        #map-wrap { margin-top: 20px; border-radius: 14px; overflow: hidden; height: 220px; display: none; }
        #map-wrap iframe { width: 100%; height: 100%; border: none; }

        /* Result alert */
        .result-alert {
            border-radius: 14px; padding: 16px 20px;
            display: none; text-align: left;
            margin-top: 16px;
        }
        .result-alert.hadir   { background: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
        .result-alert.ditolak { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        /* Table */
        .card { border-radius: 16px; border: none; box-shadow: 0 4px 16px rgba(0,0,0,.06); }
        .card-header { background: #fff; border-bottom: 1px solid #eef0f5; border-radius: 16px 16px 0 0 !important; padding: 16px 20px; }
        .table th { font-size: .77rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #888; background: #f8f9fc; }
        .table td { font-size: .87rem; vertical-align: middle; }
        .badge-hadir   { background: #d1fae5; color: #065f46; border-radius: 8px; padding: 3px 10px; font-weight: 600; font-size: .77rem; }
        .badge-ditolak { background: #fee2e2; color: #991b1b; border-radius: 8px; padding: 3px 10px; font-weight: 600; font-size: .77rem; }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-building-check"></i></div>
        LLDIKTI Wilayah II — Absensi Magang
    </div>
    <a href="logout.php" class="btn btn-sm btn-outline-light rounded-3">
        <i class="bi bi-box-arrow-left me-1"></i>Logout
    </a>
</div>

<div class="wrapper">

    <!-- Profil -->
    <div class="profile-card">
        <div class="profile-avatar"><i class="bi bi-person-fill"></i></div>
        <div>
            <div class="profile-name"><?= htmlspecialchars($user['nama']) ?></div>
            <div class="profile-meta">
                <?= $user['nim'] ? 'NIM: ' . htmlspecialchars($user['nim']) . ' &bull; ' : '' ?>
                <?= htmlspecialchars($user['prodi'] ?? 'Mahasiswa Magang') ?>
            </div>
            <span class="badge-role"><i class="bi bi-mortarboard me-1"></i>Mahasiswa</span>
        </div>
        <div class="ms-auto text-end">
            <div style="font-size:.8rem;opacity:.7">Hari ini</div>
            <div style="font-size:1.1rem;font-weight:700"><?= date('d M Y') ?></div>
            <div style="font-size:.85rem;opacity:.8" id="live-clock"><?= date('H:i:s') ?></div>
        </div>
    </div>
<script>
    // set time server-side untuk sinkron dengan real-time backend
    const serverTime = new Date('<?= date('c') ?>');
    setInterval(() => {
        serverTime.setSeconds(serverTime.getSeconds() + 1);
        document.getElementById('live-clock').textContent = serverTime.toLocaleTimeString('id-ID');
    }, 1000);
</script>

    <!-- Absen Card -->
    <div class="absen-card">
        <div class="absen-title"><i class="bi bi-geo-alt-fill me-2 text-danger"></i>Absensi Kehadiran</div>
        <div class="absen-date"><?= date('l, d F Y') ?></div>

        <?php if ($cekHadir): ?>
        <div class="result-alert hadir" style="display:block;text-align:center">
            <div style="font-size:2rem">✅</div>
            <strong>Anda sudah tercatat HADIR hari ini.</strong><br>
            <small>Terima kasih, selamat bekerja!</small>
        </div>
        <?php else: ?>
        <button class="btn-absen" id="btnAbsen" onclick="mulaiAbsen()">
            <i class="bi bi-geo-alt me-2"></i>Klik untuk Absen Hadir
        </button>
        <div class="absen-loc"><i class="bi bi-pin-map me-1"></i>Lokasi LLDIKTI: Jl. Srijaya No.883, Alang-Alang Lebar, Palembang &bull; Radius <strong>2000m</strong></div>

        <div id="status-box"></div>
        <div id="map-wrap"></div>
        <div class="result-alert" id="result-alert"></div>
        <?php endif; ?>
    </div>

    <!-- Riwayat Hari Ini -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-clock-history me-2" style="color:var(--primary)"></i><strong>Absensi Hari Ini</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th class="ps-4">Waktu</th><th>Koordinat</th><th>Jarak</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php if ($riwayatHariIni->num_rows > 0): ?>
                        <?php while ($r = $riwayatHariIni->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4"><?= htmlspecialchars($r['waktu']) ?></td>
                            <td style="font-size:.8rem">
                                <a href="https://maps.google.com/?q=<?= $r['latitude'] ?>,<?= $r['longitude'] ?>" target="_blank" class="text-decoration-none">
                                    <i class="bi bi-geo-alt text-danger"></i>
                                    <?= number_format($r['latitude'],6) ?>, <?= number_format($r['longitude'],6) ?>
                                </a>
                            </td>
                            <td><?= $r['jarak_meter'] !== null ? number_format($r['jarak_meter'],1).' m' : '-' ?></td>
                            <td><?= $r['status']==='Hadir' ? '<span class="badge-hadir">✓ Hadir</span>' : '<span class="badge-ditolak">✗ Ditolak</span>' ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada absensi hari ini.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Riwayat 7 Hari -->
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-calendar3 me-2" style="color:var(--primary)"></i><strong>Riwayat 7 Hari Terakhir</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th class="ps-4">Tanggal</th><th>Waktu</th><th>Jarak</th><th>Status</th><th>Keterangan</th></tr></thead>
                    <tbody>
                    <?php if ($riwayat7->num_rows > 0): ?>
                        <?php while ($r = $riwayat7->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-4"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                            <td><?= htmlspecialchars(substr($r['waktu'], 0, 5)) ?></td>
                            <td><?= $r['jarak_meter'] !== null ? number_format($r['jarak_meter'],1).' m' : '-' ?></td>
                            <td><?= $r['status']==='Hadir' ? '<span class="badge-hadir">✓ Hadir</span>' : '<span class="badge-ditolak">✗ Ditolak</span>' ?></td>
                            <td style="font-size:.82rem;color:#666"><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Belum ada riwayat.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div><!-- /wrapper -->

<script>
// ============================================================
// GPS Absensi
// ============================================================
function mulaiAbsen() {
    const btn = document.getElementById('btnAbsen');
    const statusBox = document.getElementById('status-box');
    const resultAlert = document.getElementById('result-alert');

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengambil lokasi GPS...';
    statusBox.innerHTML = '<div class="status-loading mt-2"><i class="bi bi-broadcast me-1"></i>Meminta akses GPS dari perangkat Anda...</div>';
    resultAlert.style.display = 'none';

    if (!navigator.geolocation) {
        tampilkanError('Browser Anda tidak mendukung Geolocation API.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-geo-alt me-2"></i>Klik untuk Absen Hadir';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        posisiBerhasil,
        posisiGagal,
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
}

function posisiBerhasil(position) {
    const lat = position.coords.latitude;
    const lng = position.coords.longitude;
    const acc = Math.round(position.coords.accuracy);

    // Koordinat LLDIKTI (hardcoded sesuai koneksi.php)
    const lldiktiLat = -2.964400;
    const lldiktiLng = 104.717600;

    const statusBox = document.getElementById('status-box');
    statusBox.innerHTML = `
        <div class="status-loading mt-2">
            <i class="bi bi-check-circle text-success me-1"></i>
            Lokasi ditemukan: <strong>${lat.toFixed(6)}, ${lng.toFixed(6)}</strong>
            (akurasi ±${acc}m)<br>
            <small class="text-muted">Mengirim ke server untuk validasi jarak...</small>
        </div>`;

    // Tampilkan peta mini — titik lokasi user
    const mapWrap = document.getElementById('map-wrap');
    mapWrap.style.display = 'block';
    mapWrap.innerHTML = `<iframe 
        src="https://maps.google.com/maps?q=${lat},${lng}&z=16&output=embed" 
        allowfullscreen loading="lazy"></iframe>`;

    // Kirim ke server via fetch
    kirimAbsensi(lat, lng);
}

function posisiGagal(err) {
    const pesan = {
        1: 'Izin akses lokasi ditolak. Silakan aktifkan izin lokasi di browser.',
        2: 'Lokasi tidak dapat ditentukan. Pastikan GPS aktif.',
        3: 'Waktu habis saat mengambil lokasi. Coba lagi.',
    };
    tampilkanError(pesan[err.code] || 'Gagal mendapatkan lokasi GPS.');

    const btn = document.getElementById('btnAbsen');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-geo-alt me-2"></i>Coba Lagi';
}

function kirimAbsensi(lat, lng) {
    const formData = new FormData();
    formData.append('latitude',  lat);
    formData.append('longitude', lng);

    fetch('proses_absen.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            const alert = document.getElementById('result-alert');
            const btn   = document.getElementById('btnAbsen');
            document.getElementById('status-box').innerHTML = '';

            if (data.sudah_absen) {
                alert.className = 'result-alert hadir';
                alert.innerHTML = `
                    <div class="d-flex align-items-start gap-3">
                        <div style="font-size:2rem">ℹ️</div>
                        <div>
                            <strong>Anda sudah absen hari ini</strong><br>
                            Status tercatat: <strong>${data.status_sebelumnya}</strong><br>
                            <small>Absensi hanya dapat dilakukan 1 kali per hari.</small>
                        </div>
                    </div>`;
                alert.style.display = 'block';
                btn.innerHTML = '✅ Sudah Absen Hari Ini';
                setTimeout(() => location.reload(), 1500);
                return;
            }

            if (data.success && data.status === 'Hadir') {
                alert.className = 'result-alert hadir';
                alert.innerHTML = `
                    <div class="d-flex align-items-start gap-3">
                        <div style="font-size:2rem">✅</div>
                        <div>
                            <strong>Absensi Berhasil — HADIR</strong><br>
                            Jarak dari LLDIKTI: <strong>${data.jarak} meter</strong><br>
                            <small>Waktu: ${data.waktu}</small>
                        </div>
                    </div>`;
                alert.style.display = 'block';
                btn.innerHTML = '✅ Sudah Absen Hadir';
                setTimeout(() => location.reload(), 2000);
            } else {
                alert.className = 'result-alert ditolak';
                alert.innerHTML = `
                    <div class="d-flex align-items-start gap-3">
                        <div style="font-size:2rem">⛔</div>
                        <div>
                            <strong>Absensi Ditolak — Di Luar Area</strong><br>
                            ${data.message}<br>
                            Jarak Anda dari LLDIKTI: <strong>${data.jarak} meter</strong><br>
                            <small class="text-muted">Batas radius: 2000 meter dari gedung LLDIKTI</small>
                        </div>
                    </div>`;
                alert.style.display = 'block';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-geo-alt me-2"></i>Coba Lagi';
            }
        })
        .catch(err => {
            tampilkanError('Gagal menghubungi server: ' + err.message);
            const btn = document.getElementById('btnAbsen');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt me-2"></i>Coba Lagi';
        });
}

function tampilkanError(pesan) {
    document.getElementById('status-box').innerHTML = `
        <div class="alert alert-danger mt-3 rounded-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>${pesan}
        </div>`;
}
</script>
</body>
</html>
