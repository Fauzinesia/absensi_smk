<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'add') {
        $nama_kelas = trim((string)($_POST['nama_kelas'] ?? ''));
        $tingkat = (string)($_POST['tingkat'] ?? '');
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        if ($nama_kelas !== '' && in_array($tingkat, ['X','XI','XII'], true)) {
            $exists = db_query("SELECT kelas_id FROM tb_kelas WHERE nama_kelas = ?", "s", [$nama_kelas]);
            if ($exists instanceof mysqli_result && $exists->num_rows > 0) {
                $msg = 'Nama kelas sudah ada';
            } else {
                $ok = db_query(
                    "INSERT INTO tb_kelas (nama_kelas, tingkat, is_active) VALUES (?, ?, ?)",
                    "ssi",
                    [$nama_kelas, $tingkat, $is_active]
                );
                $msg = $ok ? 'Kelas ditambahkan' : 'Gagal menambah kelas';
            }
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'edit') {
        $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;
        $nama_kelas = trim((string)($_POST['nama_kelas'] ?? ''));
        $tingkat = (string)($_POST['tingkat'] ?? '');
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        if ($kelas_id > 0 && $nama_kelas !== '' && in_array($tingkat, ['X','XI','XII'], true)) {
            $exists = db_query("SELECT kelas_id FROM tb_kelas WHERE nama_kelas = ? AND kelas_id <> ?", "si", [$nama_kelas, $kelas_id]);
            if ($exists instanceof mysqli_result && $exists->num_rows > 0) {
                $msg = 'Nama kelas sudah ada';
            } else {
                $ok = db_query(
                    "UPDATE tb_kelas SET nama_kelas = ?, tingkat = ?, is_active = ? WHERE kelas_id = ?",
                    "ssii",
                    [$nama_kelas, $tingkat, $is_active, $kelas_id]
                );
                $msg = $ok ? 'Data kelas diperbarui' : 'Gagal memperbarui data kelas';
            }
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'delete') {
        $kelas_id = isset($_POST['kelas_id']) ? (int)$_POST['kelas_id'] : 0;
        if ($kelas_id > 0) {
            $ok = db_query("DELETE FROM tb_kelas WHERE kelas_id = ?", "i", [$kelas_id]);
            $msg = $ok ? 'Kelas dihapus' : 'Gagal menghapus kelas';
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
$countRes = db_query("SELECT COUNT(*) AS total FROM tb_kelas");
if ($countRes instanceof mysqli_result) {
    $countRow = $countRes->fetch_assoc();
    $total = (int)$countRow['total'];
}
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;
$res = db_query(
    "SELECT kelas_id, nama_kelas, tingkat, is_active, created_at FROM tb_kelas ORDER BY tingkat, nama_kelas ASC LIMIT ? OFFSET ?",
    "ii",
    [$perPage, $offset]
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelas • E-Absensi</title>
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
                <h3>Kelas</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Manajemen Kelas</h4>
                                <div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd"><i class="bi bi-building-add"></i> Tambah</button>
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
                                        <h5 class="mb-1">Daftar Kelas</h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                    </div>
                                    <div class="ms-auto d-flex align-items-center">
                                        <form method="get" class="d-flex align-items-center">
                                            <input type="hidden" name="page" value="<?= (int)$page ?>">
                                            <label class="me-2 text-muted">Per halaman</label>
                                            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <?php foreach ([10,15,20,30] as $pp): ?>
                                                    <option value="<?= $pp ?>" <?= $perPage===$pp?'selected':'' ?>><?= $pp ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lg align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nama Kelas</th>
                                                <th>Tingkat</th>
                                                <th>Status</th>
                                                <th>Dibuat</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = $offset + 1;
                                            if ($res instanceof mysqli_result) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $id = (int)$row['kelas_id'];
                                                    $namaRow = (string)$row['nama_kelas'];
                                                    $tingkatRow = (string)$row['tingkat'];
                                                    $activeRow = (int)$row['is_active'];
                                                    $createdRow = (string)$row['created_at'];
                                                    echo '<tr>';
                                                    echo '<td>'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($namaRow) .'</td>';
                                                    echo '<td><span class="badge bg-primary">'. htmlspecialchars($tingkatRow) .'</span></td>';
                                                    echo '<td>'. ($activeRow===1 ? 'Aktif' : 'Nonaktif') .'</td>';
                                                    echo '<td>'. htmlspecialchars($createdRow) .'</td>';
                                                    echo '<td class="text-end">';
                                                    echo '<button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit"';
                                                    echo ' data-id="'. $id .'" data-nama="'. htmlspecialchars($namaRow) .'" data-tingkat="'. htmlspecialchars($tingkatRow) .'" data-active="'. $activeRow .'">';
                                                    echo '<i class="bi bi-pencil-square"></i> Edit</button>';
                                                    echo '<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalDelete" data-id="'. $id .'" data-nama="'. htmlspecialchars($namaRow) .'"><i class="bi bi-trash"></i> Hapus</button>';
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
                                <nav aria-label="Kelas pagination" class="mt-3">
                                    <ul class="pagination justify-content-end">
                                        <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                            <a class="page-link" href="kelas.php?page=<?= $prev ?>&per_page=<?= (int)$perPage ?>">Prev</a>
                                        </li>
                                        <?php if ($start > 1): ?>
                                            <li class="page-item"><a class="page-link" href="kelas.php?page=1&per_page=<?= (int)$perPage ?>">1</a></li>
                                            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                        <?php endif; ?>
                                        <?php for ($p=$start; $p<=$end; $p++): ?>
                                            <li class="page-item <?= $p===$page?'active':'' ?>">
                                                <a class="page-link" href="kelas.php?page=<?= $p ?>&per_page=<?= (int)$perPage ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <?php if ($end < $totalPages): ?>
                                            <?php if ($end < $totalPages-1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                            <li class="page-item"><a class="page-link" href="kelas.php?page=<?= $totalPages ?>&per_page=<?= (int)$perPage ?>"><?= $totalPages ?></a></li>
                                        <?php endif; ?>
                                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                            <a class="page-link" href="kelas.php?page=<?= $next ?>&per_page=<?= (int)$perPage ?>">Next</a>
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
                    <h5 class="modal-title"><i class="bi bi-building-add me-1"></i> Tambah Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="kelas.php">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Kelas</label>
                            <input name="nama_kelas" type="text" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tingkat</label>
                            <select name="tingkat" class="form-select" required>
                                <option value="X">X</option>
                                <option value="XI">XI</option>
                                <option value="XII">XII</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="is_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
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
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="kelas.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="kelas_id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama Kelas</label>
                            <input name="nama_kelas" id="edit_nama" type="text" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tingkat</label>
                            <select name="tingkat" id="edit_tingkat" class="form-select" required>
                                <option value="X">X</option>
                                <option value="XI">XI</option>
                                <option value="XII">XII</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Status</label>
                            <select name="is_active" id="edit_active" class="form-select">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
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
                    <h5 class="modal-title"><i class="bi bi-trash me-1"></i> Hapus Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="kelas.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="kelas_id" id="del_id">
                    <div class="modal-body">
                        <p>Anda yakin akan menghapus kelas <strong id="del_nama"></strong>?</p>
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
            var button = event.relatedTarget;
            document.getElementById('edit_id').value = button.getAttribute('data-id');
            document.getElementById('edit_nama').value = button.getAttribute('data-nama');
            document.getElementById('edit_tingkat').value = button.getAttribute('data-tingkat');
            document.getElementById('edit_active').value = button.getAttribute('data-active');
        });
        var delModal = document.getElementById('modalDelete');
        delModal && delModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            document.getElementById('del_id').value = button.getAttribute('data-id');
            document.getElementById('del_nama').textContent = button.getAttribute('data-nama');
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
