<?php
session_start();
require __DIR__ . '/../config/koneksi.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'siswa') {
    header('Location: /login.php');
    exit;
}
$nama = $_SESSION['nama'] ?? 'Siswa';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
$userId = (int)($_SESSION['user_id'] ?? 0);
$siswaId = 0;
$resSiswa = db_query("SELECT siswa_id FROM tb_siswa WHERE user_id = ? LIMIT 1", "i", [$userId]);
if ($resSiswa instanceof mysqli_result && $resSiswa->num_rows === 1) {
    $rowS = $resSiswa->fetch_assoc();
    $siswaId = (int)$rowS['siswa_id'];
}
$kelasNama = ''; $kelasTingkat = '';
$resKelas = db_query("SELECT k.nama_kelas, k.tingkat FROM tb_kelas k JOIN tb_siswa s ON s.kelas_id = k.kelas_id WHERE s.siswa_id = ? LIMIT 1", "i", [$siswaId]);
if ($resKelas instanceof mysqli_result && $resKelas->num_rows === 1) { $rk = $resKelas->fetch_assoc(); $kelasNama = (string)$rk['nama_kelas']; $kelasTingkat = (string)$rk['tingkat']; }
$riwayat = db_query("SELECT tanggal, status_masuk, jam_masuk, status_pulang, jam_pulang, jarak_masuk_meter FROM tb_absensi WHERE jenis='siswa' AND siswa_id = ? ORDER BY tanggal DESC, absensi_id DESC LIMIT 10", "i", [$siswaId]);
$tz = new DateTimeZone('Asia/Makassar');
$now = new DateTimeImmutable('now', $tz);
$today = $now->format('Y-m-d');
$hariMap = ['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu'];
$hari = $hariMap[$now->format('D')] ?? 'Senin';
$jadwalRes = db_query("SELECT jam_masuk, batas_telat, jam_pulang, is_libur FROM tb_jadwal_absensi WHERE berlaku_untuk='siswa' AND hari=? LIMIT 1", "s", [$hari]);
$jamMasukJ = null; $batasTelatJ = null; $jamPulangJ = null; $liburHari = 0;
if ($jadwalRes instanceof mysqli_result && $jadwalRes->num_rows === 1) {
    $rj = $jadwalRes->fetch_assoc();
    $jamMasukJ = (string)$rj['jam_masuk'];
    $batasTelatJ = (string)$rj['batas_telat'];
    $jamPulangJ = (string)$rj['jam_pulang'];
    $liburHari = (int)$rj['is_libur'];
}
$todayStatusRes = db_query("SELECT status_masuk, status_pulang FROM tb_absensi WHERE jenis='siswa' AND siswa_id=? AND tanggal=? LIMIT 1", "is", [$siswaId, $today]);
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
    <title>Dashboard Siswa • E-Absensi</title>
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
        .hint { font-size: 12px; color:#6b7280; }
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
                <h3>Dashboard Siswa</h3>
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
                                        <p class="text-muted mb-0">Kelas: <?= htmlspecialchars($kelasNama !== '' ? $kelasNama : '-') ?></p>
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
                                    <a href="../siswa/absensi.php" class="btn btn-primary btn-absen"><i class="bi bi-camera"></i> Absen Sekarang</a>
                                    <a href="../siswa/izin.php" class="btn btn-outline-secondary btn-absen"><i class="bi bi-file-earmark-text"></i> Ajukan Izin</a>
                                </div>
                                <h6 class="mb-2">Riwayat Absensi Terbaru</h6>
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
                                                <tr><td colspan="6" class="text-muted">Belum ada data absensi</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
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
            jam_masuk: "<?= htmlspecialchars((string)($jamMasukJ ?? '07:00:00')) ?>",
            batas_telat: "<?= htmlspecialchars((string)($batasTelatJ ?? '07:15:00')) ?>",
            jam_pulang: "<?= htmlspecialchars((string)($jamPulangJ ?? '15:00:00')) ?>",
            is_libur: <?= (int)$liburHari ?>
        };
        var statusToday = { has_masuk: <?= $hasMasukToday ? 'true' : 'false' ?>, has_pulang: <?= $hasPulangToday ? 'true' : 'false' ?> };
        var siswaId = <?= (int)$siswaId ?>;
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
            fetch('../admin/absensi/absensi.php?action=status_today&jenis=siswa&id=' + siswaId)
                .then(function(r){ return r.json(); })
                .then(function(js){
                    statusToday.has_masuk = !!js.has_masuk;
                    statusToday.has_pulang = !!js.has_pulang;
                    buildNotifications();
                })
                .catch(function(){ buildNotifications(); });
        }, 60000);
        var statusEl = document.getElementById('status-geoloc');
        function setStatus(t) { if (statusEl) statusEl.textContent = t; }
        function initGeolocation() {
            if (!navigator.geolocation) { setStatus('Geolocation tidak didukung browser'); return; }
            navigator.geolocation.getCurrentPosition(function(pos) {
                document.getElementById('lat').value = pos.coords.latitude;
                document.getElementById('lng').value = pos.coords.longitude;
                document.getElementById('akurasi').value = pos.coords.accuracy;
                setStatus('Lokasi siap • akurasi ± ' + Math.round(pos.coords.accuracy) + ' m');
            }, function(err) {
                setStatus('Gagal ambil lokasi: ' + err.message);
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        }
        initGeolocation();
        document.getElementById('btn-absen').addEventListener('click', function() {
            var lat = document.getElementById('lat').value;
            var lng = document.getElementById('lng').value;
            var fotoInput = document.querySelector('input[name="foto"]');
            if (!lat || !lng) { setStatus('Lokasi belum siap, coba lagi'); return; }
            if (!fotoInput || !fotoInput.files || fotoInput.files.length === 0) { setStatus('Ambil foto terlebih dahulu'); return; }
            document.getElementById('form-absen').submit();
        });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
