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
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $jabatan = trim((string)($_POST['jabatan'] ?? ''));
        $nip = trim((string)($_POST['nip'] ?? ''));
        $jenis_kelamin = (string)($_POST['jenis_kelamin'] ?? '');
        $alamat = trim((string)($_POST['alamat'] ?? ''));
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $fotoPath = null;
        if (isset($_FILES['foto']) && is_array($_FILES['foto']) && (int)$_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['foto']['tmp_name'];
            $size = (int)$_FILES['foto']['size'];
            $info = @getimagesize($tmp);
            $mime = is_array($info) && isset($info['mime']) ? $info['mime'] : null;
            if ($mime === 'image/jpeg' || $mime === 'image/png') {
                if ($size <= 2 * 1024 * 1024) {
                    $ext = $mime === 'image/jpeg' ? 'jpg' : 'png';
                    $name = 'guru_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $dir = __DIR__ . '/../../uploads/guru';
                    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                    $dest = $dir . '/' . $name;
                    if (@move_uploaded_file($tmp, $dest)) {
                        $fotoPath = 'uploads/guru/' . $name;
                    } else {
                        $msg = 'Gagal menyimpan foto';
                    }
                } else {
                    $msg = 'Ukuran foto terlalu besar (maks 2MB)';
                }
            } else {
                $msg = 'Format foto tidak didukung';
            }
        }
        if ($user_id > 0 && $jabatan !== '' && in_array($jenis_kelamin, ['L','P'], true)) {
            $existsUser = db_query("SELECT user_id, role FROM tb_users WHERE user_id = ?", "i", [$user_id]);
            $existsGuru = db_query("SELECT guru_id FROM tb_guru WHERE user_id = ?", "i", [$user_id]);
            if (!($existsUser instanceof mysqli_result) || $existsUser->num_rows !== 1) {
                $msg = 'Pengguna tidak ditemukan';
            } elseif ($existsGuru instanceof mysqli_result && $existsGuru->num_rows > 0) {
                $msg = 'Pengguna sudah terdaftar sebagai guru';
            } else {
                $ok = db_query(
                    "INSERT INTO tb_guru (user_id, nip, jabatan, foto, jenis_kelamin, alamat, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    "isssssi",
                    [$user_id, $nip !== '' ? $nip : null, $jabatan, $fotoPath, $jenis_kelamin, $alamat !== '' ? $alamat : null, $is_active]
                );
                $msg = $ok ? 'Guru ditambahkan' : 'Gagal menambah guru';
            }
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'edit') {
        $guru_id = isset($_POST['guru_id']) ? (int)$_POST['guru_id'] : 0;
        $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $jabatan = trim((string)($_POST['jabatan'] ?? ''));
        $nip = trim((string)($_POST['nip'] ?? ''));
        $jenis_kelamin = (string)($_POST['jenis_kelamin'] ?? '');
        $alamat = trim((string)($_POST['alamat'] ?? ''));
        $old_foto = trim((string)($_POST['old_foto'] ?? ''));
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $newFotoPath = null;
        if (isset($_FILES['foto']) && is_array($_FILES['foto']) && (int)$_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $tmp = $_FILES['foto']['tmp_name'];
            $size = (int)$_FILES['foto']['size'];
            $info = @getimagesize($tmp);
            $mime = is_array($info) && isset($info['mime']) ? $info['mime'] : null;
            if ($mime === 'image/jpeg' || $mime === 'image/png') {
                if ($size <= 2 * 1024 * 1024) {
                    $ext = $mime === 'image/jpeg' ? 'jpg' : 'png';
                    $name = 'guru_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $dir = __DIR__ . '/../../uploads/guru';
                    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
                    $dest = $dir . '/' . $name;
                    if (@move_uploaded_file($tmp, $dest)) {
                        $newFotoPath = 'uploads/guru/' . $name;
                        if ($old_foto !== '' && str_starts_with($old_foto, 'uploads/guru/')) {
                            $oldPath = __DIR__ . '/../../' . $old_foto;
                            if (is_file($oldPath)) { @unlink($oldPath); }
                        }
                    } else {
                        $msg = 'Gagal menyimpan foto';
                    }
                } else {
                    $msg = 'Ukuran foto terlalu besar (maks 2MB)';
                }
            } else {
                $msg = 'Format foto tidak didukung';
            }
        }
        if ($guru_id > 0 && $user_id > 0 && $jabatan !== '' && in_array($jenis_kelamin, ['L','P'], true)) {
            $existsGuru = db_query("SELECT guru_id FROM tb_guru WHERE user_id = ? AND guru_id <> ?", "ii", [$user_id, $guru_id]);
            if ($existsGuru instanceof mysqli_result && $existsGuru->num_rows > 0) {
                $msg = 'Pengguna sudah terdaftar sebagai guru lain';
            } else {
                $ok = db_query(
                    "UPDATE tb_guru SET user_id = ?, nip = ?, jabatan = ?, foto = ?, jenis_kelamin = ?, alamat = ?, is_active = ? WHERE guru_id = ?",
                    "isssssii",
                    [$user_id, $nip !== '' ? $nip : null, $jabatan, $newFotoPath !== null ? $newFotoPath : ($old_foto !== '' ? $old_foto : null), $jenis_kelamin, $alamat !== '' ? $alamat : null, $is_active, $guru_id]
                );
                $msg = $ok ? 'Data guru diperbarui' : 'Gagal memperbarui data guru';
            }
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'delete') {
        $guru_id = isset($_POST['guru_id']) ? (int)$_POST['guru_id'] : 0;
        if ($guru_id > 0) {
            $ok = db_query("DELETE FROM tb_guru WHERE guru_id = ?", "i", [$guru_id]);
            $msg = $ok ? 'Guru dihapus' : 'Gagal menghapus guru';
        } else {
            $msg = 'Data tidak valid';
        }
    }
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
$activeF = isset($_GET['active']) && in_array($_GET['active'], ['1','0','all'], true) ? (string)$_GET['active'] : 'all';
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$total = 0;
$where = []; $types = ""; $params = [];
if ($activeF !== 'all') { $where[] = "g.is_active = ?"; $types .= "i"; $params[] = (int)$activeF; }
if ($q !== '') { $where[] = "(u.nama LIKE CONCAT('%',?,'%') OR u.username LIKE CONCAT('%',?,'%') OR g.nip LIKE CONCAT('%',?,'%') OR g.jabatan LIKE CONCAT('%',?,'%'))"; $types .= "ssss"; $params[] = $q; $params[] = $q; $params[] = $q; $params[] = $q; }
$whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";
$countRes = db_query("SELECT COUNT(*) AS total FROM tb_guru g JOIN tb_users u ON g.user_id=u.user_id $whereSql", $types !== "" ? $types : null, $params !== [] ? $params : null);
if ($countRes instanceof mysqli_result) {
    $countRow = $countRes->fetch_assoc();
    $total = (int)$countRow['total'];
}
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;
$res = db_query(
    "SELECT g.guru_id, g.nip, g.jabatan, g.foto, g.jenis_kelamin, g.alamat, g.is_active, g.created_at, u.user_id, u.nama, u.username 
     FROM tb_guru g JOIN tb_users u ON g.user_id = u.user_id 
     $whereSql
     ORDER BY g.created_at DESC LIMIT ? OFFSET ?",
    ($types !== "" ? ($types . "ii") : "ii"),
    array_merge($params, [$perPage, $offset])
);
$usersGuru = db_query("SELECT user_id, nama, username FROM tb_users WHERE role = 'guru' ORDER BY nama ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guru • E-Absensi</title>
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
                <h3>Guru</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Manajemen Guru</h4>
                                <div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd"><i class="bi bi-person-plus"></i> Tambah</button>
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
                                        <h5 class="mb-1">Daftar Guru</h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                    </div>
                                    <div class="ms-auto d-flex align-items-end gap-2">
                                        <form method="get" class="d-flex align-items-end gap-2">
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
                                                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="nama/username/NIP/jabatan">
                                            </div>
                                            <div>
                                                <label class="form-label">Per halaman</label>
                                                <select name="per_page" class="form-select form-select-sm">
                                                    <?php foreach ([10,15,20,30] as $pp): ?>
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
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lg align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Nama</th>
                                                <th>Username</th>
                                                <th>NIP</th>
                                                <th>Jabatan</th>
                                                <th>JK</th>
                                                <th>Alamat</th>
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
                                                    $id = (int)$row['guru_id'];
                                                    $namaRow = (string)$row['nama'];
                                                    $usernameRow = (string)$row['username'];
                                                    $nipRow = (string)($row['nip'] ?? '');
                                                    $jabatanRow = (string)$row['jabatan'];
                                                    $jkRow = (string)$row['jenis_kelamin'];
                                                    $alamatRow = (string)($row['alamat'] ?? '');
                                                    $activeRow = (int)$row['is_active'];
                                                    $createdRow = (string)$row['created_at'];
                                                    $fotoRow = (string)($row['foto'] ?? '');
                                                    echo '<tr>';
                                                    echo '<td>'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($namaRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($usernameRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($nipRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($jabatanRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($jkRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($alamatRow) .'</td>';
                                                    echo '<td>'. ($activeRow===1 ? 'Aktif' : 'Nonaktif') .'</td>';
                                                    echo '<td>'. htmlspecialchars($createdRow) .'</td>';
                                                    echo '<td class="text-end">';
                                                    echo '<button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit"';
                                                    echo ' data-id="'. $id .'" data-user="'. (int)$row['user_id'] .'" data-nip="'. htmlspecialchars($nipRow) .'" data-jabatan="'. htmlspecialchars($jabatanRow) .'"';
                                                    echo ' data-foto="'. htmlspecialchars($fotoRow) .'" data-jk="'. htmlspecialchars($jkRow) .'" data-alamat="'. htmlspecialchars($alamatRow) .'" data-active="'. $activeRow .'">';
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
                                <nav aria-label="Guru pagination" class="mt-3">
                                    <ul class="pagination justify-content-end">
                                        <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                            <a class="page-link" href="guru.php?page=<?= $prev ?>&per_page=<?= (int)$perPage ?>">Prev</a>
                                        </li>
                                        <?php if ($start > 1): ?>
                                            <li class="page-item"><a class="page-link" href="guru.php?page=1&per_page=<?= (int)$perPage ?>">1</a></li>
                                            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                        <?php endif; ?>
                                        <?php for ($p=$start; $p<=$end; $p++): ?>
                                            <li class="page-item <?= $p===$page?'active':'' ?>">
                                                <a class="page-link" href="guru.php?page=<?= $p ?>&per_page=<?= (int)$perPage ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <?php if ($end < $totalPages): ?>
                                            <?php if ($end < $totalPages-1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                            <li class="page-item"><a class="page-link" href="guru.php?page=<?= $totalPages ?>&per_page=<?= (int)$perPage ?>"><?= $totalPages ?></a></li>
                                        <?php endif; ?>
                                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                            <a class="page-link" href="guru.php?page=<?= $next ?>&per_page=<?= (int)$perPage ?>">Next</a>
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
                    <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i> Tambah Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="guru.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Pengguna</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- pilih pengguna (role guru) --</option>
                                <?php if ($usersGuru instanceof mysqli_result) { while ($u = $usersGuru->fetch_assoc()) { ?>
                                    <option value="<?= (int)$u['user_id'] ?>"><?= htmlspecialchars((string)$u['nama']) ?> (<?= htmlspecialchars((string)$u['username']) ?>)</option>
                                <?php } } ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIP</label>
                            <input name="nip" type="text" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jabatan</label>
                            <input name="jabatan" type="text" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L">L</option>
                                <option value="P">P</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Foto</label>
                            <input name="foto" type="file" accept="image/png,image/jpeg" class="form-control">
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
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="guru.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="guru_id" id="edit_id">
                    <input type="hidden" name="old_foto" id="edit_old_foto">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Pengguna</label>
                            <select name="user_id" id="edit_user" class="form-select" required>
                                <?php if ($usersGuru instanceof mysqli_result) { mysqli_data_seek($usersGuru, 0); while ($u = $usersGuru->fetch_assoc()) { ?>
                                    <option value="<?= (int)$u['user_id'] ?>"><?= htmlspecialchars((string)$u['nama']) ?> (<?= htmlspecialchars((string)$u['username']) ?>)</option>
                                <?php } } ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">NIP</label>
                            <input name="nip" id="edit_nip" type="text" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jabatan</label>
                            <input name="jabatan" id="edit_jabatan" type="text" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="edit_jk" class="form-select" required>
                                <option value="L">L</option>
                                <option value="P">P</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" id="edit_alamat" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Foto</label>
                            <input name="foto" id="edit_foto" type="file" accept="image/png,image/jpeg" class="form-control">
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
                    <h5 class="modal-title"><i class="bi bi-trash me-1"></i> Hapus Guru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="guru.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="guru_id" id="del_id">
                    <div class="modal-body">
                        <p>Anda yakin akan menghapus guru <strong id="del_nama"></strong>?</p>
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
            document.getElementById('edit_user').value = button.getAttribute('data-user');
            document.getElementById('edit_nip').value = button.getAttribute('data-nip');
            document.getElementById('edit_jabatan').value = button.getAttribute('data-jabatan');
            document.getElementById('edit_old_foto').value = button.getAttribute('data-foto');
            document.getElementById('edit_jk').value = button.getAttribute('data-jk');
            document.getElementById('edit_alamat').value = button.getAttribute('data-alamat');
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
