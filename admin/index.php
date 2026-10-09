<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$pageTitle = 'Dashboard';

$totalKriteria = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM kriteria"))['c'];
$totalPenerima = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM penerima"))['c'];
$totalPending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM penerima WHERE status_validasi = 'pending'"))['c'];
$totalValid = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM penerima WHERE status_validasi = 'valid'"))['c'];
$ahpTerakhir = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ahp_hasil ORDER BY id DESC LIMIT 1"));
$sudahRanking = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) c FROM saw_hasil"))['c'];

$topRanking = mysqli_query($koneksi, "SELECT sh.*, p.nama FROM saw_hasil sh JOIN penerima p ON sh.penerima_id = p.id ORDER BY sh.ranking ASC LIMIT 5");

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>
<main class="app-content">
  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show alert-auto-dismiss" role="alert">
      <?= clean($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="page-header">
    <h4 class="fw-bold mb-0">Dashboard Admin</h4>
    <p class="text-muted small mb-0">Ringkasan data sistem pendukung keputusan kelayakan penerima bantuan.</p>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
      <div class="card card-stat p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:#2c5f8a;"><i class="bi bi-ui-checks-grid"></i></div>
          <div>
            <div class="text-muted small">Kriteria</div>
            <div class="fs-4 fw-bold"><?= $totalKriteria ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card card-stat p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:#2e9e6b;"><i class="bi bi-people"></i></div>
          <div>
            <div class="text-muted small">Data Penerima</div>
            <div class="fs-4 fw-bold"><?= $totalPenerima ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card card-stat p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:#f2a71b;"><i class="bi bi-hourglass-split"></i></div>
          <div>
            <div class="text-muted small">Menunggu Validasi</div>
            <div class="fs-4 fw-bold"><?= $totalPending ?></div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card card-stat p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="icon-box" style="background:#e35d6a;"><i class="bi bi-patch-check"></i></div>
          <div>
            <div class="text-muted small">Tervalidasi</div>
            <div class="fs-4 fw-bold"><?= $totalValid ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card card-stat p-3 h-100">
        <h6 class="fw-semibold mb-3"><i class="bi bi-calculator me-1"></i> Status Perhitungan AHP</h6>
        <?php if ($ahpTerakhir): ?>
          <table class="table table-sm mb-0">
            <tr><td>Lambda Max</td><td class="text-end fw-semibold"><?= number_format($ahpTerakhir['lambda_max'], 4) ?></td></tr>
            <tr><td>Consistency Index (CI)</td><td class="text-end fw-semibold"><?= number_format($ahpTerakhir['ci'], 4) ?></td></tr>
            <tr><td>Random Index (RI)</td><td class="text-end fw-semibold"><?= number_format($ahpTerakhir['ri'], 4) ?></td></tr>
            <tr><td>Consistency Ratio (CR)</td><td class="text-end fw-semibold"><?= number_format($ahpTerakhir['cr'], 4) ?></td></tr>
            <tr>
              <td>Status</td>
              <td class="text-end">
                <span class="badge <?= $ahpTerakhir['keterangan'] === 'KONSISTEN' ? 'bg-success' : 'bg-danger' ?>">
                  <?= $ahpTerakhir['keterangan'] ?>
                </span>
              </td>
            </tr>
          </table>
        <?php else: ?>
          <p class="text-muted small mb-2">Perhitungan AHP belum dilakukan.</p>
          <a href="<?= BASE_URL ?>admin/ahp.php" class="btn btn-sm btn-primary">Hitung AHP Sekarang</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card card-stat p-3 h-100">
        <h6 class="fw-semibold mb-3"><i class="bi bi-trophy me-1"></i> Top 5 Ranking Terbaru</h6>
        <?php if ($sudahRanking > 0): ?>
          <table class="table table-sm mb-0">
            <thead><tr><th>#</th><th>Nama</th><th class="text-end">Nilai V</th></tr></thead>
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
          <a href="<?= BASE_URL ?>admin/hasil.php" class="btn btn-sm btn-outline-primary mt-2">Lihat Semua</a>
        <?php else: ?>
          <p class="text-muted small mb-2">Perhitungan SAW / ranking belum dilakukan.</p>
          <a href="<?= BASE_URL ?>admin/saw.php" class="btn btn-sm btn-primary">Hitung SAW Sekarang</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
