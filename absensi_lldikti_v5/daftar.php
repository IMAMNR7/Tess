<?php
// ============================================================
// daftar.php — Pendaftaran Akun Mahasiswa (perlu persetujuan admin)
// ============================================================
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_mahasiswa.php'));
    exit;
}
require_once 'koneksi.php';

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirm'] ?? '';
    $nim      = trim($_POST['nim'] ?? '');
    $prodi    = trim($_POST['prodi'] ?? '');

    if (empty($nama) || empty($username) || empty($password) || empty($nim) || empty($prodi)) {
        $error = 'Semua field wajib diisi.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirm) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        // Cek username & NIM duplikat
        $chk = $conn->prepare("SELECT id FROM users WHERE username = ? OR nim = ?");
        $chk->bind_param('ss', $username, $nim);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'Username atau NIM sudah terdaftar.';
        } else {
            $hash   = password_hash($password, PASSWORD_DEFAULT);
            $pwPlain = $password; // simpan plain untuk ditampilkan admin
            $stmt = $conn->prepare("INSERT INTO users (nama, username, password, password_plain, role, nim, prodi, status) VALUES (?, ?, ?, ?, 'mahasiswa', ?, ?, 'pending')");
            $stmt->bind_param('ssssss', $nama, $username, $hash, $pwPlain, $nim, $prodi);
            $stmt->execute();
            $stmt->close();
            $success = true;
        }
        $chk->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun — Absensi LLDIKTI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --primary:#1a3c6e; --accent:#e8b84b; }
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: #f0f4f9;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 30px 16px;
        }
        .daftar-wrapper {
            display: flex; width: 960px; max-width: 100%;
            border-radius: 20px; overflow: hidden;
            box-shadow: 0 20px 60px rgba(26,60,110,.18);
        }
        .brand-side {
            background: linear-gradient(145deg, #1a3c6e 0%, #0d2347 100%);
            width: 38%; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 40px 28px; color: #fff; position: relative; overflow: hidden;
        }
        .brand-side::before {
            content:''; position:absolute; width:220px; height:220px;
            background:rgba(232,184,75,.12); border-radius:50%;
            top:-50px; right:-70px;
        }
        .brand-logo {
            width:72px; height:72px; background:var(--accent);
            border-radius:50%; display:flex; align-items:center; justify-content:center;
            font-size:1.8rem; margin-bottom:18px;
            box-shadow:0 8px 24px rgba(232,184,75,.35);
        }
        .brand-title { font-size:1.2rem; font-weight:800; text-align:center; line-height:1.3; }
        .brand-sub   { font-size:.78rem; opacity:.7; text-align:center; margin-top:8px; line-height:1.5; }
        .divider-dot { width:6px; height:6px; background:var(--accent); border-radius:50%; margin:16px auto; }

        .form-side {
            background:#fff; flex:1;
            padding: 36px 40px;
            overflow-y: auto;
        }
        .page-title { font-size:1.4rem; font-weight:800; color:var(--primary); }
        .page-sub   { font-size:.83rem; color:#888; margin-bottom:22px; }
        .form-label { font-weight:600; font-size:.85rem; color:#444; }
        .form-control {
            border-radius:10px; border:1.5px solid #d8e0ec;
            padding:9px 13px; font-size:.9rem; transition:border .2s;
        }
        .form-control:focus { border-color:var(--primary); box-shadow:0 0 0 3px rgba(26,60,110,.1); }
        .input-group-text {
            border-radius:10px 0 0 10px;
            background:#f0f4f9; border:1.5px solid #d8e0ec; border-right:none;
            color:var(--primary);
        }
        .input-group .form-control { border-radius:0 10px 10px 0; }
        .btn-daftar {
            background:linear-gradient(135deg,var(--primary),#2a5ca8);
            color:#fff; border:none; border-radius:10px;
            padding:12px; font-weight:700; font-size:.95rem;
            transition:transform .15s, box-shadow .15s;
        }
        .btn-daftar:hover { transform:translateY(-1px); box-shadow:0 8px 20px rgba(26,60,110,.28); color:#fff; }
        .alert { border-radius:10px; font-size:.88rem; }
        .back-link { font-size:.83rem; color:#888; text-align:center; margin-top:14px; }
        .back-link a { color:var(--primary); font-weight:600; text-decoration:none; }
        @media(max-width:640px) {
            .brand-side { display:none; }
            .form-side  { padding:28px 20px; }
        }
    </style>
</head>
<body>
<div class="daftar-wrapper">
    <!-- Brand -->
    <div class="brand-side">
        <div class="brand-logo"><i class="bi bi-person-plus-fill"></i></div>
        <div class="brand-title">LLDIKTI Wilayah II<br>Palembang</div>
        <div class="divider-dot"></div>
        <div class="brand-sub">Daftar sebagai Mahasiswa Magang.<br>Akun akan aktif setelah disetujui Admin.</div>
    </div>

    <!-- Form -->
    <div class="form-side">
        <?php if ($success): ?>
        <div class="text-center py-4">
            <div style="font-size:3.5rem">🎉</div>
            <h4 class="fw-800 mt-3" style="color:var(--primary)">Pendaftaran Terkirim!</h4>
            <p class="text-muted" style="font-size:.9rem">
                Akun Anda sedang menunggu persetujuan dari Admin LLDIKTI.<br>
                Silakan hubungi admin untuk mempercepat proses aktivasi.
            </p>
            <a href="login.php" class="btn btn-daftar px-5 mt-2">
                <i class="bi bi-box-arrow-in-right me-2"></i>Kembali ke Login
            </a>
        </div>
        <?php else: ?>
        <div class="page-title">Buat Akun Baru</div>
        <div class="page-sub">Isi data lengkap untuk mendaftar sebagai mahasiswa magang</div>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                        <input type="text" name="nama" class="form-control" placeholder="Nama lengkap sesuai KTP"
                               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">NIM <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-card-text"></i></span>
                        <input type="text" name="nim" class="form-control" placeholder="Nomor Induk Mahasiswa"
                               value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Program Studi <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-mortarboard-fill"></i></span>
                        <input type="text" name="prodi" class="form-control" placeholder="Jurusan / Prodi"
                               value="<?= htmlspecialchars($_POST['prodi'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Username untuk login"
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock-fill"></i></span>
                        <input type="password" name="konfirm" class="form-control" placeholder="Ulangi password" required>
                    </div>
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-daftar w-100">
                        <i class="bi bi-send-fill me-2"></i>Kirim Pendaftaran
                    </button>
                </div>
            </div>
        </form>
        <div class="back-link mt-3">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
