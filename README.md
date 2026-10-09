# SPK Kelayakan Penerima Bantuan (Metode AHP & SAW)

Sistem Pendukung Keputusan (SPK) untuk menentukan kelayakan penerima bantuan
sosial menggunakan kombinasi metode **AHP (Analytical Hierarchy Process)**
untuk pembobotan kriteria dan **SAW (Simple Additive Weighting)** untuk
perangkingan alternatif (calon penerima).




---

## 1. Fitur

### Role Admin
- **CRUD Kriteria** — kelola kriteria penilaian (kode, nama, jenis cost/benefit).
- **CRUD Skala Penilaian** — kelola skala/rentang nilai (1-5) tiap kriteria (mengikuti Tabel Skala Penilaian).
- **CRUD Data Penerima** — tambah/ubah/hapus data calon penerima secara mandiri (form dengan dropdown skala).
- **Import Excel (.xlsx) / CSV** — import data penerima secara massal (parser XLSX native, tanpa library eksternal).
- **Perhitungan AHP** — input matriks perbandingan berpasangan (skala Saaty 1-9), lalu sistem menghitung otomatis:
  1. Matriks perbandingan berpasangan & jumlah kolom
  2. Matriks nilai kriteria (normalisasi)
  3. Jumlah baris & prioritas/bobot
  4. Eigen value tiap kriteria & eigen maksimum (λ maks)
  5. Consistency Index (CI)
  6. Random Index (RI) — tabel Saaty n=1..10
  7. Consistency Ratio (CR) & kesimpulan konsisten/tidak
- **Perhitungan SAW** — otomatis menggunakan bobot dari AHP:
  1. Matriks keputusan (data penerima)
  2. Nilai maksimum & minimum tiap kriteria
  3. Normalisasi matriks (Cost: Min/Xij, Benefit: Xij/Max)
  4. Perhitungan nilai preferensi Vi
  5. Perangkingan akhir
- **Halaman Hasil Ranking** & **Cetak Laporan** (print-friendly / simpan PDF via browser).

### Role Pimpinan
- **Validasi Data Penerima** — meninjau, menyetujui (valid), atau menolak data yang diinput admin. Data berstatus **ditolak** otomatis dikecualikan dari perhitungan SAW.
- **Halaman Hasil Ranking** (read-only) & **Cetak Laporan**.

---

## 2. Kebutuhan Server

- PHP 7.4+ atau PHP 8.x
- Ekstensi PHP: `mysqli`, `zip` (untuk import xlsx), `simplexml`, `json` (biasanya sudah aktif default)
- MySQL / MariaDB
- Web server: Apache (XAMPP/Laragon) atau Nginx

---

## 3. Instalasi

1. **Ekstrak** file zip ini ke folder web server, contoh (XAMPP):
   ```
   C:\xampp\htdocs\spk\
   ```

2. **Buat database** — buka phpMyAdmin, lalu import file:
   ```
   database/spk_ahp_saw.sql
   ```
   File ini akan otomatis:
   - Membuat database `spk_ahp_saw`
   - Membuat seluruh tabel
   - Mengisi kriteria, skala penilaian, matriks AHP contoh, dan **40 data calon penerima fiktif/dummy** (nama diganti `Penerima 01` dst.)

3. **Atur koneksi database** — salin file contoh lalu isi kredensial Anda:
   ```bash
   cp config/database.local.example.php config/database.local.php
   ```
   `config/database.local.php` sudah masuk `.gitignore` sehingga tidak ikut ter-commit.
   Alternatif: set environment variable `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`.

4. **Buat akun pengguna** — tidak ada akun/password bawaan. Jalankan lewat terminal:
   ```bash
   php database/create_user.php
   ```
   Buat minimal satu akun `admin` dan satu akun `pimpinan`. Password diketik saat prompt
   dan disimpan sebagai hash bcrypt.

5. **Akses aplikasi** melalui browser:
   ```
   http://localhost/spk/
   ```

---

## 4. Alur Penggunaan

