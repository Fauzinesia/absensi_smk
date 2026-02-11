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
$filterNama = isset($_GET['nama']) && $_GET['nama'] !== '' ? trim((string)$_GET['nama']) : '';
$filterJabatan = isset($_GET['jabatan']) && $_GET['jabatan'] !== '' ? trim((string)$_GET['jabatan']) : '';
$filterJK = isset($_GET['jk']) && $_GET['jk'] !== '' ? (string)$_GET['jk'] : '';
$filterStatus = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;
$tanggalCetak = formatTanggalIndonesia($now);

// Ambil data guru
$sql = "SELECT g.guru_id, g.nip, g.jabatan, g.jenis_kelamin, g.alamat, g.is_active, g.created_at,
               u.nama, u.username, u.no_hp
        FROM tb_guru g
        JOIN tb_users u ON g.user_id = u.user_id
        WHERE 1=1";
$params = [];
$types = "";

if ($filterNama !== '') {
    $sql .= " AND u.nama LIKE ?";
    $params[] = "%{$filterNama}%";
    $types .= "s";
}

if ($filterJabatan !== '') {
    $sql .= " AND g.jabatan LIKE ?";
    $params[] = "%{$filterJabatan}%";
    $types .= "s";
}

if ($filterJK !== '') {
    $sql .= " AND g.jenis_kelamin = ?";
    $params[] = $filterJK;
    $types .= "s";
}

if ($filterStatus >= 0) {
    $sql .= " AND g.is_active = ?";
    $params[] = $filterStatus;
    $types .= "i";
}

$sql .= " ORDER BY u.nama ASC";

$list = [];
$totals = ['aktif' => 0, 'nonaktif' => 0, 'laki' => 0, 'perempuan' => 0];

if (!empty($params)) {
    $res = db_query($sql, $types, $params);
} else {
    $res = db_query($sql);
}

