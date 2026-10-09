<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$pageTitle = $id ? 'Edit Penerima' : 'Tambah Penerima';

$kriteriaList = getKriteriaList($koneksi);

if (empty($kriteriaList)) {
    setFlash('warning', 'Tambahkan kriteria terlebih dahulu sebelum input data penerima.');
    redirect(BASE_URL . 'admin/kriteria.php');
}

// ambil skala per kriteria
$skalaByKriteria = [];
foreach ($kriteriaList as $k) {
    $q = mysqli_query($koneksi, "SELECT * FROM skala_kriteria WHERE kriteria_id = {$k['id']} ORDER BY urutan ASC, nilai ASC");
    $skalaByKriteria[$k['id']] = [];
    while ($row = mysqli_fetch_assoc($q)) $skalaByKriteria[$k['id']][] = $row;
}

$dataPenerima = ['nama' => '', 'alamat' => '', 'nik' => '', 'status_validasi' => 'pending'];
$nilaiTersimpan = [];

if ($id) {
    $r = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM penerima WHERE id = $id"));
    if (!$r) {
        setFlash('danger', 'Data penerima tidak ditemukan.');
        redirect(BASE_URL . 'admin/penerima.php');
    }
    $dataPenerima = $r;
    $q = mysqli_query($koneksi, "SELECT * FROM nilai_penerima WHERE penerima_id = $id");
    while ($row = mysqli_fetch_assoc($q)) $nilaiTersimpan[$row['kriteria_id']] = $row['nilai'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = mysqli_real_escape_string($koneksi, trim($_POST['nama']));
    // Alamat and NIK removed from the form — preserve existing values on edit, empty on create
    $alamat = $dataPenerima['alamat'] ?? '';
    $nik = $dataPenerima['nik'] ?? '';
    $nilaiPost = $_POST['nilai'] ?? [];

    if ($nama === '') {
        setFlash('danger', 'Nama wajib diisi.');
        redirect(BASE_URL . 'admin/penerima_form.php' . ($id ? "?id=$id" : ''));
    }

    if ($id) {
        mysqli_query($koneksi, "UPDATE penerima SET nama='$nama', alamat='$alamat', nik='$nik' WHERE id=$id");
        $penerimaId = $id;
    } else {
        $userId = (int) $_SESSION['user_id'];
        mysqli_query($koneksi, "INSERT INTO penerima (nama, alamat, nik, status_validasi, input_by) VALUES ('$nama','$alamat','$nik','pending',$userId)");
        $penerimaId = mysqli_insert_id($koneksi);
    }

    foreach ($kriteriaList as $k) {
        $nilai = (int) ($nilaiPost[$k['id']] ?? 0);
        if ($nilai < 1) $nilai = 1;
        $exists = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM nilai_penerima WHERE penerima_id=$penerimaId AND kriteria_id={$k['id']}"));
        if ($exists) {
            mysqli_query($koneksi, "UPDATE nilai_penerima SET nilai=$nilai WHERE id={$exists['id']}");
        } else {
            mysqli_query($koneksi, "INSERT INTO nilai_penerima (penerima_id, kriteria_id, nilai) VALUES ($penerimaId, {$k['id']}, $nilai)");
        }
    }

    setFlash('success', 'Data penerima berhasil disimpan.');
    redirect(BASE_URL . 'admin/penerima.php');
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar_admin.php';
?>
<main class="app-content">
  <div class="page-header">
    <h4 class="fw-bold mb-0"><?= $pageTitle ?></h4>
    <p class="text-muted small mb-0">Input data calon penerima bantuan beserta nilai tiap kriteria berdasarkan skala penilaian.</p>
  </div>

  <div class="card card-stat p-4">
    <form method="POST">
      <div class="row g-3 mb-2">
        <div class="col-md-12">
          <label class="form-label small fw-semibold">Nama Lengkap</label>
          <input type="text" name="nama" class="form-control" required value="<?= clean($dataPenerima['nama']) ?>">
        </div>
      </div>

      <hr class="my-4">
      <h6 class="fw-semibold mb-3">Nilai Kriteria</h6>
      <div class="row g-3">
        <?php foreach ($kriteriaList as $k): ?>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">
              <?= clean($k['nama_kriteria']) ?>
              <span class="badge <?= $k['jenis'] === 'cost' ? 'badge-cost' : 'badge-benefit' ?>"><?= strtoupper($k['jenis']) ?></span>
            </label>
            <?php if (!empty($skalaByKriteria[$k['id']])): ?>
              <select name="nilai[<?= $k['id'] ?>]" class="form-select" required>
                <option value="">-- Pilih --</option>
                <?php foreach ($skalaByKriteria[$k['id']] as $s): ?>
                  <option value="<?= $s['nilai'] ?>" <?= (isset($nilaiTersimpan[$k['id']]) && $nilaiTersimpan[$k['id']] == $s['nilai']) ? 'selected' : '' ?>>
                    <?= clean($s['label']) ?> (Skala <?= $s['nilai'] ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <input type="number" min="1" max="5" name="nilai[<?= $k['id'] ?>]" class="form-control" required
                     value="<?= $nilaiTersimpan[$k['id']] ?? '' ?>">
              <small class="text-muted">Belum ada skala untuk kriteria ini, isi manual (1-5).</small>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan Data</button>
        <a href="<?= BASE_URL ?>admin/penerima.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
