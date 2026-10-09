<?php
/**
 * Kumpulan fungsi bantu: autentikasi, perhitungan AHP, perhitungan SAW, helper umum
 */

/* ==================== HELPER UMUM ==================== */

function clean($str)
{
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function setFlash($type, $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/* ==================== AUTENTIKASI ==================== */

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function currentRole()
{
    return $_SESSION['role'] ?? null;
}

function requireLogin()
{
    if (!isLoggedIn()) {
        redirect(BASE_URL . 'auth/login.php');
    }
}

function requireRole($role)
{
    requireLogin();
    if (currentRole() !== $role) {
        redirect(BASE_URL . 'index.php');
    }
}

/* ==================== RANDOM INDEX (RI) SAATY ==================== */

function getRandomIndex($n)
{
    $ri = [
        1 => 0, 2 => 0, 3 => 0.58, 4 => 0.9, 5 => 1.12,
        6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49,
    ];
    if ($n < 1) return 0;
    if ($n > 10) return 1.49;
    return $ri[$n];
}

/* ==================== PERHITUNGAN AHP ==================== */

/**
 * Mengambil daftar kriteria terurut
 */
function getKriteriaList($koneksi)
{
    $result = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY urutan ASC, id ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

/**
 * Mengambil matriks perbandingan berpasangan (n x n) dari tabel ahp_matriks.
 * Jika data belum ada, isi default 1 (diagonal) agar tidak error.
 */
function getMatriksPerbandingan($koneksi, $kriteriaList)
{
    $n = count($kriteriaList);
    $ids = array_column($kriteriaList, 'id');
    $matriks = [];

    // inisialisasi identitas
    foreach ($ids as $i => $idBaris) {
        foreach ($ids as $j => $idKolom) {
            $matriks[$i][$j] = ($i == $j) ? 1.0 : 1.0;
        }
    }

    $q = mysqli_query($koneksi, "SELECT * FROM ahp_matriks");
    $map = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $map[$row['kriteria_baris_id']][$row['kriteria_kolom_id']] = (float) $row['nilai'];
    }

    foreach ($ids as $i => $idBaris) {
        foreach ($ids as $j => $idKolom) {
            if (isset($map[$idBaris][$idKolom])) {
                $matriks[$i][$j] = (float) $map[$idBaris][$idKolom];
            }
        }
    }

    return $matriks;
}

/**
 * Simpan matriks perbandingan berpasangan (dari form segitiga atas) ke database.
 * $inputAtas = array [ "i-j" => nilai ] untuk i < j (baris i dibanding kolom j)
 */
function simpanMatriksPerbandingan($koneksi, $kriteriaList, $inputAtas)
{
    $n = count($kriteriaList);
    $ids = array_column($kriteriaList, 'id');

    mysqli_query($koneksi, "DELETE FROM ahp_matriks");

    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            if ($i == $j) {
                $nilai = 1.0;
            } elseif ($i < $j) {
                $key = $i . '-' . $j;
                $nilai = isset($inputAtas[$key]) ? (float) $inputAtas[$key] : 1.0;
            } else {
                // segitiga bawah = kebalikan (reciprocal) dari segitiga atas
                // presisi penuh (TIDAK dibulatkan) agar sama dengan pembagian di Excel
                $key = $j . '-' . $i;
                $atas = isset($inputAtas[$key]) ? (float) $inputAtas[$key] : 1.0;
                $nilai = $atas != 0 ? 1 / $atas : 1;
            }
            $baris_id = $ids[$i];
            $kolom_id = $ids[$j];
            mysqli_query($koneksi, "INSERT INTO ahp_matriks (kriteria_baris_id, kriteria_kolom_id, nilai)
                VALUES ($baris_id, $kolom_id, $nilai)");
        }
    }
}

/**
 * Proses lengkap perhitungan AHP.
 * Mengembalikan array berisi seluruh tahapan perhitungan agar bisa ditampilkan di view.
 */
function hitungAHP($koneksi)
{
    $kriteriaList = getKriteriaList($koneksi);
    $n = count($kriteriaList);

    if ($n < 2) {
        return null;
    }

    $matriks = getMatriksPerbandingan($koneksi, $kriteriaList);

    // 1. Jumlah tiap kolom pada matriks perbandingan berpasangan
    $jumlahKolom = array_fill(0, $n, 0.0);
    for ($j = 0; $j < $n; $j++) {
        for ($i = 0; $i < $n; $i++) {
            $jumlahKolom[$j] += $matriks[$i][$j];
        }
    }

    // 2. Matriks nilai kriteria (matriks ternormalisasi) = nilai / jumlah kolom
    $matriksNormalisasi = [];
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $matriksNormalisasi[$i][$j] = $jumlahKolom[$j] != 0 ? $matriks[$i][$j] / $jumlahKolom[$j] : 0;
        }
    }

    // 3. Jumlah tiap baris & prioritas (bobot) = rata-rata baris matriks ternormalisasi
    $jumlahBaris = array_fill(0, $n, 0.0);
    $prioritas = array_fill(0, $n, 0.0);
    for ($i = 0; $i < $n; $i++) {
        $total = 0;
        for ($j = 0; $j < $n; $j++) {
            $total += $matriksNormalisasi[$i][$j];
        }
        $jumlahBaris[$i] = $total;
        $prioritas[$i] = $total / $n;
    }

    // 4. Eigen value tiap kriteria = (baris matriks . vektor prioritas) / prioritas_i
    //    (samakan dengan Excel: F = (A*x)_i / x_i)
    $eigenValue = array_fill(0, $n, 0.0);
    for ($i = 0; $i < $n; $i++) {
        $ax = 0.0;
        for ($j = 0; $j < $n; $j++) {
            $ax += $matriks[$i][$j] * $prioritas[$j];
        }
        $eigenValue[$i] = $prioritas[$i] != 0 ? $ax / $prioritas[$i] : 0;
    }

    // 5. Eigen maksimum (lambda max) = rata-rata seluruh eigen value
    //    (samakan dengan Excel: SUM(F)/n, bukan SUM(F) saja)
    $lambdaMax = array_sum($eigenValue) / $n;

    // 6. Consistency Index (CI)
    $ci = ($lambdaMax - $n) / ($n - 1);

    // 7. Random Index (RI)
    $ri = getRandomIndex($n);

    // 8. Consistency Ratio (CR)
    $cr = $ri != 0 ? $ci / $ri : 0;

    $keterangan = $cr <= 0.1 ? 'KONSISTEN' : 'TIDAK KONSISTEN';

    return [
        'kriteria' => $kriteriaList,
        'n' => $n,
        'matriks' => $matriks,
        'jumlah_kolom' => $jumlahKolom,
        'matriks_normalisasi' => $matriksNormalisasi,
        'jumlah_baris' => $jumlahBaris,
        'prioritas' => $prioritas,
        'eigen_value' => $eigenValue,
        'lambda_max' => $lambdaMax,
        'ci' => $ci,
        'ri' => $ri,
        'cr' => $cr,
        'keterangan' => $keterangan,
    ];
}

