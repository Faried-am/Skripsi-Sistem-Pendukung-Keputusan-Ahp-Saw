<?php
/**
 * UTILITAS CLI: membuat akun admin / pimpinan dengan password ter-hash.
 *
 * Jalankan dari terminal (BUKAN lewat browser):
 *   php database/create_user.php
 *
 * Password diketik saat prompt dan tidak disimpan di file apa pun.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Skrip ini hanya boleh dijalankan lewat CLI.');
}

require_once __DIR__ . '/../config/database.php';

function tanya(string $label, ?string $default = null): string
{
    echo $label . ($default ? " [$default]" : '') . ': ';
    $v = trim((string) fgets(STDIN));
    return $v === '' && $default !== null ? $default : $v;
}

function tanyaRahasia(string $label): string
{
    echo $label . ': ';
    if (DIRECTORY_SEPARATOR === '/') {
        shell_exec('stty -echo');
        $v = trim((string) fgets(STDIN));
        shell_exec('stty echo');
        echo "\n";
    } else {
        $v = trim((string) fgets(STDIN));
    }
    return $v;
}

$username = tanya('Username');
$nama     = tanya('Nama lengkap');
$role     = tanya('Role (admin/pimpinan)', 'admin');
$password = tanyaRahasia('Password (min. 8 karakter)');

if ($username === '' || $nama === '' || !in_array($role, ['admin', 'pimpinan'], true)) {
    exit("Input tidak valid.\n");
}
if (strlen($password) < 8) {
    exit("Password minimal 8 karakter.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($koneksi, 'INSERT INTO users (username, password, nama, role) VALUES (?, ?, ?, ?)');
mysqli_stmt_bind_param($stmt, 'ssss', $username, $hash, $nama, $role);

if (mysqli_stmt_execute($stmt)) {
    echo "Akun '$username' ($role) berhasil dibuat.\n";
} else {
    echo 'Gagal: ' . mysqli_stmt_error($stmt) . "\n";
}