1. **Admin** login → cek/atur menu **Kriteria** dan **Skala Penilaian** (sudah terisi data contoh contoh: Penghasilan, Tanggungan, Pekerjaan, Status Tempat Tinggal).
2. **Admin** → **Data Penerima**: tambah manual atau **Import Excel**. Data dummy 40 penerima sudah tersedia otomatis dari file SQL.
3. **Admin** → **Perhitungan AHP**: isi/ubah matriks perbandingan berpasangan (nilai contoh sudah terisi contoh), klik **Hitung AHP**. Sistem menyimpan bobot ke tiap kriteria bila **CR ≤ 0.1 (konsisten)**.
4. **Admin** → **Perhitungan SAW**: klik **Proses/Hitung Ulang SAW** untuk menghasilkan ranking berdasarkan bobot AHP.
5. **Pimpinan** login → menu **Validasi Data Penerima**: tinjau data, tandai **Valid** atau **Ditolak**.
6. **Admin/Pimpinan** → **Hasil Ranking** untuk melihat urutan akhir, atau **Cetak Laporan** untuk mencetak/menyimpan sebagai PDF.

> Data berstatus **ditolak** oleh pimpinan otomatis dikecualikan saat Admin menjalankan ulang Perhitungan SAW.

---

## 5. Format File Import Excel/CSV

Baris pertama = header (dilewati). Kolom mengikuti urutan kriteria yang aktif:

| Nama       | C1 (Penghasilan) | C2 (Tanggungan) | C3 (Pekerjaan) | C4 (Tempat Tinggal) |
|------------|-------------------|-------------------|------------------|------------------------|
| Budi       | 3                 | 4                 | 2                | 3                      |

Nilai kriteria berupa **angka skala 1–5** sesuai skala penilaian yang telah ditentukan di menu **Skala Penilaian**.

Format yang didukung: `.xlsx` (dibaca native tanpa library eksternal, menggunakan `ZipArchive` + `SimpleXML`) dan `.csv`.

---

## 6. Struktur Folder

```
spk/
├── admin/                  Halaman-halaman untuk role Admin
├── pimpinan/                Halaman-halaman untuk role Pimpinan
├── auth/                    Login & logout
├── config/                  Konfigurasi database & aplikasi
├── includes/                 Fungsi bantu (AHP, SAW, parser XLSX), header/footer/sidebar
├── assets/                   CSS & JS
├── database/                 File SQL & utilitas pembuat akun (CLI)
└── index.php                 Redirect awal sesuai status login
```

---

## 7. Catatan Perhitungan

- **AHP** menggunakan rumus standar:
  - Normalisasi matriks = nilai / jumlah kolom
  - Prioritas (bobot) = rata-rata baris matriks ternormalisasi
  - Eigen value kriteria-i = jumlah kolom-i × prioritas-i
  - λ maks (lambda maks) = Σ eigen value
  - CI = (λ maks − n) / (n − 1)
  - RI = tabel Random Index Saaty (n = 1..10)
  - CR = CI / RI — dinyatakan **konsisten** jika CR ≤ 0.1
- **SAW** menggunakan rumus standar:
  - Cost: rij = Min(Xj) / Xij
  - Benefit: rij = Xij / Max(Xj)
  - Vi = Σ (bobot kriteria-j × rij)
  - Ranking diurutkan dari Vi tertinggi ke terendah

---

## 8. Keamanan

- Password disimpan menggunakan `password_hash()` (bcrypt). Tidak ada kredensial yang di-hardcode di repo.
- **Jangan commit** data penerima asli (nama, NIK, alamat) maupun file import `.xlsx/.csv`; gunakan data dummy.
- Autentikasi berbasis session, setiap halaman admin/pimpinan divalidasi lewat `requireRole()`.
- Query menggunakan `mysqli_real_escape_string()` untuk mencegah SQL Injection dasar. Untuk production, disarankan migrasi ke prepared statement (PDO) serta menambahkan CSRF token pada form.
