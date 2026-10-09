<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$pageTitle = 'Skala Penilaian';

$kriteriaList = getKriteriaList($koneksi);

$kriteriaAktifId = isset($_GET['kriteria_id']) ? (int) $_GET['kriteria_id'] : (($kriteriaList[0]['id'] ?? 0));

/* ==== PROSES CRUD SKALA ==== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $kriteria_id = (int) $_POST['kriteria_id'];
        $label = mysqli_real_escape_string($koneksi, trim($_POST['label']));
        $nilai = (int) $_POST['nilai'];
        $urutan = (int) ($_POST['urutan'] ?? $nilai);

        if ($label === '' || $nilai < 1) {
            setFlash('danger', 'Label dan nilai skala wajib diisi dengan benar.');
        } else {
            if ($id > 0) {
                mysqli_query($koneksi, "UPDATE skala_kriteria SET label='$label', nilai=$nilai, urutan=$urutan WHERE id=$id");
                setFlash('success', 'Skala berhasil diperbarui.');
            } else {
                mysqli_query($koneksi, "INSERT INTO skala_kriteria (kriteria_id, label, nilai, urutan) VALUES ($kriteria_id, '$label', $nilai, $urutan)");
                setFlash('success', 'Skala berhasil ditambahkan.');
            }
        }
        redirect(BASE_URL . 'admin/skala.php?kriteria_id=' . $kriteria_id);
    }
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM skala_kriteria WHERE id = $id");
    setFlash('success', 'Skala berhasil dihapus.');
    redirect(BASE_URL . 'admin/skala.php?kriteria_id=' . $kriteriaAktifId);
}

$editData = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $editData = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM skala_kriteria WHERE id = $id"));
}

$skalaList = [];
if ($kriteriaAktifId) {
    $q = mysqli_query($koneksi, "SELECT * FROM skala_kriteria WHERE kriteria_id = $kriteriaAktifId ORDER BY urutan ASC, nilai ASC");
    while ($row = mysqli_fetch_assoc($q)) $skalaList[] = $row;
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

  <div class="page-header">
    <h4 class="fw-bold mb-0">Skala Penilaian Kriteria</h4>
    <p class="text-muted small mb-0">Kelola rentang / kategori nilai (skala 1-5) untuk masing-masing kriteria, digunakan saat input data penerima.</p>
  </div>

  <?php if (empty($kriteriaList)): ?>
    <div class="alert alert-warning">Silakan tambahkan kriteria terlebih dahulu di menu <a href="<?= BASE_URL ?>admin/kriteria.php">Kelola Kriteria</a>.</div>
  <?php else: ?>

  <ul class="nav nav-pills mb-3">
    <?php foreach ($kriteriaList as $k): ?>
      <li class="nav-item">
        <a class="nav-link <?= $k['id'] == $kriteriaAktifId ? 'active' : '' ?>" href="?kriteria_id=<?= $k['id'] ?>">
          <?= clean($k['kode']) ?> - <?= clean($k['nama_kriteria']) ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card card-stat p-3">
        <h6 class="fw-semibold mb-3"><?= $editData ? 'Edit Skala' : 'Tambah Skala' ?></h6>
        <form method="POST">
          <input type="hidden" name="action" value="simpan">
          <input type="hidden" name="id" value="<?= $editData['id'] ?? 0 ?>">
          <input type="hidden" name="kriteria_id" value="<?= $kriteriaAktifId ?>">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Label / Keterangan</label>
            <input type="text" name="label" class="form-control" placeholder="Contoh: Rp 500.001 - Rp 1.000.000" required
                   value="<?= clean($editData['label'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nilai Skala (1-5)</label>
            <input type="number" name="nilai" min="1" max="5" class="form-control" required
                   value="<?= clean($editData['nilai'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Urutan Tampil</label>
            <input type="number" name="urutan" class="form-control" value="<?= (int) ($editData['urutan'] ?? (count($skalaList) + 1)) ?>">
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
          <?php if ($editData): ?>
            <a href="<?= BASE_URL ?>admin/skala.php?kriteria_id=<?= $kriteriaAktifId ?>" class="btn btn-outline-secondary">Batal</a>
          <?php endif; ?>
        </form>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card card-stat p-3">
        <h6 class="fw-semibold mb-3">Daftar Skala</h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr><th>Label</th><th class="text-center">Nilai</th><th class="text-end">Aksi</th></tr>
            </thead>
            <tbody>
              <?php if (empty($skalaList)): ?>
                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada skala untuk kriteria ini.</td></tr>
              <?php endif; ?>
              <?php foreach ($skalaList as $s): ?>
                <tr>
                  <td><?= clean($s['label']) ?></td>
                  <td class="text-center"><span class="badge bg-primary"><?= $s['nilai'] ?></span></td>
                  <td class="text-end">
                    <a href="?kriteria_id=<?= $kriteriaAktifId ?>&edit=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <a href="?kriteria_id=<?= $kriteriaAktifId ?>&hapus=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete"><i class="bi bi-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
