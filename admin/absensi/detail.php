<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$row = null;
if ($id > 0) {
    $res = db_query("SELECT a.*, u.nama, u.username, g.nip, s.nis FROM tb_absensi a LEFT JOIN tb_guru g ON a.guru_id = g.guru_id LEFT JOIN tb_siswa s ON a.siswa_id = s.siswa_id LEFT JOIN tb_users u ON u.user_id = COALESCE(g.user_id, s.user_id) WHERE a.absensi_id = ? LIMIT 1", "i", [$id]);
    if ($res instanceof mysqli_result && $res->num_rows === 1) { $row = $res->fetch_assoc(); }
}
if (!$row) { header('Location: absensi.php'); exit; }
$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Absensi • E-Absensi</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../../assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="shortcut icon" href="../../assets/images/favicon.svg" type="image/x-icon">
</head>
<body>
    <div id="app">
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>
            <div class="page-heading">
                <h3>Detail Absensi</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Data Absensi</h4>
                                <div class="d-flex gap-2">
                                    <a href="absensi.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="logo">
                                        <img src="../../assets/images/logo/logo_absensi.png" alt="Logo" style="height:48px">
                                    </div>
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars((string)$row['nama'] ?? '') ?> (<?= htmlspecialchars((string)$row['username'] ?? '') ?>)</h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lg">
                                        <tbody>
                                            <tr><th style="width:260px">Jenis</th><td><?= htmlspecialchars((string)$row['jenis']) ?></td></tr>
                                            <tr><th>Identitas</th><td><?= htmlspecialchars((string)($row['nip'] ?? $row['nis'] ?? '')) ?></td></tr>
                                            <tr><th>Tanggal</th><td><?= htmlspecialchars((string)$row['tanggal']) ?></td></tr>
                                            <tr><th>Jadwal Masuk</th><td><?= htmlspecialchars((string)$row['jadwal_jam_masuk'] ?? '') ?></td></tr>
                                            <tr><th>Jadwal Batas Telat</th><td><?= htmlspecialchars((string)$row['jadwal_batas_telat'] ?? '') ?></td></tr>
                                            <tr><th>Jadwal Pulang</th><td><?= htmlspecialchars((string)$row['jadwal_jam_pulang'] ?? '') ?></td></tr>
                                            <tr><th>Status Masuk</th><td><?= htmlspecialchars((string)$row['status_masuk'] ?? '') ?></td></tr>
                                            <tr><th>Jam Masuk</th><td><?= htmlspecialchars((string)$row['jam_masuk'] ?? '') ?></td></tr>
                                            <tr><th>Koordinat Masuk</th><td><?= htmlspecialchars((string)$row['lat_masuk'] ?? '') ?>, <?= htmlspecialchars((string)$row['lng_masuk'] ?? '') ?></td></tr>
                                            <tr><th>Akurasi Masuk</th><td><?= htmlspecialchars((string)$row['akurasi_masuk'] ?? '') ?></td></tr>
                                            <tr><th>Jarak Masuk (m)</th><td><?= htmlspecialchars((string)$row['jarak_masuk_meter'] ?? '') ?></td></tr>
                                            <tr><th>Foto Masuk</th><td><?php if (!empty($row['foto_masuk'])) { ?><img src="../../<?= htmlspecialchars((string)$row['foto_masuk']) ?>" alt="Foto Masuk" style="max-height:160px"><?php } ?></td></tr>
                                            <tr><th>Status Pulang</th><td><?= htmlspecialchars((string)$row['status_pulang'] ?? '') ?></td></tr>
                                            <tr><th>Jam Pulang</th><td><?= htmlspecialchars((string)$row['jam_pulang'] ?? '') ?></td></tr>
                                            <tr><th>Koordinat Pulang</th><td><?= htmlspecialchars((string)$row['lat_pulang'] ?? '') ?>, <?= htmlspecialchars((string)$row['lng_pulang'] ?? '') ?></td></tr>
                                            <tr><th>Akurasi Pulang</th><td><?= htmlspecialchars((string)$row['akurasi_pulang'] ?? '') ?></td></tr>
                                            <tr><th>Jarak Pulang (m)</th><td><?= htmlspecialchars((string)$row['jarak_pulang_meter'] ?? '') ?></td></tr>
                                            <tr><th>Foto Pulang</th><td><?php if (!empty($row['foto_pulang'])) { ?><img src="../../<?= htmlspecialchars((string)$row['foto_pulang']) ?>" alt="Foto Pulang" style="max-height:160px"><?php } ?></td></tr>
                                            <tr><th>Perangkat</th><td><?= htmlspecialchars((string)$row['device_info'] ?? '') ?></td></tr>
                                            <tr><th>Catatan</th><td><?= nl2br(htmlspecialchars((string)$row['catatan'] ?? '')) ?></td></tr>
                                            <tr><th>Dibuat</th><td><?= htmlspecialchars((string)$row['created_at'] ?? '') ?></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>
    <script src="../../assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