/**
 * Simpan hasil akhir AHP (bobot per kriteria) ke tabel kriteria & riwayat ke ahp_hasil
 */
function simpanHasilAHP($koneksi, $hasil)
{
    foreach ($hasil['kriteria'] as $idx => $k) {
        $bobot = $hasil['prioritas'][$idx];
        mysqli_query($koneksi, "UPDATE kriteria SET bobot_ahp = $bobot WHERE id = " . (int) $k['id']);
    }

    $lambdaMax = $hasil['lambda_max'];
    $ci = $hasil['ci'];
    $ri = $hasil['ri'];
    $cr = $hasil['cr'];
    $ket = mysqli_real_escape_string($koneksi, $hasil['keterangan']);

    mysqli_query($koneksi, "INSERT INTO ahp_hasil (lambda_max, ci, ri, cr, keterangan)
        VALUES ($lambdaMax, $ci, $ri, $cr, '$ket')");
}

/* ==================== PERHITUNGAN SAW ==================== */

/**
 * Proses lengkap perhitungan SAW berdasarkan bobot AHP yang sudah tersimpan di tabel kriteria.
 * $hanyaValid : jika true, penerima berstatus 'ditolak' akan dikecualikan
 */
function hitungSAW($koneksi, $hanyaValid = true)
{
    $kriteriaList = getKriteriaList($koneksi);
    if (empty($kriteriaList)) return null;

    $where = $hanyaValid ? "WHERE status_validasi != 'ditolak'" : "";
    $q = mysqli_query($koneksi, "SELECT * FROM penerima $where ORDER BY id ASC");
    $penerimaList = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $penerimaList[] = $row;
    }
    if (empty($penerimaList)) return null;

    // Ambil matriks keputusan: baris = penerima, kolom = kriteria
    $matriksKeputusan = [];
    foreach ($penerimaList as $p) {
        $baris = [];
        foreach ($kriteriaList as $k) {
            $qq = mysqli_query($koneksi, "SELECT nilai FROM nilai_penerima WHERE penerima_id = {$p['id']} AND kriteria_id = {$k['id']}");
            $r = mysqli_fetch_assoc($qq);
            $baris[] = $r ? (float) $r['nilai'] : 0;
        }
        $matriksKeputusan[] = $baris;
    }

    $jumlahAlt = count($penerimaList);
    $jumlahKrit = count($kriteriaList);

    // Nilai maksimum & minimum tiap kolom kriteria
    $maxKolom = array_fill(0, $jumlahKrit, PHP_FLOAT_MIN);
    $minKolom = array_fill(0, $jumlahKrit, PHP_FLOAT_MAX);
    for ($j = 0; $j < $jumlahKrit; $j++) {
        for ($i = 0; $i < $jumlahAlt; $i++) {
            $val = $matriksKeputusan[$i][$j];
            if ($val > $maxKolom[$j]) $maxKolom[$j] = $val;
            if ($val < $minKolom[$j]) $minKolom[$j] = $val;
        }
    }

    // Normalisasi matriks keputusan (Cost: min/xij, Benefit: xij/max)
    $matriksNormalisasi = [];
    for ($i = 0; $i < $jumlahAlt; $i++) {
        for ($j = 0; $j < $jumlahKrit; $j++) {
            $val = $matriksKeputusan[$i][$j];
            $jenis = $kriteriaList[$j]['jenis'];
            if ($jenis === 'cost') {
                $matriksNormalisasi[$i][$j] = $val != 0 ? $minKolom[$j] / $val : 0;
            } else { // benefit
                $matriksNormalisasi[$i][$j] = $maxKolom[$j] != 0 ? $val / $maxKolom[$j] : 0;
            }
        }
    }

    // Bobot kriteria yang dipakai untuk SAW dibulatkan 2 desimal terlebih dahulu,
    // supaya hasil Vi sama persis dengan perhitungan manual di Excel
    // (di sheet 'saw' pada Excel, bobot AHP juga ditulis dalam 2 desimal, mis. 0.56 / 0.26 / 0.12 / 0.06).
    $bobotSAW = [];
    for ($j = 0; $j < $jumlahKrit; $j++) {
        $bobotSAW[$j] = round((float) $kriteriaList[$j]['bobot_ahp'], 2);
    }

    // Perhitungan Vi = sigma (bobot_j * normalisasi_ij)
    $hasilVi = [];
    for ($i = 0; $i < $jumlahAlt; $i++) {
        $total = 0;
        for ($j = 0; $j < $jumlahKrit; $j++) {
            $total += $bobotSAW[$j] * $matriksNormalisasi[$i][$j];
        }
        $hasilVi[] = $total;
    }

    // Ranking (descending berdasarkan Vi)
    $ranking = $hasilVi;
    arsort($ranking); // tetap simpan key asal (index array $penerimaList)
    $rankMap = [];
    $urutan = 1;
    foreach ($ranking as $idx => $vi) {
        $rankMap[$idx] = $urutan;
        $urutan++;
    }

    return [
        'kriteria' => $kriteriaList,
        'penerima' => $penerimaList,
        'matriks_keputusan' => $matriksKeputusan,
        'max_kolom' => $maxKolom,
        'min_kolom' => $minKolom,
        'matriks_normalisasi' => $matriksNormalisasi,
        'bobot_saw' => $bobotSAW,
        'hasil_vi' => $hasilVi,
        'rank_map' => $rankMap,
    ];
}

