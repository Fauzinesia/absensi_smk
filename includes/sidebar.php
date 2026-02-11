<?php
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$role = $_SESSION['role'] ?? null;
$posAdmin = strpos($script, '/admin/');
$posSiswa = strpos($script, '/siswa/');
$posGuru  = strpos($script, '/guru/');
if ($posAdmin !== false) {
    $BASE = substr($script, 0, $posAdmin);
    $ROOT = 'admin';
} elseif ($posSiswa !== false) {
    $BASE = substr($script, 0, $posSiswa);
    $ROOT = 'siswa';
} elseif ($posGuru !== false) {
    $BASE = substr($script, 0, $posGuru);
    $ROOT = 'guru';
} else {
    $BASE = rtrim(dirname($script), '/');
    $ROOT = $role === 'admin' ? 'admin' : ($role === 'siswa' ? 'siswa' : ($role === 'guru' ? 'guru' : 'admin'));
}
$active = function(string $path) use ($script, $BASE) {
    return ($script === $BASE . $path) ? ' active' : '';
};
?>
<div id="sidebar" class="active">
    <div class="sidebar-wrapper active">
        <div class="sidebar-header">
            <div class="d-flex justify-content-between">
                <div class="logo">
                    <a href="<?= htmlspecialchars($BASE) ?>/admin/dashboard.php"><img src="<?= htmlspecialchars($BASE) ?>/assets/images/logo/logo_absensi.png" alt="Logo" style="height:150px; width:auto;"></a>
                </div>
                <div class="toggler">
                    <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                </div>
            </div>
        </div>
        <div class="sidebar-menu">
            <ul class="menu">
                <li class="sidebar-title">Menu</li>
                <li class="sidebar-item<?= $active('/'.$ROOT.'/dashboard.php') ?>">
                    <a href="<?= htmlspecialchars($BASE) ?>/<?= htmlspecialchars($ROOT) ?>/dashboard.php" class='sidebar-link'>
                        <i class="bi bi-grid-fill"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php if (($role ?? '') === 'admin'): ?>
                    <li class="sidebar-title">Data Master</li>
                    <li class="sidebar-item<?= $active('/admin/users/users.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/users/users.php" class="sidebar-link"><i class="bi bi-people-fill"></i><span>Users</span></a></li>
                    <li class="sidebar-item<?= $active('/admin/guru/guru.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/guru/guru.php" class="sidebar-link"><i class="bi bi-person-fill"></i><span>Guru</span></a></li>
                    <li class="sidebar-item<?= $active('/admin/siswa/siswa.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/siswa/siswa.php" class="sidebar-link"><i class="bi bi-person-badge"></i><span>Siswa</span></a></li>
                    <li class="sidebar-item<?= $active('/admin/kelas/kelas.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/kelas/kelas.php" class="sidebar-link"><i class="bi bi-building"></i><span>Kelas</span></a></li>
                    <li class="sidebar-title">Absensi</li>
                    <li class="sidebar-item<?= $active('/admin/absensi/absensi.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/absensi/absensi.php" class="sidebar-link"><i class="bi bi-check2-circle"></i><span>Absensi Harian</span></a></li>
                    <li class="sidebar-item<?= $active('/admin/jadwal/jadwal.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/jadwal/jadwal.php" class="sidebar-link"><i class="bi bi-clock-history"></i><span>Jadwal Absensi</span></a></li>
                    <li class="sidebar-item<?= $active('/admin/pengaturan/pengaturan.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/pengaturan/pengaturan.php" class="sidebar-link"><i class="bi bi-geo-alt-fill"></i><span>Pengaturan Lokasi</span></a></li>
                    <li class="sidebar-item<?= $active('/admin/izin/izin.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/izin/izin.php" class="sidebar-link"><i class="bi bi-file-earmark-text"></i><span>Izin</span></a></li>
                    <li class="sidebar-title">Kalender</li>
                    <li class="sidebar-item<?= $active('/admin/hari-libur/hari-libur.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/hari-libur/hari-libur.php" class="sidebar-link"><i class="bi bi-calendar-event"></i><span>Hari Libur</span></a></li>
                    <li class="sidebar-item<?= $active('/admin/override-hari/override-hari.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/override-hari/override-hari.php" class="sidebar-link"><i class="bi bi-calendar2-check"></i><span>Override Hari</span></a></li>
                    <li class="sidebar-title">Laporan</li>
                    <li class="sidebar-item has-sub">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-file-earmark-bar-graph"></i>
                            <span>Laporan</span>
                        </a>
                        <ul class="submenu">
                            <li class="submenu-item<?= $active('/admin/laporan/laporan-absensi.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/laporan/laporan-absensi.php"><i class="bi bi-clipboard-check"></i> Absensi</a></li>
                            <li class="submenu-item<?= $active('/admin/laporan/laporan-izin.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/laporan/laporan-izin.php"><i class="bi bi-file-earmark-check"></i> Izin</a></li>
                            <li class="submenu-item<?= $active('/admin/laporan/laporan-guru.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/laporan/laporan-guru.php"><i class="bi bi-person-lines-fill"></i> Data Guru</a></li>
                            <li class="submenu-item<?= $active('/admin/laporan/laporan-siswa.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/laporan/laporan-siswa.php"><i class="bi bi-people"></i> Data Siswa</a></li>
                        </ul>
                    </li>
                <?php elseif (($role ?? '') === 'guru'): ?>
                    <li class="sidebar-title">Absensi</li>
                    <li class="sidebar-item has-sub">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-check2-circle"></i>
                            <span>Absensi</span>
                        </a>
                        <ul class="submenu">
                            <li class="submenu-item<?= $active('/guru/absensi.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/guru/absensi.php">Absensi</a></li>
                            <li class="submenu-item<?= $active('/guru/riwayat.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/guru/riwayat.php">Riwayat Absensi</a></li>
                            <li class="submenu-item<?= $active('/guru/izin.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/guru/izin.php">Izin</a></li>
                        </ul>
                    </li>
                <?php elseif (($role ?? '') === 'siswa'): ?>
                    <li class="sidebar-title">Absensi</li>
                    <li class="sidebar-item has-sub">
                        <a href="#" class="sidebar-link">
                            <i class="bi bi-check2-circle"></i>
                            <span>Absensi</span>
                        </a>
                        <ul class="submenu">
                            <li class="submenu-item<?= $active('/siswa/absensi.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/siswa/absensi.php">Absensi</a></li>
                            <li class="submenu-item<?= $active('/siswa/riwayat.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/siswa/riwayat.php">Riwayat Absensi</a></li>
                            <li class="submenu-item<?= $active('/siswa/izin.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/siswa/izin.php">Izin</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <li class="sidebar-item<?= $active('/admin/logout.php') ?>"><a href="<?= htmlspecialchars($BASE) ?>/admin/logout.php" class="sidebar-link"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a></li>
            </ul>
        </div>
        <button class="sidebar-toggler btn x"><i data-feather="x"></i></button>
    </div>
</div>
