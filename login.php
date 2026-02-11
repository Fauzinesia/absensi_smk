<?php
session_start();
require __DIR__ . '/config/koneksi.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
    $password = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($username !== '' && $password !== '') {
        $res = db_query(
            "SELECT user_id, nama, username, password_hash, role, is_active FROM tb_users WHERE username = ? LIMIT 1",
            "s",
            [$username]
        );
        if ($res instanceof mysqli_result && $res->num_rows === 1) {
            $row = $res->fetch_assoc();
            if ((int)$row['is_active'] !== 1) {
                $error = 'Akun tidak aktif';
            } elseif (password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = (int)$row['user_id'];
                $_SESSION['nama']    = (string)$row['nama'];
                $_SESSION['role']    = (string)$row['role'];
                if (isset($_SESSION['role'])) {
                    if ($_SESSION['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                    } elseif ($_SESSION['role'] === 'guru') {
                        header('Location: guru/dashboard.php');
                    } elseif ($_SESSION['role'] === 'siswa') {
                        header('Location: siswa/dashboard.php');
                    } else {
                        header('Location: index.php');
                    }
                } else {
                    header('Location: index.php');
                }
                exit;
            } else {
                $error = 'Username atau password salah';
            }
        } else {
            $error = 'Username atau password salah';
        }
    } else {
        $error = 'Isi username dan password';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk • E-Absensi</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/logo/logo-smk.png">
    <link rel="shortcut icon" type="image/png" href="assets/images/logo/logo-smk.png">
    <link rel="apple-touch-icon" href="assets/images/logo/logo-smk.png">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:#f5f6fa; color:#1f2328; }
        .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding: 24px; }
        .card { width: 100%; max-width: 420px; background:#fff; border-radius:16px; box-shadow: 0 12px 28px rgba(0,0,0,0.10); padding: 28px; }
        .logo { display:flex; align-items:center; gap:12px; margin-bottom: 16px; }
        .logo img { width:48px; height:48px; object-fit:contain; border-radius:10px; background:#eef2ff; padding:6px; }
        .title { font-size:20px; font-weight:600; margin:0; }
        .subtitle { margin:4px 0 16px; font-size:13px; color:#6b7280; }
        .field { margin-bottom:14px; }
        label { display:block; font-size:13px; color:#374151; margin-bottom:6px; }
        input { width:100%; padding:12px; border:1px solid #e5e7eb; border-radius:10px; font-size:14px; outline:none; transition:border .2s, box-shadow .2s; }
        input:focus { border-color:#93c5fd; box-shadow: 0 0 0 4px rgba(59,130,246,.15); }
        .btn { width:100%; appearance:none; border:none; border-radius:10px; padding:12px; font-size:14px; font-weight:600; cursor:pointer; background:#2563eb; color:#fff; }
        .error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:10px; padding:10px 12px; font-size:13px; margin-bottom:14px; }
        .meta { margin-top:12px; font-size:12px; color:#6b7280; text-align:center; }
        .actions { margin-top:12px; display:flex; justify-content:center; gap:8px; }
        .btn-outline { width:auto; background:#fff; color:#2563eb; border:1px solid #2563eb; }
        .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.35); display:none; align-items:center; justify-content:center; z-index:50; }
        .modal-card { width:100%; max-width:520px; background:#fff; border-radius:16px; box-shadow:0 12px 28px rgba(0,0,0,.15); overflow:hidden; }
        .modal-header { padding:14px 16px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; }
        .modal-title { font-size:16px; font-weight:600; margin:0; }
        .modal-body { padding:16px; }
        .about-wrap { display:flex; align-items:center; gap:14px; }
        .about-wrap img { width:150px; height:150px; object-fit:contain; border-radius:12px; background:#eef2ff; padding:6px; }
        .about-list { margin:0; padding:0; list-style:none; font-size:14px; }
        .about-list li { margin-bottom:6px; }
        .modal-close { appearance:none; border:none; background:#f9fafb; border-radius:8px; padding:8px 10px; cursor:pointer; font-weight:600; }
        .show { display:flex; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="logo">
                <img src="assets/images/logo/logo_absensi.png" alt="Logo">
                <div>
                    <p class="title">Masuk ke E-Absensi</p>
                    <p class="subtitle">SMK Farmasi Mandiri Banjarmasin</p>
                </div>
            </div>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="post" action="login.php" autocomplete="off">
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required>
                </div>
                <button class="btn" type="submit">Masuk</button>
            </form>
            <div class="meta">Waktu: <?= htmlspecialchars(formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')))) ?> • <span id="clock"></span> • WITA</div>
            <div class="actions">
                <button class="btn btn-outline" id="btn-about">Tentang Aplikasi</button>
            </div>
        </div>
    </div>
    <div class="modal-backdrop" id="about-modal">
        <div class="modal-card">
            <div class="modal-header">
                <div class="modal-title">Tentang Aplikasi</div>
                <button class="modal-close" id="about-close">Tutup</button>
            </div>
            <div class="modal-body">
                <div class="about-wrap">
                    <img src="assets/images/logo/logo-upk.png" alt="Logo">
                    <ul class="about-list">
                        <li>NAMA: MUHAMMAD NORSAUFI</li>
                        <li>NPM: 3062146027</li>
                        <li>Program Studi: Pendidikan Teknologi Informasi</li>
                        <li>Fakultas: Sains dan teknologi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <script>
        var btn = document.getElementById('btn-about');
        var modal = document.getElementById('about-modal');
        var closeBtn = document.getElementById('about-close');
        btn && btn.addEventListener('click', function(){ modal.classList.add('show'); });
        closeBtn && closeBtn.addEventListener('click', function(){ modal.classList.remove('show'); });
        modal && modal.addEventListener('click', function(e){ if (e.target === modal) modal.classList.remove('show'); });
        function updateClock() {
            var el = document.getElementById('clock');
            if (!el) return;
            var fmt = new Intl.DateTimeFormat('id-ID', {
                timeZone: 'Asia/Makassar',
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            }).format(new Date());
            el.textContent = fmt.replace(/\./g, ':');
        }
        updateClock(); setInterval(updateClock, 1000);
    </script>
</body>
</html>
