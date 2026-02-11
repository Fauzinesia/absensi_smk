<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { 
    header('Location: ../../login.php'); 
    exit; 
}

// Konfigurasi waktu dan input filter
$tz = new DateTimeZone('Asia/Makassar');
$now = new DateTimeImmutable('now', $tz);
$jenis = isset($_GET['jenis']) && in_array($_GET['jenis'], ['guru','siswa'], true) ? (string)$_GET['jenis'] : 'guru';
$from = isset($_GET['from']) && $_GET['from'] !== '' ? (string)$_GET['from'] : $now->modify('first day of this month')->format('Y-m-d');
$to = isset($_GET['to']) && $_GET['to'] !== '' ? (string)$_GET['to'] : $now->modify('last day of this month')->format('Y-m-d');
$filterKelas = isset($_GET['kelas']) && $_GET['kelas'] !== '' ? (int)$_GET['kelas'] : 0;
$filterNama = isset($_GET['nama']) && $_GET['nama'] !== '' ? trim((string)$_GET['nama']) : '';
$tanggalCetak = formatTanggalIndonesia($now);

// Ambil daftar kelas untuk dropdown
$kelasOptions = [];
$resKelas = db_query("SELECT kelas_id, nama_kelas, tingkat FROM tb_kelas WHERE is_active=1 ORDER BY tingkat ASC, nama_kelas ASC");
if ($resKelas instanceof mysqli_result) {
    while ($rk = $resKelas->fetch_assoc()) {
        $kelasOptions[(int)$rk['kelas_id']] = (string)$rk['nama_kelas'];
    }
}

// Susun daftar tanggal dalam periode
$startDt = DateTimeImmutable::createFromFormat('Y-m-d', $from, $tz);
$endDt = DateTimeImmutable::createFromFormat('Y-m-d', $to, $tz);
if (!$startDt || !$endDt || $endDt < $startDt) { 
    $startDt = $now->modify('first day of this month'); 
    $endDt = $now->modify('last day of this month'); 
    $from = $startDt->format('Y-m-d'); 
    $to = $endDt->format('Y-m-d'); 
}
$dates = [];
for ($d = $startDt; $d <= $endDt; $d = $d->modify('+1 day')) { 
    $dates[] = $d; 
}

// Ambil data absensi untuk periode dan jenis
$list = [];        // id => ['nama','idno','dept','kelas_id']
$map = [];         // id => ['Y-m-d' => status_masuk]
$totalsAll = ['Hadir'=>0,'Telat'=>0,'Izin'=>0,'Sakit'=>0,'Tidak Hadir'=>0];

if ($jenis === 'guru') {
    // Query untuk guru
    $sql = "SELECT a.tanggal, a.status_masuk, g.guru_id AS uid, u.nama, g.nip AS idno, g.jabatan AS dept, 0 AS kelas_id 
            FROM tb_absensi a 
            JOIN tb_guru g ON a.guru_id=g.guru_id 
            JOIN tb_users u ON g.user_id=u.user_id 
            WHERE a.jenis='guru' AND a.tanggal BETWEEN ? AND ?";
    $params = [$from, $to];
    $types = "ss";
    
    // Filter nama
    if ($filterNama !== '') {
        $sql .= " AND u.nama LIKE ?";
        $params[] = "%{$filterNama}%";
        $types .= "s";
    }
    
    $sql .= " ORDER BY u.nama ASC, a.tanggal ASC";
    $res = db_query($sql, $types, $params);
} else {
    // Query untuk siswa
    $sql = "SELECT a.tanggal, a.status_masuk, s.siswa_id AS uid, u.nama, s.nis AS idno, k.nama_kelas AS dept, s.kelas_id 
            FROM tb_absensi a 
            JOIN tb_siswa s ON a.siswa_id=s.siswa_id 
            JOIN tb_users u ON s.user_id=u.user_id 
            LEFT JOIN tb_kelas k ON s.kelas_id=k.kelas_id 
            WHERE a.jenis='siswa' AND a.tanggal BETWEEN ? AND ?";
    $params = [$from, $to];
    $types = "ss";
    
    // Filter kelas
    if ($filterKelas > 0) {
        $sql .= " AND s.kelas_id = ?";
        $params[] = $filterKelas;
        $types .= "i";
    }
    
    // Filter nama
    if ($filterNama !== '') {
        $sql .= " AND u.nama LIKE ?";
        $params[] = "%{$filterNama}%";
        $types .= "s";
    }
    
    $sql .= " ORDER BY k.nama_kelas ASC, u.nama ASC, a.tanggal ASC";
    $res = db_query($sql, $types, $params);
}

