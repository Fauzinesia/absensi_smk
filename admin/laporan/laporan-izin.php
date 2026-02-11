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
$filterStatus = isset($_GET['status']) && $_GET['status'] !== '' ? (string)$_GET['status'] : '';
$tanggalCetak = formatTanggalIndonesia($now);

// Ambil daftar kelas untuk dropdown
$kelasOptions = [];
$resKelas = db_query("SELECT kelas_id, nama_kelas, tingkat FROM tb_kelas WHERE is_active=1 ORDER BY tingkat ASC, nama_kelas ASC");
if ($resKelas instanceof mysqli_result) {
    while ($rk = $resKelas->fetch_assoc()) {
        $kelasOptions[(int)$rk['kelas_id']] = (string)$rk['nama_kelas'];
    }
}

// Validasi periode
$startDt = DateTimeImmutable::createFromFormat('Y-m-d', $from, $tz);
$endDt = DateTimeImmutable::createFromFormat('Y-m-d', $to, $tz);
if (!$startDt || !$endDt || $endDt < $startDt) { 
    $startDt = $now->modify('first day of this month'); 
    $endDt = $now->modify('last day of this month'); 
    $from = $startDt->format('Y-m-d'); 
    $to = $endDt->format('Y-m-d'); 
}

// Ambil data izin
$list = [];
$totalsAll = ['Diajukan'=>0,'Disetujui'=>0,'Ditolak'=>0];

if ($jenis === 'guru') {
    $sql = "SELECT i.izin_id, i.tanggal_mulai, i.tanggal_selesai, i.jenis_izin, i.keterangan, i.status, i.diajukan_pada,
                   g.guru_id AS uid, u.nama, g.nip AS idno, g.jabatan AS dept, 0 AS kelas_id
            FROM tb_izin i
            JOIN tb_guru g ON i.guru_id=g.guru_id
            JOIN tb_users u ON g.user_id=u.user_id
            WHERE i.jenis_pengguna='guru' 
            AND (i.tanggal_mulai BETWEEN ? AND ? OR i.tanggal_selesai BETWEEN ? AND ?)";
    $params = [$from, $to, $from, $to];
    $types = "ssss";
    
    if ($filterNama !== '') {
        $sql .= " AND u.nama LIKE ?";
        $params[] = "%{$filterNama}%";
        $types .= "s";
    }
    
    if ($filterStatus !== '') {
        $sql .= " AND i.status = ?";
        $params[] = $filterStatus;
        $types .= "s";
    }
    
    $sql .= " ORDER BY i.diajukan_pada DESC, u.nama ASC";
    $res = db_query($sql, $types, $params);
} else {
    $sql = "SELECT i.izin_id, i.tanggal_mulai, i.tanggal_selesai, i.jenis_izin, i.keterangan, i.status, i.diajukan_pada,
                   s.siswa_id AS uid, u.nama, s.nis AS idno, k.nama_kelas AS dept, s.kelas_id
            FROM tb_izin i
            JOIN tb_siswa s ON i.siswa_id=s.siswa_id
            JOIN tb_users u ON s.user_id=u.user_id
            LEFT JOIN tb_kelas k ON s.kelas_id=k.kelas_id
            WHERE i.jenis_pengguna='siswa' 
            AND (i.tanggal_mulai BETWEEN ? AND ? OR i.tanggal_selesai BETWEEN ? AND ?)";
    $params = [$from, $to, $from, $to];
    $types = "ssss";
    
    if ($filterKelas > 0) {
        $sql .= " AND s.kelas_id = ?";
        $params[] = $filterKelas;
        $types .= "i";
    }
    
    if ($filterNama !== '') {
        $sql .= " AND u.nama LIKE ?";
        $params[] = "%{$filterNama}%";
        $types .= "s";
    }
    
    if ($filterStatus !== '') {
        $sql .= " AND i.status = ?";
        $params[] = $filterStatus;
        $types .= "s";
    }
    
    $sql .= " ORDER BY k.nama_kelas ASC, i.diajukan_pada DESC, u.nama ASC";
    $res = db_query($sql, $types, $params);
}