/**
 * Simpan hasil SAW ke tabel saw_hasil (menghapus hasil lama)
 */
function simpanHasilSAW($koneksi, $hasil)
{
    mysqli_query($koneksi, "TRUNCATE TABLE saw_hasil");
    foreach ($hasil['penerima'] as $idx => $p) {
        $vi = $hasil['hasil_vi'][$idx];
        $rank = $hasil['rank_map'][$idx];
        mysqli_query($koneksi, "INSERT INTO saw_hasil (penerima_id, nilai_v, ranking) VALUES ({$p['id']}, $vi, $rank)");
    }
}

/**
 * Ambil hasil ranking akhir (join dari saw_hasil + penerima), terurut berdasarkan ranking
 */
function getHasilRanking($koneksi)
{
    $q = mysqli_query($koneksi, "SELECT sh.*, p.nama, p.alamat, p.nik, p.status_validasi
        FROM saw_hasil sh JOIN penerima p ON sh.penerima_id = p.id
        ORDER BY sh.ranking ASC");
    $data = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $data[] = $row;
    }
    return $data;
}

/* ==================== IMPORT XLSX (NATIVE, TANPA LIBRARY) ==================== */

function excelColToIndex($col)
{
    $col = strtoupper($col);
    $len = strlen($col);
    $index = 0;
    for ($i = 0; $i < $len; $i++) {
        $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
    }
    return $index - 1; // 0-based
}

