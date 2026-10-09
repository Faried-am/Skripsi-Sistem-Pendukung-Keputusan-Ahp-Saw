<?php
$current = basename($_SERVER['PHP_SELF']);
function navActiveP($file, $current)
{
    return $file === $current ? 'active' : '';
}
?>
<aside class="app-sidebar" id="appSidebar">
  <div class="sidebar-section">Menu Utama</div>
  <a class="sidebar-link <?= navActiveP('index.php', $current) ?>" href="<?= BASE_URL ?>pimpinan/index.php">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>

  <div class="sidebar-section">Validasi</div>
  <a class="sidebar-link <?= navActiveP('validasi.php', $current) ?>" href="<?= BASE_URL ?>pimpinan/validasi.php">
    <i class="bi bi-check2-square"></i> Validasi Data Penerima
  </a>

  <div class="sidebar-section">Hasil</div>
  <a class="sidebar-link <?= navActiveP('hasil.php', $current) ?>" href="<?= BASE_URL ?>pimpinan/hasil.php">
    <i class="bi bi-trophy"></i> Hasil Ranking
  </a>
  <a class="sidebar-link <?= navActiveP('cetak.php', $current) ?>" href="<?= BASE_URL ?>pimpinan/cetak.php" target="_blank">
    <i class="bi bi-printer"></i> Cetak Laporan
  </a>
</aside>
