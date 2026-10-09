<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$pageTitle = 'Kelola Kriteria';

/* ==== PROSES CRUD ==== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'simpan') {
        $id = (int) ($_POST['id'] ?? 0);
        $kode = mysqli_real_escape_string($koneksi, trim($_POST['kode']));
        $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama_kriteria']));
        $jenis = $_POST['jenis'] === 'benefit' ? 'benefit' : 'cost';
        $urutan = (int) ($_POST['urutan'] ?? 0);

        if ($kode === '' || $nama === '') {
            setFlash('danger', 'Kode dan nama kriteria wajib diisi.');
        } else {
            if ($id > 0) {
                mysqli_query($koneksi, "UPDATE kriteria SET kode='$kode', nama_kriteria='$nama', jenis='$jenis', urutan=$urutan WHERE id=$id");
                setFlash('success', 'Kriteria berhasil diperbarui.');
            } else {
                mysqli_query($koneksi, "INSERT INTO kriteria (kode, nama_kriteria, jenis, urutan) VALUES ('$kode','$nama','$jenis',$urutan)");
                setFlash('success', 'Kriteria berhasil ditambahkan.');
            }
        }
        redirect(BASE_URL . 'admin/kriteria.php');
    }
}

if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM kriteria WHERE id = $id");
    setFlash('success', 'Kriteria berhasil dihapus beserta skala & data nilai terkait.');
    redirect(BASE_URL . 'admin/kriteria.php');
}

$editData = null;
if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];
    $editData = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM kriteria WHERE id = $id"));
}

$list = getKriteriaList($koneksi);

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

  <div class="page-header d-flex justify-content-between align-items-center">
    <div>
      <h4 class="fw-bold mb-0">Kelola Kriteria</h4>
      <p class="text-muted small mb-0">Tambah, ubah, atau hapus kriteria penilaian kelayakan penerima bantuan.</p>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card card-stat p-3">
        <h6 class="fw-semibold mb-3"><?= $editData ? 'Edit Kriteria' : 'Tambah Kriteria Baru' ?></h6>
        <form method="POST">
          <input type="hidden" name="action" value="simpan">
          <input type="hidden" name="id" value="<?= $editData['id'] ?? 0 ?>">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Kode Kriteria</label>
            <input type="text" name="kode" class="form-control" placeholder="Contoh: C1" required
                   value="<?= clean($editData['kode'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nama Kriteria</label>
            <input type="text" name="nama_kriteria" class="form-control" placeholder="Contoh: Penghasilan" required
                   value="<?= clean($editData['nama_kriteria'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Jenis / Atribut</label>
            <select name="jenis" class="form-select">
              <option value="cost" <?= (isset($editData['jenis']) && $editData['jenis'] === 'cost') ? 'selected' : '' ?>>Cost (semakin kecil semakin baik)</option>
              <option value="benefit" <?= (isset($editData['jenis']) && $editData['jenis'] === 'benefit') ? 'selected' : '' ?>>Benefit (semakin besar semakin baik)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Urutan Tampil</label>
            <input type="number" name="urutan" class="form-control" value="<?= (int) ($editData['urutan'] ?? (count($list) + 1)) ?>">
          </div>
          <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan</button>
          <?php if ($editData): ?>
            <a href="<?= BASE_URL ?>admin/kriteria.php" class="btn btn-outline-secondary">Batal</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card card-stat p-3">
        <h6 class="fw-semibold mb-3">Daftar Kriteria</h6>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Kode</th>
                <th>Nama Kriteria</th>
                <th>Jenis</th>
                <th>Bobot AHP</th>
                <th class="text-end">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($list)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data kriteria.</td></tr>
              <?php endif; ?>
              <?php foreach ($list as $k): ?>
                <tr>
                  <td><span class="badge bg-secondary"><?= clean($k['kode']) ?></span></td>
                  <td><?= clean($k['nama_kriteria']) ?></td>
                  <td>
                    <span class="badge <?= $k['jenis'] === 'cost' ? 'badge-cost' : 'badge-benefit' ?>">
                      <?= strtoupper($k['jenis']) ?>
                    </span>
                  </td>
                  <td><?= $k['bobot_ahp'] > 0 ? number_format($k['bobot_ahp'], 4) : '-' ?></td>
                  <td class="text-end">
                    <a href="?edit=<?= $k['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                    <a href="?hapus=<?= $k['id'] ?>" class="btn btn-sm btn-outline-danger btn-confirm-delete"><i class="bi bi-trash"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <p class="small text-muted mb-0">
          <i class="bi bi-info-circle"></i> Setelah mengubah kriteria, lakukan ulang <a href="<?= BASE_URL ?>admin/ahp.php">Perhitungan AHP</a> agar bobot tetap akurat.
        </p>
      </div>
    </div>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
