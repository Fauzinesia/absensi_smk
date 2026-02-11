<?php
session_start();
require __DIR__ . '/../../config/koneksi.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }

$msg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? (string)$_POST['action'] : '';
    if ($action === 'add') {
        $berlaku_untuk = (string)($_POST['berlaku_untuk'] ?? '');
        $hari = (string)($_POST['hari'] ?? '');
        $jam_masuk = (string)($_POST['jam_masuk'] ?? '');
        $batas_telat = (string)($_POST['batas_telat'] ?? '');
        $jam_pulang = (string)($_POST['jam_pulang'] ?? '');
        $is_libur = isset($_POST['is_libur']) ? (int)$_POST['is_libur'] : 0;
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $days = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
        if (in_array($berlaku_untuk, ['guru','siswa'], true) && in_array($hari, $days, true) && $jam_masuk !== '' && $batas_telat !== '' && $jam_pulang !== '') {
            $exists = db_query("SELECT jadwal_id FROM tb_jadwal_absensi WHERE berlaku_untuk = ? AND hari = ?", "ss", [$berlaku_untuk, $hari]);
            if ($exists instanceof mysqli_result && $exists->num_rows > 0) {
                $msg = 'Jadwal untuk hari ini sudah ada';
            } else {
                $ok = db_query(
                    "INSERT INTO tb_jadwal_absensi (berlaku_untuk, hari, jam_masuk, batas_telat, jam_pulang, is_libur, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    "sssssii",
                    [$berlaku_untuk, $hari, $jam_masuk, $batas_telat, $jam_pulang, $is_libur, $is_active]
                );
                $msg = $ok ? 'Jadwal ditambahkan' : 'Gagal menambah jadwal';
            }
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'edit') {
        $jadwal_id = isset($_POST['jadwal_id']) ? (int)$_POST['jadwal_id'] : 0;
        $berlaku_untuk = (string)($_POST['berlaku_untuk'] ?? '');
        $hari = (string)($_POST['hari'] ?? '');
        $jam_masuk = (string)($_POST['jam_masuk'] ?? '');
        $batas_telat = (string)($_POST['batas_telat'] ?? '');
        $jam_pulang = (string)($_POST['jam_pulang'] ?? '');
        $is_libur = isset($_POST['is_libur']) ? (int)$_POST['is_libur'] : 0;
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        $days = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
        if ($jadwal_id > 0 && in_array($berlaku_untuk, ['guru','siswa'], true) && in_array($hari, $days, true) && $jam_masuk !== '' && $batas_telat !== '' && $jam_pulang !== '') {
            $exists = db_query("SELECT jadwal_id FROM tb_jadwal_absensi WHERE berlaku_untuk = ? AND hari = ? AND jadwal_id <> ?", "ssi", [$berlaku_untuk, $hari, $jadwal_id]);
            if ($exists instanceof mysqli_result && $exists->num_rows > 0) {
                $msg = 'Jadwal untuk hari ini sudah ada';
            } else {
                $ok = db_query(
                    "UPDATE tb_jadwal_absensi SET berlaku_untuk = ?, hari = ?, jam_masuk = ?, batas_telat = ?, jam_pulang = ?, is_libur = ?, is_active = ? WHERE jadwal_id = ?",
                    "sssssiii",
                    [$berlaku_untuk, $hari, $jam_masuk, $batas_telat, $jam_pulang, $is_libur, $is_active, $jadwal_id]
                );
                $msg = $ok ? 'Jadwal diperbarui' : 'Gagal memperbarui jadwal';
            }
        } else {
            $msg = 'Isi data dengan benar';
        }
    } elseif ($action === 'delete') {
        $jadwal_id = isset($_POST['jadwal_id']) ? (int)$_POST['jadwal_id'] : 0;
        if ($jadwal_id > 0) {
            $ok = db_query("DELETE FROM tb_jadwal_absensi WHERE jadwal_id = ?", "i", [$jadwal_id]);
            $msg = $ok ? 'Jadwal dihapus' : 'Gagal menghapus jadwal';
        } else {
            $msg = 'Data tidak valid';
        }
    }
}

$nama = $_SESSION['nama'] ?? 'Pengguna';
$tanggalLabel = formatTanggalIndonesia(new DateTimeImmutable('now', new DateTimeZone('Asia/Makassar')));
$jenis = isset($_GET['jenis']) && in_array($_GET['jenis'], ['guru','siswa'], true) ? $_GET['jenis'] : 'guru';

