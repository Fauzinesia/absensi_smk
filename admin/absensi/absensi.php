<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }

$tz = new DateTimeZone('Asia/Makassar');
if (isset($_GET['action']) && $_GET['action'] === 'time') {
    $now = new DateTimeImmutable('now', $tz);
    header('Content-Type: application/json');
    echo json_encode(['server_time' => $now->format('c')]);
    exit;
}
if (isset($_GET['action']) && $_GET['action'] === 'status_today') {
    $jenisQ = isset($_GET['jenis']) ? (string)$_GET['jenis'] : 'siswa';
    $idQ = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $today = (new DateTimeImmutable('now', $tz))->format('Y-m-d');
    $hasMasuk = false; $hasPulang = false;
    if ($jenisQ === 'guru') {
        $rs = db_query("SELECT status_masuk, status_pulang FROM tb_absensi WHERE jenis='guru' AND guru_id=? AND tanggal=?", "is", [$idQ, $today]);
    } else {
        $rs = db_query("SELECT status_masuk, status_pulang FROM tb_absensi WHERE jenis='siswa' AND siswa_id=? AND tanggal=?", "is", [$idQ, $today]);
    }
    if ($rs instanceof mysqli_result && $rs->num_rows === 1) {
        $row = $rs->fetch_assoc();
        $hasMasuk = !empty($row['status_masuk']);
        $hasPulang = !empty($row['status_pulang']) && $row['status_pulang'] === 'Pulang';
    }
    header('Content-Type: application/json');
    echo json_encode(['has_masuk' => $hasMasuk, 'has_pulang' => $hasPulang]);
    exit;
}

