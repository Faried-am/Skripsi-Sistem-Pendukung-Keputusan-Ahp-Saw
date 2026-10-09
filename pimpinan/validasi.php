<?php
require_once __DIR__ . '/../config/config.php';
requireRole('pimpinan');
$pageTitle = 'Validasi Data Penerima';

if (isset($_GET['aksi'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $aksi = $_GET['aksi'];
    $userId = (int) $_SESSION['user_id'];

    if ($aksi === 'valid') {
        mysqli_query($koneksi, "UPDATE penerima SET status_validasi='valid', validated_by=$userId, validated_at=NOW() WHERE id=$id");
        setFlash('success', 'Data penerima berhasil divalidasi.');
    } elseif ($aksi === 'tolak') {
        mysqli_query($koneksi, "UPDATE penerima SET status_validasi='ditolak', validated_by=$userId, validated_at=NOW() WHERE id=$id");
        setFlash('success', 'Data penerima ditolak dan akan dikecualikan dari perhitungan ranking.');
    } elseif ($aksi === 'pending') {
        mysqli_query($koneksi, "UPDATE penerima SET status_validasi='pending', validated_by=NULL, validated_at=NULL WHERE id=$id");
        setFlash('success', 'Status validasi dikembalikan ke menunggu.');
    }
    redirect(BASE_URL . 'pimpinan/validasi.php');
}

$filter = $_GET['status'] ?? 'pending';
$whereClause = '';
if (in_array($filter, ['pending', 'valid', 'ditolak'])) {
    $whereClause = "WHERE status_validasi = '$filter'";
}

$kriteriaList = getKriteriaList($koneksi);
$q = mysqli_query($koneksi, "SELECT * FROM penerima $whereClause ORDER BY id ASC");
$penerimaList = [];
while ($row = mysqli_fetch_assoc($q)) $penerimaList[] = $row;

$nilaiMap = [];
$q2 = mysqli_query($koneksi, "SELECT * FROM nilai_penerima");
while ($row = mysqli_fetch_assoc($q2)) {
    $nilaiMap[$row['penerima_id']][$row['kriteria_id']] = $row['nilai'];
}

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

  <div class="page-header d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
    <div>
      <h4 class="fw-bold mb-0">Validasi Data Calon Penerima Bantuan</h4>
      <p class="text-muted small mb-0">Tinjau dan validasi data yang telah diinput oleh admin sebelum digunakan dalam perhitungan ranking.</p>
    </div>
    <div class="text-muted small">Pilih status di atas untuk memfilter data cepat.</div>
  </div>

  <div class="card card-stat mb-4 shadow-sm">
    <div class="card-body p-3">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills mb-0">
          <li class="nav-item"><a class="nav-link <?= $filter === 'pending' ? 'active' : '' ?>" href="?status=pending">Menunggu</a></li>
          <li class="nav-item"><a class="nav-link <?= $filter === 'valid' ? 'active' : '' ?>" href="?status=valid">Valid</a></li>
          <li class="nav-item"><a class="nav-link <?= $filter === 'ditolak' ? 'active' : '' ?>" href="?status=ditolak">Ditolak</a></li>
          <li class="nav-item"><a class="nav-link <?= $filter === 'semua' ? 'active' : '' ?>" href="?status=semua">Semua</a></li>
        </ul>
        <div class="text-muted small">Filter status validasi</div>
      </div>
      <div class="table-responsive">
    <div class="card-body p-3">
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm table-matrix mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Nama</th>
            <?php foreach ($kriteriaList as $k): ?><th class="text-center"><?= clean($k['kode']) ?></th><?php endforeach; ?>
            <th class="text-center">Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($penerimaList)): ?>
            <tr><td colspan="<?= 3 + count($kriteriaList) ?>" class="text-center text-muted py-4">Tidak ada data.</td></tr>
          <?php endif; ?>
          <?php foreach ($penerimaList as $p): ?>
            <?php
              $badge = ['pending' => 'bg-warning text-dark', 'valid' => 'bg-success', 'ditolak' => 'bg-danger'];
              $label = ['pending' => 'Menunggu', 'valid' => 'Valid', 'ditolak' => 'Ditolak'];
            ?>
            <tr>
              <td>
                <?= clean($p['nama']) ?>
                <?php if ($p['alamat']): ?><div class="small text-muted"><?= clean($p['alamat']) ?></div><?php endif; ?>
              </td>
              <?php foreach ($kriteriaList as $k): ?>
                <td class="text-center"><?= $nilaiMap[$p['id']][$k['id']] ?? '-' ?></td>
              <?php endforeach; ?>
              <td class="text-center"><span class="badge <?= $badge[$p['status_validasi']] ?>"><?= $label[$p['status_validasi']] ?></span></td>
              <td class="text-end text-nowrap">
                <div class="d-flex flex-wrap gap-1 justify-content-end">
                  <?php if ($p['status_validasi'] !== 'valid'): ?>
                    <a href="?aksi=valid&id=<?= $p['id'] ?>&status=<?= $filter ?>" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Valid</a>
                  <?php endif; ?>
                  <?php if ($p['status_validasi'] !== 'ditolak'): ?>
                    <a href="?aksi=tolak&id=<?= $p['id'] ?>&status=<?= $filter ?>" class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i> Tolak</a>
                  <?php endif; ?>
                  <?php if ($p['status_validasi'] !== 'pending'): ?>
                    <a href="?aksi=pending&id=<?= $p['id'] ?>&status=<?= $filter ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