/**
 * Parser XLSX sederhana menggunakan ZipArchive + SimpleXML (native PHP, tanpa dependency).
 * Mengembalikan array baris (tiap baris adalah array kolom, urut A,B,C,...).
 */
function bacaXLSX($filePath)
{
    if (!class_exists('ZipArchive')) {
        throw new Exception('Ekstensi PHP ZipArchive tidak aktif di server. Aktifkan extension=zip pada php.ini.');
    }

    $zip = new ZipArchive();
    if ($zip->open($filePath) !== true) {
        throw new Exception('File XLSX tidak dapat dibuka / rusak.');
    }

    // 1. Baca shared strings (jika ada)
    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $xml = simplexml_load_string($sharedXml, 'SimpleXMLElement', LIBXML_NOCDATA);
        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                $sharedStrings[] = (string) $si->t;
            } else {
                // rich text (banyak <r><t>)
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string) $r->t;
                }
                $sharedStrings[] = $text;
            }
        }
    }

    // 2. Tentukan sheet pertama
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        // coba cari sheet lain
        for ($i = 1; $i <= 10; $i++) {
            $sheetXml = $zip->getFromName("xl/worksheets/sheet$i.xml");
            if ($sheetXml !== false) break;
        }
    }
    if ($sheetXml === false) {
        $zip->close();
        throw new Exception('Sheet tidak ditemukan dalam file XLSX.');
    }

    $xml = simplexml_load_string($sheetXml, 'SimpleXMLElement', LIBXML_NOCDATA);
    $rows = [];

    foreach ($xml->sheetData->row as $row) {
        $rowIndex = (int) $row['r'] - 1;
        $rowData = [];
        foreach ($row->c as $c) {
            $ref = (string) $c['r']; // contoh: B3
            preg_match('/([A-Z]+)(\d+)/', $ref, $m);
            $colLetter = $m[1] ?? 'A';
            $colIndex = excelColToIndex($colLetter);

            $type = (string) $c['t'];
            $value = isset($c->v) ? (string) $c->v : '';

            if ($type === 's') {
                $value = $sharedStrings[(int) $value] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = (string) $c->is->t;
            } elseif ($type === 'str') {
                // formula string, biarkan apa adanya
            }

            $rowData[$colIndex] = trim($value);
        }
        if (!empty($rowData)) {
            ksort($rowData);
            $rows[$rowIndex] = $rowData;
        }
    }

    $zip->close();
    ksort($rows);
    return $rows;
}

/**
 * Baca file CSV sederhana sebagai alternatif import
 */
function bacaCSV($filePath)
{
    $rows = [];
    if (($handle = fopen($filePath, 'r')) !== false) {
        $i = 0;
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $rows[$i] = $data;
            $i++;
        }
        fclose($handle);
    }
    return $rows;
}