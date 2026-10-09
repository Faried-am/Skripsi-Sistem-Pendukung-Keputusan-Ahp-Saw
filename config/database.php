<?php
/**
 * Konfigurasi Koneksi Database
 * SPK Kelayakan Penerimaan Bantuan - Metode AHP & SAW
 *
 * Kredensial TIDAK disimpan di repo. Urutan pembacaan:
 *   1. File config/database.local.php (diabaikan git) -> salin dari database.local.example.php
 *   2. Environment variable: DB_HOST, DB_USER, DB_PASS, DB_NAME
 */

if (file_exists(__DIR__ . '/database.local.php')) {
    require_once __DIR__ . '/database.local.php';
}

defined('DB_HOST') || define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_USER') || define('DB_USER', getenv('DB_USER') ?: '');
defined('DB_PASS') || define('DB_PASS', getenv('DB_PASS') ?: '');
defined('DB_NAME') || define('DB_NAME', getenv('DB_NAME') ?: 'spk_ahp_saw');

mysqli_report(MYSQLI_REPORT_OFF);
$koneksi = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$koneksi) {
    // Detail error hanya ke log server, jangan ditampilkan ke pengunjung
    error_log('Koneksi database gagal: ' . mysqli_connect_error());
    die('Koneksi database gagal. Periksa konfigurasi (lihat README).');
}

mysqli_set_charset($koneksi, 'utf8mb4');
