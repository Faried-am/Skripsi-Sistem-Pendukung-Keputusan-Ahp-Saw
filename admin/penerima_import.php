<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$pageTitle = 'Import Excel';

$kriteriaList = getKriteriaList($koneksi);
$hasilImport = null;

if (empty($kriteriaList)) {
    setFlash('warning', 'Tambahkan kriteria terlebih dahulu sebelum melakukan import data.');
    redirect(BASE_URL . 'admin/kriteria.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_import'])) {
    $file = $_FILES['file_import'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', 'Terjadi kesalahan saat mengunggah file.');
        redirect(BASE_URL . 'admin/penerima_import.php');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $tmpPath = $file['tmp_name'];

    try {
        if ($ext === 'xlsx') {
            $rows = bacaXLSX($tmpPath);
        } elseif ($ext === 'csv') {
            $rows = bacaCSV($tmpPath);
        } else {
            throw new Exception('Format file tidak didukung. Gunakan .xlsx atau .csv');
        }

        $rows = array_values($rows); // reindex 0..n
        if (empty($rows)) throw new Exception('File kosong atau format tidak dikenali.');

        // Baris pertama dianggap header, dilewati
        $baris = array_slice($rows, 1);

        $userId = (int) $_SESSION['user_id'];
        $sukses = 0;
        $gagal = 0;

        foreach ($baris as $r) {
            // kolom 0 = Nama, kolom 1..n = nilai tiap kriteria (sesuai urutan kriteria aktif)
            $nama = trim($r[0] ?? '');
            if ($nama === '') continue;

            $nilaiPerKriteria = [];
            $valid = true;
            foreach ($kriteriaList as $idx => $k) {
                $val = isset($r[$idx + 1]) ? (int) $r[$idx + 1] : 0;
                if ($val < 1 || $val > 5) $valid = false;
                $nilaiPerKriteria[$k['id']] = max(1, min(5, $val ?: 1));
            }

            $namaEsc = mysqli_real_escape_string($koneksi, $nama);
            mysqli_query($koneksi, "INSERT INTO penerima (nama, status_validasi, input_by) VALUES ('$namaEsc','pending',$userId)");
            $penerimaId = mysqli_insert_id($koneksi);

            foreach ($nilaiPerKriteria as $kriteriaId => $nilai) {
                mysqli_query($koneksi, "INSERT INTO nilai_penerima (penerima_id, kriteria_id, nilai) VALUES ($penerimaId, $kriteriaId, $nilai)");
            }

            $valid ? $sukses++ : $gagal++;
        }

        setFlash('success', "Import selesai. $sukses data berhasil diimpor" . ($gagal ? ", $gagal data memiliki nilai di luar rentang 1-5 (otomatis disesuaikan)." : '.'));
        redirect(BASE_URL . 'admin/penerima.php');

    } catch (Exception $e) {
        setFlash('danger', 'Gagal import: ' . $e->getMessage());
        redirect(BASE_URL . 'admin/penerima_import.php');
    }
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
    <h4 class="fw-bold mb-0">Import Data Penerima dari Excel</h4>
    <p class="text-muted small mb-0">Unggah file Excel (.xlsx) atau CSV berisi data calon penerima bantuan secara massal.</p>
  </div>

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card card-stat p-4">
        <h6 class="fw-semibold mb-3">Unggah File</h6>
        <form method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Pilih File (.xlsx / .csv)</label>
            <input type="file" name="file_import" class="form-control" accept=".xlsx,.csv" required>
          </div>
          <button type="submit" class="btn btn-success"><i class="bi bi-upload me-1"></i> Import Sekarang</button>
        </form>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card card-stat p-4">
        <h6 class="fw-semibold mb-3"><i class="bi bi-info-circle me-1"></i> Format File</h6>
        <p class="small mb-2">Baris pertama adalah <strong>header</strong> (dilewati saat import). Kolom mengikuti urutan berikut:</p>
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr>
              <th>Nama</th>
              <?php foreach ($kriteriaList as $k): ?>
                <th><?= clean($k['kode']) ?> - <?= clean($k['nama_kriteria']) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Contoh Nama</td>
              <?php foreach ($kriteriaList as $k): ?>
                <td class="text-center">1-5</td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
        <p class="small text-muted mb-0">Nilai tiap kriteria berupa angka skala <strong>1 sampai 5</strong> sesuai skala penilaian yang telah ditentukan pada menu Skala Penilaian.</p>
      </div>
    </div>
  </div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
