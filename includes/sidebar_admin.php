<?php
$current = basename($_SERVER['PHP_SELF']);
function navActive($file, $current)
{
    return $file === $current ? 'active' : '';
}
?>
<aside class="app-sidebar" id="appSidebar">
  <div class="sidebar-section">Menu Utama</div>
  <a class="sidebar-link <?= navActive('index.php', $current) ?>" href="<?= BASE_URL ?>admin/index.php">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>

  <div class="sidebar-section">Data Master</div>
  <a class="sidebar-link <?= navActive('kriteria.php', $current) ?>" href="<?= BASE_URL ?>admin/kriteria.php">
    <i class="bi bi-ui-checks-grid"></i> Kriteria
  </a>
  <a class="sidebar-link <?= navActive('skala.php', $current) ?>" href="<?= BASE_URL ?>admin/skala.php">
    <i class="bi bi-sliders"></i> Skala Penilaian
  </a>
  <a class="sidebar-link <?= navActive('penerima.php', $current) ?>" href="<?= BASE_URL ?>admin/penerima.php">
    <i class="bi bi-people"></i> Data Penerima
  </a>
  <a class="sidebar-link <?= navActive('penerima_import.php', $current) ?>" href="<?= BASE_URL ?>admin/penerima_import.php">
    <i class="bi bi-file-earmark-excel"></i> Import Excel
  </a>

  <div class="sidebar-section">Perhitungan</div>
  <a class="sidebar-link <?= navActive('ahp.php', $current) ?>" href="<?= BASE_URL ?>admin/ahp.php">
    <i class="bi bi-calculator"></i> Perhitungan AHP
  </a>
  <a class="sidebar-link <?= navActive('saw.php', $current) ?>" href="<?= BASE_URL ?>admin/saw.php">
    <i class="bi bi-graph-up-arrow"></i> Perhitungan SAW
  </a>

  <div class="sidebar-section">Hasil</div>
  <a class="sidebar-link <?= navActive('hasil.php', $current) ?>" href="<?= BASE_URL ?>admin/hasil.php">
    <i class="bi bi-trophy"></i> Hasil Ranking
  </a>
  <a class="sidebar-link <?= navActive('cetak.php', $current) ?>" href="<?= BASE_URL ?>admin/cetak.php" target="_blank">
    <i class="bi bi-printer"></i> Cetak Laporan
  </a>
</aside>