if ($res instanceof mysqli_result) {
    while ($r = $res->fetch_assoc()) {
        $list[] = [
            'izin_id' => (int)$r['izin_id'],
            'nama' => (string)$r['nama'],
            'idno' => (string)$r['idno'],
            'dept' => (string)($r['dept'] ?? ''),
            'kelas_id' => (int)($r['kelas_id'] ?? 0),
            'tanggal_mulai' => (string)$r['tanggal_mulai'],
            'tanggal_selesai' => (string)$r['tanggal_selesai'],
            'jenis_izin' => (string)$r['jenis_izin'],
            'keterangan' => (string)($r['keterangan'] ?? ''),
            'status' => (string)$r['status'],
            'diajukan_pada' => (string)$r['diajukan_pada']
        ];
        
        $st = (string)$r['status'];
        if ($st === 'Diajukan') { $totalsAll['Diajukan']++; }
        elseif ($st === 'Disetujui') { $totalsAll['Disetujui']++; }
        elseif ($st === 'Ditolak') { $totalsAll['Ditolak']++; }
    }
}

function hitungHari($start, $end) {
    $d1 = new DateTime($start);
    $d2 = new DateTime($end);
    $diff = $d1->diff($d2);
    return $diff->days + 1;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Izin • E-Absensi</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/bootstrap.css">
    <link rel="stylesheet" href="../../assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/vendors/perfect-scrollbar/perfect-scrollbar.css">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="shortcut icon" href="../../assets/images/favicon.svg" type="image/x-icon">
    <style>
        @page { 
            size: A4 portrait; 
            margin: 10mm 15mm; 
        }
        body { 
            background: #f5f6fa; 
            font-family: 'Nunito', sans-serif; 
        }
        
        /* Letterhead */
        .letterhead { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            margin-bottom: 6px; 
            padding: 6px 0;
        }
        .letterhead-logo { flex-shrink: 0; }
        .letterhead-logo img { height: 70px; width: auto; }
        .letterhead-text { flex-grow: 1; text-align: center; }
        .letterhead-title { font-size: 11px; font-weight: 700; line-height: 1.2; margin: 0; }
        .letterhead-school { font-size: 14px; font-weight: 700; margin: 1px 0; }
        .letterhead-address { font-size: 9px; line-height: 1.3; margin: 0; }
        .letter-separator { border-top: 2px solid #000; margin: 3px 0; }
        .letter-separator-thin { border-top: 1px solid #000; margin: 0 0 8px 0; }
        
        /* Report Title */
        .report-title { text-align: center; margin: 8px 0 6px 0; }
        .report-title h4 { font-size: 13px; font-weight: 700; margin: 0 0 2px 0; }
        .report-title .subtitle { font-size: 10px; color: #333; }
        
        /* Filter Bar */
        .filter-bar { 
            background: #fff; 
            padding: 16px; 
            border-radius: 8px; 
            margin-bottom: 16px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        /* Table */
        .table-wrap { 
            overflow-x: auto; 
            background: #fff;
            border-radius: 8px;
            padding: 8px;
        }
        .report-table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 10px;
        }
        .report-table th, .report-table td { 
            border: 1px solid #333; 
            padding: 4px 6px; 
            text-align: left; 
            line-height: 1.3; 
        }
        .report-table th { 
            background: #f0f0f0; 
            font-weight: 700;
            text-align: center;
        }
        .report-table .text-center { text-align: center; }
        
        /* Status Colors */
        .status-diajukan { background: #fef3c7; color: #92400e; font-weight: 600; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .status-disetujui { background: #d1fae5; color: #065f46; font-weight: 600; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .status-ditolak { background: #fee2e2; color: #991b1b; font-weight: 600; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        
        /* Jenis Izin Colors */
        .jenis-izin { background: #e0e7ff; color: #3730a3; font-weight: 600; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .jenis-sakit { background: #fce7f3; color: #831843; font-weight: 600; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .jenis-dinas { background: #dbeafe; color: #1e3a8a; font-weight: 600; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .jenis-dispensasi { background: #fef3c7; color: #78350f; font-weight: 600; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        
        /* Kelas Header */
        .kelas-header { background: #e3f2fd; font-weight: 700; }
        
        /* Summary */
        .summary-box {
            background: #fff;
            padding: 8px 12px;
            border-radius: 6px;
            margin: 12px 0;
            font-size: 10px;
        }
        
        /* Signature */
        .signature-section { display: flex; justify-content: flex-end; margin-top: 16px; }
        .signature-block { width: 220px; text-align: center; }
        .signature-location { font-size: 10px; margin-bottom: 2px; }
        .signature-title { font-size: 10px; font-weight: 600; margin-bottom: 40px; }
        .signature-name { font-size: 11px; font-weight: 700; text-decoration: underline; margin-bottom: 2px; }
        .signature-nik { font-size: 9px; }
        
        /* Print Styles */
        @media print {
            body { background: #fff; margin: 0; padding: 0; }
            #sidebar, #app > div > header, .page-heading, .filter-bar, footer, .no-print { 
                display: none !important; 
            }
            #main, .page-content { padding: 0 !important; margin: 0 !important; }
            .card { border: none !important; box-shadow: none !important; margin: 0 !important; padding: 0 !important; }
            .card-body { padding: 0 !important; margin: 0 !important; }
            .table-wrap, .summary-box { 
                box-shadow: none !important;
                border: none !important;
                padding: 4px !important;
            }
            body { 
                -webkit-print-color-adjust: exact !important; 
                print-color-adjust: exact !important; 
            }
            .letterhead-logo img { height: 60px !important; }
            .letterhead-title { font-size: 10px !important; }
            .letterhead-school { font-size: 12px !important; }
            .letterhead-address { font-size: 8px !important; }
            .report-title h4 { font-size: 12px !important; }
            .report-title .subtitle { font-size: 9px !important; }
            .report-table { font-size: 9px !important; }
            .signature-title { margin-bottom: 30px !important; }
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
                <h3>Laporan Izin</h3>
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
                                    <label class="form-label fw-bold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="">Semua Status</option>
                                        <option value="Diajukan" <?= $filterStatus==='Diajukan'?'selected':'' ?>>Diajukan</option>
                                        <option value="Disetujui" <?= $filterStatus==='Disetujui'?'selected':'' ?>>Disetujui</option>
                                        <option value="Ditolak" <?= $filterStatus==='Ditolak'?'selected':'' ?>>Ditolak</option>
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
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-filter"></i> Tampilkan
                                    </button>
                                    <button type="button" class="btn btn-success" onclick="window.print()">
                                        <i class="bi bi-printer"></i> Cetak
                                    </button>
                                    <a href="laporan-izin.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset Filter
                                    </a>
                                    <?php if ($filterKelas > 0 || $filterNama !== '' || $filterStatus !== ''): ?>
                                        <span class="badge bg-info ms-2">
                                            Filter Aktif: 
                                            <?php if ($filterKelas > 0): ?>
                                                Kelas: <?= htmlspecialchars($kelasOptions[$filterKelas] ?? '') ?>
                                            <?php endif; ?>
                                            <?php if ($filterNama !== ''): ?>
                                                • Nama: "<?= htmlspecialchars($filterNama) ?>"
                                            <?php endif; ?>
                                            <?php if ($filterStatus !== ''): ?>
                                                • Status: <?= htmlspecialchars($filterStatus) ?>
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
                                    <h4>LAPORAN PENGAJUAN IZIN <?= strtoupper($jenis) ?></h4>
                                    <p class="subtitle">
                                        PERIODE: <?= strtoupper(formatTanggalIndonesia($startDt)) ?> s.d <?= strtoupper(formatTanggalIndonesia($endDt)) ?>
                                    </p>
                                </div>
                                
                                <!-- Table -->
                                <div class="table-wrap">
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 30px;">No</th>
                                                <th style="width: 150px;">Nama <?= $jenis === 'guru' ? 'Guru' : 'Siswa' ?></th>
                                                <th style="width: 80px;"><?= $jenis==='guru'?'NIP':'NIS' ?></th>
                                                <th style="width: 100px;"><?= $jenis==='guru'?'Jabatan':'Kelas' ?></th>
                                                <th style="width: 90px;">Tanggal Mulai</th>
                                                <th style="width: 90px;">Tanggal Selesai</th>
                                                <th style="width: 50px;" class="text-center">Lama</th>
                                                <th style="width: 80px;">Jenis Izin</th>
                                                <th>Keterangan</th>
                                                <th style="width: 80px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 1;
                                            if (count($list) > 0) {
                                                if ($jenis === 'siswa') {
                                                    // Grouping per kelas untuk siswa
                                                    $currentKelas = null;
                                                    foreach ($list as $item) {
                                                        if ($currentKelas !== $item['dept']) {
                                                            $currentKelas = $item['dept'];
                                                            echo '<tr class="kelas-header">';
                                                            echo '<td colspan="10" style="padding: 4px 6px; font-size: 10px;">KELAS: ' . htmlspecialchars($currentKelas) . '</td>';
                                                            echo '</tr>';
                                                        }
                                                        
                                                        $lama = hitungHari($item['tanggal_mulai'], $item['tanggal_selesai']);
                                                        $statusClass = 'status-' . strtolower($item['status']);
                                                        $jenisClass = 'jenis-' . strtolower($item['jenis_izin']);
                                                        
                                                        echo '<tr>';
                                                        echo '<td class="text-center">'. $no++ .'</td>';
                                                        echo '<td>'. htmlspecialchars($item['nama']) .'</td>';
                                                        echo '<td class="text-center">'. htmlspecialchars($item['idno']) .'</td>';
                                                        echo '<td>'. htmlspecialchars($item['dept']) .'</td>';
                                                        echo '<td class="text-center">'. date('d/m/Y', strtotime($item['tanggal_mulai'])) .'</td>';
                                                        echo '<td class="text-center">'. date('d/m/Y', strtotime($item['tanggal_selesai'])) .'</td>';
                                                        echo '<td class="text-center">'. $lama .' hari</td>';
                                                        echo '<td class="text-center"><span class="'. $jenisClass .'">'. htmlspecialchars($item['jenis_izin']) .'</span></td>';
                                                        echo '<td>'. htmlspecialchars(substr($item['keterangan'], 0, 80)) . (strlen($item['keterangan']) > 80 ? '...' : '') .'</td>';
                                                        echo '<td class="text-center"><span class="'. $statusClass .'">'. htmlspecialchars($item['status']) .'</span></td>';
                                                        echo '</tr>';
                                                    }
                                                } else {
                                                    // Untuk guru tanpa grouping
                                                    foreach ($list as $item) {
                                                        $lama = hitungHari($item['tanggal_mulai'], $item['tanggal_selesai']);
                                                        $statusClass = 'status-' . strtolower($item['status']);
                                                        $jenisClass = 'jenis-' . strtolower($item['jenis_izin']);
                                                        
                                                        echo '<tr>';
                                                        echo '<td class="text-center">'. $no++ .'</td>';
                                                        echo '<td>'. htmlspecialchars($item['nama']) .'</td>';
                                                        echo '<td class="text-center">'. htmlspecialchars($item['idno']) .'</td>';
                                                        echo '<td>'. htmlspecialchars($item['dept']) .'</td>';
                                                        echo '<td class="text-center">'. date('d/m/Y', strtotime($item['tanggal_mulai'])) .'</td>';
                                                        echo '<td class="text-center">'. date('d/m/Y', strtotime($item['tanggal_selesai'])) .'</td>';
                                                        echo '<td class="text-center">'. $lama .' hari</td>';
                                                        echo '<td class="text-center"><span class="'. $jenisClass .'">'. htmlspecialchars($item['jenis_izin']) .'</span></td>';
                                                        echo '<td>'. htmlspecialchars(substr($item['keterangan'], 0, 80)) . (strlen($item['keterangan']) > 80 ? '...' : '') .'</td>';
                                                        echo '<td class="text-center"><span class="'. $statusClass .'">'. htmlspecialchars($item['status']) .'</span></td>';
                                                        echo '</tr>';
                                                    }
                                                }
                                            } else {
                                                echo '<tr><td colspan="10" class="text-center">Tidak ada data untuk periode ini</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Summary -->
                                <div class="summary-box">
                                    <strong>Total Pengajuan: <?= count($list) ?> izin</strong><br>
                                    <strong>Periode: <?= date('d', strtotime($from)) ?> - <?= date('d F Y', strtotime($to)) ?></strong>
                                    <hr style="margin: 8px 0;">
                                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                        <span><strong>Diajukan:</strong> <?= (int)$totalsAll['Diajukan'] ?></span>
                                        <span><strong>Disetujui:</strong> <?= (int)$totalsAll['Disetujui'] ?></span>
                                        <span><strong>Ditolak:</strong> <?= (int)$totalsAll['Ditolak'] ?></span>
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
