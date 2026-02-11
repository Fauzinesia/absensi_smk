<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'add') {
        $tanggal = (string)($_POST['tanggal'] ?? '');
        $nama_libur = trim((string)($_POST['nama_libur'] ?? ''));
        $jenis = (string)($_POST['jenis'] ?? 'Sekolah');
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        $is_active = (int)($_POST['is_active'] ?? 1);
        if ($tanggal !== '' && $nama_libur !== '' && in_array($jenis, ['Nasional','Cuti Bersama','Sekolah','Khusus'], true)) {
            $ok = db_query("INSERT INTO tb_hari_libur (tanggal, nama_libur, jenis, keterangan, is_active) VALUES (?, ?, ?, ?, ?)", "ssssi", [$tanggal, $nama_libur, $jenis, $keterangan !== '' ? $keterangan : null, $is_active]);
            $msg = $ok ? 'Hari libur ditambahkan' : 'Gagal menambah hari libur';
        } else { $msg = 'Isi data dengan benar'; }
    } elseif ($action === 'edit') {
        $libur_id = isset($_POST['libur_id']) ? (int)$_POST['libur_id'] : 0;
        $tanggal = (string)($_POST['tanggal'] ?? '');
        $nama_libur = trim((string)($_POST['nama_libur'] ?? ''));
        $jenis = (string)($_POST['jenis'] ?? 'Sekolah');
        $keterangan = trim((string)($_POST['keterangan'] ?? ''));
        $is_active = (int)($_POST['is_active'] ?? 1);
        if ($libur_id > 0 && $tanggal !== '' && $nama_libur !== '' && in_array($jenis, ['Nasional','Cuti Bersama','Sekolah','Khusus'], true)) {
            $ok = db_query("UPDATE tb_hari_libur SET tanggal = ?, nama_libur = ?, jenis = ?, keterangan = ?, is_active = ? WHERE libur_id = ?", "ssssii", [$tanggal, $nama_libur, $jenis, $keterangan !== '' ? $keterangan : null, $is_active, $libur_id]);
            $msg = $ok ? 'Hari libur diperbarui' : 'Gagal memperbarui hari libur';
        } else { $msg = 'Isi data dengan benar'; }
    } elseif ($action === 'delete') {
        $libur_id = isset($_POST['libur_id']) ? (int)$_POST['libur_id'] : 0;
        if ($libur_id > 0) {
            $ok = db_query("DELETE FROM tb_hari_libur WHERE libur_id = ?", "i", [$libur_id]);
            $msg = $ok ? 'Hari libur dihapus' : 'Gagal menghapus hari libur';
        } else { $msg = 'Data tidak valid'; }
    }
}
$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
$jenisF = isset($_GET['jenis']) && in_array($_GET['jenis'], ['Nasional','Cuti Bersama','Sekolah','Khusus','all'], true) ? (string)$_GET['jenis'] : 'all';
$activeF = isset($_GET['active']) && in_array($_GET['active'], ['1','0','all'], true) ? (string)$_GET['active'] : 'all';
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$total = 0;
$where = []; $types = ""; $params = [];
if ($jenisF !== 'all') { $where[] = "jenis = ?"; $types .= "s"; $params[] = $jenisF; }
if ($activeF !== 'all') { $where[] = "is_active = ?"; $types .= "i"; $params[] = (int)$activeF; }
if ($q !== '') { $where[] = "(nama_libur LIKE CONCAT('%',?,'%') OR tanggal = ?)"; $types .= "ss"; $params[] = $q; $params[] = $q; }
$whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";
$countRes = db_query("SELECT COUNT(*) AS total FROM tb_hari_libur $whereSql", $types !== "" ? $types : null, $params !== [] ? $params : null);
if ($countRes instanceof mysqli_result) { $total = (int)$countRes->fetch_assoc()['total']; }
$totalPages = max(1, (int)ceil($total / $perPage)); if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;
$res = db_query("SELECT libur_id, tanggal, nama_libur, jenis, keterangan, is_active, created_at FROM tb_hari_libur $whereSql ORDER BY tanggal DESC, libur_id DESC LIMIT ? OFFSET ?", ($types !== "" ? ($types . "ii") : "ii"), array_merge($params, [$perPage, $offset]));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hari Libur • E-Absensi</title>
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
            <div class="page-heading"><h3>Hari Libur</h3></div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Manajemen Hari Libur</h4>
                                <div><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd"><i class="bi bi-calendar-plus"></i> Tambah</button></div>
                            </div>
                            <div class="card-body">
                                <?php if ($msg): ?>
                                    <div class="alert alert-info d-flex align-items-center" role="alert"><i class="bi bi-info-circle me-2"></i><span><?= htmlspecialchars($msg) ?></span></div>
                                <?php endif; ?>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <div class="logo"><img src="../../assets/images/logo/logo_absensi.png" alt="Logo" style="height:48px"></div>
                                    <div><h5 class="mb-1">Daftar Hari Libur</h5><p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p></div>
                                    <div class="ms-auto d-flex align-items-end gap-2">
                                        <form method="get" class="d-flex align-items-end gap-2">
                                            <div>
                                                <label class="form-label">Jenis</label>
                                                <select name="jenis" class="form-select form-select-sm">
                                                    <option value="all" <?= $jenisF==='all'?'selected':'' ?>>Semua</option>
                                                    <option value="Nasional" <?= $jenisF==='Nasional'?'selected':'' ?>>Nasional</option>
                                                    <option value="Cuti Bersama" <?= $jenisF==='Cuti Bersama'?'selected':'' ?>>Cuti Bersama</option>
                                                    <option value="Sekolah" <?= $jenisF==='Sekolah'?'selected':'' ?>>Sekolah</option>
                                                    <option value="Khusus" <?= $jenisF==='Khusus'?'selected':'' ?>>Khusus</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Status</label>
                                                <select name="active" class="form-select form-select-sm">
                                                    <option value="all" <?= $activeF==='all'?'selected':'' ?>>Semua</option>
                                                    <option value="1" <?= $activeF==='1'?'selected':'' ?>>Aktif</option>
                                                    <option value="0" <?= $activeF==='0'?'selected':'' ?>>Nonaktif</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Cari</label>
                                                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="nama/tanggal YYYY-MM-DD">
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
                                        <thead><tr><th>#</th><th>Tanggal</th><th>Nama Libur</th><th>Jenis</th><th>Status</th><th>Keterangan</th><th>Dibuat</th><th class="text-end">Aksi</th></tr></thead>
                                        <tbody>
                                            <?php
                                            $no = $offset + 1;
                                            if ($res instanceof mysqli_result) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $id = (int)$row['libur_id'];
                                                    $tanggalRow = (string)$row['tanggal'];
                                                    $namaRow = (string)$row['nama_libur'];
                                                    $jenisRow = (string)$row['jenis'];
                                                    $ketRow = (string)($row['keterangan'] ?? '');
                                                    $status = ((int)$row['is_active'] === 1) ? 'Aktif' : 'Nonaktif';
                                                    $createdRow = (string)($row['created_at'] ?? '');
                                                    echo '<tr>';
                                                    echo '<td>'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($tanggalRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($namaRow) .'</td>';
                                                    echo '<td><span class="badge bg-primary">'. htmlspecialchars($jenisRow) .'</span></td>';
                                                    echo '<td>'. htmlspecialchars($status) .'</td>';
                                                    echo '<td>'. htmlspecialchars($ketRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($createdRow) .'</td>';
                                                    echo '<td class="text-end">';
                                                    echo '<button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit" data-id="'. $id .'" data-tanggal="'. htmlspecialchars($tanggalRow) .'" data-nama="'. htmlspecialchars($namaRow) .'" data-jenis="'. htmlspecialchars($jenisRow) .'" data-keterangan="'. htmlspecialchars($ketRow) .'" data-active="'. ((int)$row['is_active']) .'"><i class="bi bi-pencil-square"></i> Edit</button>';
                                                    echo '<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalDelete" data-id="'. $id .'" data-nama="'. htmlspecialchars($namaRow) .'"><i class="bi bi-trash"></i> Hapus</button>';
                                                    echo '</td></tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php $prev = max(1, $page - 1); $next = min($totalPages, $page + 1); $start = max(1, $page - 2); $end = min($totalPages, $page + 2); ?>
                                <nav aria-label="Libur pagination" class="mt-3">
                                    <ul class="pagination justify-content-end">
                                        <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="hari-libur.php?page=<?= $prev ?>&per_page=<?= (int)$perPage ?>">Prev</a></li>
                                        <?php if ($start > 1): ?><li class="page-item"><a class="page-link" href="hari-libur.php?page=1&per_page=<?= (int)$perPage ?>">1</a></li><?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?><?php endif; ?>
                                        <?php for ($p=$start; $p<=$end; $p++): ?><li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="hari-libur.php?page=<?= $p ?>&per_page=<?= (int)$perPage ?>"><?= $p ?></a></li><?php endfor; ?>
                                        <?php if ($end < $totalPages): ?><?php if ($end < $totalPages-1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?><li class="page-item"><a class="page-link" href="hari-libur.php?page=<?= $totalPages ?>&per_page=<?= (int)$perPage ?>"><?= $totalPages ?></a></li><?php endif; ?>
                                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>"><a class="page-link" href="hari-libur.php?page=<?= $next ?>&per_page=<?= (int)$perPage ?>">Next</a></li>
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
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-calendar-plus me-1"></i> Tambah Hari Libur</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="post" action="hari-libur.php">
                <input type="hidden" name="action" value="add">
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Tanggal</label><input name="tanggal" type="date" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Nama Libur</label><input name="nama_libur" type="text" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Jenis</label><select name="jenis" class="form-select" required><option value="Nasional">Nasional</option><option value="Cuti Bersama">Cuti Bersama</option><option value="Sekolah">Sekolah</option><option value="Khusus">Khusus</option></select></div>
                    <div class="mb-2"><label class="form-label">Status</label><select name="is_active" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
                    <div class="mb-2"><label class="form-label">Keterangan</label><input name="keterangan" type="text" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button></div>
            </form>
        </div></div>
    </div>
    <div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Hari Libur</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="post" action="hari-libur.php">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="libur_id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label">Tanggal</label><input name="tanggal" id="edit_tanggal" type="date" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Nama Libur</label><input name="nama_libur" id="edit_nama" type="text" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Jenis</label><select name="jenis" id="edit_jenis" class="form-select" required><option value="Nasional">Nasional</option><option value="Cuti Bersama">Cuti Bersama</option><option value="Sekolah">Sekolah</option><option value="Khusus">Khusus</option></select></div>
                    <div class="mb-2"><label class="form-label">Status</label><select name="is_active" id="edit_active" class="form-select"><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
                    <div class="mb-2"><label class="form-label">Keterangan</label><input name="keterangan" id="edit_keterangan" type="text" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Batal</button><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button></div>
            </form>
        </div></div>
    </div>
    <div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-trash me-1"></i> Hapus Hari Libur</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="post" action="hari-libur.php">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="libur_id" id="del_id">
                <div class="modal-body"><p>Anda yakin akan menghapus hari libur <strong id="del_nama"></strong>?</p></div>
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
            document.getElementById('edit_nama').value = b.getAttribute('data-nama');
            document.getElementById('edit_jenis').value = b.getAttribute('data-jenis');
            document.getElementById('edit_keterangan').value = b.getAttribute('data-keterangan');
            document.getElementById('edit_active').value = b.getAttribute('data-active');
        });
        var delModal = document.getElementById('modalDelete');
        delModal && delModal.addEventListener('show.bs.modal', function (event) {
            var b = event.relatedTarget;
            document.getElementById('del_id').value = b.getAttribute('data-id');
            document.getElementById('del_nama').textContent = b.getAttribute('data-nama') || '';
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
