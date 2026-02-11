<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'guru') {
    header('Location: /login.php');
    exit;
}
$nama = $_SESSION['nama'] ?? 'Guru';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
$userId = (int)($_SESSION['user_id'] ?? 0);
$jabatan = '';
$resJ = db_query("SELECT jabatan FROM tb_guru WHERE user_id = ? LIMIT 1", "i", [$userId]);
if ($resJ instanceof mysqli_result && $resJ->num_rows === 1) { $jabatan = (string)$resJ->fetch_assoc()['jabatan']; }
$guruId = 0;
$resG = db_query("SELECT guru_id FROM tb_guru WHERE user_id = ? LIMIT 1", "i", [$userId]);
if ($resG instanceof mysqli_result && $resG->num_rows === 1) { $guruId = (int)$resG->fetch_assoc()['guru_id']; }
$tz = new DateTimeZone('Asia/Makassar');
$now = new DateTimeImmutable('now', $tz);
$today = $now->format('Y-m-d');
$hariMap = ['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu'];
$hari = $hariMap[$now->format('D')] ?? 'Senin';
$jadwalRes = db_query("SELECT jam_masuk, batas_telat, jam_pulang, is_libur FROM tb_jadwal_absensi WHERE berlaku_untuk='guru' AND hari=? LIMIT 1", "s", [$hari]);
$jamMasukJ = null; $batasTelatJ = null; $jamPulangJ = null; $liburHari = 0;
if ($jadwalRes instanceof mysqli_result && $jadwalRes->num_rows === 1) {
    $rj = $jadwalRes->fetch_assoc();
    $jamMasukJ = (string)$rj['jam_masuk'];
    $batasTelatJ = (string)$rj['batas_telat'];
    $jamPulangJ = (string)$rj['jam_pulang'];
    $liburHari = (int)$rj['is_libur'];
}
$todayStatusRes = db_query("SELECT status_masuk, status_pulang FROM tb_absensi WHERE jenis='guru' AND guru_id=? AND tanggal=? LIMIT 1", "is", [$guruId, $today]);
$hasMasukToday = false; $hasPulangToday = false;
if ($todayStatusRes instanceof mysqli_result && $todayStatusRes->num_rows === 1) {
    $rowT = $todayStatusRes->fetch_assoc();
    $hasMasukToday = !empty($rowT['status_masuk']);
    $hasPulangToday = !empty($rowT['status_pulang']) && $rowT['status_pulang'] === 'Pulang';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Guru • E-Absensi</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/x-icon">
    <style>
        .absen-card { border: 1px solid #eef2ff; border-radius: 16px; }
        .absen-actions { display: grid; grid-template-columns: 1fr; gap: 12px; }
        @media (min-width: 480px) { .absen-actions { grid-template-columns: 1fr 1fr; } }
        .btn-absen { padding: 14px; font-weight: 700; border-radius: 14px; }
        .clock { font-size: 20px; font-weight: 700; color:#2563eb; }
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
                <h3>Dashboard Guru</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card absen-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="logo">
                                        <img src="../assets/images/logo/logo_absensi.png" alt="Logo" style="height:64px">
                                    </div>
                                    <div>
                                        <h4 class="mb-1">Selamat datang, <?= htmlspecialchars($nama) ?></h4>
                                        <p class="text-muted mb-0">Jabatan: <?= htmlspecialchars($jabatan !== '' ? $jabatan : '-') ?></p>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                        <p class="clock mt-1"><span id="clock"></span> • WITA</p>
                                    </div>
                                    <div class="ms-auto dropdown">
                                        <button class="btn btn-light position-relative" id="btn-notif" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-bell"></i>
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notif-count">0</span>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="btn-notif" style="min-width:320px">
                                            <div class="px-3 py-2" id="notif-list">
                                                <div class="text-muted">Tidak ada notifikasi</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="absen-actions mb-3">
                                    <a href="../guru/absensi.php" class="btn btn-primary btn-absen"><i class="bi bi-camera"></i> Absen Sekarang</a>
                                    <a href="../guru/izin.php" class="btn btn-outline-secondary btn-absen"><i class="bi bi-file-earmark-text"></i> Ajukan Izin</a>
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
    <script>
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
        var jadwal = {
            jam_masuk: "<?= htmlspecialchars((string)($jamMasukJ ?? '06:45:00')) ?>",
            batas_telat: "<?= htmlspecialchars((string)($batasTelatJ ?? '07:05:00')) ?>",
            jam_pulang: "<?= htmlspecialchars((string)($jamPulangJ ?? '15:30:00')) ?>",
            is_libur: <?= (int)$liburHari ?>
        };
        var statusToday = { has_masuk: <?= $hasMasukToday ? 'true' : 'false' ?>, has_pulang: <?= $hasPulangToday ? 'true' : 'false' ?> };
        var guruId = <?= (int)$guruId ?>;
        function parseTimeToDate(timeStr) {
            var now = new Date();
            var parts = timeStr.split(':');
            var d = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parseInt(parts[0]), parseInt(parts[1]), parseInt(parts[2]||'0'));
            return d;
        }
        function buildNotifications() {
            var listEl = document.getElementById('notif-list');
            var countEl = document.getElementById('notif-count');
            if (!listEl || !countEl) return;
            var items = [];
            if (jadwal.is_libur === 1) {
                items.push({ text: "Hari ini libur. Tidak ada absensi.", type: "secondary" });
            } else {
                var now = new Date();
                var masukOpen = parseTimeToDate(jadwal.jam_masuk);
                var batasTelat = parseTimeToDate(jadwal.batas_telat);
                if (!statusToday.has_masuk && now >= masukOpen && now < batasTelat) {
                    items.push({ text: "Pengingat: waktu absen sudah dibuka. Segera absen.", type: "info" });
                }
                if (!statusToday.has_masuk && now >= batasTelat) {
                    items.push({ text: "Anda terlambat. Segera lakukan absen.", type: "warning" });
                }
                if (statusToday.has_masuk && !statusToday.has_pulang) {
                    var pulangOpen = parseTimeToDate(jadwal.jam_pulang);
                    if (now >= pulangOpen) {
                        items.push({ text: "Pengingat: waktu absen pulang sudah dibuka.", type: "primary" });
                    }
                }
            }
            listEl.innerHTML = "";
            if (items.length === 0) {
                listEl.innerHTML = '<div class="text-muted">Tidak ada notifikasi</div>';
                countEl.textContent = "0";
            } else {
                items.forEach(function(it){
                    var div = document.createElement('div');
                    div.className = "alert alert-" + it.type + " mb-2 py-1 px-2";
                    div.textContent = it.text;
                    listEl.appendChild(div);
                });
                countEl.textContent = String(items.length);
            }
        }
        buildNotifications();
        setInterval(function(){
            fetch('../admin/absensi/absensi.php?action=status_today&jenis=guru&id=' + guruId)
                .then(function(r){ return r.json(); })
                .then(function(js){
                    statusToday.has_masuk = !!js.has_masuk;
                    statusToday.has_pulang = !!js.has_pulang;
                    buildNotifications();
                })
                .catch(function(){ buildNotifications(); });
        }, 60000);
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
