<?php
session_start();
require __DIR__ . '/config/koneksi.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-Absensi SMK Farmasi Mandiri</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { margin:0; font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:#f5f6fa; color:#1f2328; }
        .container { max-width: 880px; margin: 60px auto; padding: 24px; }
        .card { background: #fff; border-radius: 14px; box-shadow: 0 8px 24px rgba(0,0,0,0.08); padding: 28px; display:flex; align-items:center; gap:24px; }
        .logo { width: 88px; height: 88px; border-radius:12px; overflow:hidden; background:#eef2ff; display:flex; align-items:center; justify-content:center; }
        .logo img { max-width: 88px; max-height: 88px; object-fit: contain; }
        .content h1 { margin:0 0 8px; font-size: 24px; font-weight: 600; }
        .content p { margin:0 0 16px; font-size: 14px; color:#4b5563; }
        .actions { display:flex; gap:12px; }
        .btn { appearance:none; border:none; border-radius:10px; padding:10px 16px; font-weight:600; cursor:pointer; font-size:14px; }
        .primary { background:#2563eb; color:#fff; }
        .secondary { background:#eef2ff; color:#1f2328; }
        .meta { margin-top:10px; font-size:13px; color:#6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <img src="assets/images/logo/logo_absensi.png" alt="Logo E-Absensi">
            </div>
            <div class="content">
                <h1>Selamat datang, <?= htmlspecialchars($nama) ?></h1>
                <p>E-Absensi SMK Farmasi Mandiri Banjarmasin</p>
                <div class="actions">
                    <a class="btn primary" href="login.php?logout=1">Keluar</a>
                    <a class="btn secondary" href="#">Menu Utama</a>
                </div>
                <div class="meta"><?= htmlspecialchars($tanggalLabel) ?> • WITA</div>
            </div>
        </div>
    </div>
</body>
<html>
