-- Update PPN default: tabel pajak, menu, dan kolom ppn_persen faktur penjualan.
-- Jalankan file ini pada database aplikasi harga_pokok_produksi.

CREATE TABLE IF NOT EXISTS `tb_pajak` (
  `id_pajak` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entitas` bigint UNSIGNED NOT NULL,
  `kode_pajak` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_pajak` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `persentase` decimal(5,2) NOT NULL DEFAULT '0.00',
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `status_aktif` tinyint(1) NOT NULL DEFAULT '1',
  `keterangan` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_dibuat` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` bigint UNSIGNED DEFAULT NULL,
  `tanggal_diubah` datetime DEFAULT NULL,
  `diubah_oleh` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_pajak`),
  KEY `idx_tb_pajak_entitas` (`id_entitas`),
  KEY `idx_tb_pajak_kode` (`kode_pajak`),
  KEY `idx_tb_pajak_default` (`id_entitas`,`kode_pajak`,`is_default`,`status_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tb_pajak` (`id_entitas`, `kode_pajak`, `nama_pajak`, `persentase`, `is_default`, `status_aktif`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT e.`id_entitas`, 'PPN', 'Pajak Pertambahan Nilai', 11.00, 1, 1, 'PPN default untuk faktur pembelian dan penjualan', NOW(), 1
FROM `tb_entitas` e
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_pajak` p WHERE p.`id_entitas` = e.`id_entitas` AND p.`kode_pajak` = 'PPN'
);

SET @db_name := DATABASE();
SET @sql_add_ppn_persen := (
    SELECT IF(COUNT(*) = 0,
        'ALTER TABLE `tb_faktur_penjualan` ADD COLUMN `ppn_persen` decimal(5,2) NOT NULL DEFAULT ''0.00'' AFTER `ppn`',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'tb_faktur_penjualan'
      AND COLUMN_NAME = 'ppn_persen'
);
PREPARE stmt_add_ppn_persen FROM @sql_add_ppn_persen;
EXECUTE stmt_add_ppn_persen;
DEALLOCATE PREPARE stmt_add_ppn_persen;

INSERT INTO `tb_menu` (`id_menu_induk`, `kode_menu`, `nama_menu`, `jenis_menu`, `url`, `ikon`, `urutan`, `tingkat_menu`, `status_aktif`, `perlu_login`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT 4, 'PAJAK_PPN', 'Pajak / PPN', 'menu', '/master_setup/pajak', 'bi bi-percent', 34, 2, 1, 1, 'Pengaturan persentase pajak default transaksi', NOW(), 1
WHERE NOT EXISTS (SELECT 1 FROM `tb_menu` WHERE `kode_menu` = 'PAJAK_PPN');

SET @id_menu_pajak := (SELECT `id_menu` FROM `tb_menu` WHERE `kode_menu` = 'PAJAK_PPN' LIMIT 1);

INSERT INTO `tb_role_menu` (`id_role`, `id_menu`, `boleh_lihat`, `boleh_tambah`, `boleh_ubah`, `boleh_hapus`, `boleh_posting`, `boleh_approve`, `boleh_cetak`, `boleh_export`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT DISTINCT rm.`id_role`, @id_menu_pajak, 1, 1, 1, rm.`boleh_hapus`, 0, 0, 1, 1, 1, NOW(), 1
FROM `tb_role_menu` rm
JOIN `tb_menu` m ON m.`id_menu` = rm.`id_menu`
WHERE m.`kode_menu` = 'MASTER_SETUP'
  AND rm.`status_aktif` = 1
  AND rm.`boleh_lihat` = 1
  AND NOT EXISTS (
      SELECT 1 FROM `tb_role_menu` x
      WHERE x.`id_role` = rm.`id_role`
        AND x.`id_menu` = @id_menu_pajak
  );
