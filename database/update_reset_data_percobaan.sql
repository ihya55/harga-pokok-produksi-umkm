-- =========================================================
-- UPDATE MENU + FITUR RESET DATA PERCOBAAN
-- Aplikasi Harga Pokok Produksi
-- Tujuan: menambah menu Master Setup > Reset Data Percobaan
-- dan menyediakan log reset.
-- =========================================================

START TRANSACTION;

CREATE TABLE IF NOT EXISTS `tb_reset_data_log` (
  `id_reset_data_log` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_pengguna` bigint UNSIGNED DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `tanggal_reset` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_baris_dihapus` int NOT NULL DEFAULT 0,
  `detail_tabel` longtext DEFAULT NULL,
  `alasan` text DEFAULT NULL,
  PRIMARY KEY (`id_reset_data_log`),
  KEY `idx_reset_data_log_pengguna` (`id_pengguna`),
  KEY `idx_reset_data_log_tanggal` (`tanggal_reset`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tambahkan menu baru di bawah Master Setup.
SET @id_master_setup := (SELECT id_menu FROM tb_menu WHERE kode_menu = 'MASTER_SETUP' LIMIT 1);

INSERT INTO `tb_menu`
(`id_menu_induk`, `kode_menu`, `nama_menu`, `jenis_menu`, `url`, `ikon`, `urutan`, `tingkat_menu`, `status_aktif`, `perlu_login`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT @id_master_setup, 'RESET_DATA_PERCOBAAN', 'Reset Data Percobaan', 'menu', '/master_setup/reset_data', 'fa fa-trash', 37, 2, 1, 1, 'Reset data dummy transaksi, stok, mutasi, dan jurnal', NOW(), NULL
WHERE @id_master_setup IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM tb_menu WHERE kode_menu = 'RESET_DATA_PERCOBAAN');

SET @id_menu_reset := (SELECT id_menu FROM tb_menu WHERE kode_menu = 'RESET_DATA_PERCOBAAN' LIMIT 1);

-- Berikan akses penuh hanya ke role super_admin dan admin_entitas/admin utama.
INSERT INTO `tb_role_menu`
(`id_role`, `id_menu`, `boleh_lihat`, `boleh_tambah`, `boleh_ubah`, `boleh_hapus`, `boleh_posting`, `boleh_approve`, `boleh_cetak`, `boleh_export`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT r.id_role, @id_menu_reset, 1, 1, 1, 1, 1, 1, 1, 1, 1, NOW(), NULL
FROM tb_role r
WHERE r.nama_role IN ('super_admin', 'admin_entitas')
  AND @id_menu_reset IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM tb_role_menu rm
      WHERE rm.id_role = r.id_role AND rm.id_menu = @id_menu_reset
  );

COMMIT;

-- =========================================================
-- OPSIONAL: SQL MANUAL UNTUK RESET SEMUA DATA DUMMY
-- Jalankan bagian di bawah ini hanya jika ingin membersihkan
-- data transaksi/stok/jurnal langsung dari phpMyAdmin.
-- Master data tidak dihapus.
-- =========================================================

/*
START TRANSACTION;
SET FOREIGN_KEY_CHECKS=0;

DELETE FROM tb_pembatalan_transaksi;
DELETE FROM tb_log_jurnal_sumber;
DELETE FROM tb_jurnal_detail;
DELETE FROM tb_jurnal;
DELETE FROM tb_kas_masuk;
DELETE FROM tb_kas_keluar;

DELETE FROM tb_saldo_stok;
DELETE FROM tb_mutasi_stok;
DELETE FROM tb_saldo_awal_stok_detail;
DELETE FROM tb_saldo_awal_stok;
DELETE FROM tb_stok_opname_detail;
DELETE FROM tb_stok_opname;

DELETE FROM tb_pembayaran_pembelian;
DELETE FROM tb_faktur_pembelian_detail;
DELETE FROM tb_faktur_pembelian;
DELETE FROM tb_penerimaan_pembelian_detail;
DELETE FROM tb_penerimaan_pembelian;
DELETE FROM tb_pesanan_pembelian_detail;
DELETE FROM tb_pesanan_pembelian;

DELETE FROM tb_pembayaran_penjualan;
DELETE FROM tb_faktur_penjualan_detail;
DELETE FROM tb_faktur_penjualan;
DELETE FROM tb_penyerahan_penjualan_detail;
DELETE FROM tb_penyerahan_penjualan;
DELETE FROM tb_pesanan_penjualan_detail;
DELETE FROM tb_pesanan_penjualan;

DELETE FROM tb_biaya_produksi_detail;
DELETE FROM tb_biaya_produksi;
DELETE FROM tb_hasil_produksi;
DELETE FROM tb_pengambilan_bahan_detail;
DELETE FROM tb_pengambilan_bahan;
DELETE FROM tb_perintah_produksi;

ALTER TABLE tb_pembatalan_transaksi AUTO_INCREMENT = 1;
ALTER TABLE tb_log_jurnal_sumber AUTO_INCREMENT = 1;
ALTER TABLE tb_jurnal_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_jurnal AUTO_INCREMENT = 1;
ALTER TABLE tb_kas_masuk AUTO_INCREMENT = 1;
ALTER TABLE tb_kas_keluar AUTO_INCREMENT = 1;
ALTER TABLE tb_saldo_stok AUTO_INCREMENT = 1;
ALTER TABLE tb_mutasi_stok AUTO_INCREMENT = 1;
ALTER TABLE tb_saldo_awal_stok_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_saldo_awal_stok AUTO_INCREMENT = 1;
ALTER TABLE tb_stok_opname_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_stok_opname AUTO_INCREMENT = 1;
ALTER TABLE tb_pembayaran_pembelian AUTO_INCREMENT = 1;
ALTER TABLE tb_faktur_pembelian_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_faktur_pembelian AUTO_INCREMENT = 1;
ALTER TABLE tb_penerimaan_pembelian_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_penerimaan_pembelian AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan_pembelian_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan_pembelian AUTO_INCREMENT = 1;
ALTER TABLE tb_pembayaran_penjualan AUTO_INCREMENT = 1;
ALTER TABLE tb_faktur_penjualan_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_faktur_penjualan AUTO_INCREMENT = 1;
ALTER TABLE tb_penyerahan_penjualan_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_penyerahan_penjualan AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan_penjualan_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan_penjualan AUTO_INCREMENT = 1;
ALTER TABLE tb_biaya_produksi_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_biaya_produksi AUTO_INCREMENT = 1;
ALTER TABLE tb_hasil_produksi AUTO_INCREMENT = 1;
ALTER TABLE tb_pengambilan_bahan_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_pengambilan_bahan AUTO_INCREMENT = 1;
ALTER TABLE tb_perintah_produksi AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS=1;
COMMIT;
*/
