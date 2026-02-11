<?php
session_start();
require __DIR__ . '/../config/koneksi.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'siswa') { header('Location: /login.php'); exit; }
$nama = $_SESSION['nama'] ?? 'Siswa';
$userId = (int)($_SESSION['user_id'] ?? 0);
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
$resSiswa = db_query("SELECT s.siswa_id, u.username FROM tb_siswa s JOIN tb_users u ON s.user_id=u.user_id WHERE s.user_id = ? LIMIT 1", "i", [$userId]);
$siswaId = 0; $username = '';
if ($resSiswa instanceof mysqli_result && $resSiswa->num_rows === 1) { $rowS = $resSiswa->fetch_assoc(); $siswaId = (int)$rowS['siswa_id']; $username = (string)$rowS['username']; }
$hariMap = ['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu'];
$now = new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar'));
$hari = $hariMap[$now->format('D')] ?? 'Senin';
$resJ = db_query("SELECT jam_masuk, batas_telat, jam_pulang, is_libur FROM tb_jadwal_absensi WHERE berlaku_untuk='siswa' AND hari=? LIMIT 1", "s", [$hari]);
$jadwal = ['jam_masuk'=>null,'batas_telat'=>null,'jam_pulang'=>null,'is_libur'=>0];
if ($resJ instanceof mysqli_result && $resJ->num_rows === 1) { $rj = $resJ->fetch_assoc(); $jadwal['jam_masuk'] = (string)$rj['jam_masuk']; $jadwal['batas_telat'] = (string)$rj['batas_telat']; $jadwal['jam_pulang'] = (string)$rj['jam_pulang']; $jadwal['is_libur'] = (int)$rj['is_libur']; }
$lok = ['nama_lokasi'=>'','lat'=>null,'lng'=>null,'radius'=>100];
$resP = db_query("SELECT nama_lokasi, latitude, longitude, radius_meter FROM tb_pengaturan_absensi WHERE pengaturan_id=1 LIMIT 1");
if ($resP instanceof mysqli_result && $resP->num_rows === 1) { $rp = $resP->fetch_assoc(); $lok['nama_lokasi'] = (string)$rp['nama_lokasi']; $lok['lat'] = (float)$rp['latitude']; $lok['lng'] = (float)$rp['longitude']; $lok['radius'] = (int)$rp['radius_meter']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Absensi Siswa • E-Absensi</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="shortcut icon" href="../assets/images/favicon.svg" type="image/x-icon">
    <style>
        .absen-wrap { border:1px solid #eef2ff; border-radius:16px; }
        .btn-absen { padding: 14px; font-weight: 700; border-radius: 14px; }
        .clock { font-size: 20px; font-weight: 700; color:#2563eb; }
        .hint { font-size: 12px; color:#6b7280; }
        .badge-info { background:#e0f2fe; color:#075985; }
        .info-list { font-size: 13px; line-height: 1.6; }
        .status-ok { color:#16a34a; }
        .status-bad { color:#dc2626; }
        .cam-box { position:relative; width:100%; border-radius:16px; overflow:hidden; background:#0b1324; box-shadow:0 6px 16px rgba(0,0,0,.12); }
        .cam-box.ratio-4x3 { padding-top:75%; }
        .cam-layer { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    </style>
</head>
<body>
    <div id="app">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none"><i class="bi bi-justify fs-3"></i></a>
            </header>
            <div class="page-heading">
                <h3>Absensi</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card absen-wrap">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="logo"><img src="../assets/images/logo/logo_absensi.png" alt="Logo" style="height:64px"></div>
                                    <div>
                                        <h4 class="mb-1"><?= htmlspecialchars($nama) ?> (<?= htmlspecialchars($username) ?>)</h4>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                        <p class="clock mt-1"><span id="clock"></span> • WITA</p>
                                    </div>
                                </div>
                                <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge badge-info">Lokasi: <?= htmlspecialchars($lok['nama_lokasi']) ?> • Radius <?= (int)$lok['radius'] ?> m</span>
                                    <?php if ($jadwal['is_libur'] === 1): ?>
                                        <span class="badge bg-danger">Libur</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Masuk <?= htmlspecialchars((string)$jadwal['jam_masuk'] ?? '-') ?> • Telat <?= htmlspecialchars((string)$jadwal['batas_telat'] ?? '-') ?> • Pulang <?= htmlspecialchars((string)$jadwal['jam_pulang'] ?? '-') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="mt-2 info-list">
                                    <div>Koordinat Sekolah: <?= htmlspecialchars((string)$lok['lat']) ?>, <?= htmlspecialchars((string)$lok['lng']) ?></div>
                                    <div>Koordinat Perangkat: <span id="dev-lat">-</span>, <span id="dev-lng">-</span></div>
                                    <div>Jarak ke Sekolah: <span id="dev-dist">-</span> m</div>
                                    <div>Status Radius: <span id="dev-radius" class="status-bad">Belum diverifikasi</span></div>
                                </div>
                                <hr>
                                <form id="form-absen" method="post" action="../admin/absensi/absensi.php" enctype="multipart/form-data">
                                    <input type="hidden" name="action" value="add_secure">
                                    <input type="hidden" name="jenis" value="siswa">
                                    <input type="hidden" name="user_map" value="<?= (int)$siswaId ?>">
                                    <input type="hidden" name="tanggal" value="<?= $now->format('Y-m-d') ?>">
                                    <input type="hidden" id="lat" name="lat" value="">
                                    <input type="hidden" id="lng" name="lng" value="">
                                    <input type="hidden" id="akurasi" name="akurasi" value="">
                                    <input type="hidden" id="client_time" name="client_time" value="">
                                    <div class="row g-3 align-items-start">
                                        <div class="col-12 col-lg-7">
                                            <label class="form-label">Kamera</label>
                                            <div class="cam-box ratio-4x3 mb-2">
                                                <video id="cam" class="cam-layer" autoplay playsinline></video>
                                                <canvas id="canvas" class="cam-layer d-none"></canvas>
                                            </div>
                                            <input type="hidden" id="photo_blob_ready" value="0">
                                            <button type="button" id="btn-capture" class="btn btn-outline-primary"><i class="bi bi-camera"></i> Ambil Foto</button>
                                            <div class="hint mt-1">Gunakan kamera belakang, foto akan diberi watermark waktu dan lokasi</div>
                                        </div>
                                        <div class="col-12 col-lg-5">
                                            <div class="alert alert-secondary py-2 px-3 mb-3" id="status-geoloc">Mengambil lokasi perangkat…</div>
                                            <div class="d-grid gap-2">
                                                <button type="button" id="btn-aksi" class="btn btn-primary w-100 btn-absen"><i class="bi bi-geo-alt"></i> Absen</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
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
        var serverTime = null;
        var JADWAL_PULANG = <?= json_encode((string)$jadwal['jam_pulang'] ?? '') ?>;
        var HAS_MASUK = false; var HAS_PULANG = false;
        function refreshButtonState(){
            var btnA = document.getElementById('btn-aksi');
            if (!btnA) return;
            if (!HAS_MASUK) {
                btnA.disabled = false;
                btnA.textContent = 'Absen Masuk';
                btnA.className = 'btn btn-primary w-100 btn-absen';
                btnA.title = '';
            } else {
                btnA.textContent = 'Absen Pulang';
                btnA.className = 'btn btn-outline-primary w-100 btn-absen';
                if (serverTime && JADWAL_PULANG) {
                    var sdt = new Date(serverTime);
                    var y = sdt.getFullYear(), m = ('0'+(sdt.getMonth()+1)).slice(-2), d = ('0'+sdt.getDate()).slice(-2);
                    var tp = new Date(y+'-'+m+'-'+d+'T'+JADWAL_PULANG+':00');
                    var threshold = new Date(tp.getTime() - 10*60*1000);
                    if (sdt < threshold) {
                        btnA.disabled = true;
                        btnA.title = 'Pulang bisa 10 menit sebelum jam pulang';
                    } else {
                        btnA.disabled = false;
                        btnA.title = '';
                    }
                } else {
                    btnA.disabled = false;
                }
            }
        }
        function updateClock(){var el=document.getElementById('clock');if(!el)return;var fmt=new Intl.DateTimeFormat('id-ID',{timeZone:'Asia/Makassar',hour12:false,hour:'2-digit',minute:'2-digit',second:'2-digit'}).format(new Date());el.textContent=fmt.replace(/\./g,':');}
        updateClock();setInterval(updateClock,1000);
        var statusEl=document.getElementById('status-geoloc');function setStatus(t){if(statusEl)statusEl.textContent=t;}
        fetch('../admin/absensi/absensi.php?action=time').then(r=>r.json()).then(j=>{ serverTime=j.server_time; refreshButtonState(); }).catch(()=>{});
        fetch('../admin/absensi/absensi.php?action=status_today&jenis=siswa&id=<?= (int)$siswaId ?>').then(r=>r.json()).then(s=>{ HAS_MASUK=!!s.has_masuk; HAS_PULANG=!!s.has_pulang; refreshButtonState(); }).catch(()=>{});
        function initGeolocation(){
            if(!navigator.geolocation){setStatus('Geolocation tidak didukung browser');return;}
            navigator.geolocation.getCurrentPosition(function(pos){
                document.getElementById('lat').value=pos.coords.latitude;
                document.getElementById('lng').value=pos.coords.longitude;
                document.getElementById('akurasi').value=pos.coords.accuracy;
                var acc=Math.round(pos.coords.accuracy);
                var slat=<?= json_encode($lok['lat']) ?>;var slng=<?= json_encode($lok['lng']) ?>;
                var d=(function(lat1,lon1,lat2,lon2){var R=6371000;var toRad=function(x){return x*Math.PI/180};var dLat=toRad(lat2-lat1);var dLon=toRad(lon2-lon1);var a=Math.sin(dLat/2)*Math.sin(dLat/2)+Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLon/2)*Math.sin(dLon/2);var c=2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));return R*c;})(pos.coords.latitude,pos.coords.longitude,slat,slng);
                document.getElementById('dev-lat').textContent=pos.coords.latitude.toFixed(6);
                document.getElementById('dev-lng').textContent=pos.coords.longitude.toFixed(6);
                document.getElementById('dev-dist').textContent=Math.round(d);
                var ok=d<=<?= (int)$lok['radius'] ?>;
                var el=document.getElementById('dev-radius');el.textContent=ok?'Dalam radius':'Di luar radius';el.className=ok?'status-ok':'status-bad';
                var statusText='Lokasi '+(ok?'siap':'di luar radius')+' • akurasi ± '+acc+' m';
                var statusBox=document.getElementById('status-geoloc');statusBox.textContent=statusText;statusBox.className='alert '+(ok?'alert-success':'alert-warning')+' py-2 px-3 mb-3';
            },function(err){
                setStatus('Gagal ambil lokasi: '+err.message);
            },{enableHighAccuracy:true,timeout:15000,maximumAge:0});
        }
        initGeolocation();
        var cam=document.getElementById('cam');var canvas=document.getElementById('canvas');var captureBtn=document.getElementById('btn-capture');
        if(navigator.mediaDevices&&navigator.mediaDevices.getUserMedia){
            navigator.mediaDevices.getUserMedia({video:{facingMode:'environment',width:{ideal:640},height:{ideal:480}}})
                .then(stream=>{cam.srcObject=stream;})
                .catch(()=>{});
        }
        captureBtn.addEventListener('click',function(){
            var lat=document.getElementById('lat').value;var lng=document.getElementById('lng').value;
            var ts=new Date().toLocaleString('id-ID',{hour12:false});
            var w=cam.videoWidth||640,h=cam.videoHeight||480;
            canvas.width=w;canvas.height=h;
            var ctx=canvas.getContext('2d');
            ctx.drawImage(cam,0,0,w,h);
            ctx.fillStyle='rgba(0,0,0,0.5)';ctx.fillRect(0,h-50,w,50);
            ctx.fillStyle='#fff';ctx.font='16px Nunito';
            ctx.fillText('E-Absensi • '+ts+' • '+lat+','+lng,12,h-20);
            document.getElementById('photo_blob_ready').value='1';
            cam.classList.add('d-none');
            canvas.classList.remove('d-none');
        });
        function submitAction(){
            var lat=document.getElementById('lat').value;var lng=document.getElementById('lng').value;
            if(!lat||!lng){setStatus('Lokasi belum siap');return;}
            var btn=document.getElementById('btn-aksi');btn.disabled=true;btn.textContent='Mengirim...';
            var diffOk=true;
            try{if(serverTime){var st=new Date(serverTime).getTime();var ct=Date.now();var skew=Math.abs(ct-st)/1000;diffOk=skew<=60;}}catch(e){}
            if(!diffOk){setStatus('Sinkronisasi waktu tidak valid');btn.disabled=false;btn.textContent='Absen Sekarang';return;}
            var actionName = (!HAS_MASUK ? 'add_secure' : 'add_pulang_secure');
            if(actionName==='add_pulang_secure' && JADWAL_PULANG){
                try{
                    var sdt = serverTime ? new Date(serverTime) : new Date();
                    var y = sdt.getFullYear(), m = ('0'+(sdt.getMonth()+1)).slice(-2), d = ('0'+sdt.getDate()).slice(-2);
                    var tp = new Date(y+'-'+m+'-'+d+'T'+JADWAL_PULANG+':00');
                    var threshold = new Date(tp.getTime() - 10*60*1000);
                    if (sdt < threshold) {
                        setStatus('Belum waktu pulang (10 menit sebelum jam pulang)');
                        btn.disabled=false;btn.textContent='Absen';
                        return;
                    }
                }catch(e){}
            }
            document.getElementById('client_time').value=new Date().toISOString();
            var sendForm=function(fd){
                fetch('../admin/absensi/absensi.php',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd})
                    .then(r=>r.json())
                    .then(j=>{
                        if(j.status==='ok'){
                            setStatus('Berhasil: '+(j.message||'Absensi ditambahkan'));
                            window.location.href='../siswa/riwayat.php';
                        }else{
                            setStatus('Gagal: '+(j.message||'Tidak tersimpan'));
                            btn.disabled=false;btn.textContent='Absen Sekarang';
                        }
                    })
                    .catch(()=>{setStatus('Gagal kirim');btn.disabled=false;btn.textContent='Absen Sekarang';});
            };
            if(document.getElementById('photo_blob_ready').value==='1'){
                canvas.toBlob(function(blob){var fd=new FormData(document.getElementById('form-absen'));fd.set('action', actionName);fd.append('photo',blob,'absen.jpg');sendForm(fd);},'image/jpeg',0.6);
            }else{
                setStatus('Ambil foto terlebih dahulu');btn.disabled=false;btn.textContent='Absen Sekarang';
            }
        }
        document.getElementById('btn-aksi').addEventListener('click',function(){ submitAction(); });
    </script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
