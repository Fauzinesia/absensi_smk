<?php

date_default_timezone_set('Asia/Makassar');

$DB_HOST = getenv('ABSENSI_DB_HOST') ?: '127.0.0.1';
$DB_USER = getenv('ABSENSI_DB_USER') ?: 'root';
$DB_PASS = getenv('ABSENSI_DB_PASS') ?: '';
$DB_NAME = getenv('ABSENSI_DB_NAME') ?: 'absensi_smk';
$DB_PORT = (int)(getenv('ABSENSI_DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_OFF);

$mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($mysqli->connect_errno) {
    die('Koneksi gagal: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');

function db()
{
    global $mysqli;
    return $mysqli;
}

function db_query($sql, $types = null, $params = [])
{
    $conn = db();
    if ($types !== null && !empty($params)) {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            return false;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        return $res instanceof mysqli_result ? $res : true;
    }
    return $conn->query($sql);
}

function formatTanggalIndonesia($date)
{
    $dt = $date instanceof DateTimeInterface
        ? DateTimeImmutable::createFromInterface($date)
        : new DateTimeImmutable((string)$date, new DateTimeZone('Asia/Makassar'));
    $hariMap = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulanMap = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $hari  = $hariMap[(int)$dt->format('w')];
    $bulan = $bulanMap[(int)$dt->format('n') - 1];
    return sprintf('%s, %s %s %s', $hari, $dt->format('d'), $bulan, $dt->format('Y'));
}
