<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
$nama = $_SESSION['nama'] ?? 'Pengguna';
$role = $_SESSION['role'] ?? 'admin';
$roleLabel = ucfirst($role);
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));

$conn = db();

$countsRole = ['admin' => 0, 'guru' => 0, 'siswa' => 0];
$resRole = db_query("SELECT role, COUNT(*) AS c FROM tb_users GROUP BY role");
if ($resRole instanceof mysqli_result) {
    while ($r = $resRole->fetch_assoc()) {
        $role = (string)$r['role'];
        $countsRole[$role] = (int)$r['c'];
    }
}

$countsJadwal = ['guru' => 0, 'siswa' => 0];
$resJadwal = db_query("SELECT berlaku_untuk, COUNT(*) AS c FROM tb_jadwal_absensi WHERE is_active = 1 GROUP BY berlaku_untuk");
if ($resJadwal instanceof mysqli_result) {
    while ($r = $resJadwal->fetch_assoc()) {
        $j = (string)$r['berlaku_untuk'];
        $countsJadwal[$j] = (int)$r['c'];
    }
}

$today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')))->format('Y-m-d');
$statusList = ['Hadir','Telat','Izin','Sakit','Tidak Hadir'];
$countsAbsen = array_fill_keys($statusList, 0);
$resAbsen = db_query("SELECT status_masuk, COUNT(*) AS c FROM tb_absensi WHERE tanggal = ? GROUP BY status_masuk", "s", [$today]);
if ($resAbsen instanceof mysqli_result) {
    while ($r = $resAbsen->fetch_assoc()) {
        $s = (string)$r['status_masuk'];
        if (isset($countsAbsen[$s])) {
            $countsAbsen[$s] = (int)$r['c'];
        }
    }
}
$totalGuru = 0; $totalSiswa = 0;
$rg = db_query("SELECT COUNT(*) AS c FROM tb_guru WHERE is_active=1");
if ($rg instanceof mysqli_result) { $totalGuru = (int)$rg->fetch_assoc()['c']; }
$rs = db_query("SELECT COUNT(*) AS c FROM tb_siswa WHERE is_active=1");
if ($rs instanceof mysqli_result) { $totalSiswa = (int)$rs->fetch_assoc()['c']; }
$absenGuruToday = 0; $absenSiswaToday = 0;
$ag = db_query("SELECT COUNT(*) AS c FROM tb_absensi WHERE jenis='guru' AND tanggal=?", "s", [$today]);
if ($ag instanceof mysqli_result) { $absenGuruToday = (int)$ag->fetch_assoc()['c']; }
$asw = db_query("SELECT COUNT(*) AS c FROM tb_absensi WHERE jenis='siswa' AND tanggal=?", "s", [$today]);
if ($asw instanceof mysqli_result) { $absenSiswaToday = (int)$asw->fetch_assoc()['c']; }
$izinGuruToday = 0; $izinSiswaToday = 0;
$ig = db_query("SELECT COUNT(*) AS c FROM tb_izin WHERE jenis_pengguna='guru' AND status='Disetujui' AND tanggal_mulai<=? AND tanggal_selesai>=?", "ss", [$today, $today]);
if ($ig instanceof mysqli_result) { $izinGuruToday = (int)$ig->fetch_assoc()['c']; }
$isw = db_query("SELECT COUNT(*) AS c FROM tb_izin WHERE jenis_pengguna='siswa' AND status='Disetujui' AND tanggal_mulai<=? AND tanggal_selesai>=?", "ss", [$today, $today]);
if ($isw instanceof mysqli_result) { $izinSiswaToday = (int)$isw->fetch_assoc()['c']; }
$missingGuru = max(0, $totalGuru - $absenGuruToday - $izinGuruToday);
$missingSiswa = max(0, $totalSiswa - $absenSiswaToday - $izinSiswaToday);
$missingTotal = $missingGuru + $missingSiswa;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard • E-Absensi</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/x-icon">
    <style>
        .stats-card .card-body { padding: 18px; }
        .chart-card .card-body { padding: 18px; }
        .absen-actions { display: grid; grid-template-columns: 1fr; gap: 12px; }
        @media (min-width: 480px) { .absen-actions { grid-template-columns: 1fr 1fr; } }
        .btn-absen { padding: 14px; font-weight: 700; border-radius: 14px; }
    </style>
