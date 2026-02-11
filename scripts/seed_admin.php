<?php
/**
 * Script seeding akun admin ke tb_users.
 * - Membaca kredensial dari env jika ada: ABSENSI_ADMIN_USER, ABSENSI_ADMIN_PASS, ABSENSI_ADMIN_NAME, ABSENSI_ADMIN_HP
 * - Default: user=admin, pass=admin123, nama=Administrator
 * - Aman dengan prepared statement dan password_hash
 */
require __DIR__ . '/../config/koneksi.php';

$username = getenv('ABSENSI_ADMIN_USER') ?: 'admin';
$password = getenv('ABSENSI_ADMIN_PASS') ?: 'admin123';
$nama     = getenv('ABSENSI_ADMIN_NAME') ?: 'Administrator';
$nohp     = getenv('ABSENSI_ADMIN_HP') ?: null;

$exists = db_query("SELECT user_id FROM tb_users WHERE username = ?", "s", [$username]);
if ($exists instanceof mysqli_result && $exists->num_rows > 0) {
    echo "Admin sudah ada: {$username}\n";
    exit(0);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$ok = db_query(
    "INSERT INTO tb_users (nama, username, password_hash, role, no_hp, is_active) VALUES (?, ?, ?, 'admin', ?, 1)",
    "ssss",
    [$nama, $username, $hash, $nohp]
);

if ($ok) {
    echo "Admin dibuat: {$username}\n";
    echo "Password: {$password}\n";
    exit(0);
}

echo "Gagal membuat admin\n";
exit(1);
