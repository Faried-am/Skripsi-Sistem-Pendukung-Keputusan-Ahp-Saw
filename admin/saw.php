<?php
require_once __DIR__ . '/../config/config.php';
requireRole('admin');
$pageTitle = 'Perhitungan SAW';

$kriteriaList = getKriteriaList($koneksi);
$totalBobot = array_sum(array_column($kriteriaList, 'bobot_ahp'));

if ($totalBobot <= 0) {
    setFlash('warning', 'Lakukan Perhitungan AHP terlebih dahulu untuk mendapatkan bobot kriteria.');
    redirect(BASE_URL . 'admin/ahp.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'hitung_saw') {
    $hasil = hitungSAW($koneksi, true);
    if ($hasil) {
        simpanHasilSAW($koneksi, $hasil);
        setFlash('success', 'Perhitungan SAW berhasil dilakukan dan ranking telah diperbarui.');
    } else {
        setFlash('danger', 'Tidak ada data penerima yang dapat dihitung (pastikan data penerima sudah diinput dan tidak berstatus ditolak semua).');
    }
    redirect(BASE_URL . 'admin/saw.php');
}

$hasil = hitungSAW($koneksi, true);

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
      <h4 class="fw-bold mb-0">Perhitungan SAW (Simple Additive Weighting)</h4>
      <p class="text-muted small mb-0">Perankingan calon penerima bantuan berdasarkan bobot kriteria hasil AHP.</p>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="hitung_saw">
      <button type="submit" class="btn btn-primary"><i class="bi bi-graph-up-arrow me-1"></i> Proses / Hitung Ulang SAW</button>
    </form>
  </div>

  <!-- Bobot yang digunakan -->
  <div class="card card-stat p-3 mb-4">
    <h6 class="fw-semibold mb-3">Bobot Kriteria (dari AHP)</h6>
    <div class="table-responsive">
      <table class="table table-bordered table-sm">
        <thead class="table-light">
          <tr>
            <?php foreach ($kriteriaList as $k): ?><th><?= clean($k['kode']) ?></th><?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <tr>
            <?php foreach ($kriteriaList as $idx => $k): ?><td class="fw-semibold"><?= number_format($hasil ? $hasil['bobot_saw'][$idx] : round($k['bobot_ahp'], 2), 2) ?></td><?php endforeach; ?>
          </tr>
          <tr class="table-light">
            <?php foreach ($kriteriaList as $k): ?><td><span class="badge <?= $k['jenis'] === 'cost' ? 'badge-cost' : 'badge-benefit' ?>"><?= strtoupper($k['jenis']) ?></span></td><?php endforeach; ?>
          </tr>
        </tbody>
      </table>
    </div>
    <p class="small text-muted mb-0">Bobot dibulatkan 2 desimal sebelum dipakai menghitung nilai Vi, mengikuti perhitungan manual di Excel.</p>
  </div>

  <?php if (!$hasil): ?>
    <div class="alert alert-info">Belum ada data penerima yang bisa dihitung. Silakan tambahkan data penerima terlebih dahulu.</div>
  <?php else: ?>
    <?php $kList = $hasil['kriteria']; $n = count($kList); ?>

    <!-- Matriks Keputusan -->
    <div class="card card-stat p-3 mb-4">
      <h6 class="fw-semibold mb-3">1. Matriks Keputusan (Data Penerima)</h6>
      <div class="table-responsive">
        <table class="table table-bordered table-sm table-matrix">
          <thead class="table-light">
            <tr><th>Alternatif</th><th>Nama</th><?php foreach ($kList as $k): ?><th><?= clean($k['kode']) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($hasil['penerima'] as $i => $p): ?>
              <tr>
                <td>A<?= $i + 1 ?></td>
                <td class="text-start"><?= clean($p['nama']) ?></td>
                <?php for ($j = 0; $j < $n; $j++): ?>
                  <td><?= $hasil['matriks_keputusan'][$i][$j] ?></td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
            <tr class="table-light">
              <th colspan="2" class="text-start">Nilai Maksimum</th>
              <?php foreach ($hasil['max_kolom'] as $mx): ?><td class="fw-semibold"><?= $mx ?></td><?php endforeach; ?>
            </tr>
            <tr class="table-light">
              <th colspan="2" class="text-start">Nilai Minimum</th>
              <?php foreach ($hasil['min_kolom'] as $mn): ?><td class="fw-semibold"><?= $mn ?></td><?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="small text-muted mb-0">
        <strong>Cost</strong> (Penghasilan, Pekerjaan, Tempat Tinggal): normalisasi = Min / Xij &nbsp;|&nbsp;
        <strong>Benefit</strong> (Tanggungan): normalisasi = Xij / Max
      </p>
    </div>

    <!-- Matriks Normalisasi -->
    <div class="card card-stat p-3 mb-4">
      <h6 class="fw-semibold mb-3">2. Matriks Ternormalisasi (R)</h6>
      <div class="table-responsive">
        <table class="table table-bordered table-sm table-matrix">
          <thead class="table-light">
            <tr><th>Alternatif</th><th>Nama</th><?php foreach ($kList as $k): ?><th><?= clean($k['kode']) ?></th><?php endforeach; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($hasil['penerima'] as $i => $p): ?>
              <tr>
                <td>A<?= $i + 1 ?></td>
                <td class="text-start"><?= clean($p['nama']) ?></td>
                <?php for ($j = 0; $j < $n; $j++): ?>
                  <td><?= number_format($hasil['matriks_normalisasi'][$i][$j], 4) ?></td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Hasil Vi & Ranking -->
    <div class="card card-stat p-3 mb-4">
      <h6 class="fw-semibold mb-3">3. Nilai Preferensi (Vi) &amp; Perangkingan</h6>
      <p class="small text-muted">Vi = &sum; (bobot kriteria &times; nilai ternormalisasi)</p>
      <div class="table-responsive">
        <table class="table table-bordered table-sm">
          <thead class="table-light">
            <tr><th>Alternatif</th><th>Nama</th><th>Nilai Vi</th><th>Ranking</th></tr>
          </thead>
          <tbody>
            <?php
              $urut = $hasil['rank_map'];
              asort($urut); // urutkan berdasarkan ranking 1..n
              foreach ($urut as $idx => $rank):
                $p = $hasil['penerima'][$idx];
                $vi = $hasil['hasil_vi'][$idx];
                $rowClass = $rank == 1 ? 'rank-1' : ($rank == 2 ? 'rank-2' : ($rank == 3 ? 'rank-3' : ''));
            ?>
              <tr class="<?= $rowClass ?>">
                <td>A<?= $idx + 1 ?></td>
                <td class="text-start"><?= clean($p['nama']) ?></td>
                <td class="fw-semibold"><?= number_format($vi, 4) ?></td>
                <td><span class="badge bg-primary">#<?= $rank ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <a href="<?= BASE_URL ?>admin/hasil.php" class="btn btn-success"><i class="bi bi-trophy me-1"></i> Lihat Halaman Hasil Ranking</a>
    </div>
  <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
