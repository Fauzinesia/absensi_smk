<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: /login.php'); exit; }
$tz = new DateTimeZone('Asia/Makassar');
$msg = null;
$jenis = isset($_GET['jenis']) && in_array($_GET['jenis'], ['guru','siswa','all'], true) ? $_GET['jenis'] : 'all';
$status = isset($_GET['status']) && in_array($_GET['status'], ['all','Diajukan','Disetujui','Ditolak'], true) ? $_GET['status'] : 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
$offset = ($page - 1) * $perPage;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'update_status') {
        $izin_id = isset($_POST['izin_id']) ? (int)$_POST['izin_id'] : 0;
        $decision = (string)($_POST['decision'] ?? '');
        if ($izin_id > 0 && in_array($decision, ['approve','reject'], true)) {
            $resI = db_query("SELECT izin_id, jenis_pengguna, siswa_id, guru_id, tanggal_mulai, tanggal_selesai, jenis_izin, status FROM tb_izin WHERE izin_id = ? LIMIT 1", "i", [$izin_id]);
            if ($resI instanceof mysqli_result && $resI->num_rows === 1) {
                $izin = $resI->fetch_assoc();
                $newStatus = $decision === 'approve' ? 'Disetujui' : 'Ditolak';
                $ok = db_query("UPDATE tb_izin SET status = ? WHERE izin_id = ?", "si", [$newStatus, $izin_id]);
                if ($ok && $decision === 'approve') {
                    $start = new DateTimeImmutable((string)$izin['tanggal_mulai'], $tz);
                    $end = new DateTimeImmutable((string)$izin['tanggal_selesai'], $tz);
                    $cur = $start;
                    $jenisPengguna = (string)$izin['jenis_pengguna'];
                    $userMap = $jenisPengguna === 'siswa' ? (int)$izin['siswa_id'] : (int)$izin['guru_id'];
                    $statusMasuk = ((string)$izin['jenis_izin'] === 'Sakit') ? 'Sakit' : 'Izin';
                    while ($cur <= $end) {
                        $tanggal = $cur->format('Y-m-d');
                        if ($jenisPengguna === 'siswa') {
                            db_query(
                                "INSERT INTO tb_absensi (jenis, siswa_id, tanggal, status_masuk, jam_masuk, catatan)
                                 VALUES ('siswa', ?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE
                                   status_masuk = VALUES(status_masuk),
                                   jam_masuk = VALUES(jam_masuk),
                                   catatan = VALUES(catatan)",
                                "issss",
                                [$userMap, $tanggal, $statusMasuk, $tanggal . ' 00:00:00', 'Izin disetujui']
                            );
                        } else {
                            db_query(
                                "INSERT INTO tb_absensi (jenis, guru_id, tanggal, status_masuk, jam_masuk, catatan)
                                 VALUES ('guru', ?, ?, ?, ?, ?)
                                 ON DUPLICATE KEY UPDATE
                                   status_masuk = VALUES(status_masuk),
                                   jam_masuk = VALUES(jam_masuk),
                                   catatan = VALUES(catatan)",
                                "issss",
                                [$userMap, $tanggal, $statusMasuk, $tanggal . ' 00:00:00', 'Izin disetujui']
                            );
                        }
                        $cur = $cur->modify('+1 day');
                    }
                    $msg = 'Izin disetujui dan tersinkron ke absensi';
                } else {
                    $msg = $ok ? 'Status izin diperbarui' : 'Gagal memperbarui izin';
                }
            } else {
                $msg = 'Izin tidak ditemukan';
            }
        } else {
            $msg = 'Data tidak valid';
        }
    }
}
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', $tz));
$where = []; $params = []; $types = "";
if ($jenis !== 'all') { $where[] = "jenis_pengguna = ?"; $types .= "s"; $params[] = $jenis; }
if ($status !== 'all') { $where[] = "status = ?"; $types .= "s"; $params[] = $status; }
$whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";
$countRes = db_query("SELECT COUNT(*) AS total FROM tb_izin $whereSql", $types !== "" ? $types : null, $params !== [] ? $params : null);
$total = 0; if ($countRes instanceof mysqli_result) { $total = (int)$countRes->fetch_assoc()['total']; }
$totalPages = max(1, (int)ceil($total / $perPage)); if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }
$res = db_query(
    "SELECT i.izin_id, i.jenis_pengguna, i.siswa_id, i.guru_id, i.tanggal_mulai, i.tanggal_selesai, i.jenis_izin, i.keterangan, i.status, i.bukti,
            u.nama, u.username
     FROM tb_izin i
     LEFT JOIN tb_siswa s ON i.jenis_pengguna='siswa' AND i.siswa_id = s.siswa_id
     LEFT JOIN tb_guru g ON i.jenis_pengguna='guru' AND i.guru_id = g.guru_id
     LEFT JOIN tb_users u ON u.user_id = COALESCE(s.user_id, g.user_id)
     $whereSql
     ORDER BY i.diajukan_pada DESC, i.izin_id DESC
     LIMIT ? OFFSET ?",
    ($types !== "" ? ($types . "ii") : "ii"),
    array_merge($params, [$perPage, $offset])
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Izin • E-Absensi</title>
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
                <h3>Verifikasi Izin</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Pengajuan Izin</h4>
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
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                <option value="all" <?= $status==='all'?'selected':'' ?>>Semua</option>
                                                <option value="Diajukan" <?= $status==='Diajukan'?'selected':'' ?>>Diajukan</option>
                                                <option value="Disetujui" <?= $status==='Disetujui'?'selected':'' ?>>Disetujui</option>
                                                <option value="Ditolak" <?= $status==='Ditolak'?'selected':'' ?>>Ditolak</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Tampilkan</label>
                                            <select name="per_page" class="form-select form-select-sm">
                                                <?php foreach ([10,20,30,50] as $pp): ?>
                                                    <option value="<?= $pp ?>" <?= $perPage===$pp?'selected':'' ?>><?= $pp ?></option>
                                                <?php endforeach; ?>
                                            </select>
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
                                                <th>Jenis</th>
                                                <th>Periode</th>
                                                <th>Jenis Izin</th>
                                                <th>Status</th>
                                                <th>Bukti</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = $offset + 1;
                                            if ($res instanceof mysqli_result) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $id = (int)$row['izin_id'];
                                                    $namaRow = (string)$row['nama'];
                                                    $userRow = (string)$row['username'];
                                                    $jenisRow = (string)$row['jenis_pengguna'];
                                                    $periodeRow = htmlspecialchars((string)$row['tanggal_mulai']) . ' s/d ' . htmlspecialchars((string)$row['tanggal_selesai']);
                                                    $jenisIzin = (string)$row['jenis_izin'];
                                                    $statusRow = (string)$row['status'];
                                                    $buktiRow = (string)($row['bukti'] ?? '');
                                                    echo '<tr>';
                                                    echo '<td>'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($namaRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($userRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($jenisRow) .'</td>';
                                                    echo '<td>'. $periodeRow .'</td>';
                                                    echo '<td>'. htmlspecialchars($jenisIzin) .'</td>';
                                                    echo '<td><span class="badge bg-'. ($statusRow==='Disetujui'?'success':($statusRow==='Ditolak'?'danger':'secondary')) .'">'. htmlspecialchars($statusRow) .'</span></td>';
                                                    echo '<td>'. ($buktiRow !== '' ? ('<a href="../../'. htmlspecialchars($buktiRow) .'" target="_blank">Lihat</a>') : '-') .'</td>';
                                                    echo '<td class="text-end">';
                                                    if ($statusRow === 'Diajukan') {
                                                        echo '<button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#modalVerify" data-id="'. $id .'" data-decision="approve"><i class="bi bi-check2-circle"></i> Setujui</button>';
                                                        echo '<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalVerify" data-id="'. $id .'" data-decision="reject"><i class="bi bi-x-circle"></i> Tolak</button>';
                                                    } else {
                                                        echo '<span class="text-muted">Sudah '. htmlspecialchars(strtolower($statusRow)) .'</span>';
                                                    }
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
                                ?>
                                <nav aria-label="Izin pagination" class="mt-3">
                                    <ul class="pagination justify-content-end">
                                        <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                            <a class="page-link" href="izin.php?page=<?= $prev ?>&per_page=<?= (int)$perPage ?>&jenis=<?= htmlspecialchars($jenis) ?>&status=<?= htmlspecialchars($status) ?>">Prev</a>
                                        </li>
                                        <?php for ($p=max(1,$page-2); $p<=min($totalPages,$page+2); $p++): ?>
                                            <li class="page-item <?= $p===$page?'active':'' ?>">
                                                <a class="page-link" href="izin.php?page=<?= $p ?>&per_page=<?= (int)$perPage ?>&jenis=<?= htmlspecialchars($jenis) ?>&status=<?= htmlspecialchars($status) ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                            <a class="page-link" href="izin.php?page=<?= $next ?>&per_page=<?= (int)$perPage ?>&jenis=<?= htmlspecialchars($jenis) ?>&status=<?= htmlspecialchars($status) ?>">Next</a>
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
    <div class="modal fade" id="modalVerify" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-shield-check me-1"></i> Verifikasi Izin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="izin.php">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="izin_id" id="verify_id">
                    <input type="hidden" name="decision" id="verify_decision">
                    <div class="modal-body">
                        <p id="verify_text">Konfirmasi tindakan verifikasi.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../../assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        var vModal = document.getElementById('modalVerify');
        vModal && vModal.addEventListener('show.bs.modal', function (event) {
            var b = event.relatedTarget;
            var id = b.getAttribute('data-id');
            var decision = b.getAttribute('data-decision');
            document.getElementById('verify_id').value = id;
            document.getElementById('verify_decision').value = decision;
            var txt = decision==='approve' ? 'Setujui pengajuan izin ini? Data absensi akan ditandai Izin/Sakit sesuai jenis.' : 'Tolak pengajuan izin ini? Tidak ada perubahan ke absensi.';
            document.getElementById('verify_text').textContent = txt;
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
