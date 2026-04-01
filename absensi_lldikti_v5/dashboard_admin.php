<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php'); exit;
}
require_once 'koneksi.php';

// ---- Hapus mahasiswa ----
if (isset($_GET['hapus']) && is_numeric($_GET['hapus'])) {
    $hid = (int)$_GET['hapus'];
    $conn->query("DELETE FROM users WHERE id=$hid AND role='mahasiswa'");
    header('Location: dashboard_admin.php?notif=hapus'); exit;
}

// ---- Setujui akun ----
if (isset($_GET['setujui']) && is_numeric($_GET['setujui'])) {
    $sid = (int)$_GET['setujui'];
    $conn->query("UPDATE users SET status='aktif' WHERE id=$sid AND role='mahasiswa'");
    header('Location: dashboard_admin.php?tab=pending&notif=setujui'); exit;
}

// ---- Tolak / nonaktifkan akun ----
if (isset($_GET['tolak']) && is_numeric($_GET['tolak'])) {
    $tid = (int)$_GET['tolak'];
    $conn->query("UPDATE users SET status='nonaktif' WHERE id=$tid AND role='mahasiswa'");
    header('Location: dashboard_admin.php?tab=pending&notif=tolak'); exit;
}

// ---- Tambah mahasiswa ----
$errTambah = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'tambah') {
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $nim      = trim($_POST['nim'] ?? '');
    $prodi    = trim($_POST['prodi'] ?? '');
    if (empty($nama)||empty($username)||empty($password)) {
        $errTambah = 'Nama, username, dan password wajib diisi.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE username=?");
        $chk->bind_param('s',$username); $chk->execute(); $chk->store_result();
        if ($chk->num_rows > 0) {
            $errTambah = 'Username sudah digunakan.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (nama,username,password,password_plain,role,nim,prodi,status) VALUES (?,?,?,?,'mahasiswa',?,?,'aktif')");
            $ins->bind_param('ssssss',$nama,$username,$hash,$password,$nim,$prodi);
            $ins->execute(); $ins->close();
            header('Location: dashboard_admin.php?notif=tambah'); exit;
        }
        $chk->close();
    }
}

// ---- Filter absensi ----
$filterUser   = $_GET['user_id'] ?? '';
$filterStatus = $_GET['status']  ?? '';
$filterTgl    = $_GET['tgl']     ?? '';
$filterBulan  = $_GET['bulan']   ?? '';
$activeTab    = $_GET['tab']     ?? 'absensi';

$where = ["1=1"];
if ($filterUser)   $where[] = "a.user_id=".(int)$filterUser;
if ($filterStatus) $where[] = "a.status='".$conn->real_escape_string($filterStatus)."'";
if ($filterTgl)    $where[] = "a.tanggal='".$conn->real_escape_string($filterTgl)."'";
if ($filterBulan)  $where[] = "DATE_FORMAT(a.tanggal,'%Y-%m')='".$conn->real_escape_string($filterBulan)."'";
$whereStr = implode(' AND ',$where);

