-- =====================================================================
-- Skema Basis Data Sistem Informasi Operasional Cabang
-- PT Jaco Nusantara Mandiri (multi-cabang) - Normalisasi 3NF, 17 tabel
-- DBMS: MySQL 8.0.16+ (CHECK constraint aktif) | Engine: InnoDB
-- Penyusun: Rani Dwi Putri (Database Architect)
-- =====================================================================
CREATE DATABASE IF NOT EXISTS jaco_cabang
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jaco_cabang;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS LaporanHarian, KasTransaksi, Garansi, Pengantaran, POPengantaran,
  Pembayaran, DetailFaktur, Faktur, PenerimaanBarang, MutasiStok, Stok, Promo, Produk,
  Pelanggan, Absensi, Karyawan, Cabang;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------- Master & referensi ----------
CREATE TABLE Cabang (
  id_cabang    INT AUTO_INCREMENT PRIMARY KEY,
  nama_cabang  VARCHAR(100) NOT NULL,
  jenis        ENUM('showroom','kantor','gudang') NOT NULL,
  alamat       TEXT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Karyawan (
  id_karyawan  INT AUTO_INCREMENT PRIMARY KEY,
  id_cabang    INT NOT NULL,
  nama         VARCHAR(100) NOT NULL,
  peran ENUM(
      'sales_cabang',
      'admin_cabang',
      'gudang_cabang',
      'manajer_cabang',
      'admin_pusat'
  ) NOT NULL,
  status       VARCHAR(20) NOT NULL DEFAULT 'aktif',
  CONSTRAINT fk_karyawan_cabang FOREIGN KEY (id_cabang) REFERENCES Cabang(id_cabang)
) ENGINE=InnoDB;

CREATE TABLE Absensi (                      -- rancangan M5 (tidak dibangun pada MVP)
  id_absensi   INT AUTO_INCREMENT PRIMARY KEY,
  id_karyawan  INT NOT NULL,
  tanggal      DATE NOT NULL,
  jam_masuk    TIME NULL,
  jam_pulang   TIME NULL,
  status_hadir VARCHAR(20) NOT NULL,
  metode       VARCHAR(30) NULL,
  CONSTRAINT fk_absensi_karyawan FOREIGN KEY (id_karyawan) REFERENCES Karyawan(id_karyawan)
) ENGINE=InnoDB;

CREATE TABLE Pelanggan (
  id_pelanggan INT AUTO_INCREMENT PRIMARY KEY,
  nama         VARCHAR(100) NOT NULL,
  alamat       TEXT NOT NULL,
  no_hp        VARCHAR(20) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE Produk (                       -- referensi pusat (read-only di cabang)
  id_produk    INT AUTO_INCREMENT PRIMARY KEY,
  kode_produk  VARCHAR(30) NOT NULL,
  nama_produk  VARCHAR(150) NOT NULL,
  kategori     VARCHAR(50) NOT NULL,
  harga        DECIMAL(15,2) NOT NULL,
  masa_garansi INT NOT NULL DEFAULT 0,
  CONSTRAINT uq_produk_kode UNIQUE (kode_produk),
  CONSTRAINT ck_produk_harga CHECK (harga >= 0),
  CONSTRAINT ck_produk_garansi CHECK (masa_garansi >= 0)
) ENGINE=InnoDB;

CREATE TABLE Promo (                        -- referensi pusat (read-only di cabang)
  id_promo       INT AUTO_INCREMENT PRIMARY KEY,
  kode_promo     VARCHAR(30) NOT NULL,
  deskripsi      VARCHAR(255) NOT NULL,
  jenis_potongan ENUM('persen','nominal') NOT NULL,
  nilai          DECIMAL(15,2) NOT NULL,
  tgl_mulai      DATE NOT NULL,
  tgl_selesai    DATE NOT NULL,
  status         VARCHAR(20) NOT NULL DEFAULT 'aktif',
  CONSTRAINT uq_promo_kode UNIQUE (kode_promo),
  CONSTRAINT ck_promo_nilai CHECK (nilai > 0),
  CONSTRAINT ck_promo_persen CHECK (jenis_potongan <> 'persen' OR nilai <= 100),
  CONSTRAINT ck_promo_tanggal CHECK (tgl_selesai >= tgl_mulai)
) ENGINE=InnoDB;

-- ---------- Stok & gudang (M2) ----------
CREATE TABLE Stok (
  id_stok       INT AUTO_INCREMENT PRIMARY KEY,
  id_cabang     INT NOT NULL,
  id_produk     INT NOT NULL,
  jumlah        INT NOT NULL DEFAULT 0,
  batas_minimum INT NOT NULL DEFAULT 0,
  tgl_update    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT uq_stok_cabang_produk UNIQUE (id_cabang, id_produk),
  CONSTRAINT ck_stok_jumlah CHECK (jumlah >= 0),
  CONSTRAINT ck_stok_minimum CHECK (batas_minimum >= 0),
  CONSTRAINT fk_stok_cabang FOREIGN KEY (id_cabang) REFERENCES Cabang(id_cabang),
  CONSTRAINT fk_stok_produk FOREIGN KEY (id_produk) REFERENCES Produk(id_produk)
) ENGINE=InnoDB;

CREATE TABLE MutasiStok (
  id_mutasi         INT AUTO_INCREMENT PRIMARY KEY,
  id_stok           INT NOT NULL,
  id_karyawan       INT NOT NULL,
  jenis             ENUM('masuk','keluar') NOT NULL,
  jumlah            INT NOT NULL,
  tanggal           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  referensi_dokumen VARCHAR(50) NULL,
  CONSTRAINT ck_mutasi_jumlah CHECK (jumlah > 0),
  CONSTRAINT fk_mutasi_stok FOREIGN KEY (id_stok) REFERENCES Stok(id_stok),
  CONSTRAINT fk_mutasi_karyawan FOREIGN KEY (id_karyawan) REFERENCES Karyawan(id_karyawan)
) ENGINE=InnoDB;

CREATE TABLE PenerimaanBarang (
  id_penerimaan    INT AUTO_INCREMENT PRIMARY KEY,
  id_cabang        INT NOT NULL,
  id_produk        INT NOT NULL,
  id_karyawan      INT NOT NULL,
  jumlah           INT NOT NULL,
  tanggal          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  no_dokumen_pusat VARCHAR(30) NOT NULL,
  -- satu dokumen kiriman pusat tidak dapat dikonfirmasi dua kali (US-03)
  CONSTRAINT uq_penerimaan_dokumen UNIQUE (no_dokumen_pusat, id_cabang, id_produk),
  CONSTRAINT ck_penerimaan_jumlah CHECK (jumlah > 0),
  CONSTRAINT fk_penerimaan_cabang FOREIGN KEY (id_cabang) REFERENCES Cabang(id_cabang),
  CONSTRAINT fk_penerimaan_produk FOREIGN KEY (id_produk) REFERENCES Produk(id_produk),
  CONSTRAINT fk_penerimaan_karyawan FOREIGN KEY (id_karyawan) REFERENCES Karyawan(id_karyawan)
) ENGINE=InnoDB;

-- ---------- Penjualan & faktur (M1) ----------
CREATE TABLE Faktur (
  id_faktur    INT AUTO_INCREMENT PRIMARY KEY,
  no_faktur    VARCHAR(30) NOT NULL,
  id_cabang    INT NOT NULL,
  id_pelanggan INT NOT NULL,
  id_karyawan  INT NOT NULL,
  id_promo     INT NULL,
  tanggal      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  total        DECIMAL(15,2) NOT NULL DEFAULT 0,
  status       VARCHAR(20) NOT NULL DEFAULT 'belum_dibayar',
  CONSTRAINT uq_faktur_no UNIQUE (no_faktur),
  CONSTRAINT ck_faktur_total CHECK (total >= 0),
  CONSTRAINT fk_faktur_cabang FOREIGN KEY (id_cabang) REFERENCES Cabang(id_cabang),
  CONSTRAINT fk_faktur_pelanggan FOREIGN KEY (id_pelanggan) REFERENCES Pelanggan(id_pelanggan),
  CONSTRAINT fk_faktur_karyawan FOREIGN KEY (id_karyawan) REFERENCES Karyawan(id_karyawan),
  CONSTRAINT fk_faktur_promo FOREIGN KEY (id_promo) REFERENCES Promo(id_promo)
) ENGINE=InnoDB;

CREATE TABLE DetailFaktur (
  id_detail    INT AUTO_INCREMENT PRIMARY KEY,
  id_faktur    INT NOT NULL,
  id_produk    INT NOT NULL,
  jumlah       INT NOT NULL,
  harga_satuan DECIMAL(15,2) NOT NULL,      -- harga saat transaksi
  subtotal     DECIMAL(15,2) NOT NULL,      -- jumlah x harga_satuan
  CONSTRAINT ck_detail_jumlah CHECK (jumlah > 0),
  CONSTRAINT ck_detail_harga CHECK (harga_satuan >= 0),
  CONSTRAINT ck_detail_subtotal CHECK (subtotal = jumlah * harga_satuan),
  CONSTRAINT fk_detail_faktur FOREIGN KEY (id_faktur) REFERENCES Faktur(id_faktur),
  CONSTRAINT fk_detail_produk FOREIGN KEY (id_produk) REFERENCES Produk(id_produk)
) ENGINE=InnoDB;

CREATE TABLE Pembayaran (
  id_bayar               INT AUTO_INCREMENT PRIMARY KEY,
  id_faktur              INT NOT NULL,
  metode                 ENUM('tunai','debit','transfer','cicilan') NOT NULL,
  jumlah                 DECIMAL(15,2) NOT NULL,
  tanggal                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status_verifikasi_sales BOOLEAN NOT NULL DEFAULT FALSE,
  status_cek_pusat       BOOLEAN NOT NULL DEFAULT FALSE,   -- diisi pusat lewat A9
  tgl_cek_pusat          DATETIME NULL,
  CONSTRAINT ck_bayar_jumlah CHECK (jumlah > 0),
  CONSTRAINT fk_bayar_faktur FOREIGN KEY (id_faktur) REFERENCES Faktur(id_faktur)
) ENGINE=InnoDB;

CREATE TABLE POPengantaran (
  id_po     INT AUTO_INCREMENT PRIMARY KEY,
  id_faktur INT NOT NULL,
  tanggal   DATE NOT NULL,
  status    VARCHAR(20) NOT NULL DEFAULT 'baru',
  CONSTRAINT uq_po_faktur UNIQUE (id_faktur),
  CONSTRAINT fk_po_faktur FOREIGN KEY (id_faktur) REFERENCES Faktur(id_faktur)
) ENGINE=InnoDB;

CREATE TABLE Pengantaran (                  -- rancangan M3 (tidak dibangun pada MVP)
  id_pengantaran INT AUTO_INCREMENT PRIMARY KEY,
  id_po          INT NOT NULL,
  id_karyawan    INT NOT NULL,
  tgl_kirim      DATETIME NULL,
  tgl_terima     DATETIME NULL,
  status         VARCHAR(20) NOT NULL DEFAULT 'disiapkan',
  CONSTRAINT uq_pengantaran_po UNIQUE (id_po),
  CONSTRAINT fk_pengantaran_po FOREIGN KEY (id_po) REFERENCES POPengantaran(id_po),
  CONSTRAINT fk_pengantaran_karyawan FOREIGN KEY (id_karyawan) REFERENCES Karyawan(id_karyawan)
) ENGINE=InnoDB;

CREATE TABLE Garansi (                      -- rancangan M3 (tidak dibangun pada MVP)
  id_garansi       INT AUTO_INCREMENT PRIMARY KEY,
  id_faktur        INT NOT NULL,
  id_produk        INT NOT NULL,
  tgl_klaim        DATE NOT NULL,
  keluhan          TEXT NOT NULL,
  hasil_pengecekan VARCHAR(255) NULL,
  tindakan         VARCHAR(100) NULL,
  CONSTRAINT fk_garansi_faktur FOREIGN KEY (id_faktur) REFERENCES Faktur(id_faktur),
  CONSTRAINT fk_garansi_produk FOREIGN KEY (id_produk) REFERENCES Produk(id_produk)
) ENGINE=InnoDB;

-- ---------- Kas & laporan (M4) ----------
CREATE TABLE KasTransaksi (
  id_kas      INT AUTO_INCREMENT PRIMARY KEY,
  id_cabang   INT NOT NULL,
  id_karyawan INT NOT NULL,
  id_faktur   INT NULL,                     -- terisi jika kas masuk dari penjualan
  tanggal     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  jenis       ENUM('masuk','keluar','setoran') NOT NULL,
  jumlah      DECIMAL(15,2) NOT NULL,
  keterangan  VARCHAR(255) NULL,
  CONSTRAINT ck_kas_jumlah CHECK (jumlah > 0),
  CONSTRAINT fk_kas_cabang FOREIGN KEY (id_cabang) REFERENCES Cabang(id_cabang),
  CONSTRAINT fk_kas_karyawan FOREIGN KEY (id_karyawan) REFERENCES Karyawan(id_karyawan),
  CONSTRAINT fk_kas_faktur FOREIGN KEY (id_faktur) REFERENCES Faktur(id_faktur)
) ENGINE=InnoDB;

CREATE TABLE LaporanHarian (
  id_laporan    INT AUTO_INCREMENT PRIMARY KEY,
  id_cabang     INT NOT NULL,
  id_karyawan   INT NOT NULL,
  tanggal       DATE NOT NULL,
  jenis_laporan ENUM('penjualan','stok','kas_masuk','kas_keluar') NOT NULL,
  status_kirim  VARCHAR(20) NOT NULL DEFAULT 'belum_kirim',
  tgl_kirim     DATETIME NULL,
  -- satu laporan per jenis per cabang per hari
  CONSTRAINT uq_laporan_harian UNIQUE (id_cabang, tanggal, jenis_laporan),
  CONSTRAINT fk_laporan_cabang FOREIGN KEY (id_cabang) REFERENCES Cabang(id_cabang),
  CONSTRAINT fk_laporan_karyawan FOREIGN KEY (id_karyawan) REFERENCES Karyawan(id_karyawan)
) ENGINE=InnoDB;

-- Indeks pendukung pencarian per cabang/tanggal (MySQL otomatis membuat indeks untuk FK)
CREATE INDEX idx_faktur_cabang_tanggal ON Faktur (id_cabang, tanggal);
CREATE INDEX idx_kas_cabang_tanggal    ON KasTransaksi (id_cabang, tanggal);

-- ---------- Indeks tambahan (3.1) ----------
CREATE INDEX idx_mutasi_stok_tanggal  ON MutasiStok (id_stok, tanggal);
CREATE INDEX idx_pembayaran_cek_pusat ON Pembayaran (status_cek_pusat, tanggal);

-- ---------- View laporan harian dan rekap mingguan (3.1, US-07, CR-01) ----------
CREATE OR REPLACE VIEW v_laporan_penjualan AS
SELECT f.id_cabang,
       c.nama_cabang,
       CAST(f.tanggal AS DATE) AS tanggal,
       COUNT(*)                AS jumlah_faktur,
       SUM(f.total)            AS total_penjualan,
       SUM(CASE WHEN f.status = 'lunas' THEN 1 ELSE 0 END) AS faktur_lunas
FROM Faktur f
JOIN Cabang c ON c.id_cabang = f.id_cabang
GROUP BY f.id_cabang, c.nama_cabang, CAST(f.tanggal AS DATE);

CREATE OR REPLACE VIEW v_laporan_stok AS
SELECT s.id_cabang,
       c.nama_cabang,
       p.kode_produk,
       p.nama_produk,
       s.jumlah,
       s.batas_minimum,
       CASE WHEN s.jumlah = 0 THEN 'habis'
            WHEN s.jumlah <= s.batas_minimum THEN 'minimum'
            ELSE 'aman' END AS status_stok,
       s.tgl_update
FROM Stok s
JOIN Cabang c ON c.id_cabang = s.id_cabang
JOIN Produk p ON p.id_produk = s.id_produk;

CREATE OR REPLACE VIEW v_laporan_kas_masuk AS
SELECT k.id_cabang,
       c.nama_cabang,
       CAST(k.tanggal AS DATE) AS tanggal,
       COUNT(*)                AS jumlah_transaksi,
       SUM(k.jumlah)           AS total_kas_masuk
FROM KasTransaksi k
JOIN Cabang c ON c.id_cabang = k.id_cabang
WHERE k.jenis = 'masuk'
GROUP BY k.id_cabang, c.nama_cabang, CAST(k.tanggal AS DATE);

CREATE OR REPLACE VIEW v_laporan_kas_keluar AS
SELECT k.id_cabang,
       c.nama_cabang,
       CAST(k.tanggal AS DATE) AS tanggal,
       SUM(CASE WHEN k.jenis = 'keluar'  THEN k.jumlah ELSE 0 END) AS kas_keluar_operasional,
       SUM(CASE WHEN k.jenis = 'setoran' THEN k.jumlah ELSE 0 END) AS setoran_ke_pusat,
       SUM(k.jumlah) AS total_kas_keluar
FROM KasTransaksi k
JOIN Cabang c ON c.id_cabang = k.id_cabang
WHERE k.jenis IN ('keluar', 'setoran')
GROUP BY k.id_cabang, c.nama_cabang, CAST(k.tanggal AS DATE);

CREATE OR REPLACE VIEW v_rekap_mingguan AS
SELECT x.id_cabang,
       YEARWEEK(x.tgl, 1) AS tahun_minggu,
       MIN(CAST(x.tgl AS DATE) - INTERVAL WEEKDAY(x.tgl) DAY) AS awal_minggu,
       SUM(x.faktur)      AS jumlah_faktur,
       SUM(x.penjualan)   AS total_penjualan,
       SUM(x.kas_masuk)   AS total_kas_masuk,
       SUM(x.kas_keluar)  AS total_kas_keluar
FROM (
  SELECT id_cabang, tanggal AS tgl, 1 AS faktur, total AS penjualan,
         0 AS kas_masuk, 0 AS kas_keluar
  FROM Faktur
  UNION ALL
  SELECT id_cabang, tanggal, 0, 0,
         CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END,
         CASE WHEN jenis IN ('keluar', 'setoran') THEN jumlah ELSE 0 END
  FROM KasTransaksi
) x
GROUP BY x.id_cabang, YEARWEEK(x.tgl, 1);
