<?php
session_start();
require __DIR__ . '/../config/koneksi.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'guru') { header('Location: /login.php'); exit; }
$userId = (int)($_SESSION['user_id'] ?? 0);
$resGuru = db_query("SELECT guru_id FROM tb_guru WHERE user_id = ? LIMIT 1", "i", [$userId]);
$guruId = 0; if ($resGuru instanceof mysqli_result && $resGuru->num_rows === 1) { $guruId = (int)$resGuru->fetch_assoc()['guru_id']; }
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis_izin = (string)($_POST['jenis_izin'] ?? '');
    $tanggal_mulai = (string)($_POST['tanggal_mulai'] ?? '');
    $tanggal_selesai = (string)($_POST['tanggal_selesai'] ?? '');
    $keterangan = trim((string)($_POST['keterangan'] ?? ''));
    $buktiPath = null;
    if (isset($_FILES['bukti']) && is_array($_FILES['bukti']) && (int)$_FILES['bukti']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['bukti']['tmp_name']; $size = (int)$_FILES['bukti']['size'];
        $info = @getimagesize($tmp); $mime = is_array($info) && isset($info['mime']) ? $info['mime'] : null;
        if (($mime === 'image/jpeg' || $mime === 'image/png') && $size <= 2*1024*1024) {
            $ext = $mime === 'image/jpeg' ? 'jpg' : 'png';
            $name = 'izin_' . bin2hex(random_bytes(8)) . '.' . $ext;
            $dir = __DIR__ . '/../uploads/izin'; if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            $dest = $dir . '/' . $name; if (@move_uploaded_file($tmp, $dest)) { $buktiPath = 'uploads/izin/' . $name; }
        }
    }
    if ($guruId > 0 && in_array($jenis_izin, ['Izin','Sakit','Dinas','Dispensasi'], true) && $tanggal_mulai !== '' && $tanggal_selesai !== '') {
        $ok = db_query("INSERT INTO tb_izin (jenis_pengguna, guru_id, tanggal_mulai, tanggal_selesai, jenis_izin, keterangan, bukti, status) VALUES ('guru', ?, ?, ?, ?, ?, ?, 'Diajukan')", "isssss", [$guruId, $tanggal_mulai, $tanggal_selesai, $jenis_izin, $keterangan !== '' ? $keterangan : null, $buktiPath]);
        $msg = $ok ? 'Izin diajukan' : 'Gagal mengajukan izin';
    } else {
        $msg = 'Isi data dengan benar';
    }
}
$riwayat = db_query("SELECT izin_id, tanggal_mulai, tanggal_selesai, jenis_izin, status, bukti FROM tb_izin WHERE jenis_pengguna='guru' AND guru_id = ? ORDER BY diajukan_pada DESC LIMIT 12", "i", [$guruId]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Izin Guru • E-Absensi</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/x-icon">
</head>
<body>
    <div id="app">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none"><i class="bi bi-justify fs-3"></i></a>
            </header>
            <div class="page-heading">
                <h3>Izin</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Ajukan Izin</h4>
                                <a href="../guru/absensi.php" class="btn btn-outline-primary"><i class="bi bi-camera"></i> Absen</a>
                            </div>
                            <div class="card-body">
                                <?php if ($msg): ?><div class="alert alert-info"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                                <form method="post" action="izin.php" enctype="multipart/form-data" class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Jenis Izin</label>
                                        <select name="jenis_izin" class="form-select" required>
                                            <option value="Izin">Izin</option>
                                            <option value="Sakit">Sakit</option>
                                            <option value="Dinas">Dinas</option>
                                            <option value="Dispensasi">Dispensasi</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal Mulai</label>
                                        <input name="tanggal_mulai" type="date" class="form-control" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Tanggal Selesai</label>
                                        <input name="tanggal_selesai" type="date" class="form-control" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Keterangan</label>
                                        <textarea name="keterangan" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Bukti (opsional)</label>
                                        <input name="bukti" type="file" class="form-control" accept="image/png,image/jpeg">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Ajukan</button>
                                    </div>
                                </form>
                                <hr>
                                <h6 class="mb-2">Riwayat Izin</h6>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Periode</th>
                                                <th>Jenis</th>
                                                <th>Status</th>
                                                <th>Bukti</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($riwayat instanceof mysqli_result && $riwayat->num_rows > 0): ?>
                                                <?php while ($r = $riwayat->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars((string)$r['tanggal_mulai']) ?> s/d <?= htmlspecialchars((string)$r['tanggal_selesai']) ?></td>
                                                        <td><?= htmlspecialchars((string)$r['jenis_izin']) ?></td>
                                                        <td><span class="badge bg-<?= ($r['status']==='Disetujui'?'success':($r['status']==='Ditolak'?'danger':'secondary')) ?>"><?= htmlspecialchars((string)$r['status']) ?></span></td>
                                                        <td><?php if (!empty($r['bukti'])): ?><a href="../<?= htmlspecialchars((string)$r['bukti']) ?>" target="_blank">Lihat</a><?php else: ?>-<?php endif; ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="4" class="text-muted">Belum ada data izin</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php include __DIR__ . '/../includes/footer.php'; ?>
        </div>
    </div>
    <script src="../assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
