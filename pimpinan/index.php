<?php
require_once __DIR__ . '/../config/config.php';
requireRole('pimpinan');
$pageTitle = 'Dashboard';

$totalPenerima = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM penerima"))['c'];
$totalPending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM penerima WHERE status_validasi = 'pending'"))['c'];
$totalValid = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM penerima WHERE status_validasi = 'valid'"))['c'];
$totalDitolak = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM penerima WHERE status_validasi = 'ditolak'"))['c'];

$topRanking = mysqli_query($koneksi, "SELECT sh.*, p.nama FROM saw_hasil sh JOIN penerima p ON sh.penerima_id = p.id ORDER BY sh.ranking ASC LIMIT 10");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_pimpinan.php';
?>
<main class="app-content">
  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show alert-auto-dismiss">
      <?= clean($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="page-header">
    <h4 class="fw-bold mb-0">Dashboard Pimpinan</h4>
    <p class="text-muted small mb-0">Ringkasan data penerima bantuan dan status validasi.</p>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
      <div class="card card-stat h-100 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-box" style="background:#2c5f8a;"><i class="bi bi-people"></i></div>
            <div><div class="text-muted small">Total Data</div><div class="fs-4 fw-bold"><?= $totalPenerima ?></div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="card card-stat h-100 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-box" style="background:#f2a71b;"><i class="bi bi-hourglass-split"></i></div>
            <div><div class="text-muted small">Menunggu Validasi</div><div class="fs-4 fw-bold"><?= $totalPending ?></div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-4 col-sm-6">
      <div class="card card-stat h-100 shadow-sm">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="icon-box" style="background:#2e9e6b;"><i class="bi bi-patch-check"></i></div>
            <div><div class="text-muted small">Tervalidasi</div><div class="fs-4 fw-bold"><?= $totalValid ?></div></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card card-stat shadow-sm">
    <div class="card-body">
      <h6 class="fw-semibold mb-3"><i class="bi bi-trophy me-1"></i> Top 10 Ranking Penerima Bantuan</h6>
    <?php if (mysqli_num_rows($topRanking) > 0): ?>
      <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Nama</th>
              <th class="text-end">Nilai V</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($r = mysqli_fetch_assoc($topRanking)): ?>
              <tr>
                <td><?= $r['ranking'] ?></td>
                <td><?= clean($r['nama']) ?></td>
                <td class="text-end"><?= number_format($r['nilai_v'], 4) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-muted small mb-0">Belum ada hasil perhitungan ranking dari admin.</p>
    <?php endif; ?>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
