<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$ranking = getHasilRanking($koneksi);
$kriteriaList = getKriteriaList($koneksi);
$ahpTerakhir = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ahp_hasil ORDER BY id DESC LIMIT 1"));
$tanggalCetak = date('d-m-Y H:i');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Laporan Hasil Ranking</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family: Arial, sans-serif; font-size: 13px; padding: 30px; }
  .kop { text-align: center; border-bottom: 3px double #000; padding-bottom: 12px; margin-bottom: 20px; }
  table { width: 100%; }
  th, td { padding: 6px 8px; }
  .rank-1 { background: #fff3cd !important; }
  .rank-2 { background: #f0f0f0 !important; }
  .rank-3 { background: #fbe6d4 !important; }
  @media print {
    .no-print { display: none !important; }
    body { padding: 0; }
  }
</style>
</head>
<body>
  <div class="text-end no-print mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Cetak / Simpan PDF</button>
  </div>

  <div class="kop">
    <h5 class="mb-1 fw-bold">LAPORAN HASIL PERANGKINGAN</h5>
    <h6 class="mb-1">SISTEM PENDUKUNG KEPUTUSAN KELAYAKAN PENERIMA BANTUAN</h6>
    <p class="mb-0 small">Metode AHP (Analytical Hierarchy Process) &amp; SAW (Simple Additive Weighting)</p>
  </div>

  <p class="small">Dicetak pada: <?= $tanggalCetak ?></p>

  <!-- Bobot Kriteria dan CR disembunyikan sesuai permintaan -->

  <table class="table table-bordered table-sm">
    <thead>
      <tr class="table-light">
        <th>Ranking</th>
        <th>Nama</th>
        <th>Nilai Vi</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ranking as $r): ?>
        <?php
          $rowClass = $r['ranking'] == 1 ? 'rank-1' : ($r['ranking'] == 2 ? 'rank-2' : ($r['ranking'] == 3 ? 'rank-3' : ''));
          $label = ['pending' => 'Menunggu', 'valid' => 'Valid', 'ditolak' => 'Ditolak'];
        ?>
        <tr class="<?= $rowClass ?>">
          <td><?= $r['ranking'] ?></td>
          <td><?= clean($r['nama']) ?></td>
          <td><?= number_format($r['nilai_v'], 4) ?></td>
          <td><?= $label[$r['status_validasi']] ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="row mt-5">
    <div class="col-6"></div>
    <div class="col-6 text-center">
      <p>Mengetahui,<br>Pimpinan</p>
      <br><br><br>
      <p class="fw-bold text-decoration-underline">( ......................................... )</p>
    </div>
  </div>
</body>
</html>