$absensiList = $conn->query("
    SELECT a.*, u.nama, u.nim, u.prodi,
           CONCAT(a.tanggal,' ',a.waktu) as tanggal_waktu
    FROM absensi a JOIN users u ON u.id=a.user_id
    WHERE $whereStr ORDER BY a.tanggal DESC, u.nama
");

// Stats
$totalHadir   = $conn->query("SELECT COUNT(*) c FROM absensi WHERE status='Hadir'")->fetch_assoc()['c'];
$totalDitolak = $conn->query("SELECT COUNT(*) c FROM absensi WHERE status='Ditolak'")->fetch_assoc()['c'];
$totalMhs     = $conn->query("SELECT COUNT(*) c FROM users WHERE role='mahasiswa' AND status='aktif'")->fetch_assoc()['c'];
$totalPending = $conn->query("SELECT COUNT(*) c FROM users WHERE role='mahasiswa' AND status='pending'")->fetch_assoc()['c'];

$mahasiswaAktif  = $conn->query("SELECT * FROM users WHERE role='mahasiswa' AND status='aktif' ORDER BY created_at DESC");
$mahasiswaPending= $conn->query("SELECT * FROM users WHERE role='mahasiswa' AND status='pending' ORDER BY created_at DESC");
$mhsFilterList   = $conn->query("SELECT id,nama FROM users WHERE role='mahasiswa' AND status='aktif' ORDER BY nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard Admin — Absensi LLDIKTI</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{--primary:#1a3c6e;--accent:#e8b84b;}
*{font-family:'Plus Jakarta Sans',sans-serif;}
body{background:#f0f4f9;margin:0;}
/* Sidebar */
.sidebar{width:240px;min-height:100vh;background:linear-gradient(180deg,#0d2347,#1a3c6e);position:fixed;left:0;top:0;display:flex;flex-direction:column;z-index:100;}
.sb-brand{padding:22px 18px 14px;border-bottom:1px solid rgba(255,255,255,.1);}
.sb-logo{width:42px;height:42px;background:var(--accent);border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;color:#fff;margin-bottom:9px;}
.sb-name{color:#fff;font-weight:700;font-size:.9rem;line-height:1.3;}
.sb-sub{color:rgba(255,255,255,.45);font-size:.7rem;}
.sb-nav{padding:14px 10px;flex:1;}
.nav-lbl{color:rgba(255,255,255,.32);font-size:.67rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:8px 10px 3px;}
.nav-item a{display:flex;align-items:center;gap:9px;color:rgba(255,255,255,.65);text-decoration:none;padding:9px 12px;border-radius:9px;font-size:.84rem;font-weight:500;transition:all .15s;}
.nav-item a:hover,.nav-item a.active{background:rgba(255,255,255,.12);color:#fff;}
.nav-item .badge-pending{background:#ef4444;color:#fff;border-radius:20px;padding:1px 7px;font-size:.68rem;font-weight:700;margin-left:auto;}
.sb-footer{padding:12px 18px;border-top:1px solid rgba(255,255,255,.1);}
.sb-footer .u-info{color:rgba(255,255,255,.6);font-size:.78rem;}
.sb-footer .u-name{color:#fff;font-weight:600;font-size:.85rem;}
/* Main */
.main{margin-left:240px;padding:26px 28px;min-height:100vh;}
/* Stat cards */
.stat-card{background:#fff;border-radius:15px;border:none;padding:20px 22px;display:flex;align-items:center;gap:16px;box-shadow:0 3px 14px rgba(0,0,0,.06);}
.stat-icon{width:50px;height:50px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;}
.stat-val{font-size:1.7rem;font-weight:800;line-height:1;}
.stat-lbl{font-size:.78rem;color:#888;margin-top:3px;}
/* Cards */
.card{border-radius:14px;border:none;box-shadow:0 3px 14px rgba(0,0,0,.05);}
.card-header{background:#fff;border-bottom:1px solid #eef0f5;border-radius:14px 14px 0 0!important;padding:16px 20px;}
/* Table */
.table th{font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:#888;background:#f8f9fc;border-top:none;}
.table td{font-size:.85rem;vertical-align:middle;}
.table tbody tr:hover{background:#f5f7ff;}
/* Badges */
.b-hadir{background:#d1fae5;color:#065f46;border-radius:7px;padding:3px 10px;font-weight:700;font-size:.75rem;}
.b-ditolak{background:#fee2e2;color:#991b1b;border-radius:7px;padding:3px 10px;font-weight:700;font-size:.75rem;}
.b-pending{background:#fef3c7;color:#92400e;border-radius:7px;padding:3px 10px;font-weight:700;font-size:.75rem;}
.b-aktif{background:#d1fae5;color:#065f46;border-radius:7px;padding:3px 10px;font-weight:700;font-size:.75rem;}
/* Tabs */
.nav-tabs .nav-link{font-size:.85rem;font-weight:600;color:#666;border:none;padding:9px 18px;border-radius:10px 10px 0 0;}
.nav-tabs .nav-link.active{color:var(--primary);background:#fff;border-bottom:2px solid var(--primary);}
/* Buttons */
.btn-prim{background:linear-gradient(135deg,var(--primary),#2a5ca8);color:#fff;border:none;border-radius:9px;font-weight:600;font-size:.83rem;}
.btn-prim:hover{color:#fff;opacity:.9;}
.pw-cell{font-family:monospace;background:#f0f4f9;border-radius:6px;padding:2px 8px;font-size:.82rem;color:#333;letter-spacing:.5px;}
/* Page header */
.pg-title{font-size:1.4rem;font-weight:800;color:var(--primary);}
.pg-sub{font-size:.82rem;color:#888;}
</style>
</head>
<body>

<div class="sidebar">
  <div class="sb-brand">
    <div class="sb-logo"><i class="bi bi-building-check"></i></div>
    <div class="sb-name">LLDIKTI Wil. II</div>
    <div class="sb-sub">Sistem Absensi Magang</div>
  </div>
  <div class="sb-nav">
    <div class="nav-lbl">Menu Utama</div>
    <div class="nav-item"><a href="?tab=absensi" class="<?= $activeTab==='absensi'?'active':'' ?>"><i class="bi bi-calendar-check"></i> Data Absensi</a></div>
    <div class="nav-item"><a href="?tab=mahasiswa" class="<?= $activeTab==='mahasiswa'?'active':'' ?>"><i class="bi bi-people-fill"></i> Mahasiswa Aktif</a></div>
    <div class="nav-item"><a href="?tab=pending" class="<?= $activeTab==='pending'?'active':'' ?>"><i class="bi bi-person-check"></i> Persetujuan Akun <?= $totalPending>0?"<span class='badge-pending'>$totalPending</span>":'' ?></a></div>
    <div class="nav-item"><a href="?tab=tambah" class="<?= $activeTab==='tambah'?'active':'' ?>"><i class="bi bi-person-plus-fill"></i> Tambah Mahasiswa</a></div>
    <div class="nav-item"><a href="print_absensi.php" target="_blank"><i class="bi bi-printer-fill"></i> Print Laporan</a></div>
  </div>
  <div class="sb-footer">
    <div class="u-info">Login sebagai</div>
    <div class="u-name"><?= htmlspecialchars($_SESSION['user_nama']) ?></div>
    <a href="logout.php" class="btn btn-sm btn-outline-light w-100 mt-2 rounded-3"><i class="bi bi-box-arrow-left me-1"></i>Logout</a>
  </div>
</div>

<div class="main">

<?php
$notifMap = [
    'hapus'=>['danger','🗑️ Mahasiswa berhasil dihapus.'],
    'tambah'=>['success','✅ Mahasiswa berhasil ditambahkan.'],
    'setujui'=>['success','✅ Akun mahasiswa berhasil disetujui dan diaktifkan.'],
    'tolak'=>['warning','⚠️ Akun mahasiswa ditolak dan dinonaktifkan.'],
];
if (isset($_GET['notif']) && isset($notifMap[$_GET['notif']])):
    [$cls,$msg] = $notifMap[$_GET['notif']];
?>
<div class="alert alert-<?=$cls?> alert-dismissible fade show rounded-3 mb-3">
    <?=$msg?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($errTambah): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-3">
    ⚠️ <?=htmlspecialchars($errTambah)?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
  <div>
    <div class="pg-title">Dashboard Admin</div>
    <div class="pg-sub">Sistem Absensi Mahasiswa Magang — LLDIKTI Wilayah II Palembang</div>
  </div>
  <div class="text-end">
    <div style="font-size:.8rem;color:#aaa">Hari &amp; Waktu</div>
    <div style="font-size:.95rem;font-weight:700;color:#333" id="live-clock-admin"></div>
  </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card"><div class="stat-icon" style="background:#dbeafe"><i class="bi bi-people-fill" style="color:#1d4ed8"></i></div><div><div class="stat-val" style="color:#1d4ed8"><?=$totalMhs?></div><div class="stat-lbl">Mahasiswa Aktif</div></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card"><div class="stat-icon" style="background:#fef3c7"><i class="bi bi-hourglass-split" style="color:#d97706"></i></div><div><div class="stat-val" style="color:#d97706"><?=$totalPending?></div><div class="stat-lbl">Menunggu Persetujuan</div></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card"><div class="stat-icon" style="background:#d1fae5"><i class="bi bi-check-circle-fill" style="color:#059669"></i></div><div><div class="stat-val" style="color:#059669"><?=$totalHadir?></div><div class="stat-lbl">Total Hadir</div></div></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card"><div class="stat-icon" style="background:#fee2e2"><i class="bi bi-x-circle-fill" style="color:#dc2626"></i></div><div><div class="stat-val" style="color:#dc2626"><?=$totalDitolak?></div><div class="stat-lbl">Total Ditolak</div></div></div>
  </div>
</div>

<!-- ============================
     TAB: DATA ABSENSI
     ============================ -->
<?php if ($activeTab === 'absensi'): ?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div><i class="bi bi-calendar-check me-2" style="color:var(--primary)"></i><strong>Data Absensi Harian</strong>
    <small class="text-muted ms-2" style="font-size:.75rem">(1 catatan per mahasiswa per hari)</small></div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
        <input type="hidden" name="tab" value="absensi">
        <select name="user_id" class="form-select form-select-sm" style="width:150px">
          <option value="">Semua Mahasiswa</option>
          <?php $mhsFilterList->data_seek(0); while($m=$mhsFilterList->fetch_assoc()): ?>
          <option value="<?=$m['id']?>" <?=$filterUser==$m['id']?'selected':''?>><?=htmlspecialchars($m['nama'])?></option>
          <?php endwhile; ?>
        </select>
        <select name="status" class="form-select form-select-sm" style="width:120px">
          <option value="">Semua Status</option>
          <option value="Hadir"   <?=$filterStatus==='Hadir'?'selected':''?>>Hadir</option>
          <option value="Ditolak" <?=$filterStatus==='Ditolak'?'selected':''?>>Ditolak</option>
        </select>
        <input type="date" name="tgl" class="form-control form-control-sm" style="width:140px" value="<?=htmlspecialchars($filterTgl)?>">
        <input type="month" name="bulan" class="form-control form-control-sm" style="width:140px" value="<?=htmlspecialchars($filterBulan)?>">
        <button class="btn btn-sm btn-prim"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="?tab=absensi" class="btn btn-sm btn-outline-secondary rounded-3">Reset</a>
      </form>
      <a href="print_absensi.php<?=$filterBulan?"?bulan=$filterBulan":''?><?=$filterUser?"&user_id=$filterUser":''?>" target="_blank" class="btn btn-sm btn-success rounded-3">
        <i class="bi bi-printer me-1"></i>Print
      </a>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-4" style="width:40px">#</th>
          <th>Mahasiswa</th>
          <th>NIM</th>
          <th>Tanggal</th>
          <th>Waktu Absen</th>
          <th>Koordinat</th>
          <th>Jarak</th>
          <th>Status</th>
        </tr></thead>
        <tbody>
        <?php
        $no=1;
        if ($absensiList && $absensiList->num_rows > 0):
            while ($row=$absensiList->fetch_assoc()):
        ?>
        <tr>
          <td class="ps-4 text-muted"><?=$no++?></td>
          <td><strong><?=htmlspecialchars($row['nama'])?></strong><br><small class="text-muted"><?=htmlspecialchars($row['prodi']??'-')?></small></td>
          <td><?=htmlspecialchars($row['nim']??'-')?></td>
          <td><strong><?=date('d M Y',strtotime($row['tanggal']))?></strong></td>
          <td><span class="badge bg-light text-dark border" style="font-size:.82rem"><?=$row['waktu']??'-'?></span></td>
          <td style="font-size:.77rem">
            <a href="https://maps.google.com/?q=<?=$row['latitude']?>,<?=$row['longitude']?>" target="_blank" class="text-decoration-none">
              <i class="bi bi-geo-alt text-danger"></i>
              <?=number_format($row['latitude'],5)?>, <?=number_format($row['longitude'],5)?>
            </a>
          </td>
          <td><?=$row['jarak_meter']!==null?number_format($row['jarak_meter'],0).' m':'-'?></td>
          <td><?=$row['status']==='Hadir'?"<span class='b-hadir'>✓ Hadir</span>":"<span class='b-ditolak'>✗ Ditolak</span>"?></td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data absensi.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ============================
     TAB: MAHASISWA AKTIF
     ============================ -->
<?php elseif ($activeTab === 'mahasiswa'): ?>
<div class="card">
  <div class="card-header"><i class="bi bi-people-fill me-2" style="color:var(--primary)"></i><strong>Mahasiswa Aktif</strong></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-4">#</th><th>Nama</th><th>Username</th>
          <th>Password</th><th>NIM</th><th>Program Studi</th>
          <th>Status</th><th>Terdaftar</th><th>Aksi</th>
        </tr></thead>
        <tbody>
        <?php $no=1; while ($m=$mahasiswaAktif->fetch_assoc()): ?>
        <tr>
          <td class="ps-4 text-muted"><?=$no++?></td>
          <td><strong><?=htmlspecialchars($m['nama'])?></strong></td>
          <td><code><?=htmlspecialchars($m['username'])?></code></td>
          <td>
            <span class="pw-cell" id="pw-<?=$m['id']?>" style="filter:blur(4px);cursor:pointer" onclick="togglePw(<?=$m['id']?>)" title="Klik untuk lihat/sembunyikan">
              <?=htmlspecialchars($m['password_plain']?:'••••••')?>
            </span>
          </td>
          <td><?=htmlspecialchars($m['nim']??'-')?></td>
          <td><?=htmlspecialchars($m['prodi']??'-')?></td>
          <td><span class="b-aktif">Aktif</span></td>
          <td><?=date('d M Y',strtotime($m['created_at']))?></td>
          <td>
            <a href="?hapus=<?=$m['id']?>&tab=mahasiswa" class="btn btn-sm btn-danger rounded-3" onclick="return confirm('Hapus mahasiswa ini?')">
              <i class="bi bi-trash3"></i>
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ============================
     TAB: PERSETUJUAN AKUN
     ============================ -->
<?php elseif ($activeTab === 'pending'): ?>
<div class="card">
  <div class="card-header d-flex align-items-center gap-2">
    <i class="bi bi-person-check-fill" style="color:var(--primary)"></i>
    <strong>Persetujuan Akun Mahasiswa</strong>
    <?php if ($totalPending > 0): ?>
    <span class="badge bg-danger ms-1"><?=$totalPending?> menunggu</span>
    <?php endif; ?>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-4">#</th><th>Nama</th><th>Username</th>
          <th>Password</th><th>NIM</th><th>Program Studi</th>
          <th>Daftar</th><th>Aksi</th>
        </tr></thead>
        <tbody>
        <?php
        $no=1; $adaPending = false;
        while ($m=$mahasiswaPending->fetch_assoc()):
            $adaPending = true;
        ?>
        <tr style="background:#fffbeb">
          <td class="ps-4 text-muted"><?=$no++?></td>
          <td><strong><?=htmlspecialchars($m['nama'])?></strong></td>
          <td><code><?=htmlspecialchars($m['username'])?></code></td>
          <td><span class="pw-cell"><?=htmlspecialchars($m['password_plain']?:'N/A')?></span></td>
          <td><?=htmlspecialchars($m['nim']??'-')?></td>
          <td><?=htmlspecialchars($m['prodi']??'-')?></td>
          <td style="font-size:.8rem"><?=date('d M Y H:i',strtotime($m['created_at']))?></td>
          <td>
            <a href="?setujui=<?=$m['id']?>&tab=pending" class="btn btn-sm btn-success rounded-3 me-1" onclick="return confirm('Setujui akun ini?')">
              <i class="bi bi-check-lg me-1"></i>Setujui
            </a>
            <a href="?tolak=<?=$m['id']?>&tab=pending" class="btn btn-sm btn-danger rounded-3" onclick="return confirm('Tolak akun ini?')">
              <i class="bi bi-x-lg me-1"></i>Tolak
            </a>
          </td>
        </tr>
        <?php endwhile; ?>
        <?php if (!$adaPending): ?>
        <tr><td colspan="8" class="text-center text-muted py-5">
          <i class="bi bi-check2-circle" style="font-size:2rem;color:#059669"></i><br>
          <span class="mt-2 d-block">Tidak ada akun yang menunggu persetujuan.</span>
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ============================
     TAB: TAMBAH MAHASISWA
     ============================ -->
<?php elseif ($activeTab === 'tambah'): ?>
<div class="card">
  <div class="card-header"><i class="bi bi-person-plus-fill me-2" style="color:var(--primary)"></i><strong>Tambah Mahasiswa</strong></div>
  <div class="card-body p-4">
    <form method="POST" action="?tab=tambah">
      <input type="hidden" name="action" value="tambah">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control rounded-3" placeholder="Nama mahasiswa" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
          <input type="text" name="username" class="form-control rounded-3" placeholder="Username login" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
          <input type="text" name="password" class="form-control rounded-3" placeholder="Password (akan disimpan & tampil)" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">NIM</label>
          <input type="text" name="nim" class="form-control rounded-3" placeholder="NIM">
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold">Program Studi</label>
          <input type="text" name="prodi" class="form-control rounded-3" placeholder="Prodi">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-prim px-4 py-2">
            <i class="bi bi-person-plus me-2"></i>Tambah Mahasiswa
          </button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

</div><!-- /main -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateClock() {
    const now = new Date();
    const hari  = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    const bulan = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const jam = String(now.getHours()).padStart(2,'0');
    const mnt = String(now.getMinutes()).padStart(2,'0');
    const dtk = String(now.getSeconds()).padStart(2,'0');
    const el = document.getElementById('live-clock-admin');
    if(el) el.textContent = `${hari[now.getDay()]}, ${now.getDate()} ${bulan[now.getMonth()]} ${now.getFullYear()} — ${jam}:${mnt}:${dtk}`;
}
updateClock(); setInterval(updateClock,1000);

// Toggle show/hide password
const pwVisible = {};
function togglePw(id) {
    const el = document.getElementById('pw-'+id);
    if (!el) return;
    pwVisible[id] = !pwVisible[id];
    el.style.filter = pwVisible[id] ? 'none' : 'blur(4px)';
    el.title = pwVisible[id] ? 'Klik untuk sembunyikan' : 'Klik untuk lihat';
}
</script>
</body>
</html>
