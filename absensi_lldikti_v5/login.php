<?php
// ============================================================
// login.php
// ============================================================
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_mahasiswa.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'koneksi.php';

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT id, nama, password, role FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] === 'pending') {
                $error = 'Akun Anda belum disetujui oleh Admin. Silakan tunggu konfirmasi.';
            } elseif ($user['status'] === 'nonaktif') {
                $error = 'Akun Anda telah dinonaktifkan. Hubungi Admin LLDIKTI.';
            } else {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['role']      = $user['role'];
                header('Location: ' . ($user['role'] === 'admin' ? 'dashboard_admin.php' : 'dashboard_mahasiswa.php'));
                exit;
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Absensi LLDIKTI Wilayah II</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a3c6e;
            --accent:  #e8b84b;
            --light-bg: #f0f4f9;
        }
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body {
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-wrapper {
            display: flex;
            width: 900px;
            max-width: 100%;
            min-height: 520px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(26,60,110,.18);
        }
        .login-brand {
            background: linear-gradient(145deg, #1a3c6e 0%, #0d2347 100%);
            width: 42%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 30px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .login-brand::before {
            content: '';
            position: absolute;
            width: 260px; height: 260px;
            background: rgba(232,184,75,.12);
            border-radius: 50%;
            top: -60px; right: -80px;
        }
        .login-brand::after {
            content: '';
            position: absolute;
            width: 180px; height: 180px;
            background: rgba(232,184,75,.08);
            border-radius: 50%;
            bottom: -40px; left: -50px;
        }
        .brand-logo {
            width: 80px; height: 80px;
            background: var(--accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(232,184,75,.35);
        }
        .brand-title { font-size: 1.35rem; font-weight: 800; line-height: 1.3; text-align: center; }
        .brand-sub   { font-size: .82rem; opacity: .75; margin-top: 8px; text-align: center; line-height: 1.5; }
        .divider-dot { width: 6px; height: 6px; border-radius: 50%; background: var(--accent); margin: 18px auto; }

        .login-form-side {
            background: #fff;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 50px 45px;
        }
        .form-label { font-weight: 600; font-size: .88rem; color: #444; }
        .form-control {
            border-radius: 10px;
            border: 1.5px solid #d8e0ec;
            padding: 10px 14px;
            font-size: .93rem;
            transition: border .2s;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(26,60,110,.1); }
        .input-group-text {
            border-radius: 10px 0 0 10px;
            background: #f0f4f9;
            border: 1.5px solid #d8e0ec;
            border-right: none;
            color: var(--primary);
        }
        .input-group .form-control { border-radius: 0 10px 10px 0; }
        .btn-login {
            background: linear-gradient(135deg, var(--primary), #2a5ca8);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            font-size: .95rem;
            letter-spacing: .3px;
            transition: transform .15s, box-shadow .15s;
        }
        .btn-login:hover { transform: translateY(-1px); box-shadow: 0 8px 20px rgba(26,60,110,.28); color: #fff; }
        .section-title { font-size: 1.5rem; font-weight: 800; color: var(--primary); margin-bottom: 4px; }
        .section-sub   { font-size: .85rem; color: #888; margin-bottom: 28px; }
        .alert { border-radius: 10px; font-size: .88rem; }
        @media (max-width: 640px) {
            .login-brand { display: none; }
            .login-form-side { padding: 36px 24px; }
            .login-wrapper { border-radius: 16px; }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <!-- Branding -->
    <div class="login-brand">
        <div class="brand-logo"><i class="bi bi-building-check"></i></div>
        <div class="brand-title">LLDIKTI Wilayah II<br>Palembang</div>
        <div class="divider-dot"></div>
        <div class="brand-sub">Sistem Informasi Absensi<br>Mahasiswa Magang Berbasis Web<br>dengan Geolocation & GPS Tracking</div>
    </div>

    <!-- Form -->
    <div class="login-form-side">
        <div class="section-title">Selamat Datang</div>
        <div class="section-sub">Masuk ke akun Anda untuk melanjutkan</div>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="mb-3">
                <label class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                    <input type="text" name="username" class="form-control"
                           placeholder="Masukkan username"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" name="password" id="pwdInput" class="form-control"
                           placeholder="Masukkan password" required>
                    <button type="button" class="btn btn-outline-secondary border border-start-0"
                            style="border-radius:0 10px 10px 0;border-left:none!important"
                            onclick="togglePwd()">
                        <i class="bi bi-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-login w-100">
                <i class="bi bi-box-arrow-in-right me-2"></i>Masuk
            </button>
        </form>

        <div class="mt-4 p-3 rounded-3" style="background:#f0f4f9;font-size:.82rem;color:#666">
            <button type="button" class="btn btn-link p-0 text-decoration-none" style="color:#1a3c6e;font-weight:700" onclick="toggleInfo()">
                <strong>Tata Cara Absen</strong> <i class="bi bi-chevron-down" id="chevronIcon"></i>
            </button>
            <div id="infoContent" style="display:none;margin-top:10px">
                Daftar akun jika belum memiliki akun <code><br>→ Tunggu persetujuan admin jika sudah mendaftar</code>
                <code><br>→ Absen Di wilayah LLDIKTI & Klik Absen Sehari 1 Kali</code> 
                <br> <code><a href="https://wa.me/082277961521" target="_blank">Ada pertanyaan Klik Sini</a></code><br>
            </div>
        </div>
        <div class="text-center mt-3" style="font-size:.85rem;color:#888">
            Belum punya akun? <a href="daftar.php" style="color:#1a3c6e;font-weight:700;text-decoration:none">Daftar di sini →</a>
        </div>
    </div>
</div>

<script>
function togglePwd() {
    const input = document.getElementById('pwdInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

function toggleInfo() {
    const content = document.getElementById('infoContent');
    const chevron = document.getElementById('chevronIcon');
    if (content.style.display === 'none') {
        content.style.display = 'block';
        chevron.style.transform = 'rotate(180deg)';
    } else {
        content.style.display = 'none';
        chevron.style.transform = 'rotate(0deg)';
    }
}
</script>
</body>
</html>