$msg = null;
$jenis = isset($_GET['jenis']) && in_array($_GET['jenis'], ['guru','siswa','all'], true) ? $_GET['jenis'] : 'guru';
$date_from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : (new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')))->format('Y-m-d');
$date_to = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : $date_from;
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'add') {
        $ajenis = (string)($_POST['jenis'] ?? 'guru');
        $user_map = isset($_POST['user_map']) ? (int)$_POST['user_map'] : 0;
        $tanggal = (string)($_POST['tanggal'] ?? '');
        $status_masuk = (string)($_POST['status_masuk'] ?? '');
        $status_pulang = (string)($_POST['status_pulang'] ?? '');
        $jam_masuk = (string)($_POST['jam_masuk'] ?? '');
        $jam_pulang = (string)($_POST['jam_pulang'] ?? '');
        $catatan = trim((string)($_POST['catatan'] ?? ''));
        if (in_array($ajenis,['guru','siswa'],true) && $user_map>0 && $tanggal!=='') {
            if ($ajenis === 'guru') {
                $ok = db_query("INSERT INTO tb_absensi (jenis, guru_id, tanggal, status_masuk, status_pulang, jam_masuk, jam_pulang, catatan) VALUES ('guru', ?, ?, ?, ?, ?, ?, ?)", "issssss", [$user_map, $tanggal, $status_masuk !== '' ? $status_masuk : null, $status_pulang !== '' ? $status_pulang : null, $jam_masuk !== '' ? $jam_masuk : null, $jam_pulang !== '' ? $jam_pulang : null, $catatan !== '' ? $catatan : null]);
            } else {
                $ok = db_query("INSERT INTO tb_absensi (jenis, siswa_id, tanggal, status_masuk, status_pulang, jam_masuk, jam_pulang, catatan) VALUES ('siswa', ?, ?, ?, ?, ?, ?, ?)", "issssss", [$user_map, $tanggal, $status_masuk !== '' ? $status_masuk : null, $status_pulang !== '' ? $status_pulang : null, $jam_masuk !== '' ? $jam_masuk : null, $jam_pulang !== '' ? $jam_pulang : null, $catatan !== '' ? $catatan : null]);
            }
            $msg = $ok ? 'Absensi ditambahkan' : 'Gagal menambah absensi';
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'add_quick') {
        $ajenis = (string)($_POST['jenis'] ?? 'siswa');
        $user_map = isset($_POST['user_map']) ? (int)$_POST['user_map'] : 0;
        $tanggal = (string)($_POST['tanggal'] ?? '');
        $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
        $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
        $akurasi = isset($_POST['akurasi']) ? (float)$_POST['akurasi'] : null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;
        $fotoPath = null;
        if (isset($_FILES['foto']) && is_array($_FILES['foto']) && (int)$_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['foto']['tmp_name'];
            $size = (int)$_FILES['foto']['size'];
            $info = @getimagesize($tmp);
            $mime = is_array($info) && isset($info['mime']) ? $info['mime'] : null;
            if (($mime === 'image/jpeg' || $mime === 'image/png') && $size <= 2 * 1024 * 1024) {
                $ext = $mime === 'image/jpeg' ? 'jpg' : 'png';
                $name = 'absen_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $dir = __DIR__ . '/../../uploads/absensi';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $dest = $dir . '/' . $name;
                if (@move_uploaded_file($tmp, $dest)) { $fotoPath = 'uploads/absensi/' . $name; }
            }
        }
        if (in_array($ajenis,['guru','siswa'],true) && $user_map>0 && $tanggal!=='' && $lat !== null && $lng !== null) {
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar'));
            $hariMap = ['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu'];
            $hari = $hariMap[$now->format('D')] ?? 'Senin';
            $resJ = db_query("SELECT jam_masuk, batas_telat, jam_pulang, is_libur FROM tb_jadwal_absensi WHERE berlaku_untuk = ? AND hari = ? LIMIT 1", "ss", [$ajenis, $hari]);
            $jamMasukJ = null; $batasTelatJ = null; $jamPulangJ = null; $isLibur = 0;
            if ($resJ instanceof mysqli_result && $resJ->num_rows === 1) {
                $rj = $resJ->fetch_assoc();
                $jamMasukJ = (string)$rj['jam_masuk'];
                $batasTelatJ = (string)$rj['batas_telat'];
                $jamPulangJ = (string)$rj['jam_pulang'];
                $isLibur = (int)$rj['is_libur'];
            }
            $resP = db_query("SELECT latitude, longitude, radius_meter FROM tb_pengaturan_absensi WHERE pengaturan_id = 1 LIMIT 1");
            $latSch = null; $lngSch = null; $radius = 100;
            if ($resP instanceof mysqli_result && $resP->num_rows === 1) {
                $rp = $resP->fetch_assoc();
                $latSch = (float)$rp['latitude']; $lngSch = (float)$rp['longitude']; $radius = (int)$rp['radius_meter'];
            }
            $distance = null;
            if ($latSch !== null && $lngSch !== null) {
                $toRad = function($d){ return $d * (pi()/180); };
                $R = 6371000.0;
                $dLat = $toRad($lat - $latSch);
                $dLng = $toRad($lng - $lngSch);
                $a = sin($dLat/2)**2 + cos($toRad($latSch)) * cos($toRad($lat)) * sin($dLng/2)**2;
                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                $distance = $R * $c;
            }
            $jamNow = $now->format('Y-m-d H:i:s');
            $statusMasuk = null;
            if ($isLibur === 1) {
                $statusMasuk = 'Tidak Hadir';
            } else {
                if ($distance !== null && $distance > $radius) {
                    $statusMasuk = 'Tidak Hadir';
                } else {
                    if ($batasTelatJ) {
                        $bt = DateTimeImmutable::createFromFormat('H:i:s', $batasTelatJ, new DateTimeZone('Asia/Makassar'));
                        $todayBt = $now->format('Y-m-d') . ' ' . $batasTelatJ;
                        $statusMasuk = ($jamNow <= ($now->format('Y-m-d') . ' ' . $batasTelatJ)) ? 'Hadir' : 'Telat';
                    } else {
                        $statusMasuk = 'Hadir';
                    }
                }
            }
            if ($ajenis === 'guru') {
                $ok = db_query(
                    "INSERT INTO tb_absensi (jenis, guru_id, tanggal, jadwal_jam_masuk, jadwal_batas_telat, jadwal_jam_pulang, status_masuk, jam_masuk, lat_masuk, lng_masuk, akurasi_masuk, jarak_masuk_meter, foto_masuk, device_info) VALUES ('guru', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    "issssssddddss",
                    [$user_map, $tanggal, $jamMasukJ, $batasTelatJ, $jamPulangJ, $statusMasuk, $jamNow, $lat, $lng, $akurasi, $distance, $fotoPath, $ua]
                );
            } else {
                $ok = db_query(
                    "INSERT INTO tb_absensi (jenis, siswa_id, tanggal, jadwal_jam_masuk, jadwal_batas_telat, jadwal_jam_pulang, status_masuk, jam_masuk, lat_masuk, lng_masuk, akurasi_masuk, jarak_masuk_meter, foto_masuk, device_info) VALUES ('siswa', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    "issssssddddss",
                    [$user_map, $tanggal, $jamMasukJ, $batasTelatJ, $jamPulangJ, $statusMasuk, $jamNow, $lat, $lng, $akurasi, $distance, $fotoPath, $ua]
                );
            }
            $msg = $ok ? 'Absensi ditambahkan' : 'Gagal menambah absensi';
        } else {
            $msg = 'Data absen tidak lengkap';
        }
    } elseif ($action === 'add_secure') {
        $ok = false;
        $ajenis = (string)($_POST['jenis'] ?? 'siswa');
        $user_map = isset($_POST['user_map']) ? (int)$_POST['user_map'] : 0;
        $tanggal = (string)($_POST['tanggal'] ?? '');
        $clientTime = (string)($_POST['client_time'] ?? '');
        $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
        $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
        $akurasi = isset($_POST['akurasi']) ? (float)$_POST['akurasi'] : null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;
        $fotoPath = null;
        if (isset($_FILES['photo']) && is_array($_FILES['photo']) && (int)$_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['photo']['tmp_name'];
            $size = (int)$_FILES['photo']['size'];
            $info = @getimagesize($tmp);
            $mime = is_array($info) && isset($info['mime']) ? $info['mime'] : null;
            if (($mime === 'image/jpeg' || $mime === 'image/png') && $size <= 2 * 1024 * 1024) {
                $ext = $mime === 'image/jpeg' ? 'jpg' : 'png';
                $name = 'absen_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $dir = __DIR__ . '/../../uploads/absensi';
                if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $dest = $dir . '/' . $name;
                if (@move_uploaded_file($tmp, $dest)) { $fotoPath = 'uploads/absensi/' . $name; }
            }
        }
        if (in_array($ajenis,['guru','siswa'],true) && $user_map>0 && $tanggal!=='' && $lat !== null && $lng !== null && $clientTime !== '') {
            $now = new DateTimeImmutable('now', $tz);
            $clientDt = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $clientTime);
            if (!$clientDt) { $clientDt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $clientTime); }
            $skew = $clientDt instanceof DateTimeImmutable ? abs($now->getTimestamp() - $clientDt->getTimestamp()) : 9999;
            if ($skew > 60) {
                $msg = 'Waktu tidak sinkron (±1 menit)';
            }
            if ($msg === null) {
                $today = $now->format('Y-m-d');
                if ($ajenis === 'guru') {
                    $iz = db_query("SELECT izin_id FROM tb_izin WHERE jenis_pengguna='guru' AND guru_id=? AND status='Disetujui' AND tanggal_mulai<=? AND tanggal_selesai>=? LIMIT 1", "iss", [$user_map, $today, $today]);
                } else {
                    $iz = db_query("SELECT izin_id FROM tb_izin WHERE jenis_pengguna='siswa' AND siswa_id=? AND status='Disetujui' AND tanggal_mulai<=? AND tanggal_selesai>=? LIMIT 1", "iss", [$user_map, $today, $today]);
                }
                if ($iz instanceof mysqli_result && $iz->num_rows === 1) { $msg = 'Memiliki izin disetujui untuk hari ini'; }
                if ($msg === null) {
                    if ($ajenis === 'guru') {
                        $ex = db_query("SELECT status_masuk FROM tb_absensi WHERE jenis='guru' AND guru_id=? AND tanggal=? LIMIT 1", "is", [$user_map, $today]);
                    } else {
                        $ex = db_query("SELECT status_masuk FROM tb_absensi WHERE jenis='siswa' AND siswa_id=? AND tanggal=? LIMIT 1", "is", [$user_map, $today]);
                    }
                    if ($ex instanceof mysqli_result && $ex->num_rows === 1) {
                        $sm = (string)$ex->fetch_assoc()['status_masuk'];
                        if ($sm === 'Izin' || $sm === 'Sakit') { $msg = 'Status hari ini Izin/Sakit'; }
                    }
                }
            }
            $hariMap = ['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu'];
            $hari = $hariMap[$now->format('D')] ?? 'Senin';
            $resJ = db_query("SELECT jam_masuk, batas_telat, jam_pulang, is_libur FROM tb_jadwal_absensi WHERE berlaku_untuk = ? AND hari = ? LIMIT 1", "ss", [$ajenis, $hari]);
            $jamMasukJ = null; $batasTelatJ = null; $jamPulangJ = null; $isLibur = 0;
            if ($resJ instanceof mysqli_result && $resJ->num_rows === 1) {
                $rj = $resJ->fetch_assoc();
                $jamMasukJ = (string)$rj['jam_masuk'];
                $batasTelatJ = (string)$rj['batas_telat'];
                $jamPulangJ = (string)$rj['jam_pulang'];
                $isLibur = (int)$rj['is_libur'];
            }
            $resP = db_query("SELECT latitude, longitude, radius_meter FROM tb_pengaturan_absensi WHERE pengaturan_id = 1 LIMIT 1");
            $latSch = null; $lngSch = null; $radius = 100;
            if ($resP instanceof mysqli_result && $resP->num_rows === 1) {
                $rp = $resP->fetch_assoc();
                $latSch = (float)$rp['latitude']; $lngSch = (float)$rp['longitude']; $radius = (int)$rp['radius_meter'];
            }
            $distance = null;
            if ($latSch !== null && $lngSch !== null) {
                $toRad = function($d){ return $d * (pi()/180); };
                $R = 6371000.0;
                $dLat = $toRad($lat - $latSch);
                $dLng = $toRad($lng - $lngSch);
                $a = sin($dLat/2)**2 + cos($toRad($latSch)) * cos($toRad($lat)) * sin($dLng/2)**2;
                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                $distance = $R * $c;
            }
            if ($msg === null && ($latSch === null || $lngSch === null)) {
                $msg = 'Lokasi sekolah belum diatur';
            }
            if ($msg === null && $isLibur === 1) {
                $msg = 'Hari ini libur';
            }
            if ($msg === null && $distance !== null && $distance > $radius) {
                $msg = 'Di luar radius lokasi';
            }
            $jamNow = $now->format('Y-m-d H:i:s');
            $statusMasuk = null;
            if ($msg === null) {
                if ($batasTelatJ) {
                    $statusMasuk = ($jamNow <= ($now->format('Y-m-d') . ' ' . $batasTelatJ)) ? 'Hadir' : 'Telat';
                } else {
                    $statusMasuk = 'Hadir';
                }
            }
            $dataToHash = json_encode(['jenis'=>$ajenis,'user_map'=>$user_map,'tanggal'=>$tanggal,'lat'=>$lat,'lng'=>$lng,'akurasi'=>$akurasi,'skew'=>$skew,'distance'=>$distance,'time'=>$jamNow]);
            $secret = getenv('APP_INTEGRITY_KEY');
            $integrity = $secret ? hash_hmac('sha256', $dataToHash, $secret) : hash('sha256', $dataToHash);
            $logDir = __DIR__ . '/../../uploads/logs';
            if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
            $logLine = json_encode(['t'=>$jamNow,'ip'=>($_SERVER['REMOTE_ADDR'] ?? ''),'ua'=>$ua,'data'=>json_decode($dataToHash,true),'hash'=>$integrity]) . PHP_EOL;
            @file_put_contents($logDir . '/absensi.log', $logLine, FILE_APPEND);
            if ($msg === null) {
                $deviceCombined = ($ua ? ('ua=' . $ua . ';') : '') . 'sig=' . $integrity;
                if ($ajenis === 'guru') {
                    $ok = db_query(
                        "INSERT INTO tb_absensi (jenis, guru_id, tanggal, jadwal_jam_masuk, jadwal_batas_telat, jadwal_jam_pulang, status_masuk, jam_masuk, lat_masuk, lng_masuk, akurasi_masuk, jarak_masuk_meter, foto_masuk, device_info)
                         VALUES ('guru', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                           jadwal_jam_masuk = VALUES(jadwal_jam_masuk),
                           jadwal_batas_telat = VALUES(jadwal_batas_telat),
                           jadwal_jam_pulang = VALUES(jadwal_jam_pulang),
                           status_masuk = VALUES(status_masuk),
                           jam_masuk = VALUES(jam_masuk),
                           lat_masuk = VALUES(lat_masuk),
                           lng_masuk = VALUES(lng_masuk),
                           akurasi_masuk = VALUES(akurasi_masuk),
                           jarak_masuk_meter = VALUES(jarak_masuk_meter),
                           foto_masuk = VALUES(foto_masuk),
                           device_info = VALUES(device_info)",
                        "issssssddddss",
                        [$user_map, $tanggal, $jamMasukJ, $batasTelatJ, $jamPulangJ, $statusMasuk, $jamNow, $lat, $lng, $akurasi, $distance, $fotoPath, $deviceCombined]
                    );
                } else {
                    $ok = db_query(
                        "INSERT INTO tb_absensi (jenis, siswa_id, tanggal, jadwal_jam_masuk, jadwal_batas_telat, jadwal_jam_pulang, status_masuk, jam_masuk, lat_masuk, lng_masuk, akurasi_masuk, jarak_masuk_meter, foto_masuk, device_info)
                         VALUES ('siswa', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE
                           jadwal_jam_masuk = VALUES(jadwal_jam_masuk),
                           jadwal_batas_telat = VALUES(jadwal_batas_telat),
                           jadwal_jam_pulang = VALUES(jadwal_jam_pulang),
                           status_masuk = VALUES(status_masuk),
                           jam_masuk = VALUES(jam_masuk),
                           lat_masuk = VALUES(lat_masuk),
                           lng_masuk = VALUES(lng_masuk),
                           akurasi_masuk = VALUES(akurasi_masuk),
                           jarak_masuk_meter = VALUES(jarak_masuk_meter),
                           foto_masuk = VALUES(foto_masuk),
                           device_info = VALUES(device_info)",
                        "issssssddddss",
                        [$user_map, $tanggal, $jamMasukJ, $batasTelatJ, $jamPulangJ, $statusMasuk, $jamNow, $lat, $lng, $akurasi, $distance, $fotoPath, $deviceCombined]
                    );
                }
                $msg = $ok ? 'Absensi ditambahkan' : 'Gagal menambah absensi';
            }
        } else {
            $msg = 'Data absen tidak lengkap';
        }
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => ($msg === 'Absensi ditambahkan' && $ok) ? 'ok' : 'error',
                'message' => $msg ?? 'Terjadi kesalahan'
            ]);
            exit;
        }
    } elseif ($action === 'add_pulang_secure') {
        $ok = false;
        $ajenis = (string)($_POST['jenis'] ?? 'siswa');
        $user_map = isset($_POST['user_map']) ? (int)$_POST['user_map'] : 0;
        $tanggal = (string)($_POST['tanggal'] ?? '');
        $clientTime = (string)($_POST['client_time'] ?? '');
        $lat = isset($_POST['lat']) ? (float)$_POST['lat'] : null;
        $lng = isset($_POST['lng']) ? (float)$_POST['lng'] : null;
        $akurasi = isset($_POST['akurasi']) ? (float)$_POST['akurasi'] : null;
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : null;
        $fotoPath = null;
        if (isset($_FILES['photo']) && is_array($_FILES['photo']) && (int)$_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['photo']['tmp_name']; $size = (int)$_FILES['photo']['size'];
            $info = @getimagesize($tmp); $mime = is_array($info) && isset($info['mime']) ? $info['mime'] : null;
            if (($mime === 'image/jpeg' || $mime === 'image/png') && $size <= 2 * 1024 * 1024) {
                $ext = $mime === 'image/jpeg' ? 'jpg' : 'png';
                $name = 'pulang_' . bin2hex(random_bytes(8)) . '.' . $ext;
                $dir = __DIR__ . '/../../uploads/absensi'; if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                $dest = $dir . '/' . $name; if (@move_uploaded_file($tmp, $dest)) { $fotoPath = 'uploads/absensi/' . $name; }
            }
        }
        if (in_array($ajenis,['guru','siswa'],true) && $user_map>0 && $tanggal!=='' && $lat !== null && $lng !== null && $clientTime !== '') {
            $now = new DateTimeImmutable('now', $tz);
            $clientDt = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $clientTime);
            if (!$clientDt) { $clientDt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $clientTime); }
            $skew = $clientDt instanceof DateTimeImmutable ? abs($now->getTimestamp() - $clientDt->getTimestamp()) : 9999;
            $hariMap = ['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu'];
            $hari = $hariMap[$now->format('D')] ?? 'Senin';
            $resJ = db_query("SELECT jam_pulang, is_libur FROM tb_jadwal_absensi WHERE berlaku_untuk = ? AND hari = ? LIMIT 1", "ss", [$ajenis, $hari]);
            $jamPulangJ = null; $isLibur = 0;
            if ($resJ instanceof mysqli_result && $resJ->num_rows === 1) {
                $rj = $resJ->fetch_assoc();
                $jamPulangJ = (string)$rj['jam_pulang'];
                $isLibur = (int)$rj['is_libur'];
            }
            $resP = db_query("SELECT latitude, longitude, radius_meter FROM tb_pengaturan_absensi WHERE pengaturan_id = 1 LIMIT 1");
            $latSch = null; $lngSch = null; $radius = 100;
            if ($resP instanceof mysqli_result && $resP->num_rows === 1) {
                $rp = $resP->fetch_assoc();
                $latSch = (float)$rp['latitude']; $lngSch = (float)$rp['longitude']; $radius = (int)$rp['radius_meter'];
            }
            $distance = null;
            if ($latSch !== null && $lngSch !== null) {
                $toRad = function($d){ return $d * (pi()/180); };
                $R = 6371000.0;
                $dLat = $toRad($lat - $latSch);
                $dLng = $toRad($lng - $lngSch);
                $a = sin($dLat/2)**2 + cos($toRad($latSch)) * cos($toRad($lat)) * sin($dLng/2)**2;
                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                $distance = $R * $c;
            }
            $jamNow = $now->format('Y-m-d H:i:s');
            $msg = null;
            if ($latSch === null || $lngSch === null) { $msg = 'Lokasi sekolah belum diatur'; }
            if ($isLibur === 1) { $msg = 'Hari ini libur'; }
            if ($skew > 60) { $msg = 'Waktu tidak sinkron (±1 menit)'; }
            if ($msg === null && $distance !== null && $distance > $radius) { $msg = 'Di luar radius lokasi'; }
            if ($msg === null && $jamPulangJ) {
                $threshold = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d') . ' ' . $jamPulangJ, $tz);
                if ($threshold instanceof DateTimeImmutable) {
                    $threshold = $threshold->modify('-10 minutes');
                    if ($now < $threshold) { $msg = 'Belum waktu pulang (10 menit sebelum jam pulang)'; }
                }
            }
            if ($msg === null) {
                $today = $now->format('Y-m-d');
                if ($ajenis === 'guru') {
                    $iz = db_query("SELECT izin_id FROM tb_izin WHERE jenis_pengguna='guru' AND guru_id=? AND status='Disetujui' AND tanggal_mulai<=? AND tanggal_selesai>=? LIMIT 1", "iss", [$user_map, $today, $today]);
                } else {
                    $iz = db_query("SELECT izin_id FROM tb_izin WHERE jenis_pengguna='siswa' AND siswa_id=? AND status='Disetujui' AND tanggal_mulai<=? AND tanggal_selesai>=? LIMIT 1", "iss", [$user_map, $today, $today]);
                }
                if ($iz instanceof mysqli_result && $iz->num_rows === 1) { $msg = 'Memiliki izin disetujui untuk hari ini'; }
            }
            if ($msg === null) {
                if ($ajenis === 'guru') {
                    $exists = db_query("SELECT absensi_id FROM tb_absensi WHERE jenis='guru' AND guru_id = ? AND tanggal = ? LIMIT 1", "is", [$user_map, $tanggal]);
                } else {
                    $exists = db_query("SELECT absensi_id FROM tb_absensi WHERE jenis='siswa' AND siswa_id = ? AND tanggal = ? LIMIT 1", "is", [$user_map, $tanggal]);
                }
                if ($exists instanceof mysqli_result && $exists->num_rows === 1) {
                    $stChk = db_query("SELECT status_masuk FROM tb_absensi WHERE absensi_id = ? LIMIT 1", "i", [(int)$exists->fetch_assoc()['absensi_id']]);
                    if ($stChk instanceof mysqli_result && $stChk->num_rows === 1) {
                        $sm = (string)$stChk->fetch_assoc()['status_masuk'];
                        if ($sm === 'Izin' || $sm === 'Sakit') { $msg = 'Status hari ini Izin/Sakit'; }
                    }
                    $id = (int)$exists->fetch_assoc()['absensi_id'];
                    $statusPulang = 'Pulang';
                    $deviceCombined = ($ua ? ('ua=' . $ua . ';') : '') . 'sig=' . ($skew <= 60 ? 'ok' : 'bad');
                    $ok = db_query(
                        "UPDATE tb_absensi SET status_pulang = ?, jam_pulang = ?, lat_pulang = ?, lng_pulang = ?, akurasi_pulang = ?, jarak_pulang_meter = ?, foto_pulang = ?, device_info = ? WHERE absensi_id = ?",
                        "ssddddssi",
                        [$statusPulang, $jamNow, $lat, $lng, $akurasi, $distance, $fotoPath, $deviceCombined, $id]
                    );
                    $msg = $ok ? 'Absen pulang disimpan' : 'Gagal menyimpan pulang';
                } else {
                    $msg = 'Belum ada absen masuk hari ini';
                }
            }
        } else {
            $msg = 'Data pulang tidak lengkap';
        }
        header('Content-Type: application/json');
        echo json_encode(['status' => ($msg === 'Absen pulang disimpan' && $ok) ? 'ok' : 'error', 'message' => $msg ?? 'Terjadi kesalahan']);
        exit;
    } elseif ($action === 'edit') {
        $absensi_id = isset($_POST['absensi_id']) ? (int)$_POST['absensi_id'] : 0;
        $status_masuk = (string)($_POST['status_masuk'] ?? '');
        $status_pulang = (string)($_POST['status_pulang'] ?? '');
        $jam_masuk = (string)($_POST['jam_masuk'] ?? '');
        $jam_pulang = (string)($_POST['jam_pulang'] ?? '');
        $catatan = trim((string)($_POST['catatan'] ?? ''));
        if ($absensi_id > 0) {
            $ok = db_query("UPDATE tb_absensi SET status_masuk = ?, status_pulang = ?, jam_masuk = ?, jam_pulang = ?, catatan = ? WHERE absensi_id = ?", "sssssi", [$status_masuk !== '' ? $status_masuk : null, $status_pulang !== '' ? $status_pulang : null, $jam_masuk !== '' ? $jam_masuk : null, $jam_pulang !== '' ? $jam_pulang : null, $catatan !== '' ? $catatan : null, $absensi_id]);
            $msg = $ok ? 'Absensi diperbarui' : 'Gagal memperbarui absensi';
        } else {
            $msg = 'Data tidak valid';
        }
    } elseif ($action === 'delete') {
        $absensi_id = isset($_POST['absensi_id']) ? (int)$_POST['absensi_id'] : 0;
        if ($absensi_id > 0) {
            $ok = db_query("DELETE FROM tb_absensi WHERE absensi_id = ?", "i", [$absensi_id]);
            $msg = $ok ? 'Absensi dihapus' : 'Gagal menghapus absensi';
        } else {
            $msg = 'Data tidak valid';
        }
    }
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
$total = 0;
if ($jenis === 'guru') {
    $countRes = db_query("SELECT COUNT(*) AS total FROM tb_absensi WHERE jenis='guru' AND tanggal BETWEEN ? AND ?", "ss", [$date_from, $date_to]);
} elseif ($jenis === 'siswa') {
    $countRes = db_query("SELECT COUNT(*) AS total FROM tb_absensi WHERE jenis='siswa' AND tanggal BETWEEN ? AND ?", "ss", [$date_from, $date_to]);
} else {
    $countRes = db_query("SELECT COUNT(*) AS total FROM tb_absensi WHERE tanggal BETWEEN ? AND ?", "ss", [$date_from, $date_to]);
}
if ($countRes instanceof mysqli_result) { $total = (int)$countRes->fetch_assoc()['total']; }
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;

