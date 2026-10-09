<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$pageTitle = 'Perhitungan AHP';

$kriteriaList = getKriteriaList($koneksi);

if (count($kriteriaList) < 2) {
    setFlash('warning', 'Minimal 2 kriteria diperlukan untuk melakukan perhitungan AHP.');
    redirect(BASE_URL . 'admin/kriteria.php');
}

// Simpan matriks perbandingan berpasangan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hitung_ahp') {
    $inputAtas = $_POST['matriks'] ?? [];
    simpanMatriksPerbandingan($koneksi, $kriteriaList, $inputAtas);
    $hasil = hitungAHP($koneksi);
    if ($hasil) {
        simpanHasilAHP($koneksi, $hasil);
        setFlash('success', 'Perhitungan AHP berhasil dilakukan dan bobot kriteria telah diperbarui.');
    }
    redirect(BASE_URL . 'admin/ahp.php');
}

$matriksSekarang = getMatriksPerbandingan($koneksi, $kriteriaList);
$hasil = hitungAHP($koneksi);

// Skala Saaty untuk dropdown
$skalaSaaty = [
    9 => '9 - Mutlak lebih penting',
    8 => '8',
    7 => '7 - Sangat lebih penting',
    6 => '6',
    5 => '5 - Lebih penting',
    4 => '4',
    3 => '3 - Sedikit lebih penting',
    2 => '2',
    1 => '1 - Sama penting',
    1 / 2 => '1/2',
    1 / 3 => '1/3 - Sedikit kurang penting',
    1 / 4 => '1/4',
    1 / 5 => '1/5 - Kurang penting',
    1 / 6 => '1/6',
    1 / 7 => '1/7 - Sangat kurang penting',
    1 / 8 => '1/8',
    1 / 9 => '1/9 - Mutlak kurang penting',
];

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
    <h4 class="fw-bold mb-0">Perhitungan AHP (Analytical Hierarchy Process)</h4>
    <p class="text-muted small mb-0">Menentukan bobot prioritas tiap kriteria melalui matriks perbandingan berpasangan.</p>
  </div>

  <!-- STEP 1: INPUT MATRIKS PERBANDINGAN BERPASANGAN -->
  <div class="card card-stat p-3 mb-4">
    <div class="section-header">
      <h6 class="fw-semibold mb-0">1. Matriks Perbandingan Berpasangan</h6>
    </div>
    <p class="small text-muted mb-3">Bandingkan tingkat kepentingan antar kriteria menggunakan skala Saaty (1-9). Nilai segitiga bawah dihitung otomatis (kebalikan/reciprocal).</p>
    <form method="POST">
      <input type="hidden" name="action" value="hitung_ahp">
      <div class="table-responsive">
        <table class="table table-bordered table-matrix">
          <thead class="table-light">
            <tr>
              <th>Kriteria</th>
              <?php foreach ($kriteriaList as $k): ?>
                <th><?= clean($k['kode']) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($kriteriaList as $i => $ki): ?>
              <tr>
                <th class="text-start"><?= clean($ki['kode']) ?> - <?= clean($ki['nama_kriteria']) ?></th>
                <?php foreach ($kriteriaList as $j => $kj): ?>
                  <td>
                    <?php if ($i == $j): ?>
                      1
                    <?php elseif ($i < $j): ?>
                      <select name="matriks[<?= $i ?>-<?= $j ?>]" class="form-select form-select-sm">
                        <?php foreach ($skalaSaaty as $val => $label): ?>
                          <?php
                            $selected = abs($matriksSekarang[$i][$j] - $val) < 0.0001 ? 'selected' : '';
                          ?>
                          <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                        <?php endforeach; ?>
                      </select>
                    <?php else: ?>
                      <span class="text-muted" title="Nilai kebalikan (reciprocal) otomatis">
                        <?= number_format($matriksSekarang[$i][$j], 4) ?>
                      </span>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <button type="submit" class="btn btn-primary"><i class="bi bi-calculator me-1"></i> Hitung AHP</button>
    </form>
  </div>

  <?php if ($hasil): ?>
    <?php $n = $hasil['n']; ?>

    <!-- Matriks perbandingan (nilai lengkap termasuk reciprocal) -->
    <div class="card card-stat p-3 mb-4">
      <div class="section-header">
        <h6 class="fw-semibold mb-0">Matriks Perbandingan Berpasangan (Lengkap)</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered table-matrix table-sm">
          <thead class="table-light">
            <tr><th>Kriteria</th><?php foreach ($hasil['kriteria'] as $k): ?><th><?= clean($k['kode']) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < $n; $i++): ?>
              <tr>
                <th class="text-start"><?= clean($hasil['kriteria'][$i]['kode']) ?></th>
                <?php for ($j = 0; $j < $n; $j++): ?>
                  <td><?= number_format($hasil['matriks'][$i][$j], 4) ?></td>
                <?php endfor; ?>
              </tr>
            <?php endfor; ?>
            <tr class="table-light">
              <th class="text-start">Jumlah Kolom</th>
              <?php foreach ($hasil['jumlah_kolom'] as $jk): ?>
                <td class="fw-semibold"><?= number_format($jk, 4) ?></td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Matriks nilai kriteria (normalisasi) + prioritas -->
    <div class="card card-stat p-3 mb-4">
      <div class="section-header">
        <h6 class="fw-semibold mb-0">2. Matriks Nilai Kriteria (Normalisasi) &amp; Prioritas / Bobot</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered table-matrix table-sm">
          <thead class="table-light">
            <tr>
              <th>Kriteria</th>
              <?php foreach ($hasil['kriteria'] as $k): ?><th><?= clean($k['kode']) ?></th><?php endforeach; ?>
              <th>Jumlah</th>
              <th>Prioritas (Bobot)</th>
              <th>Eigen Value</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($i = 0; $i < $n; $i++): ?>
              <tr>
                <th class="text-start"><?= clean($hasil['kriteria'][$i]['kode']) ?></th>
                <?php for ($j = 0; $j < $n; $j++): ?>
                  <td><?= number_format($hasil['matriks_normalisasi'][$i][$j], 4) ?></td>
                <?php endfor; ?>
                <td class="fw-semibold"><?= number_format($hasil['jumlah_baris'][$i], 4) ?></td>
                <td class="fw-semibold text-primary"><?= number_format($hasil['prioritas'][$i], 4) ?></td>
                <td><?= number_format($hasil['eigen_value'][$i], 4) ?></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Consistency check -->
    <div class="row g-3 mb-4">
      <div class="col-lg-7">
        <div class="card card-stat p-3 h-100">
          <div class="section-header">
            <h6 class="fw-semibold mb-0">3. Uji Konsistensi</h6>
          </div>
          <table class="table table-sm">
            <tr><td>Jumlah Kriteria (n)</td><td class="text-end fw-semibold"><?= $n ?></td></tr>
            <tr><td>Eigen Maksimum (&lambda; maks)</td><td class="text-end fw-semibold"><?= number_format($hasil['lambda_max'], 4) ?></td></tr>
            <tr><td>Consistency Index (CI = (&lambda;maks - n) / (n - 1))</td><td class="text-end fw-semibold"><?= number_format($hasil['ci'], 4) ?></td></tr>
            <tr><td>Random Index (RI)</td><td class="text-end fw-semibold"><?= number_format($hasil['ri'], 2) ?></td></tr>
            <tr><td>Consistency Ratio (CR = CI / RI)</td><td class="text-end fw-semibold"><?= number_format($hasil['cr'], 4) ?></td></tr>
            <tr>
              <td>Kesimpulan</td>
              <td class="text-end">
                <span class="badge fs-6 <?= $hasil['keterangan'] === 'KONSISTEN' ? 'bg-success' : 'bg-danger' ?>">
                  <?= $hasil['keterangan'] ?>
                </span>
              </td>
            </tr>
          </table>
          <p class="small text-muted mb-0">
            Jika CR &le; 0.1 (10%) maka matriks perbandingan dinyatakan <strong>konsisten</strong> dan bobot dapat digunakan.
            Jika CR &gt; 0.1 maka penilaian perbandingan perlu diperbaiki.
          </p>
        </div>
      </div>
      <div class="col-lg-5">
        <div class="card card-stat p-3 h-100">
          <div class="section-header">
            <h6 class="fw-semibold mb-0">Tabel Random Index (RI) Saaty</h6>
          </div>
          <table class="table table-sm table-bordered text-center">
            <thead class="table-light"><tr><th>N</th><?php for ($i = 1; $i <= 10; $i++): ?><th><?= $i ?></th><?php endfor; ?></tr></thead>
            <tbody>
              <tr>
                <th>RI</th>
                <?php for ($i = 1; $i <= 10; $i++): ?>
                  <td class="<?= $i == $n ? 'table-primary fw-bold' : '' ?>"><?= number_format(getRandomIndex($i), 2) ?></td>
                <?php endfor; ?>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Bobot akhir -->
    <div class="card card-stat p-3 mb-4">
      <div class="section-header">
        <h6 class="fw-semibold mb-0">Bobot Akhir Kriteria (Tersimpan)</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-bordered">
          <thead class="table-light"><tr><th>Kode</th><th>Nama Kriteria</th><th>Jenis</th><th>Bobot</th></tr></thead>
          <tbody>
            <?php foreach ($hasil['kriteria'] as $idx => $k): ?>
              <tr>
                <td><?= clean($k['kode']) ?></td>
                <td><?= clean($k['nama_kriteria']) ?></td>
                <td><span class="badge <?= $k['jenis'] === 'cost' ? 'badge-cost' : 'badge-benefit' ?>"><?= strtoupper($k['jenis']) ?></span></td>
                <td class="fw-bold text-primary"><?= number_format($hasil['prioritas'][$idx], 4) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($hasil['keterangan'] === 'KONSISTEN'): ?>
        <a href="<?= BASE_URL ?>admin/saw.php" class="btn btn-success"><i class="bi bi-arrow-right-circle me-1"></i> Lanjut ke Perhitungan SAW</a>
      <?php else: ?>
        <div class="alert alert-warning mb-0">Matriks belum konsisten. Perbaiki nilai perbandingan berpasangan sebelum melanjutkan ke perhitungan SAW.</div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>