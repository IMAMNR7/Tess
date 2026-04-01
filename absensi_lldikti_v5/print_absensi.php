<?php
// ============================================================
// print_absensi.php — Cetak laporan absensi (Admin only)
// ============================================================
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}
require_once 'koneksi.php';

$filterUser   = $_GET['user_id'] ?? '';
$filterBulan  = $_GET['bulan']   ?? date('Y-m');
$filterStatus = $_GET['status']  ?? '';

// Parse bulan
$tglMulai = $filterBulan . '-01';
$tglAkhir = date('Y-m-t', strtotime($tglMulai));

$where = ["DATE(a.tanggal) BETWEEN '$tglMulai' AND '$tglAkhir'"];
if ($filterUser)   $where[] = "a.user_id = " . (int)$filterUser;
if ($filterStatus) $where[] = "a.status = '" . $conn->real_escape_string($filterStatus) . "'";
$whereStr = implode(' AND ', $where);

$data = $conn->query("
    SELECT a.*, u.nama, u.nim, u.prodi
    FROM absensi a
    JOIN users u ON u.id = a.user_id
    WHERE $whereStr
    ORDER BY u.nama, a.tanggal
");

$namaMhs = '';
if ($filterUser) {
    $r = $conn->query("SELECT nama FROM users WHERE id=" . (int)$filterUser);
    $namaMhs = $r->fetch_assoc()['nama'] ?? '';
}

$totalHadir   = 0; $totalTidak = 0;
$rows = [];
while ($row = $data->fetch_assoc()) {
    $rows[] = $row;
    if ($row['status'] === 'Hadir') $totalHadir++;
    else $totalTidak++;
}

$bulanLabel = date('F Y', strtotime($tglMulai));
$mahasiswaList = $conn->query("SELECT id, nama FROM users WHERE role='mahasiswa' ORDER BY nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Print Laporan Absensi — <?= $bulanLabel ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; margin:0; padding:0; box-sizing:border-box; }
        body { background:#f0f4f9; }

        /* Filter bar — tidak ikut print */
        .filter-bar {
            background:#1a3c6e; padding:14px 28px;
            display:flex; align-items:center; gap:12px; flex-wrap:wrap;
        }
        .filter-bar select, .filter-bar input {
            padding:7px 12px; border-radius:8px; border:none;
            font-size:.85rem; font-family:inherit;
        }
        .filter-bar .btn-filter {
            background:#e8b84b; color:#fff; border:none;
            border-radius:8px; padding:8px 18px; font-weight:700;
            cursor:pointer; font-family:inherit;
        }
        .filter-bar .btn-print {
            background:#059669; color:#fff; border:none;
            border-radius:8px; padding:8px 20px; font-weight:700;
            cursor:pointer; font-family:inherit; margin-left:auto;
        }
        .filter-bar .btn-back {
            background:rgba(255,255,255,.15); color:#fff; border:none;
            border-radius:8px; padding:8px 16px; font-weight:600;
            cursor:pointer; font-family:inherit; text-decoration:none;
            font-size:.85rem;
        }

        /* Konten print */
        .print-wrap { max-width:900px; margin:28px auto; background:#fff; border-radius:16px; padding:40px; box-shadow:0 4px 20px rgba(0,0,0,.08); }

        /* Kop surat */
        .kop { text-align:center; border-bottom:3px solid #1a3c6e; padding-bottom:18px; margin-bottom:24px; }
        .kop .instansi { font-size:1.1rem; font-weight:800; color:#1a3c6e; text-transform:uppercase; letter-spacing:.5px; }
        .kop .alamat   { font-size:.8rem; color:#555; margin-top:4px; }
        .kop .judul    { font-size:1rem; font-weight:700; margin-top:14px; color:#222; text-transform:uppercase; letter-spacing:.3px; }
        .kop .sub-judul{ font-size:.85rem; color:#555; margin-top:3px; }

        /* Info box */
        .info-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:22px; }
        .info-box  { background:#f0f4f9; border-radius:10px; padding:12px 16px; }
        .info-label{ font-size:.72rem; font-weight:700; color:#888; text-transform:uppercase; letter-spacing:.5px; }
        .info-val  { font-size:1rem; font-weight:700; color:#1a3c6e; margin-top:3px; }

        /* Stat boxes */
        .stat-row { display:flex; gap:12px; margin-bottom:22px; }
        .stat-box { flex:1; border-radius:10px; padding:14px 18px; text-align:center; }
        .stat-box.hadir   { background:#d1fae5; }
        .stat-box.ditolak { background:#fee2e2; }
        .stat-box.total   { background:#dbeafe; }
        .stat-num  { font-size:1.8rem; font-weight:800; }
        .stat-lbl  { font-size:.76rem; font-weight:600; margin-top:2px; color:#555; }
        .stat-box.hadir   .stat-num { color:#065f46; }
        .stat-box.ditolak .stat-num { color:#991b1b; }
        .stat-box.total   .stat-num { color:#1d4ed8; }

        /* Tabel */
        table { width:100%; border-collapse:collapse; }
        thead tr { background:#1a3c6e; color:#fff; }
        thead th { padding:10px 12px; font-size:.78rem; font-weight:700; text-align:left; text-transform:uppercase; letter-spacing:.4px; }
        tbody tr:nth-child(even) { background:#f8f9fc; }
        tbody tr:hover { background:#eef2ff; }
        tbody td { padding:9px 12px; font-size:.85rem; border-bottom:1px solid #eee; vertical-align:middle; }
        .badge-hadir   { background:#d1fae5; color:#065f46; border-radius:6px; padding:3px 9px; font-weight:700; font-size:.75rem; }
        .badge-ditolak { background:#fee2e2; color:#991b1b; border-radius:6px; padding:3px 9px; font-weight:700; font-size:.75rem; }

        /* Tanda tangan */
        .ttd-area { display:flex; justify-content:space-between; margin-top:40px; }
        .ttd-box  { text-align:center; width:200px; }
        .ttd-line { border-top:1.5px solid #333; margin-top:56px; padding-top:6px; font-size:.82rem; font-weight:600; }
        .ttd-label{ font-size:.78rem; color:#666; margin-bottom:4px; }

        .print-footer { text-align:center; font-size:.75rem; color:#aaa; margin-top:24px; border-top:1px solid #eee; padding-top:14px; }

        @media print {
            body { background:#fff; }
            .filter-bar { display:none !important; }
            .print-wrap { box-shadow:none; border-radius:0; margin:0; padding:30px; }
            thead tr { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .badge-hadir, .badge-ditolak { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
            .stat-box { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        }
    </style>
</head>
<body>

<!-- Filter Bar (tidak ikut print) -->
<div class="filter-bar">
    <a href="dashboard_admin.php" class="btn-back">← Kembali</a>
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
        <select name="user_id">
            <option value="">Semua Mahasiswa</option>
            <?php $mahasiswaList->data_seek(0); while ($m = $mahasiswaList->fetch_assoc()): ?>
            <option value="<?= $m['id'] ?>" <?= $filterUser == $m['id'] ? 'selected':'' ?>><?= htmlspecialchars($m['nama']) ?></option>
            <?php endwhile; ?>
        </select>
        <input type="month" name="bulan" value="<?= $filterBulan ?>">
        <select name="status">
            <option value="">Semua Status</option>
            <option value="Hadir"   <?= $filterStatus==='Hadir'?'selected':'' ?>>Hadir</option>
            <option value="Tidak Hadir" <?= $filterStatus==='Tidak Hadir'?'selected':'' ?>>Tidak Hadir</option>
            <option value="Ditolak" <?= $filterStatus==='Ditolak'?'selected':'' ?>>Ditolak</option>
        </select>
        <button type="submit" class="btn-filter">🔍 Filter</button>
    </form>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak / PDF</button>
</div>

<!-- Konten Print -->
<div class="print-wrap">

    <!-- Kop -->
    <div class="kop">
        <div class="instansi">Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah II</div>
        <div class="alamat">Jl. Srijaya No.883, Srijaya, Kec. Alang-Alang Lebar, Kota Palembang, Sumatera Selatan 30153</div>
        <div class="judul">Laporan Absensi Mahasiswa Magang</div>
        <div class="sub-judul">Periode: <?= $bulanLabel ?><?= $namaMhs ? ' &mdash; ' . htmlspecialchars($namaMhs) : '' ?></div>
    </div>

    <!-- Info -->
    <div class="info-grid">
        <div class="info-box">
            <div class="info-label">Periode</div>
            <div class="info-val"><?= $bulanLabel ?></div>
        </div>
        <div class="info-box">
            <div class="info-label">Mahasiswa</div>
            <div class="info-val"><?= $namaMhs ?: 'Semua' ?></div>
        </div>
        <div class="info-box">
            <div class="info-label">Dicetak</div>
            <div class="info-val"><?= date('d M Y, H:i') ?></div>
        </div>
    </div>

    <!-- Statistik -->
    <div class="stat-row">
        <div class="stat-box total">
            <div class="stat-num"><?= count($rows) ?></div>
            <div class="stat-lbl">Total Catatan</div>
        </div>
        <div class="stat-box hadir">
            <div class="stat-num"><?= $totalHadir ?></div>
            <div class="stat-lbl">Hari Hadir</div>
        </div>
        <div class="stat-box ditolak">
            <div class="stat-num"><?= $totalTidak ?></div>
            <div class="stat-lbl">Tidak/Ditolak</div>
        </div>
    </div>

    <!-- Tabel -->
    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Nama Mahasiswa</th>
                <th>NIM</th>
                <th>Program Studi</th>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Jarak</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if (count($rows) > 0): ?>
            <?php foreach ($rows as $i => $row): ?>
            <tr>
                <td style="color:#aaa"><?= $i+1 ?></td>
                <td><strong><?= htmlspecialchars($row['nama']) ?></strong></td>
                <td><?= htmlspecialchars($row['nim'] ?? '-') ?></td>
                <td style="font-size:.8rem"><?= htmlspecialchars($row['prodi'] ?? '-') ?></td>
                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                <td><?= date('H:i', strtotime($row['tanggal'])) ?></td>
                <td><?= $row['jarak_meter'] !== null ? number_format($row['jarak_meter'],0).' m' : '-' ?></td>
                <td>
                    <?php if ($row['status'] === 'Hadir'): ?>
                        <span class="badge-hadir">✓ Hadir</span>
                    <?php elseif ($row['status'] === 'Tidak Hadir'): ?>
                        <span class="badge-ditolak">✗ Tidak Hadir</span>
                    <?php else: ?>
                        <span class="badge-ditolak">✗ Ditolak</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8" style="text-align:center;padding:24px;color:#aaa">Tidak ada data absensi pada periode ini.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Tanda Tangan -->
    <div class="ttd-area">
        <div class="ttd-box">
            <div class="ttd-label">Mengetahui,</div>
            <div class="ttd-label">Koordinator LLDIKTI Wilayah II</div>
            <div class="ttd-line">(.................................)</div>
        </div>
        <div class="ttd-box">
            <div class="ttd-label">Palembang, <?= date('d F Y') ?></div>
            <div class="ttd-label">Administrator</div>
            <div class="ttd-line">(.................................)</div>
        </div>
    </div>

    <div class="print-footer">
        Dokumen ini dicetak secara otomatis oleh Sistem Informasi Absensi Mahasiswa Magang LLDIKTI Wilayah II Palembang
    </div>
</div>

</body>
</html>
