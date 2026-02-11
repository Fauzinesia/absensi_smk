<?php
session_start();
require __DIR__ . '/../config/koneksi.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') { header('Location: ../login.php'); exit; }
header('Location: ./laporan/laporan-absensi.php');
exit;
