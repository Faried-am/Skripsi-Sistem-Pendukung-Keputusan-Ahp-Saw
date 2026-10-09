<?php
require_once __DIR__ . '/../config/config.php';
requireRole('pimpinan');
$pageTitle = 'Hasil Ranking';

$ranking = getHasilRanking($koneksi);

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_pimpinan.php';
?>
<main class="app-content">
  <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-0">Hasil Akhir Perangkingan</h4>
      <p class="text-muted small mb-0">Daftar calon penerima bantuan terurut berdasarkan nilai preferensi (Vi) tertinggi.</p>
    </div>
    <a href="<?= BASE_URL ?>pimpinan/cetak.php" target="_blank" class="btn btn-outline-secondary">
      <i class="bi bi-printer me-1"></i> Cetak Laporan
    </a>
  </div>

  <?php if (empty($ranking)): ?>
    <div class="alert alert-info">Admin belum melakukan perhitungan ranking.</div>
  <?php else: ?>
    <div class="card card-stat shadow-sm">
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Ranking</th>
              <th>Nama</th>
              <th class="text-end">Nilai Vi</th>
              <th class="text-center">Status Validasi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ranking as $r): ?>
              <?php
                $rowClass = $r['ranking'] == 1 ? 'rank-1' : ($r['ranking'] == 2 ? 'rank-2' : ($r['ranking'] == 3 ? 'rank-3' : ''));
                $badge = ['pending' => 'bg-warning text-dark', 'valid' => 'bg-success', 'ditolak' => 'bg-danger'];
                $label = ['pending' => 'Menunggu', 'valid' => 'Valid', 'ditolak' => 'Ditolak'];
              ?>
              <tr class="<?= $rowClass ?>">
                <td><span class="badge bg-primary">#<?= $r['ranking'] ?></span></td>
                <td class="fw-semibold"><?= clean($r['nama']) ?></td>
                <td class="text-end"><?= number_format($r['nilai_v'], 4) ?></td>
                <td class="text-center"><span class="badge <?= $badge[$r['status_validasi']] ?>"><?= $label[$r['status_validasi']] ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
