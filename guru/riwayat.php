<?php
session_start();
require __DIR__ . '/../config/koneksi.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'guru') { header('Location: /login.php'); exit; }
$userId = (int)($_SESSION['user_id'] ?? 0);
$resGuru = db_query("SELECT g.guru_id, u.nama FROM tb_guru g JOIN tb_users u ON g.user_id=u.user_id WHERE g.user_id = ? LIMIT 1", "i", [$userId]);
$guruId = 0; $nama = 'Guru';
$rowG = null;
if ($resGuru instanceof mysqli_result && $resGuru->num_rows === 1) { $rowG = $resGuru->fetch_assoc(); $guruId = (int)$rowG['guru_id']; $nama = (string)$rowG['nama']; }
$from = isset($_GET['from']) && $_GET['from'] !== '' ? (string)$_GET['from'] : null;
$to = isset($_GET['to']) && $_GET['to'] !== '' ? (string)$_GET['to'] : null;
$q = "SELECT tanggal, status_masuk, jam_masuk, status_pulang, jam_pulang, jarak_masuk_meter FROM tb_absensi WHERE jenis='guru' AND guru_id = ? ";
$params = [$guruId]; $types = "i";
if ($from && $to) { $q .= "AND tanggal BETWEEN ? AND ? "; $types .= "ss"; $params[] = $from; $params[] = $to; }
$q .= "ORDER BY tanggal DESC, absensi_id DESC LIMIT 50";
$riwayat = db_query($q, $types, $params);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Guru • E-Absensi</title>
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
                <h3>Riwayat Absensi</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0"><?= htmlspecialchars($nama) ?></h4>
                                <div>
                                    <a href="../guru/absensi.php" class="btn btn-primary"><i class="bi bi-camera"></i> Absen</a>
                                    <a href="../guru/dashboard.php" class="btn btn-outline-secondary"><i class="bi bi-house"></i> Dashboard</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <form method="get" class="row g-2 mb-3">
                                    <div class="col-auto"><label class="form-label">Dari</label><input type="date" name="from" class="form-control" value="<?= htmlspecialchars((string)($from ?? '')) ?>"></div>
                                    <div class="col-auto"><label class="form-label">Sampai</label><input type="date" name="to" class="form-control" value="<?= htmlspecialchars((string)($to ?? '')) ?>"></div>
                                    <div class="col-auto align-self-end"><button class="btn btn-outline-primary"><i class="bi bi-filter"></i> Filter</button></div>
                                </form>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Status Masuk</th>
                                                <th>Jam Masuk</th>
                                                <th>Status Pulang</th>
                                                <th>Jam Pulang</th>
                                                <th>Jarak (m)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($riwayat instanceof mysqli_result && $riwayat->num_rows > 0): ?>
                                                <?php while ($r = $riwayat->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars((string)$r['tanggal']) ?></td>
                                                        <td><span class="badge bg-<?= ($r['status_masuk']==='Hadir'?'success':($r['status_masuk']==='Telat'?'warning':'secondary')) ?>"><?= htmlspecialchars((string)($r['status_masuk'] ?? '-')) ?></span></td>
                                                        <td><?= htmlspecialchars((string)($r['jam_masuk'] ?? '-')) ?></td>
                                                        <td><span class="badge bg-<?= ($r['status_pulang']==='Pulang'?'success':'secondary') ?>"><?= htmlspecialchars((string)($r['status_pulang'] ?? '-')) ?></span></td>
                                                        <td><?= htmlspecialchars((string)($r['jam_pulang'] ?? '-')) ?></td>
                                                        <td><?= htmlspecialchars((string)(isset($r['jarak_masuk_meter']) ? round((float)$r['jarak_masuk_meter']) : '-')) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="6" class="text-muted">Belum ada data</td></tr>
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
