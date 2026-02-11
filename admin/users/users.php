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
        $nama = trim((string)($_POST['nama'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'siswa');
        $no_hp = trim((string)($_POST['no_hp'] ?? ''));
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        if ($nama !== '' && $username !== '' && $password !== '' && in_array($role, ['admin','guru','siswa'], true)) {
            $exists = db_query("SELECT user_id FROM tb_users WHERE username = ?", "s", [$username]);
            if ($exists instanceof mysqli_result && $exists->num_rows > 0) {
                $msg = 'Username sudah digunakan';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ok = db_query(
                    "INSERT INTO tb_users (nama, username, password_hash, role, no_hp, is_active) VALUES (?, ?, ?, ?, ?, ?)",
                    "sssssi",
                    [$nama, $username, $hash, $role, $no_hp !== '' ? $no_hp : null, $is_active]
                );
                $msg = $ok ? 'Pengguna ditambahkan' : 'Gagal menambah pengguna';
            }
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'edit') {
        $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $nama = trim((string)($_POST['nama'] ?? ''));
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $role = (string)($_POST['role'] ?? 'siswa');
        $no_hp = trim((string)($_POST['no_hp'] ?? ''));
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        if ($id > 0 && $nama !== '' && $username !== '' && in_array($role, ['admin','guru','siswa'], true)) {
            $exists = db_query("SELECT user_id FROM tb_users WHERE username = ? AND user_id <> ?", "si", [$username, $id]);
            if ($exists instanceof mysqli_result && $exists->num_rows > 0) {
                $msg = 'Username sudah digunakan';
            } else {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ok = db_query(
                        "UPDATE tb_users SET nama = ?, username = ?, password_hash = ?, role = ?, no_hp = ?, is_active = ? WHERE user_id = ?",
                        "ssssssi",
                        [$nama, $username, $hash, $role, $no_hp !== '' ? $no_hp : null, $is_active, $id]
                    );
                } else {
                    $ok = db_query(
                        "UPDATE tb_users SET nama = ?, username = ?, role = ?, no_hp = ?, is_active = ? WHERE user_id = ?",
                        "ssssi i",
                        [$nama, $username, $role, $no_hp !== '' ? $no_hp : null, $is_active, $id]
                    );
                }
                $msg = $ok ? 'Pengguna diperbarui' : 'Gagal memperbarui pengguna';
            }
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'delete') {
        $id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        if ($id > 0) {
            $ok = db_query("DELETE FROM tb_users WHERE user_id = ?", "i", [$id]);
            $msg = $ok ? 'Pengguna dihapus' : 'Gagal menghapus pengguna';
        } else {
            $msg = 'Data tidak valid';
        }
    }
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = isset($_GET['per_page']) ? max(1, min(50, (int)$_GET['per_page'])) : 10;
$roleF = isset($_GET['role']) && in_array($_GET['role'], ['admin','guru','siswa','all'], true) ? (string)$_GET['role'] : 'all';
$activeF = isset($_GET['active']) && in_array($_GET['active'], ['1','0','all'], true) ? (string)$_GET['active'] : 'all';
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$total = 0;
$where = []; $types = ""; $params = [];
if ($roleF !== 'all') { $where[] = "role = ?"; $types .= "s"; $params[] = $roleF; }
if ($activeF !== 'all') { $where[] = "is_active = ?"; $types .= "i"; $params[] = (int)$activeF; }
if ($q !== '') { $where[] = "(nama LIKE CONCAT('%',?,'%') OR username LIKE CONCAT('%',?,'%'))"; $types .= "ss"; $params[] = $q; $params[] = $q; }
$whereSql = count($where) ? ("WHERE " . implode(" AND ", $where)) : "";
$countRes = db_query("SELECT COUNT(*) AS total FROM tb_users $whereSql", $types !== "" ? $types : null, $params !== [] ? $params : null);
if ($countRes instanceof mysqli_result) {
    $countRow = $countRes->fetch_assoc();
    $total = (int)$countRow['total'];
}
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$offset = ($page - 1) * $perPage;
$res = db_query(
    "SELECT user_id, nama, username, role, is_active, no_hp, created_at FROM tb_users $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?",
    ($types !== "" ? ($types . "ii") : "ii"),
    array_merge($params, [$perPage, $offset])
);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users • E-Absensi</title>
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
                <h3>Users</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Manajemen Pengguna</h4>
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
                                        <h5 class="mb-1">Daftar Pengguna</h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                    </div>
                                    <div class="ms-auto d-flex align-items-end gap-2">
                                        <form method="get" class="d-flex align-items-end gap-2">
                                            <div>
                                                <label class="form-label">Role</label>
                                                <select name="role" class="form-select form-select-sm">
                                                    <option value="all" <?= $roleF==='all'?'selected':'' ?>>Semua</option>
                                                    <option value="admin" <?= $roleF==='admin'?'selected':'' ?>>admin</option>
                                                    <option value="guru" <?= $roleF==='guru'?'selected':'' ?>>guru</option>
                                                    <option value="siswa" <?= $roleF==='siswa'?'selected':'' ?>>siswa</option>
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
                                                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="nama/username">
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
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>No HP</th>
                                                <th>Dibuat</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = $offset + 1;
                                            if ($res instanceof mysqli_result) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $status = ((int)$row['is_active'] === 1) ? 'Aktif' : 'Nonaktif';
                                                    $id = (int)$row['user_id'];
                                                    $namaRow = (string)$row['nama'];
                                                    $usernameRow = (string)$row['username'];
                                                    $roleRow = (string)$row['role'];
                                                    $hpRow = (string)($row['no_hp'] ?? '');
                                                    $createdRow = (string)$row['created_at'];
                                                    echo '<tr>';
                                                    echo '<td>'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($namaRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($usernameRow) .'</td>';
                                                    echo '<td><span class="badge bg-primary">'. htmlspecialchars($roleRow) .'</span></td>';
                                                    echo '<td>'. htmlspecialchars($status) .'</td>';
                                                    echo '<td>'. htmlspecialchars($hpRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($createdRow) .'</td>';
                                                    echo '<td class="text-end">';
                                                    echo '<button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit"';
                                                    echo ' data-id="'. $id .'" data-nama="'. htmlspecialchars($namaRow) .'" data-username="'. htmlspecialchars($usernameRow) .'"';
                                                    echo ' data-role="'. htmlspecialchars($roleRow) .'" data-hp="'. htmlspecialchars($hpRow) .'" data-active="'. ((int)$row['is_active']) .'">';
                                                    echo '<i class="bi bi-pencil-square"></i> Edit</button>';
                                                    echo '<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalDelete" data-id="'. $id .'" data-username="'. htmlspecialchars($usernameRow) .'"><i class="bi bi-trash"></i> Hapus</button>';
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
                                <nav aria-label="Users pagination" class="mt-3">
                                    <ul class="pagination justify-content-end">
                                        <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                            <a class="page-link" href="users.php?page=<?= $prev ?>&per_page=<?= (int)$perPage ?>">Prev</a>
                                        </li>
                                        <?php if ($start > 1): ?>
                                            <li class="page-item"><a class="page-link" href="users.php?page=1&per_page=<?= (int)$perPage ?>">1</a></li>
                                            <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                        <?php endif; ?>
                                        <?php for ($p=$start; $p<=$end; $p++): ?>
                                            <li class="page-item <?= $p===$page?'active':'' ?>">
                                                <a class="page-link" href="users.php?page=<?= $p ?>&per_page=<?= (int)$perPage ?>"><?= $p ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <?php if ($end < $totalPages): ?>
                                            <?php if ($end < $totalPages-1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; ?>
                                            <li class="page-item"><a class="page-link" href="users.php?page=<?= $totalPages ?>&per_page=<?= (int)$perPage ?>"><?= $totalPages ?></a></li>
                                        <?php endif; ?>
                                        <li class="page-item <?= $page>=$totalPages?'disabled':'' ?>">
                                            <a class="page-link" href="users.php?page=<?= $next ?>&per_page=<?= (int)$perPage ?>">Next</a>
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
                    <h5 class="modal-title"><i class="bi bi-person-plus me-1"></i> Tambah users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="users.php">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama</label>
                            <input name="nama" type="text" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Username</label>
                            <input name="username" type="text" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Password</label>
                            <input name="password" type="password" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="admin">admin</option>
                                <option value="guru">guru</option>
                                <option value="siswa">siswa</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">No HP</label>
                            <input name="no_hp" type="text" class="form-control">
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
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="users.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_id">
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label">Nama</label>
                            <input name="nama" id="edit_nama" type="text" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Username</label>
                            <input name="username" id="edit_username" type="text" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Password (kosongkan jika tidak diubah)</label>
                            <input name="password" id="edit_password" type="password" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit_role" class="form-select" required>
                                <option value="admin">admin</option>
                                <option value="guru">guru</option>
                                <option value="siswa">siswa</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">No HP</label>
                            <input name="no_hp" id="edit_hp" type="text" class="form-control">
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
                    <h5 class="modal-title"><i class="bi bi-trash me-1"></i> Hapus Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="users.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="del_id">
                    <div class="modal-body">
                        <p>Anda yakin akan menghapus pengguna <strong id="del_username"></strong>?</p>
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
            var id = button.getAttribute('data-id');
            var nama = button.getAttribute('data-nama');
            var username = button.getAttribute('data-username');
            var role = button.getAttribute('data-role');
            var hp = button.getAttribute('data-hp');
            var active = button.getAttribute('data-active');
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_nama').value = nama;
            document.getElementById('edit_username').value = username;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_hp').value = hp;
            document.getElementById('edit_active').value = active;
            document.getElementById('edit_password').value = '';
        });
        var delModal = document.getElementById('modalDelete');
        delModal && delModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id = button.getAttribute('data-id');
            var username = button.getAttribute('data-username');
            document.getElementById('del_id').value = id;
            document.getElementById('del_username').textContent = username;
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