</head>
<body>
    <div id="app">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>
            <div class="page-heading">
                <h3>Dashboard</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="logo">
                                        <img src="../assets/images/logo/logo_absensi.png" alt="Logo" style="height:64px">
                                    </div>
                                    <div>
                                        <h4 class="mb-1">Selamat datang, <?= htmlspecialchars($nama) ?></h4>
                                        <p class="text-muted mb-0">Jabatan: <?= htmlspecialchars($roleLabel) ?></p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                        <p class="text-muted mb-0"><span id="clock"></span> • WITA</p>
                                    </div>
                                    <div class="ms-auto dropdown">
                                        <button class="btn btn-light position-relative" id="btn-notif" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-bell"></i>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= (int)$missingTotal ?></span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="btn-notif" style="min-width:320px">
                                            <div class="px-3 py-2">
                                                <div class="fw-bold mb-1">Peringatan Belum Absen Hari Ini</div>
                                                <div class="d-flex justify-content-between mb-1"><span>Guru</span><span class="badge bg-danger"><?= (int)$missingGuru ?></span></div>
                                                <div class="d-flex justify-content-between mb-2"><span>Siswa</span><span class="badge bg-danger"><?= (int)$missingSiswa ?></span></div>
                                                <div class="d-grid gap-2">
                                                    <a class="btn btn-sm btn-primary" href="../admin/absensi/absensi.php?jenis=guru&from=<?= htmlspecialchars($today) ?>&to=<?= htmlspecialchars($today) ?>">Lihat Guru</a>
                                                    <a class="btn btn-sm btn-outline-primary" href="../admin/absensi/absensi.php?jenis=siswa&from=<?= htmlspecialchars($today) ?>&to=<?= htmlspecialchars($today) ?>">Lihat Siswa</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="absen-actions mb-2">
                                    <?php if ($role === 'siswa'): ?>
                                        <a href="../siswa/absensi.php" class="btn btn-primary btn-absen"><i class="bi bi-camera"></i> Absen Sekarang</a>
                                        <a href="../siswa/izin.php" class="btn btn-outline-secondary btn-absen"><i class="bi bi-file-earmark-text"></i> Ajukan Izin</a>
                                    <?php elseif ($role === 'guru'): ?>
                                        <a href="../guru/absensi.php" class="btn btn-primary btn-absen"><i class="bi bi-camera"></i> Absen Sekarang</a>
                                        <a href="../guru/izin.php" class="btn btn-outline-secondary btn-absen"><i class="bi bi-file-earmark-text"></i> Ajukan Izin</a>
                                    <?php else: ?>
                                        <a href="../admin/absensi/absensi.php" class="btn btn-primary btn-absen"><i class="bi bi-clipboard-check"></i> Rekap Absensi</a>
                                        <a href="../admin/jadwal/jadwal.php" class="btn btn-outline-secondary btn-absen"><i class="bi bi-calendar"></i> Kelola Jadwal</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card stats-card">
                            <div class="card-header"><h4>Distribusi Pengguna</h4></div>
                            <div class="card-body">
                                <div id="chart-users-role"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="card stats-card">
                            <div class="card-header"><h4>Jadwal Aktif</h4></div>
                            <div class="card-body">
                                <div id="chart-jadwal-aktif"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="card chart-card">
                            <div class="card-header"><h4>Absensi Hari Ini</h4></div>
                            <div class="card-body">
                                <div id="chart-absensi-today"></div>
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
    <script src="../assets/vendors/apexcharts/apexcharts.js"></script>
    <script>
        const roleLabels = ['Admin','Guru','Siswa'];
        const roleData = [<?= (int)$countsRole['admin'] ?>, <?= (int)$countsRole['guru'] ?>, <?= (int)$countsRole['siswa'] ?>];
        new ApexCharts(document.querySelector('#chart-users-role'), {
            series: roleData,
            chart: { type: 'donut', height: 300 },
            labels: roleLabels,
            colors: ['#2563eb','#10b981','#f59e0b'],
            legend: { position: 'bottom' }
        }).render();

        const jadwalLabels = ['Guru','Siswa'];
        const jadwalData = [<?= (int)$countsJadwal['guru'] ?>, <?= (int)$countsJadwal['siswa'] ?>];
        new ApexCharts(document.querySelector('#chart-jadwal-aktif'), {
            series: [{ name: 'Jumlah', data: jadwalData }],
            chart: { type: 'bar', height: 300 },
            xaxis: { categories: jadwalLabels },
            colors: ['#22c55e'],
            plotOptions: { bar: { borderRadius: 6 } }
        }).render();

        const absenLabels = <?= json_encode(array_values($statusList), JSON_UNESCAPED_UNICODE) ?>;
        const absenData = <?= json_encode(array_values($countsAbsen), JSON_UNESCAPED_UNICODE) ?>;
        new ApexCharts(document.querySelector('#chart-absensi-today'), {
            series: [{ name: 'Jumlah', data: absenData }],
            chart: { type: 'bar', height: 320 },
            xaxis: { categories: absenLabels },
            colors: ['#3b82f6'],
            plotOptions: { bar: { borderRadius: 6 } }
        }).render();
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
        updateClock();
        setInterval(updateClock, 1000);
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
