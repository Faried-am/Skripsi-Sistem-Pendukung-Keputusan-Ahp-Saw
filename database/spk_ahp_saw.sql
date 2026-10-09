-- =====================================================================
-- SPK Kelayakan Penerima Bantuan - Metode AHP & SAW
-- Database Schema + Data Awal
-- =====================================================================

CREATE DATABASE IF NOT EXISTS spk_ahp_saw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE spk_ahp_saw;

-- ---------------------------------------------------------------------
-- Tabel Users (Admin & Pimpinan)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  role ENUM('admin','pimpinan') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel Kriteria
-- ---------------------------------------------------------------------
CREATE TABLE kriteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10) UNIQUE NOT NULL,
  nama_kriteria VARCHAR(100) NOT NULL,
  jenis ENUM('cost','benefit') NOT NULL,
  bobot_ahp DECIMAL(10,6) DEFAULT 0,
  urutan INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel Skala Penilaian per Kriteria
-- ---------------------------------------------------------------------
CREATE TABLE skala_kriteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kriteria_id INT NOT NULL,
  label VARCHAR(150) NOT NULL,
  nilai INT NOT NULL,
  urutan INT DEFAULT 0,
  FOREIGN KEY (kriteria_id) REFERENCES kriteria(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel Data Calon Penerima Bantuan
-- ---------------------------------------------------------------------
CREATE TABLE penerima (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  alamat VARCHAR(255) NULL,
  nik VARCHAR(30) NULL,
  status_validasi ENUM('pending','valid','ditolak') DEFAULT 'pending',
  input_by INT NULL,
  validated_by INT NULL,
  validated_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (input_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (validated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel Nilai Penerima per Kriteria (matriks keputusan)
-- ---------------------------------------------------------------------
CREATE TABLE nilai_penerima (
  id INT AUTO_INCREMENT PRIMARY KEY,
  penerima_id INT NOT NULL,
  kriteria_id INT NOT NULL,
  nilai INT NOT NULL,
  FOREIGN KEY (penerima_id) REFERENCES penerima(id) ON DELETE CASCADE,
  FOREIGN KEY (kriteria_id) REFERENCES kriteria(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_penerima_kriteria (penerima_id, kriteria_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel Matriks Perbandingan Berpasangan AHP
-- ---------------------------------------------------------------------
CREATE TABLE ahp_matriks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kriteria_baris_id INT NOT NULL,
  kriteria_kolom_id INT NOT NULL,
  nilai DECIMAL(12,8) NOT NULL,
  FOREIGN KEY (kriteria_baris_id) REFERENCES kriteria(id) ON DELETE CASCADE,
  FOREIGN KEY (kriteria_kolom_id) REFERENCES kriteria(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel Riwayat Hasil Perhitungan AHP (CI, RI, CR)
-- ---------------------------------------------------------------------
CREATE TABLE ahp_hasil (
  id INT AUTO_INCREMENT PRIMARY KEY,
  lambda_max DECIMAL(12,6),
  ci DECIMAL(12,6),
  ri DECIMAL(12,6),
  cr DECIMAL(12,6),
  keterangan VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Tabel Hasil Perhitungan SAW (Ranking Akhir)
-- ---------------------------------------------------------------------
CREATE TABLE saw_hasil (
  id INT AUTO_INCREMENT PRIMARY KEY,
  penerima_id INT NOT NULL,
  nilai_v DECIMAL(12,6),
  ranking INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (penerima_id) REFERENCES penerima(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- DATA AWAL (SEED)
-- =====================================================================

-- Akun TIDAK disertakan di file ini (demi keamanan repo publik).
-- Buat akun lewat CLI setelah import:
--   php database/create_user.php

-- Kriteria contoh
INSERT INTO kriteria (id, kode, nama_kriteria, jenis, urutan) VALUES
(1, 'C1', 'Penghasilan', 'cost', 1),
(2, 'C2', 'Tanggungan', 'benefit', 2),
(3, 'C3', 'Pekerjaan', 'cost', 3),
(4, 'C4', 'Status Tempat Tinggal', 'cost', 4);

-- Skala Penilaian tiap kriteria
INSERT INTO skala_kriteria (kriteria_id, label, nilai, urutan) VALUES
(1, '< Rp 500.000', 1, 1),
(1, 'Rp 500.001 - Rp 1.000.000', 2, 2),
(1, 'Rp 1.000.001 - Rp 2.000.000', 3, 3),
(1, 'Rp 2.000.001 - Rp 3.000.000', 4, 4),
(1, '> Rp 3.000.000', 5, 5),

(2, '1 orang', 1, 1),
(2, '2 orang', 2, 2),
(2, '3 orang', 3, 3),
(2, '4 - 5 orang', 4, 4),
(2, '> 5 orang', 5, 5),

(3, 'Tidak Bekerja', 1, 1),
(3, 'Buruh / Petani', 2, 2),
(3, 'Pedagang / Wiraswasta', 3, 3),
(3, 'Karyawan Swasta', 4, 4),
(3, 'PNS / TNI / POLRI', 5, 5),

(4, 'Menumpang / Tidak Punya Rumah', 1, 1),
(4, 'Sewa / Kontrak', 2, 2),
(4, 'Rumah Tidak Layak Huni', 3, 3),
(4, 'Rumah Layak Huni Belum Milik Sendiri', 4, 4),
(4, 'Rumah Milik Sendiri Layak Huni', 5, 5);

-- Matriks perbandingan berpasangan default (contoh)
-- C1 vs C2=3, C1 vs C3=5, C1 vs C4=7, C2 vs C3=3, C2 vs C4=5, C3 vs C4=3
INSERT INTO ahp_matriks (kriteria_baris_id, kriteria_kolom_id, nilai) VALUES
(1,1,1), (1,2,3), (1,3,5), (1,4,7),
(2,1,0.33333333), (2,2,1), (2,3,3), (2,4,5),
(3,1,0.2), (3,2,0.33333333), (3,3,1), (3,4,3),
(4,1,0.14285714), (4,2,0.2), (4,3,0.33333333), (4,4,1);

-- Data Calon Penerima Bantuan (DATA DUMMY / FIKTIF - nama diganti agar tidak memuat data pribadi)
INSERT INTO penerima (nama, status_validasi, input_by) VALUES
('Penerima 01', 'pending', NULL),
('Penerima 02', 'pending', NULL),
('Penerima 03', 'pending', NULL),
('Penerima 04', 'pending', NULL),
('Penerima 05', 'pending', NULL),
('Penerima 06', 'pending', NULL),
('Penerima 07', 'pending', NULL),
('Penerima 08', 'pending', NULL),
('Penerima 09', 'pending', NULL),
('Penerima 10', 'pending', NULL),
('Penerima 11', 'pending', NULL),
('Penerima 12', 'pending', NULL),
('Penerima 13', 'pending', NULL),
('Penerima 14', 'pending', NULL),
('Penerima 15', 'pending', NULL),
('Penerima 16', 'pending', NULL),
('Penerima 17', 'pending', NULL),
('Penerima 18', 'pending', NULL),
('Penerima 19', 'pending', NULL),
('Penerima 20', 'pending', NULL),
('Penerima 21', 'pending', NULL),
('Penerima 22', 'pending', NULL),
('Penerima 23', 'pending', NULL),
('Penerima 24', 'pending', NULL),
('Penerima 25', 'pending', NULL),
('Penerima 26', 'pending', NULL),
('Penerima 27', 'pending', NULL),
('Penerima 28', 'pending', NULL),
('Penerima 29', 'pending', NULL),
('Penerima 30', 'pending', NULL),
('Penerima 31', 'pending', NULL),
('Penerima 32', 'pending', NULL),
('Penerima 33', 'pending', NULL),
('Penerima 34', 'pending', NULL),
('Penerima 35', 'pending', NULL),
('Penerima 36', 'pending', NULL),
('Penerima 37', 'pending', NULL),
('Penerima 38', 'pending', NULL),
('Penerima 39', 'pending', NULL),
('Penerima 40', 'pending', NULL);

-- Nilai tiap penerima untuk masing-masing kriteria
-- kriteria_id: 1=Penghasilan, 2=Tanggungan, 3=Pekerjaan, 4=Tempat Tinggal
INSERT INTO nilai_penerima (penerima_id, kriteria_id, nilai) VALUES
(1, 1, 3), (1, 2, 4), (1, 3, 4), (1, 4, 4),
(2, 1, 4), (2, 2, 2), (2, 3, 2), (2, 4, 2),
(3, 1, 3), (3, 2, 3), (3, 3, 3), (3, 4, 3),
(4, 1, 2), (4, 2, 5), (4, 3, 5), (4, 4, 5),
(5, 1, 4), (5, 2, 1), (5, 3, 1), (5, 4, 1),
(6, 1, 3), (6, 2, 4), (6, 3, 4), (6, 4, 4),
(7, 1, 2), (7, 2, 4), (7, 3, 1), (7, 4, 5),
(8, 1, 3), (8, 2, 5), (8, 3, 3), (8, 4, 4),
(9, 1, 4), (9, 2, 2), (9, 3, 2), (9, 4, 3),
(10, 1, 2), (10, 2, 4), (10, 3, 3), (10, 4, 5),
(11, 1, 2), (11, 2, 4), (11, 3, 2), (11, 4, 2),
(12, 1, 3), (12, 2, 4), (12, 3, 1), (12, 4, 1),
(13, 1, 4), (13, 2, 1), (13, 3, 5), (13, 4, 3),
(14, 1, 4), (14, 2, 5), (14, 3, 4), (14, 4, 4),
(15, 1, 4), (15, 2, 1), (15, 3, 2), (15, 4, 3),
(16, 1, 2), (16, 2, 4), (16, 3, 3), (16, 4, 1),
(17, 1, 3), (17, 2, 4), (17, 3, 1), (17, 4, 3),
(18, 1, 2), (18, 2, 5), (18, 3, 3), (18, 4, 3),
(19, 1, 2), (19, 2, 3), (19, 3, 1), (19, 4, 2),
(20, 1, 2), (20, 2, 2), (20, 3, 3), (20, 4, 2),
(21, 1, 1), (21, 2, 1), (21, 3, 2), (21, 4, 2),
(22, 1, 3), (22, 2, 3), (22, 3, 4), (22, 4, 1),
(23, 1, 1), (23, 2, 2), (23, 3, 3), (23, 4, 2),
(24, 1, 2), (24, 2, 5), (24, 3, 2), (24, 4, 4),
(25, 1, 5), (25, 2, 5), (25, 3, 2), (25, 4, 1),
(26, 1, 1), (26, 2, 4), (26, 3, 2), (26, 4, 2),
(27, 1, 5), (27, 2, 5), (27, 3, 2), (27, 4, 1),
(28, 1, 5), (28, 2, 5), (28, 3, 2), (28, 4, 1),
(29, 1, 4), (29, 2, 5), (29, 3, 2), (29, 4, 3),
(30, 1, 5), (30, 2, 3), (30, 3, 4), (30, 4, 4),
(31, 1, 5), (31, 2, 3), (31, 3, 4), (31, 4, 2),
(32, 1, 1), (32, 2, 5), (32, 3, 4), (32, 4, 2),
(33, 1, 4), (33, 2, 4), (33, 3, 3), (33, 4, 2),
(34, 1, 2), (34, 2, 4), (34, 3, 5), (34, 4, 4),
(35, 1, 1), (35, 2, 2), (35, 3, 3), (35, 4, 3),
(36, 1, 4), (36, 2, 4), (36, 3, 4), (36, 4, 4),
(37, 1, 3), (37, 2, 2), (37, 3, 1), (37, 4, 5),
(38, 1, 5), (38, 2, 5), (38, 3, 1), (38, 4, 4),
(39, 1, 3), (39, 2, 2), (39, 3, 3), (39, 4, 4),
(40, 1, 1), (40, 2, 4), (40, 3, 4), (40, 4, 5);

-- =====================================================================
-- SELESAI
-- Setelah import, login sebagai admin lalu buka menu "Perhitungan AHP"
-- untuk menghitung bobot, kemudian "Perhitungan SAW" untuk menghasilkan
-- ranking akhir.
-- =====================================================================