if ($jenis === 'guru') {
    if ($q !== '') {
        $res = db_query("SELECT a.absensi_id, a.tanggal, a.status_masuk, a.status_pulang, a.jam_masuk, a.jam_pulang, a.foto_masuk, a.foto_pulang, a.catatan, u.nama, u.username, g.nip FROM tb_absensi a JOIN tb_guru g ON a.guru_id = g.guru_id JOIN tb_users u ON g.user_id = u.user_id WHERE a.jenis='guru' AND a.tanggal BETWEEN ? AND ? AND (u.nama LIKE CONCAT('%',?,'%') OR u.username LIKE CONCAT('%',?,'%')) ORDER BY a.tanggal DESC, a.absensi_id DESC LIMIT ? OFFSET ?", "ssssii", [$date_from, $date_to, $q, $q, $perPage, $offset]);
    } else {
        $res = db_query("SELECT a.absensi_id, a.tanggal, a.status_masuk, a.status_pulang, a.jam_masuk, a.jam_pulang, a.foto_masuk, a.foto_pulang, a.catatan, u.nama, u.username, g.nip FROM tb_absensi a JOIN tb_guru g ON a.guru_id = g.guru_id JOIN tb_users u ON g.user_id = u.user_id WHERE a.jenis='guru' AND a.tanggal BETWEEN ? AND ? ORDER BY a.tanggal DESC, a.absensi_id DESC LIMIT ? OFFSET ?", "ssii", [$date_from, $date_to, $perPage, $offset]);
    }
    $userMap = db_query("SELECT g.guru_id AS id, u.nama, u.username FROM tb_guru g JOIN tb_users u ON g.user_id=u.user_id WHERE g.is_active=1 ORDER BY u.nama ASC");
} elseif ($jenis === 'siswa') {
    if ($q !== '') {
        $res = db_query("SELECT a.absensi_id, a.tanggal, a.status_masuk, a.status_pulang, a.jam_masuk, a.jam_pulang, a.foto_masuk, a.foto_pulang, a.catatan, u.nama, u.username, s.nis FROM tb_absensi a JOIN tb_siswa s ON a.siswa_id = s.siswa_id JOIN tb_users u ON s.user_id = u.user_id WHERE a.jenis='siswa' AND a.tanggal BETWEEN ? AND ? AND (u.nama LIKE CONCAT('%',?,'%') OR u.username LIKE CONCAT('%',?,'%')) ORDER BY a.tanggal DESC, a.absensi_id DESC LIMIT ? OFFSET ?", "ssssii", [$date_from, $date_to, $q, $q, $perPage, $offset]);
    } else {
        $res = db_query("SELECT a.absensi_id, a.tanggal, a.status_masuk, a.status_pulang, a.jam_masuk, a.jam_pulang, a.foto_masuk, a.foto_pulang, a.catatan, u.nama, u.username, s.nis FROM tb_absensi a JOIN tb_siswa s ON a.siswa_id = s.siswa_id JOIN tb_users u ON s.user_id = u.user_id WHERE a.jenis='siswa' AND a.tanggal BETWEEN ? AND ? ORDER BY a.tanggal DESC, a.absensi_id DESC LIMIT ? OFFSET ?", "ssii", [$date_from, $date_to, $perPage, $offset]);
    }
    $userMap = db_query("SELECT s.siswa_id AS id, u.nama, u.username FROM tb_siswa s JOIN tb_users u ON s.user_id=u.user_id WHERE s.is_active=1 ORDER BY u.nama ASC");
} else {
    if ($q !== '') {
        $res = db_query(
            "SELECT a.absensi_id, a.tanggal, a.status_masuk, a.status_pulang, a.jam_masuk, a.jam_pulang, a.foto_masuk, a.foto_pulang, a.catatan, u.nama, u.username, 'guru' AS jenis_label, g.nip AS idno
             FROM tb_absensi a
             JOIN tb_guru g ON a.guru_id = g.guru_id
             JOIN tb_users u ON g.user_id = u.user_id
             WHERE a.jenis='guru' AND a.tanggal BETWEEN ? AND ? AND (u.nama LIKE CONCAT('%',?,'%') OR u.username LIKE CONCAT('%',?,'%'))
             UNION ALL
             SELECT a.absensi_id, a.tanggal, a.status_masuk, a.status_pulang, a.jam_masuk, a.jam_pulang, a.foto_masuk, a.foto_pulang, a.catatan, u.nama, u.username, 'siswa' AS jenis_label, s.nis AS idno
             FROM tb_absensi a
             JOIN tb_siswa s ON a.siswa_id = s.siswa_id
             JOIN tb_users u ON s.user_id = u.user_id
             WHERE a.jenis='siswa' AND a.tanggal BETWEEN ? AND ? AND (u.nama LIKE CONCAT('%',?,'%') OR u.username LIKE CONCAT('%',?,'%'))
             ORDER BY tanggal DESC, absensi_id DESC
             LIMIT ? OFFSET ?",
            "ssssssssii",
            [$date_from, $date_to, $q, $q, $date_from, $date_to, $q, $q, $perPage, $offset]
        );
    } else {
        $res = db_query(
            "SELECT a.absensi_id, a.tanggal, a.status_masuk, a.status_pulang, a.jam_masuk, a.jam_pulang, a.foto_masuk, a.foto_pulang, a.catatan, u.nama, u.username, 'guru' AS jenis_label, g.nip AS idno
             FROM tb_absensi a
             JOIN tb_guru g ON a.guru_id = g.guru_id
             JOIN tb_users u ON g.user_id = u.user_id
             WHERE a.jenis='guru' AND a.tanggal BETWEEN ? AND ?
             UNION ALL
             SELECT a.absensi_id, a.tanggal, a.status_masuk, a.status_pulang, a.jam_masuk, a.jam_pulang, a.foto_masuk, a.foto_pulang, a.catatan, u.nama, u.username, 'siswa' AS jenis_label, s.nis AS idno
             FROM tb_absensi a
             JOIN tb_siswa s ON a.siswa_id = s.siswa_id
             JOIN tb_users u ON s.user_id = u.user_id
             WHERE a.jenis='siswa' AND a.tanggal BETWEEN ? AND ?
             ORDER BY tanggal DESC, absensi_id DESC
             LIMIT ? OFFSET ?",
            "ssssii",
            [$date_from, $date_to, $date_from, $date_to, $perPage, $offset]
        );
    }
    $userMap = null;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Absensi Harian • E-Absensi</title>
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
                <h3>Absensi Harian</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Rekap Absensi</h4>
                                <div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd"><i class="bi bi-plus-lg"></i> Tambah</button>
                                </div>
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
                                        <h5 class="mb-1">Periode</h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                    </div>
                                    <form method="get" class="ms-auto d-flex align-items-end gap-2">
                                            <div>
                                                <label class="form-label">Jenis</label>
                                                <select name="jenis" class="form-select form-select-sm">
                                                <option value="all" <?= $jenis==='all'?'selected':'' ?>>Semua</option>
                                                <option value="guru" <?= $jenis==='guru'?'selected':'' ?>>guru</option>
                                                <option value="siswa" <?= $jenis==='siswa'?'selected':'' ?>>siswa</option>
                                                </select>
                                            </div>
                                        <div>
                                            <label class="form-label">Dari</label>
                                            <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>" class="form-control form-control-sm">
                                        </div>
                                        <div>
                                            <label class="form-label">Sampai</label>
                                            <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>" class="form-control form-control-sm">
                                        </div>
                                        <div>
                                            <label class="form-label">Cari</label>
                                            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="nama/username">
                                        </div>
                                        <div>
                                            <label class="form-label">&nbsp;</label>
                                            <button class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lg align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nama</th>
                                                <th>Username</th>
                                                <th><?= $jenis==='guru'?'NIP':($jenis==='siswa'?'NIS':'NIP/NIS') ?></th>
                                                <th>Tanggal</th>
                                                <th>Status Masuk</th>
                                                <th>Jam Masuk</th>
                                                <th>Status Pulang</th>
                                                <th>Jam Pulang</th>
                                                <th>Catatan</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = $offset + 1;
                                            if ($res instanceof mysqli_result) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $id = (int)$row['absensi_id'];
                                                    $namaRow = (string)$row['nama'];
                                                    $userRow = (string)$row['username'];
                                                    $idnoRow = (string)($row['idno'] ?? (isset($row['nip']) ? $row['nip'] : (isset($row['nis']) ? $row['nis'] : '')));
                                                    $tanggalRow = (string)$row['tanggal'];
                                                    $statMasuk = (string)($row['status_masuk'] ?? '');
                                                    $jamMasuk = (string)($row['jam_masuk'] ?? '');
                                                    $statPulang = (string)($row['status_pulang'] ?? '');
                                                    $jamPulang = (string)($row['jam_pulang'] ?? '');
                                                    $catatanRow = (string)($row['catatan'] ?? '');
                                                    echo '<tr>';
                                                    echo '<td>'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($namaRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($userRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($idnoRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($tanggalRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($statMasuk) .'</td>';
                                                    echo '<td>'. htmlspecialchars($jamMasuk) .'</td>';
                                                    echo '<td>'. htmlspecialchars($statPulang) .'</td>';
                                                    echo '<td>'. htmlspecialchars($jamPulang) .'</td>';
                                                    echo '<td>'. htmlspecialchars($catatanRow) .'</td>';
                                                    echo '<td class="text-end">';
                                                    echo '<a href="detail.php?id='. $id .'" class="btn btn-sm btn-info me-1"><i class="bi bi-eye"></i> Detail</a>';
                                                    echo '<button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit"';
                                                    echo ' data-id="'. $id .'" data-status_masuk="'. htmlspecialchars($statMasuk) .'" data-jam_masuk="'. htmlspecialchars($jamMasuk) .'"';
                                                    echo ' data-status_pulang="'. htmlspecialchars($statPulang) .'" data-jam_pulang="'. htmlspecialchars($jamPulang) .'" data-catatan="'. htmlspecialchars($catatanRow) .'">';
                                                    echo '<i class="bi bi-pencil-square"></i> Edit</button>';
                                                    echo '<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalDelete" data-id="'. $id .'"><i class="bi bi-trash"></i> Hapus</button>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php
                                $prev = max(1, $page - 1);
                                $next = min($totalPages, $page + 1);
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                ?>
                                <nav aria-label="Absensi pagination" class="mt-3">
                                    <ul class="pagination justify-content-end">
                                        <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                            <a class="page-link" href="absensi.php?page=<?= $prev ?>&per_page=<?= (int)$perPage ?>&jenis=<?= htmlspecialchars($jenis) ?>&from=<?= htmlspecialchars($date_from) ?>&to=<?= htmlspecialchars($date_to) ?>">Prev</a>
                                        </li>
                                        <?php if ($start > 1): ?>
                                            <li class="page-item"><a class="page-link" href="absensi.php?page=1&per_page=<?= (int)$perPage ?>&jenis=<?= htmlspecialchars($jenis) ?>&from=<?= htmlspecialchars($date_from) ?>&to=<?= htmlspecialchars($date_to) ?>">1</a></li>
                                            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                        <?php endif; ?>
                                        <?php for ($p=$start; $p<=$end; $p++): ?>
                                            <li class="page-item <?= $p===$page?'active':'' ?>">
                                                <a class="page-link" href="absensi.php?page=<?= $p ?>&per_page=<?= (int)$perPage ?>&jenis=<?= htmlspecialchars($jenis) ?>&from=<?= htmlspecialchars($date_from) ?>&to=<?= htmlspecialchars($date_to) ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <?php if ($end < $totalPages): ?>
                                            <?php if ($end < $totalPages-1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                            <li class="page-item"><a class="page-link" href="absensi.php?page=<?= $totalPages ?>&per_page=<?= (int)$perPage ?>&jenis=<?= htmlspecialchars($jenis) ?>&from=<?= htmlspecialchars($date_from) ?>&to=<?= htmlspecialchars($date_to) ?>"><?= $totalPages ?></a></li>
                                        <?php endif; ?>
                                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                            <a class="page-link" href="absensi.php?page=<?= $next ?>&per_page=<?= (int)$perPage ?>&jenis=<?= htmlspecialchars($jenis) ?>&from=<?= htmlspecialchars($date_from) ?>&to=<?= htmlspecialchars($date_to) ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php include __DIR__ . '/../../includes/footer.php'; ?>
        </div>
    </div>

    <div class="modal fade" id="modalAdd" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Tambah Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="absensi.php">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Jenis</label>
                                <select name="jenis" class="form-select" required>
                                    <option value="guru" <?= $jenis==='guru'?'selected':'' ?>>guru</option>
                                    <option value="siswa" <?= $jenis==='siswa'?'selected':'' ?>>siswa</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-2">
                                <label class="form-label">Pilih Pengguna</label>
                                <select name="user_map" class="form-select" required>
                                    <option value="">-- pilih --</option>
                                    <?php if ($userMap instanceof mysqli_result) { while ($u = $userMap->fetch_assoc()) { ?>
                                        <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars((string)$u['nama']) ?> (<?= htmlspecialchars((string)$u['username']) ?>)</option>
                                    <?php } } ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="<?= htmlspecialchars($date_from) ?>" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Status Masuk</label>
                                <select name="status_masuk" class="form-select">
                                    <option value="">-</option>
                                    <option value="Hadir">Hadir</option>
                                    <option value="Telat">Telat</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Tidak Hadir">Tidak Hadir</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Status Pulang</label>
                                <select name="status_pulang" class="form-select">
                                    <option value="">-</option>
                                    <option value="Pulang">Pulang</option>
                                    <option value="Belum Pulang">Belum Pulang</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Jam Masuk</label>
                                <input type="datetime-local" name="jam_masuk" class="form-control">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Jam Pulang</label>
                                <input type="datetime-local" name="jam_pulang" class="form-control">
                            </div>
                            <div class="col-md-12 mb-2">
                                <label class="form-label">Catatan</label>
                                <textarea name="catatan" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="absensi.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="absensi_id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Status Masuk</label>
                                <select name="status_masuk" id="edit_status_masuk" class="form-select">
                                    <option value="">-</option>
                                    <option value="Hadir">Hadir</option>
                                    <option value="Telat">Telat</option>
                                    <option value="Izin">Izin</option>
                                    <option value="Sakit">Sakit</option>
                                    <option value="Tidak Hadir">Tidak Hadir</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Status Pulang</label>
                                <select name="status_pulang" id="edit_status_pulang" class="form-select">
                                    <option value="">-</option>
                                    <option value="Pulang">Pulang</option>
                                    <option value="Belum Pulang">Belum Pulang</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Jam Masuk</label>
                                <input type="datetime-local" name="jam_masuk" id="edit_jam_masuk" class="form-control">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Jam Pulang</label>
                                <input type="datetime-local" name="jam_pulang" id="edit_jam_pulang" class="form-control">
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" id="edit_catatan" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-trash me-1"></i> Hapus Absensi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="absensi.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="absensi_id" id="del_id">
                    <div class="modal-body">
                        <p>Anda yakin akan menghapus data ini?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                        <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../../assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        var editModal = document.getElementById('modalEdit');
        editModal && editModal.addEventListener('show.bs.modal', function (event) {
            var b = event.relatedTarget;
            document.getElementById('edit_id').value = b.getAttribute('data-id');
            document.getElementById('edit_status_masuk').value = b.getAttribute('data-status_masuk');
            document.getElementById('edit_jam_masuk').value = b.getAttribute('data-jam_masuk');
            document.getElementById('edit_status_pulang').value = b.getAttribute('data-status_pulang');
            document.getElementById('edit_jam_pulang').value = b.getAttribute('data-jam_pulang');
            document.getElementById('edit_catatan').value = b.getAttribute('data-catatan');
        });
        var delModal = document.getElementById('modalDelete');
        delModal && delModal.addEventListener('show.bs.modal', function (event) {
            var b = event.relatedTarget;
            document.getElementById('del_id').value = b.getAttribute('data-id');
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
