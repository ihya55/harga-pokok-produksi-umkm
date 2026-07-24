-- Update modul Konversi Produk untuk pilihan B: produk kiloan dan produk per biji/pcs.
-- Jalankan di phpMyAdmin setelah backup database.

CREATE TABLE IF NOT EXISTS `tb_konversi_produk` (
  `id_konversi_produk` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entitas` BIGINT UNSIGNED NOT NULL,
  `no_konversi_produk` VARCHAR(50) NOT NULL,
  `tanggal_konversi` DATE NOT NULL,
  `id_gudang` BIGINT UNSIGNED NOT NULL,
  `id_produk_sumber` BIGINT UNSIGNED NOT NULL,
  `qty_sumber` DECIMAL(18,3) NOT NULL DEFAULT 0.000,
  `hpp_sumber` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `nilai_sumber` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `id_produk_tujuan` BIGINT UNSIGNED NOT NULL,
  `qty_tujuan` DECIMAL(18,3) NOT NULL DEFAULT 0.000,
  `hpp_tujuan` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `nilai_tujuan` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `status_posting` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `catatan` TEXT NULL,
  `tanggal_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` BIGINT UNSIGNED NULL,
  `tanggal_posting` DATETIME NULL,
  `diposting_oleh` BIGINT UNSIGNED NULL,
  `tanggal_diubah` DATETIME NULL,
  `diubah_oleh` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id_konversi_produk`),
  KEY `idx_konversi_produk_entitas` (`id_entitas`),
  KEY `idx_konversi_produk_sumber` (`id_produk_sumber`),
  KEY `idx_konversi_produk_tujuan` (`id_produk_tujuan`),
  KEY `idx_konversi_produk_gudang` (`id_gudang`),
  KEY `idx_konversi_produk_status` (`status_posting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agar stok dan mutasi mendukung pecahan, misalnya 0,5 kg atau 0,01 kg jika nanti dibutuhkan.
ALTER TABLE `tb_mutasi_stok`
  MODIFY COLUMN `qty_masuk` DECIMAL(18,3) NOT NULL DEFAULT 0.000,
  MODIFY COLUMN `qty_keluar` DECIMAL(18,3) NOT NULL DEFAULT 0.000;

-- Tambahkan menu di Persediaan / Gudang.
INSERT INTO `tb_menu` (`id_menu_induk`, `kode_menu`, `nama_menu`, `jenis_menu`, `url`, `ikon`, `urutan`, `tingkat_menu`, `status_aktif`, `perlu_login`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT p.`id_menu`, 'KONVERSI_PRODUK', 'Konversi Produk', 'menu', '/persediaan/konversi-produk', 'fa fa-exchange-alt', 66, 2, 1, 1, 'Pecah stok produk kg menjadi pcs/biji dengan HPP tetap tersambung', NOW(), 1
FROM `tb_menu` p
WHERE p.`kode_menu` = 'PERSEDIAAN'
  AND NOT EXISTS (SELECT 1 FROM `tb_menu` m WHERE m.`kode_menu` = 'KONVERSI_PRODUK');

-- Beri akses menu ke role gudang, produksi, penjualan, admin entitas, dan super admin jika role tersedia.
INSERT INTO `tb_role_menu` (`id_role`, `id_menu`, `boleh_lihat`, `boleh_tambah`, `boleh_ubah`, `boleh_hapus`, `boleh_posting`, `boleh_approve`, `boleh_cetak`, `boleh_export`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT r.`id_role`, m.`id_menu`, 1, 1, 1, 0, 1, 0, 1, 1, 1, NOW(), 1
FROM `tb_role` r
JOIN `tb_menu` m ON m.`kode_menu` = 'KONVERSI_PRODUK'
WHERE LOWER(REPLACE(r.`nama_role`, ' ', '_')) IN ('super_admin','admin_entitas','admin_gudang','admin_produksi','admin_penjualan')
  AND NOT EXISTS (
    SELECT 1 FROM `tb_role_menu` rm
    WHERE rm.`id_role` = r.`id_role` AND rm.`id_menu` = m.`id_menu`
  );
