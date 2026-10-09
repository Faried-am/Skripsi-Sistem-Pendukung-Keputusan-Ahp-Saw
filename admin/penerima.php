<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$pageTitle = 'Data Penerima';

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    // Hapus dulu data terkait di tabel nilai_penerima dan saw_hasil
    // agar tidak ada data "yatim" (orphan) dan penghapusan penerima tidak gagal
    // karena constraint foreign key.
    mysqli_query($koneksi, "DELETE FROM nilai_penerima WHERE penerima_id = $id");
    mysqli_query($koneksi, "DELETE FROM saw_hasil WHERE penerima_id = $id");

    $hapusOk = mysqli_query($koneksi, "DELETE FROM penerima WHERE id = $id");

    if ($hapusOk && mysqli_affected_rows($koneksi) > 0) {
        setFlash('success', 'Data penerima berhasil dihapus.');
    } else {
        setFlash('danger', 'Gagal menghapus data penerima. Silakan coba lagi.');
    }
    redirect(BASE_URL . 'admin/penerima.php');
}

$keyword = trim($_GET['q'] ?? '');
$where = '';
if ($keyword !== '') {
  $kw = mysqli_real_escape_string($koneksi, $keyword);
  $where = "WHERE p.nama LIKE '%$kw%'";
}

$kriteriaList = getKriteriaList($koneksi);

$q = mysqli_query($koneksi, "SELECT * FROM penerima p $where ORDER BY p.id ASC");
$penerimaList = [];
while ($row = mysqli_fetch_assoc($q)) $penerimaList[] = $row;

// ambil nilai semua penerima sekaligus
$nilaiMap = [];
$q2 = mysqli_query($koneksi, "SELECT * FROM nilai_penerima");
while ($row = mysqli_fetch_assoc($q2)) {
    $nilaiMap[$row['penerima_id']][$row['kriteria_id']] = $row['nilai'];
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>
<main class="app-content">
  <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show alert-auto-dismiss">
      <?= clean($flash['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-0">Data Calon Penerima Bantuan</h4>
      <p class="text-muted small mb-0">Kelola data calon penerima bantuan secara mandiri atau melalui import Excel.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="<?= BASE_URL ?>admin/penerima_import.php" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-excel me-1"></i> Import Excel
      </a>
      <a href="<?= BASE_URL ?>admin/penerima_form.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i> Tambah Penerima
      </a>
    </div>
  </div>

  <div class="card card-stat p-3">
    <form method="GET" class="row g-2 mb-3">
      <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="Cari nama..." value="<?= clean($keyword) ?>">
      </div>
      <div class="col-auto">
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Cari</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>No</th>
            <th>Nama</th>
            <?php foreach ($kriteriaList as $k): ?>
              <th class="text-center"><?= clean($k['kode']) ?></th>
            <?php endforeach; ?>
            <th class="text-center">Status</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($penerimaList)): ?>
            <tr><td colspan="<?= 4 + count($kriteriaList) ?>" class="text-center text-muted py-4">Belum ada data penerima.</td></tr>
          <?php endif; ?>
          <?php $no = 1; foreach ($penerimaList as $p): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td>
                <?= clean($p['nama']) ?>
                <?php if ($p['alamat']): ?><div class="small text-muted"><?= clean($p['alamat']) ?></div><?php endif; ?>
              </td>
              <?php foreach ($kriteriaList as $k): ?>
                <td class="text-center"><?= $nilaiMap[$p['id']][$k['id']] ?? '-' ?></td>
              <?php endforeach; ?>
              <td class="text-center">
                <?php
                  $badge = ['pending' => 'bg-warning text-dark', 'valid' => 'bg-success', 'ditolak' => 'bg-danger'];
                  $label = ['pending' => 'Menunggu', 'valid' => 'Valid', 'ditolak' => 'Ditolak'];
                ?>
                <span class="badge <?= $badge[$p['status_validasi']] ?>"><?= $label[$p['status_validasi']] ?></span>
              </td>
              <td class="text-end">
                <a href="<?= BASE_URL ?>admin/penerima_form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <a href="?hapus=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="small text-muted mb-0">Total data: <?= count($penerimaList) ?></p>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>