if ($res instanceof mysqli_result) {
    while ($r = $res->fetch_assoc()) {
        $list[] = [
            'guru_id' => (int)$r['guru_id'],
            'nama' => (string)$r['nama'],
            'nip' => (string)($r['nip'] ?? ''),
            'jabatan' => (string)$r['jabatan'],
            'jenis_kelamin' => (string)($r['jenis_kelamin'] ?? ''),
            'alamat' => (string)($r['alamat'] ?? ''),
            'no_hp' => (string)($r['no_hp'] ?? ''),
            'is_active' => (int)$r['is_active'],
            'created_at' => (string)$r['created_at']
        ];
        
        if ((int)$r['is_active'] === 1) {
            $totals['aktif']++;
        } else {
            $totals['nonaktif']++;
        }
        
        if ((string)($r['jenis_kelamin'] ?? '') === 'L') {
            $totals['laki']++;
        } elseif ((string)($r['jenis_kelamin'] ?? '') === 'P') {
            $totals['perempuan']++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Data Guru • E-Absensi</title>
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
        
        /* Status Badge */
        .badge-aktif { background: #d1fae5; color: #065f46; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 9px; }
        .badge-nonaktif { background: #fee2e2; color: #991b1b; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 9px; }
        
        /* JK Badge */
        .badge-l { background: #dbeafe; color: #1e3a8a; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 9px; }
        .badge-p { background: #fce7f3; color: #831843; font-weight: 600; padding: 2px 8px; border-radius: 4px; font-size: 9px; }
        
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
                <h3>Laporan Data Guru</h3>
            </div>
            
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <!-- Filter Bar -->
                        <div class="filter-bar no-print">
                            <form method="get" class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Nama Guru</label>
                                    <input type="text" name="nama" value="<?= htmlspecialchars($filterNama) ?>" class="form-control" placeholder="Cari nama guru...">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold">Jabatan</label>
                                    <input type="text" name="jabatan" value="<?= htmlspecialchars($filterJabatan) ?>" class="form-control" placeholder="Cari jabatan...">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Jenis Kelamin</label>
                                    <select name="jk" class="form-select">
                                        <option value="">Semua</option>
                                        <option value="L" <?= $filterJK==='L'?'selected':'' ?>>Laki-laki</option>
                                        <option value="P" <?= $filterJK==='P'?'selected':'' ?>>Perempuan</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-bold">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="">Semua</option>
                                        <option value="1" <?= $filterStatus===1?'selected':'' ?>>Aktif</option>
                                        <option value="0" <?= $filterStatus===0?'selected':'' ?>>Nonaktif</option>
                                    </select>
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
                                    <a href="laporan-guru.php" class="btn btn-secondary">
                                        <i class="bi bi-arrow-clockwise"></i> Reset Filter
                                    </a>
                                    <?php if ($filterNama !== '' || $filterJabatan !== '' || $filterJK !== '' || $filterStatus >= 0): ?>
                                        <span class="badge bg-info ms-2">
                                            Filter Aktif: 
                                            <?php if ($filterNama !== ''): ?>
                                                Nama: "<?= htmlspecialchars($filterNama) ?>"
                                            <?php endif; ?>
                                            <?php if ($filterJabatan !== ''): ?>
                                                • Jabatan: "<?= htmlspecialchars($filterJabatan) ?>"
                                            <?php endif; ?>
                                            <?php if ($filterJK !== ''): ?>
                                                • JK: <?= $filterJK === 'L' ? 'Laki-laki' : 'Perempuan' ?>
                                            <?php endif; ?>
                                            <?php if ($filterStatus >= 0): ?>
                                                • Status: <?= $filterStatus === 1 ? 'Aktif' : 'Nonaktif' ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                        
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
                                    <h4>LAPORAN DATA GURU</h4>
                                    <p class="subtitle">SMK FARMASI MANDIRI BANJARMASIN</p>
                                </div>
                                
                                <!-- Table -->
                                <div class="table-wrap">
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 30px;">No</th>
                                                <th style="width: 150px;">Nama Guru</th>
                                                <th style="width: 100px;">NIP</th>
                                                <th style="width: 120px;">Jabatan</th>
                                                <th style="width: 60px;">JK</th>
                                                <th style="width: 100px;">No. HP</th>
                                                <th>Alamat</th>
                                                <th style="width: 70px;">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 1;
                                            if (count($list) > 0) {
                                                foreach ($list as $item) {
                                                    $statusClass = $item['is_active'] === 1 ? 'badge-aktif' : 'badge-nonaktif';
                                                    $statusText = $item['is_active'] === 1 ? 'Aktif' : 'Nonaktif';
                                                    $jkClass = $item['jenis_kelamin'] === 'L' ? 'badge-l' : 'badge-p';
                                                    $jkText = $item['jenis_kelamin'] === 'L' ? 'Laki-laki' : ($item['jenis_kelamin'] === 'P' ? 'Perempuan' : '-');
                                                    
                                                    echo '<tr>';
                                                    echo '<td class="text-center">'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($item['nama']) .'</td>';
                                                    echo '<td class="text-center">'. htmlspecialchars($item['nip']) .'</td>';
                                                    echo '<td>'. htmlspecialchars($item['jabatan']) .'</td>';
                                                    echo '<td class="text-center"><span class="'. $jkClass .'">'. $jkText .'</span></td>';
                                                    echo '<td class="text-center">'. htmlspecialchars($item['no_hp']) .'</td>';
                                                    echo '<td>'. htmlspecialchars(substr($item['alamat'], 0, 60)) . (strlen($item['alamat']) > 60 ? '...' : '') .'</td>';
                                                    echo '<td class="text-center"><span class="'. $statusClass .'">'. $statusText .'</span></td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="8" class="text-center">Tidak ada data guru</td></tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Summary -->
                                <div class="summary-box">
                                    <strong>Total Guru: <?= count($list) ?> orang</strong><br>
                                    <strong>Dicetak pada: <?= $tanggalCetak ?></strong>
                                    <hr style="margin: 8px 0;">
                                    <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                                        <span><strong>Aktif:</strong> <?= $totals['aktif'] ?> orang</span>
                                        <span><strong>Nonaktif:</strong> <?= $totals['nonaktif'] ?> orang</span>
                                        <span><strong>Laki-laki:</strong> <?= $totals['laki'] ?> orang</span>
                                        <span><strong>Perempuan:</strong> <?= $totals['perempuan'] ?> orang</span>
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
