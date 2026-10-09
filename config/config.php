<?php
/**
 * Konfigurasi Umum Aplikasi
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// Pastikan timezone sesuai dengan zona lokal (ubah jika perlu)
date_default_timezone_set('Asia/Jakarta');

// Base URL otomatis (dihitung dari lokasi folder project terhadap document root,
// sehingga konsisten dipanggil dari kedalaman folder manapun: index.php, admin/, pimpinan/, auth/)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$projectRoot = str_replace('\\', '/', dirname(__DIR__)); // folder yang berisi index.php
$docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
$relative = $docRoot !== '' ? str_replace($docRoot, '', $projectRoot) : '';
$relative = '/' . trim($relative, '/');
$relative = ($relative === '/') ? '' : $relative;
define('BASE_URL', $protocol . $_SERVER['HTTP_HOST'] . $relative . '/');

define('APP_NAME', 'SPK Kelayakan Penerima Bantuan');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
