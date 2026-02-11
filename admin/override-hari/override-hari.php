<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { 
    header('Location: ../../login.php'); 
    exit; 
}

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'add') {
        $tanggal = (string)($_POST['tanggal'] ?? '');
        $status = (string)($_POST['status'] ?? 'Libur');
        $alasan = trim((string)($_POST['alasan'] ?? ''));
        if ($tanggal !== '' && in_array($status, ['Masuk','Libur'], true)) {
            $ok = db_query("INSERT INTO tb_override_hari (tanggal, status, alasan) VALUES (?, ?, ?)", "sss", [$tanggal, $status, $alasan !== '' ? $alasan : null]);
            $msg = $ok ? 'Override hari ditambahkan' : 'Gagal menambah override';
        } else { $msg = 'Isi data dengan benar'; }
    } elseif ($action === 'edit') {
        $override_id = isset($_POST['override_id']) ? (int)$_POST['override_id'] : 0;
        $tanggal = (string)($_POST['tanggal'] ?? '');
        $status = (string)($_POST['status'] ?? 'Libur');
        $alasan = trim((string)($_POST['alasan'] ?? ''));
        if ($override_id > 0 && $tanggal !== '' && in_array($status, ['Masuk','Libur'], true)) {
            $ok = db_query("UPDATE tb_override_hari SET tanggal = ?, status = ?, alasan = ? WHERE override_id = ?", "sssi", [$tanggal, $status, $alasan !== '' ? $alasan : null, $override_id]);
            $msg = $ok ? 'Override hari diperbarui' : 'Gagal memperbarui override';
        } else { $msg = 'Isi data dengan benar'; }
    } elseif ($action === 'delete') {
        $override_id = isset($_POST['override_id']) ? (int)$_POST['override_id'] : 0;
        if ($override_id > 0) {
            $ok = db_query("DELETE FROM tb_override_hari WHERE override_id = ?", "i", [$override_id]);
            $msg = $ok ? 'Override hari dihapus' : 'Gagal menghapus override';
        } else { $msg = 'Data tidak valid'; }
    }
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
$statusF = isset($_GET['status']) && in_array($_GET['status'], ['Masuk','Libur','all'], true) ? (string)$_GET['status'] : 'all';
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$total = 0;
$where = []; $types = ""; $params = [];
if ($statusF !== 'all') { $where[] = "status = ?"; $types .= "s"; $params[] = $statusF; }
if ($q !== '') { $where[] = "(alasan LIKE CONCAT('%',?,'%') OR tanggal = ?)"; $types .= "ss"; $params[] = $q; $params[] = $q; }
$whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";
$countRes = db_query("SELECT COUNT(*) AS total FROM tb_override_hari $whereSql", $types !== "" ? $types : null, $params !== [] ? $params : null);
if ($countRes instanceof mysqli_result) { $total = (int)$countRes->fetch_assoc()['total']; }
$totalPages = max(1, (int)ceil($total / $perPage)); if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;
$res = db_query("SELECT override_id, tanggal, status, alasan, created_at FROM tb_override_hari $whereSql ORDER BY tanggal DESC, override_id DESC LIMIT ? OFFSET ?", ($types !== "" ? ($types . "ii") : "ii"), array_merge($params, [$perPage, $offset]));

