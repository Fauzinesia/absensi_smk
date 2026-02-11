<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lokasi = trim((string)($_POST['nama_lokasi'] ?? ''));
    $latitude = (string)($_POST['latitude'] ?? '');
    $longitude = (string)($_POST['longitude'] ?? '');
    $radius_meter = isset($_POST['radius_meter']) ? (int)$_POST['radius_meter'] : 100;
    $id = isset($_POST['pengaturan_id']) ? (int)$_POST['pengaturan_id'] : 0;
    $lat = is_numeric($latitude) ? (float)$latitude : null;
    $lng = is_numeric($longitude) ? (float)$longitude : null;
    if ($nama_lokasi !== '' && $lat !== null && $lng !== null && $radius_meter > 0) {
        $exists = db_query("SELECT pengaturan_id FROM tb_pengaturan_absensi WHERE pengaturan_id = ?", "i", [$id]);
        if ($exists instanceof mysqli_result && $exists->num_rows === 1) {
            $ok = db_query(
                "UPDATE tb_pengaturan_absensi SET nama_lokasi = ?, latitude = ?, longitude = ?, radius_meter = ? WHERE pengaturan_id = ?",
                "sddii",
                [$nama_lokasi, $lat, $lng, $radius_meter, $id]
            );
            $msg = $ok ? 'Pengaturan diperbarui' : 'Gagal memperbarui pengaturan';
        } else {
            $ok = db_query(
                "INSERT INTO tb_pengaturan_absensi (nama_lokasi, latitude, longitude, radius_meter) VALUES (?, ?, ?, ?)",
                "sddi",
                [$nama_lokasi, $lat, $lng, $radius_meter]
            );
            $msg = $ok ? 'Pengaturan disimpan' : 'Gagal menyimpan pengaturan';
        }
    } else {
        $msg = 'Isi data dengan benar';
    }
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
$res = db_query("SELECT pengaturan_id, nama_lokasi, latitude, longitude, radius_meter, updated_at FROM tb_pengaturan_absensi ORDER BY pengaturan_id LIMIT 1");
$row = null;
if ($res instanceof mysqli_result && $res->num_rows === 1) {
    $row = $res->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengaturan Absensi • E-Absensi</title>
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
                <h3>Pengaturan Absensi</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Lokasi & Radius</h4>
                                <div class="text-muted small">Terakhir diperbarui: <?= htmlspecialchars((string)($row['updated_at'] ?? '-')) ?></div>
                            </div>
                            <div class="card-body">
                                <?php if ($msg): ?>
                                    <div class="alert alert-info d-flex align-items-center" role="alert">
                                        <i class="bi bi-info-circle me-2"></i><span><?= htmlspecialchars($msg) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="logo">
                                        <img src="../../assets/images/logo/logo_absensi.png" alt="Logo" style="height:48px">
                                    </div>
                                    <div>
                                        <h5 class="mb-1">Pengaturan Lokasi Absensi</h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                    </div>
                                </div>
                                <form method="post" action="pengaturan.php">
                                    <input type="hidden" name="pengaturan_id" value="<?= (int)($row['pengaturan_id'] ?? 0) ?>">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Nama Lokasi</label>
                                                <input name="nama_lokasi" type="text" class="form-control" required value="<?= htmlspecialchars((string)($row['nama_lokasi'] ?? '')) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Latitude</label>
                                                <input name="latitude" type="text" class="form-control" required value="<?= htmlspecialchars((string)($row['latitude'] ?? '')) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label class="form-label">Longitude</label>
                                                <input name="longitude" type="text" class="form-control" required value="<?= htmlspecialchars((string)($row['longitude'] ?? '')) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">Radius (meter)</label>
                                                <input name="radius_meter" type="number" min="1" class="form-control" required value="<?= htmlspecialchars((string)($row['radius_meter'] ?? 100)) ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                                        <a href="pengaturan.php" class="btn btn-secondary"><i class="bi bi-arrow-clockwise"></i> Reset</a>
                                    </div>
                                </form>
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