$res = db_query("SELECT jadwal_id, berlaku_untuk, hari, jam_masuk, batas_telat, jam_pulang, is_libur, is_active, created_at FROM tb_jadwal_absensi WHERE berlaku_untuk = ? ORDER BY FIELD(hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')", "s", [$jenis]);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Absensi • E-Absensi</title>
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
                <h3>Jadwal Absensi</h3>
            </div>
            <div class="page-content">
                <section class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="card-title mb-0">Jadwal <?= htmlspecialchars($jenis) ?></h4>
                                <div class="d-flex align-items-center gap-2">
                                    <form method="get" class="d-flex align-items-center">
                                        <label class="me-2 text-muted">Jenis</label>
                                        <select name="jenis" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="guru" <?= $jenis==='guru'?'selected':'' ?>>guru</option>
                                            <option value="siswa" <?= $jenis==='siswa'?'selected':'' ?>>siswa</option>
                                        </select>
                                    </form>
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
                                        <h5 class="mb-1">Pengaturan Jadwal</h5>
                                        <p class="text-muted mb-0"><?= htmlspecialchars($tanggalLabel) ?> • WITA</p>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-lg align-middle">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Hari</th>
                                                <th>Jam Masuk</th>
                                                <th>Batas Telat</th>
                                                <th>Jam Pulang</th>
                                                <th>Libur</th>
                                                <th>Status</th>
                                                <th>Dibuat</th>
                                                <th class="text-end">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 1;
                                            if ($res instanceof mysqli_result) {
                                                while ($row = $res->fetch_assoc()) {
                                                    $id = (int)$row['jadwal_id'];
                                                    $hariRow = (string)$row['hari'];
                                                    $masuk = (string)$row['jam_masuk'];
                                                    $telat = (string)$row['batas_telat'];
                                                    $pulang = (string)$row['jam_pulang'];
                                                    $libur = (int)$row['is_libur'];
                                                    $active = (int)$row['is_active'];
                                                    $createdRow = (string)$row['created_at'];
                                                    echo '<tr>';
                                                    echo '<td>'. $no++ .'</td>';
                                                    echo '<td>'. htmlspecialchars($hariRow) .'</td>';
                                                    echo '<td>'. htmlspecialchars($masuk) .'</td>';
                                                    echo '<td>'. htmlspecialchars($telat) .'</td>';
                                                    echo '<td>'. htmlspecialchars($pulang) .'</td>';
                                                    echo '<td>'. ($libur===1 ? 'Ya' : 'Tidak') .'</td>';
                                                    echo '<td>'. ($active===1 ? 'Aktif' : 'Nonaktif') .'</td>';
                                                    echo '<td>'. htmlspecialchars($createdRow) .'</td>';
                                                    echo '<td class="text-end">';
                                                    echo '<button class="btn btn-sm btn-warning me-1" data-bs-toggle="modal" data-bs-target="#modalEdit"';
                                                    echo ' data-id="'. $id .'" data-jenis="'. htmlspecialchars((string)$row['berlaku_untuk']) .'" data-hari="'. htmlspecialchars($hariRow) .'"';
                                                    echo ' data-masuk="'. htmlspecialchars($masuk) .'" data-telat="'. htmlspecialchars($telat) .'" data-pulang="'. htmlspecialchars($pulang) .'" data-libur="'. $libur .'" data-active="'. $active .'">';
                                                    echo '<i class="bi bi-pencil-square"></i> Edit</button>';
                                                    echo '<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalDelete" data-id="'. $id .'" data-hari="'. htmlspecialchars($hariRow) .'"><i class="bi bi-trash"></i> Hapus</button>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
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
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-1"></i> Tambah Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="jadwal.php">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Berlaku Untuk</label>
                                <select name="berlaku_untuk" class="form-select" required>
                                    <option value="guru" <?= $jenis==='guru'?'selected':'' ?>>guru</option>
                                    <option value="siswa" <?= $jenis==='siswa'?'selected':'' ?>>siswa</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Hari</label>
                                <select name="hari" class="form-select" required>
                                    <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $d): ?>
                                        <option value="<?= $d ?>"><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Libur?</label>
                                <select name="is_libur" class="form-select">
                                    <option value="0">Tidak</option>
                                    <option value="1">Ya</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Jam Masuk</label>
                                <input type="time" name="jam_masuk" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Batas Telat</label>
                                <input type="time" name="batas_telat" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Jam Pulang</label>
                                <input type="time" name="jam_pulang" class="form-control" required>
                            </div>
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
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-1"></i> Edit Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="jadwal.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="jadwal_id" id="edit_id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Berlaku Untuk</label>
                                <select name="berlaku_untuk" id="edit_jenis" class="form-select" required>
                                    <option value="guru">guru</option>
                                    <option value="siswa">siswa</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Hari</label>
                                <select name="hari" id="edit_hari" class="form-select" required>
                                    <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $d): ?>
                                        <option value="<?= $d ?>"><?= $d ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Libur?</label>
                                <select name="is_libur" id="edit_libur" class="form-select">
                                    <option value="0">Tidak</option>
                                    <option value="1">Ya</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Jam Masuk</label>
                                <input type="time" name="jam_masuk" id="edit_masuk" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Batas Telat</label>
                                <input type="time" name="batas_telat" id="edit_telat" class="form-control" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Jam Pulang</label>
                                <input type="time" name="jam_pulang" id="edit_pulang" class="form-control" required>
                            </div>
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
                    <h5 class="modal-title"><i class="bi bi-trash me-1"></i> Hapus Jadwal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="jadwal.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="jadwal_id" id="del_id">
                    <div class="modal-body">
                        <p>Anda yakin akan menghapus jadwal <strong id="del_hari"></strong>?</p>
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
            document.getElementById('edit_jenis').value = b.getAttribute('data-jenis');
            document.getElementById('edit_hari').value = b.getAttribute('data-hari');
            document.getElementById('edit_masuk').value = b.getAttribute('data-masuk');
            document.getElementById('edit_telat').value = b.getAttribute('data-telat');
            document.getElementById('edit_pulang').value = b.getAttribute('data-pulang');
            document.getElementById('edit_libur').value = b.getAttribute('data-libur');
            document.getElementById('edit_active').value = b.getAttribute('data-active');
        });
        var delModal = document.getElementById('modalDelete');
        delModal && delModal.addEventListener('show.bs.modal', function (event) {
            var b = event.relatedTarget;
            document.getElementById('del_id').value = b.getAttribute('data-id');
            document.getElementById('del_hari').textContent = b.getAttribute('data-hari');
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