// Helper function untuk nama hari
function hariNama($d) { 
    $m = ['Mon'=>'Senin','Tue'=>'Selasa','Wed'=>'Rabu','Thu'=>'Kamis','Fri'=>'Jumat','Sat'=>'Sabtu','Sun'=>'Minggu']; 
    return $m[$d] ?? $d; 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Override Hari • E-Absensi</title>
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
            <header class="mb-3"><a href="#" class="burger-btn d-block d-xl-none"><i class="bi bi-justify fs-3"></i></a></header>
            <div class="page-heading"><h3>Override Hari Kerja</h3></div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Manajemen Override Hari</h4>
                                <div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd"><i class="bi bi-calendar2-plus"></i> Tambah Override</button></div>
                            </div>
                            <div class="card-body">
                                <?php if ($msg): ?>
                                    <div class="alert alert-info d-flex align-items-center" role="alert"><i class="bi bi-info-circle me-2"></i><span><?= htmlspecialchars($msg) ?></span></div>
                                <?php endif; ?>
                                
                                <div class="alert alert-warning">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Catatan:</strong> Fitur ini untuk mengubah status hari kerja/libur pada tanggal tertentu. 
                                    Contoh: Sabtu (biasanya libur) dijadikan <strong>Masuk</strong>, atau Senin (biasanya masuk) dijadikan <strong>Libur</strong>.
                                </div>
                                
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="logo"><img src="../../assets/images/logo/logo_absensi.png" alt="Logo" style="height:48px"></div>
                                    <div><h5 class="mb-1">Daftar Override Hari</h5><p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p></div>
                                    <div class="ms-auto d-flex align-items-end gap-2">
                                        <form method="get" class="d-flex align-items-end gap-2">
                                            <div>
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select form-select-sm">
                                                    <option value="all" <?= $statusF==='all'?'selected':'' ?>>Semua</option>
                                                    <option value="Masuk" <?= $statusF==='Masuk'?'selected':'' ?>>Masuk</option>
                                                    <option value="Libur" <?= $statusF==='Libur'?'selected':'' ?>>Libur</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Cari</label>
                                                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="alasan/tanggal YYYY-MM-DD">
                                            </div>
                                            <div>
                                                <label class="form-label">Per halaman</label>
                                                <select name="per_page" class="form-select form-select-sm">
                                                    <?php foreach ([10,15,20,30] as $pp): ?><option value="<?= $pp ?>" <?= $perPage===$pp?'selected':'' ?>><?= $pp ?></option><?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">&nbsp;</label>
                                                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lg align-middle">
                                        <thead><tr><th>#</th><th>Tanggal</th><th>Hari</th><th>Status Override</th><th>Alasan</th><th>Dibuat</th><th class="text-end">Aksi</th></tr></thead>
                                        <tbody>
                                            <?php
                                            $no = $offset + 1;
                                            if ($res instanceof mysqli_result) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $id = (int)$row['override_id'];
                                                    $tanggalRow = (string)$row['tanggal'];
                                                    $statusRow = (string)$row['status'];
                                                    $alasanRow = (string)($row['alasan'] ?? '');
                                                    $createdRow = (string)($row['created_at'] ?? '');
                                                    
                                                    // Get day name
                                                    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $tanggalRow, new DateTimeZone('Asia/Makassar'));
                                                    $hariNamaFull = $dt ? hariNama($dt->format('D')) : '';
                                                    
                                                    echo '<tr>';
                                                    echo '<td>'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($tanggalRow) .'</td>';
                                                    echo '<td><span class="badge bg-secondary">'. htmlspecialchars($hariNamaFull) .'</span></td>';
                                                    echo '<td><span class="badge bg-'. ($statusRow==='Libur'?'danger':'success') .'">'. htmlspecialchars($statusRow) .'</span></td>';
                                                    echo '<td>'. htmlspecialchars($alasanRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($createdRow) .'</td>';
                                                    echo '<td class="text-end">';
                                                    echo '<button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit" data-id="'. $id .'" data-tanggal="'. htmlspecialchars($tanggalRow) .'" data-status="'. htmlspecialchars($statusRow) .'" data-alasan="'. htmlspecialchars($alasanRow) .'"><i class="bi bi-pencil-square"></i> Edit</button>';
                                                    echo '<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalDelete" data-id="'. $id .'" data-info="'. htmlspecialchars($tanggalRow . ' • ' . $statusRow) .'"><i class="bi bi-trash"></i> Hapus</button>';
                                                    echo '</td></tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php $prev = max(1, $page - 1); $next = min($totalPages, $page + 1); $start = max(1, $page - 2); $end = min($totalPages, $page + 2); ?>
                                <nav aria-label="Override pagination" class="mt-3">
                                    <ul class="pagination justify-content-end">
                                        <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="override-hari.php?page=<?= $prev ?>&per_page=<?= (int)$perPage ?>">Prev</a></li>
                                        <?php if ($start > 1): ?><li class="page-item"><a class="page-link" href="override-hari.php?page=1&per_page=<?= (int)$perPage ?>">1</a></li><?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?><?php endif; ?>
                                        <?php for ($p=$start; $p<=$end; $p++): ?><li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="override-hari.php?page=<?= $p ?>&per_page=<?= (int)$perPage ?>"><?= $p ?></a></li><?php endfor; ?>
                                        <?php if ($end < $totalPages): ?><?php if ($end < $totalPages-1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?><li class="page-item"><a class="page-link" href="override-hari.php?page=<?= $totalPages ?>&per_page=<?= (int)$perPage ?>"><?= $totalPages ?></a></li><?php endif; ?>
                                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="override-hari.php?page=<?= $next ?>&per_page=<?= (int)$perPage ?>">Next</a></li>
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
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-calendar2-plus me-1"></i> Tambah Override Hari</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="post" action="override-hari.php">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input name="tanggal" type="date" class="form-control" required>
                        <small class="text-muted">Pilih tanggal yang ingin di-override</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Override</label>
                        <select name="status" class="form-select" required>
                            <option value="Masuk">Masuk (Hari libur dijadikan masuk)</option>
                            <option value="Libur">Libur (Hari kerja dijadikan libur)</option>
                        </select>
                        <small class="text-muted">Pilih status yang diinginkan untuk tanggal tersebut</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <input name="alasan" type="text" class="form-control" placeholder="Contoh: Kerja bakti, Hari raya, dll">
                        <small class="text-muted">Opsional: Berikan alasan override</small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button></div>
            </form>
        </div></div>
    </div>
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Override Hari</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="post" action="override-hari.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="override_id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tanggal</label>
                        <input name="tanggal" id="edit_tanggal" type="date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status Override</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="Masuk">Masuk (Hari libur dijadikan masuk)</option>
                            <option value="Libur">Libur (Hari kerja dijadikan libur)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alasan</label>
                        <input name="alasan" id="edit_alasan" type="text" class="form-control">
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button></div>
            </form>
        </div></div>
    </div>
    <div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-trash me-1"></i> Hapus Override</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="post" action="override-hari.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="override_id" id="del_id">
                <div class="modal-body"><p>Anda yakin akan menghapus override <strong id="del_info"></strong>?</p></div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button><button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i> Hapus</button></div>
            </form>
        </div></div>
    </div>
    <script src="../../assets/vendors/perfect-scrollbar/perfect-scrollbar.min.js"></script>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        var editModal = document.getElementById('modalEdit');
        editModal && editModal.addEventListener('show.bs.modal', function (event) {
            var b = event.relatedTarget;
            document.getElementById('edit_id').value = b.getAttribute('data-id');
            document.getElementById('edit_tanggal').value = b.getAttribute('data-tanggal');
            document.getElementById('edit_status').value = b.getAttribute('data-status');
            document.getElementById('edit_alasan').value = b.getAttribute('data-alasan');
        });
        var delModal = document.getElementById('modalDelete');
        delModal && delModal.addEventListener('show.bs.modal', function (event) {
            var b = event.relatedTarget;
            document.getElementById('del_id').value = b.getAttribute('data-id');
            document.getElementById('del_info').textContent = b.getAttribute('data-info') || '';
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