if ($res instanceof mysqli_result) {
    while ($r = $res->fetch_assoc()) {
        $uid = (int)$r['uid'];
        if (!isset($list[$uid])) { 
            $list[$uid] = [
                'nama'=>(string)$r['nama'],
                'idno'=>(string)$r['idno'],
                'dept'=>(string)($r['dept'] ?? ''),
                'kelas_id'=>(int)($r['kelas_id'] ?? 0)
            ]; 
        }
        $tgl = (string)$r['tanggal']; 
        $st = (string)($r['status_masuk'] ?? '');
        $map[$uid][$tgl] = $st;
        
        if ($st === 'Hadir') { $totalsAll['Hadir']++; }
        elseif ($st === 'Telat') { $totalsAll['Telat']++; }
        elseif ($st === 'Izin') { $totalsAll['Izin']++; }
        elseif ($st === 'Sakit') { $totalsAll['Sakit']++; }
        else { $totalsAll['Tidak Hadir']++; }
    }
}

function hariNama($d){ 
    $m=['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu']; 
    return $m[$d] ?? $d; 
}

function hariSingkat($d){ 
    $m=['Mon'=>'Sen','Tue'=>'Sel','Wed'=>'Rab','Thu'=>'Kam','Fri'=>'Jum','Sat'=>'Sab','Sun'=>'Min']; 
    return $m[$d] ?? $d; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Absensi • E-Absensi</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../../assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="shortcut icon" href="../../assets/images/favicon.svg" type="image/x-icon">
    <style>
        @page { 
            size: A4 landscape; 
            margin: 8mm 10mm; 
        }
        body { 
            background: #f5f6fa; 
            font-family: 'Nunito', sans-serif; 
        }
        
        /* Letterhead Styling */
        .letterhead { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            margin-bottom: 6px; 
            padding: 6px 0;
        }
        .letterhead-logo { 
            flex-shrink: 0; 
        }
        .letterhead-logo img { 
            height: 70px; 
            width: auto; 
        }
        .letterhead-text { 
            flex-grow: 1; 
            text-align: center; 
        }
        .letterhead-title { 
            font-size: 11px; 
            font-weight: 700; 
            line-height: 1.2; 
            margin: 0;
        }
        .letterhead-school { 
            font-size: 14px; 
            font-weight: 700; 
            margin: 1px 0;
        }
        .letterhead-address { 
            font-size: 9px; 
            line-height: 1.3; 
            margin: 0;
        }
        .letter-separator { 
            border-top: 2px solid #000; 
            margin: 3px 0; 
        }
        .letter-separator-thin { 
            border-top: 1px solid #000; 
            margin: 0 0 8px 0; 
        }
        
        /* Report Title */
        .report-title {
            text-align: center;
            margin: 8px 0 6px 0;
        }
        .report-title h4 {
            font-size: 13px;
            font-weight: 700;
            margin: 0 0 2px 0;
        }
        .report-title .subtitle {
            font-size: 10px;
            color: #333;
        }
        
        /* Filter Bar */
        .filter-bar { 
            background: #fff; 
            padding: 16px; 
            border-radius: 8px; 
            margin-bottom: 16px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        /* Table Styling */
        .table-wrap { 
            overflow-x: auto; 
            background: #fff;
            border-radius: 8px;
            padding: 8px;
        }
        .report-table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
            font-size: 8px;
        }
        .report-table th, .report-table td { 
            border: 1px solid #333; 
            padding: 2px 3px; 
            text-align: center; 
            line-height: 1.1; 
        }
        .report-table th { 
            background: #f0f0f0; 
            font-weight: 700;
            font-size: 8px;
        }
        .report-table thead th { 
            vertical-align: middle; 
        }
        .report-table .text-start {
            text-align: left !important;
            padding-left: 4px;
        }
        .report-table tbody td {
            font-size: 8px;
        }
        
        /* Status Colors - sesuai referensi */
        .status-H { background: #2ecc71; color: #fff; font-weight: 700; }
        .status-T { background: #f39c12; color: #fff; font-weight: 700; }
        .status-I { background: #3498db; color: #fff; font-weight: 700; }
        .status-S { background: #f1c40f; color: #000; font-weight: 700; }
        .status-none { background: #e74c3c; color: #fff; font-weight: 700; }
        .status-DL { background: #9b59b6; color: #fff; font-weight: 700; }
        
        /* Legend */
        .legend {
            background: #fff;
            padding: 6px 8px;
            border-radius: 6px;
            margin: 8px 0;
            font-size: 9px;
        }
        .legend strong {
            display: block;
            margin-bottom: 4px;
            font-size: 9px;
        }
        .legend-items {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .legend-box {
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            border-radius: 3px;
            font-size: 8px;
        }
        
        /* Summary */
        .summary-box {
            background: #fff;
            padding: 6px 8px;
            border-radius: 6px;
            margin: 8px 0;
            font-size: 9px;
        }
        .summary-box strong {
            font-size: 9px;
        }
        
        /* Signature */
        .signature-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 12px;
        }
        .signature-block {
            width: 220px;
            text-align: center;
        }
        .signature-location {
            font-size: 10px;
            margin-bottom: 2px;
        }
        .signature-title {
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 40px;
        }
        .signature-name {
            font-size: 11px;
            font-weight: 700;
            text-decoration: underline;
            margin-bottom: 2px;
        }
        .signature-nik {
            font-size: 9px;
        }
        
        /* Print Styles */
        @media print {
            @page { 
                size: A4 landscape; 
                margin: 8mm 10mm; 
            }
            body { 
                background: #fff;
                margin: 0;
                padding: 0;
            }
            #sidebar, #app > div > header, .page-heading, .filter-bar, footer, .no-print { 
                display: none !important; 
            }
            #main {
                padding: 0 !important;
                margin: 0 !important;
            }
            .page-content {
                padding: 0 !important;
                margin: 0 !important;
            }
            .card { 
                border: none !important; 
                box-shadow: none !important; 
                margin: 0 !important;
                padding: 0 !important;
            }
            .card-body {
                padding: 0 !important;
                margin: 0 !important;
            }
            .table-wrap, .legend, .summary-box { 
                box-shadow: none !important;
                border: none !important;
                padding: 4px !important;
            }
            body { 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
            }
            .page-break {
                page-break-after: always;
            }
            
            /* Compact print layout */
            .letterhead-logo img { 
                height: 60px !important; 
            }
            .letterhead-title { 
                font-size: 10px !important; 
            }
            .letterhead-school { 
                font-size: 12px !important; 
            }
            .letterhead-address { 
                font-size: 8px !important; 
            }
            .report-title h4 {
                font-size: 12px !important;
                margin: 4px 0 !important;
            }
            .report-title .subtitle {
                font-size: 9px !important;
            }
            .report-table {
                font-size: 7px !important;
            }
            .report-table th {
                font-size: 7px !important;
                padding: 2px !important;
            }
            .report-table td {
                font-size: 7px !important;
                padding: 2px !important;
            }
            .legend {
                font-size: 8px !important;
                padding: 4px 6px !important;
            }
            .summary-box {
                font-size: 8px !important;
                padding: 4px 6px !important;
            }
            .signature-title {
                margin-bottom: 30px !important;
            }
        }
        
        @media (max-width: 992px) {
            .report-table th, .report-table td { 
                font-size: 7px; 
                padding: 2px; 
            }
        }
    </style>
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
                <h3>Laporan Absensi</h3>
            </div>
            
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <!-- Filter Bar -->
                        <div class="filter-bar no-print">
                            <form method="get" class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Jenis</label>
                                    <select name="jenis" class="form-select" id="jenisSelect">
                                        <option value="guru" <?= $jenis==='guru'?'selected':'' ?>>Guru</option>
                                        <option value="siswa" <?= $jenis==='siswa'?'selected':'' ?>>Siswa</option>
                                    </select>
                                </div>
                                <div class="col-md-2" id="filterKelasWrapper" style="<?= $jenis==='guru'?'display:none;':'' ?>">
                                    <label class="form-label fw-bold">Kelas</label>
                                    <select name="kelas" class="form-select">
                                        <option value="">Semua Kelas</option>
                                        <?php foreach ($kelasOptions as $kid => $kname): ?>
                                            <option value="<?= $kid ?>" <?= $filterKelas===$kid?'selected':'' ?>><?= htmlspecialchars($kname) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Nama</label>
                                    <input type="text" name="nama" value="<?= htmlspecialchars($filterNama) ?>" class="form-control" placeholder="Cari nama...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Dari Tanggal</label>
                                    <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Sampai Tanggal</label>
                                    <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-filter"></i> Tampilkan
                                    </button>
                                </div>
                                <div class="col-12">
                                    <button type="button" class="btn btn-success" onclick="window.print()">
                                        <i class="bi bi-printer"></i> Cetak
                                    </button>
                                    <a href="laporan-absensi.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset Filter
                                    </a>
                                    <?php if ($filterKelas > 0 || $filterNama !== ''): ?>
                                        <span class="badge bg-info ms-2">
                                            Filter Aktif: 
                                            <?php if ($filterKelas > 0): ?>
                                                Kelas: <?= htmlspecialchars($kelasOptions[$filterKelas] ?? '') ?>
                                            <?php endif; ?>
                                            <?php if ($filterNama !== ''): ?>
                                                • Nama: "<?= htmlspecialchars($filterNama) ?>"
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        
                        <script>
                            document.getElementById('jenisSelect').addEventListener('change', function() {
                                var kelasWrapper = document.getElementById('filterKelasWrapper');
                                if (this.value === 'siswa') {
                                    kelasWrapper.style.display = 'block';
                                } else {
                                    kelasWrapper.style.display = 'none';
                                }
                            });
                        </script>
                        
                        <!-- Print Area -->
                        <div class="card">
                            <div class="card-body">
                                <!-- Letterhead -->
                                <div class="letterhead">
                                    <div class="letterhead-logo">
                                        <img src="../../assets/images/logo/logo-smk.png" alt="Logo SMK">
                                    </div>
                                    <div class="letterhead-text">
                                        <p class="letterhead-title">DINAS PENDIDIKAN DAN KEBUDAYAAN PROVINSI KALIMANTAN SELATAN</p>
                                        <p class="letterhead-title">YAYASAN MANDIRI TUNAS HIKMAH BANUA</p>
                                        <p class="letterhead-school">SEKOLAH MENENGAH KEJURUAN FARMASI MANDIRI</p>
                                        <p class="letterhead-address">Jl. Pramuka Komplek Semanda II RW. 02 RT. 21 Kecamatan Banjarmasin Timur Kota Banjarmasin</p>
                                        <p class="letterhead-address">Telp. 0511 6781062 Kode Pos 70238 Email mandiri364@yahoo.co.id</p>
                                    </div>
                                </div>
                                <div class="letter-separator"></div>
                                <div class="letter-separator-thin"></div>
                                
                                <!-- Report Title -->
                                <div class="report-title">
                                    <h4>REKAP ABSENSI <?= strtoupper($jenis) ?></h4>
                                    <p class="subtitle">
                                        PERIODE: <?= strtoupper(formatTanggalIndonesia($startDt)) ?> s.d <?= strtoupper(formatTanggalIndonesia($endDt)) ?>
                                    </p>
                                </div>
                                
                                <!-- Table -->
                                <div class="table-wrap">
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th rowspan="2" style="width: 30px; max-width: 30px;">No</th>
                                                <th rowspan="2" style="width: 130px; min-width: 130px; max-width: 130px;">Nama <?= $jenis === 'guru' ? 'Guru' : 'Siswa' ?></th>
                                                <th rowspan="2" style="width: 70px; max-width: 70px;"><?= $jenis==='guru'?'NIP':'NIS' ?></th>
                                                <th rowspan="2" style="width: 90px; max-width: 90px;"><?= $jenis==='guru'?'Jabatan':'Kelas' ?></th>
                                                <th colspan="<?= count($dates) ?>">Tanggal</th>
                                            </tr>
                                            <tr>
                                                <?php foreach ($dates as $dt): 
                                                    $hn = hariSingkat($dt->format('D')); 
                                                ?>
                                                    <th style="width: 20px; min-width: 20px; max-width: 20px; padding: 1px !important;">
                                                        <?= (int)$dt->format('j') ?><br>
                                                        <small style="font-size: 6px;"><?= $hn ?></small>
                                                    </th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 1;
                                            if (count($list) > 0) {
                                                // Grouping untuk siswa per kelas
                                                if ($jenis === 'siswa') {
                                                    $currentKelas = null;
                                                    foreach ($list as $id => $info) {
                                                        // Tampilkan header kelas jika berbeda
                                                        if ($currentKelas !== $info['dept']) {
                                                            $currentKelas = $info['dept'];
                                                            echo '<tr style="background: #e3f2fd;">';
                                                            echo '<td colspan="' . (4 + count($dates)) . '" style="text-align: left; font-weight: 700; padding: 4px 6px; font-size: 9px;">KELAS: ' . htmlspecialchars($currentKelas) . '</td>';
                                                            echo '</tr>';
                                                        }
                                                        
                                                        echo '<tr>';
                                                        echo '<td>'. $no++ .'</td>';
                                                        echo '<td class="text-start">'. htmlspecialchars((string)$info['nama']) .'</td>';
                                                        echo '<td>'. htmlspecialchars((string)$info['idno']) .'</td>';
                                                        echo '<td>'. htmlspecialchars((string)$info['dept']) .'</td>';
                                                        
                                                        foreach ($dates as $dt) {
                                                            $ds = $dt->format('Y-m-d');
                                                            $st = $map[$id][$ds] ?? '';
                                                            $label = '-'; 
                                                            $cls = 'status-none';
                                                            
                                                            if ($st === 'Hadir') { 
                                                                $label='H'; 
                                                                $cls='status-H'; 
                                                            } elseif ($st === 'Telat') { 
                                                                $label='T'; 
                                                                $cls='status-T'; 
                                                            } elseif ($st === 'Izin') { 
                                                                $label='I'; 
                                                                $cls='status-I'; 
                                                            } elseif ($st === 'Sakit') { 
                                                                $label='S'; 
                                                                $cls='status-S'; 
                                                            } else { 
                                                                $label='-'; 
                                                                $cls='status-none'; 
                                                            }
                                                            
                                                            echo '<td class="'. $cls .'">'. $label .'</td>';
                                                        }
                                                        echo '</tr>';
                                                    }
                                                } else {
                                                    // Untuk guru, tampilkan biasa tanpa grouping
                                                    foreach ($list as $id => $info) {
                                                        echo '<tr>';
                                                        echo '<td>'. $no++ .'</td>';
                                                        echo '<td class="text-start">'. htmlspecialchars((string)$info['nama']) .'</td>';
                                                        echo '<td>'. htmlspecialchars((string)$info['idno']) .'</td>';
                                                        echo '<td>'. htmlspecialchars((string)$info['dept']) .'</td>';
                                                        
                                                        foreach ($dates as $dt) {
                                                            $ds = $dt->format('Y-m-d');
                                                            $st = $map[$id][$ds] ?? '';
                                                            $label = '-'; 
                                                            $cls = 'status-none';
                                                            
                                                            if ($st === 'Hadir') { 
                                                                $label='H'; 
                                                                $cls='status-H'; 
                                                            } elseif ($st === 'Telat') { 
                                                                $label='T'; 
                                                                $cls='status-T'; 
                                                            } elseif ($st === 'Izin') { 
                                                                $label='I'; 
                                                                $cls='status-I'; 
                                                            } elseif ($st === 'Sakit') { 
                                                                $label='S'; 
                                                                $cls='status-S'; 
                                                            } else { 
                                                                $label='-'; 
                                                                $cls='status-none'; 
                                                            }
                                                            
                                                            echo '<td class="'. $cls .'">'. $label .'</td>';
                                                        }
                                                        echo '</tr>';
                                                    }
                                                }
                                            } else {
                                                echo '<tr><td colspan="'. (4+count($dates)) .'" class="text-center">Tidak ada data untuk periode ini</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Legend -->
                                <div class="legend">
                                    <strong>Keterangan Kode Status:</strong>
                                    <div class="legend-items">
                                        <div class="legend-item">
                                            <span class="legend-box status-H">H</span>
                                            <span>= Hadir</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-box status-T">T</span>
                                            <span>= Telat (Terlambat/Shift)</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-box status-I">I</span>
                                            <span>= Izin</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-box status-S">S</span>
                                            <span>= Sakit</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-box status-none">-</span>
                                            <span>= Tidak Hadir</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="legend-box status-DL">DL</span>
                                            <span>= Dinas Luar (jika ada)</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Summary -->
                                <div class="summary-box">
                                    <strong>Total <?= ucfirst($jenis) ?>: <?= count($list) ?> orang</strong><br>
                                    <strong>Periode: <?= date('d', strtotime($from)) ?> - <?= date('d F Y', strtotime($to)) ?></strong>
                                    <hr style="margin: 8px 0;">
                                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                        <span><strong>Hadir:</strong> <?= (int)$totalsAll['Hadir'] ?></span>
                                        <span><strong>Telat:</strong> <?= (int)$totalsAll['Telat'] ?></span>
                                        <span><strong>Izin:</strong> <?= (int)$totalsAll['Izin'] ?></span>
                                        <span><strong>Sakit:</strong> <?= (int)$totalsAll['Sakit'] ?></span>
                                        <span><strong>Tidak Hadir:</strong> <?= (int)$totalsAll['Tidak Hadir'] ?></span>
                                    </div>
                                </div>
                                
                                <!-- Signature -->
                                <div class="signature-section">
                                    <div class="signature-block">
                                        <p class="signature-location">Banjarmasin, <?= $tanggalCetak ?></p>
                                        <p class="signature-title">Kepala Sekolah</p>
                                        <p class="signature-name">Susanti Pusparini, ST</p>
                                        <p class="signature-nik">NIK. 197119012048</p>
                                    </div>
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
