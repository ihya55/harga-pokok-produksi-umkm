-- =========================================================
-- FIX MENU NAVBAR: Reset Data Percobaan
-- Agar menu tampil di navbar/sidebar untuk user admin utama
-- Role yang diberi akses: super_admin dan admin_entitas
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

SET @id_master_setup := (SELECT id_menu FROM tb_menu WHERE kode_menu = 'MASTER_SETUP' LIMIT 1);

INSERT INTO `tb_menu`
(`id_menu_induk`, `kode_menu`, `nama_menu`, `jenis_menu`, `url`, `ikon`, `urutan`, `tingkat_menu`, `status_aktif`, `perlu_login`, `keterangan`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT @id_master_setup, 'RESET_DATA_PERCOBAAN', 'Reset Data Percobaan', 'menu', '/master_setup/reset_data', 'fa fa-trash', 38, 2, 1, 1, 'Reset data dummy transaksi, stok, mutasi, dan jurnal', NOW(), NULL
WHERE @id_master_setup IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM tb_menu WHERE kode_menu = 'RESET_DATA_PERCOBAAN');

UPDATE tb_menu
SET id_menu_induk = @id_master_setup,
    nama_menu = 'Reset Data Percobaan',
    jenis_menu = 'menu',
    url = '/master_setup/reset_data',
    urutan = 38,
    tingkat_menu = 2,
    status_aktif = 1
WHERE kode_menu = 'RESET_DATA_PERCOBAAN'
  AND @id_master_setup IS NOT NULL;

SET @id_menu_reset := (SELECT id_menu FROM tb_menu WHERE kode_menu = 'RESET_DATA_PERCOBAAN' LIMIT 1);

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

UPDATE tb_role_menu rm
JOIN tb_role r ON r.id_role = rm.id_role
JOIN tb_menu m ON m.id_menu = rm.id_menu
SET rm.boleh_lihat = 1,
    rm.boleh_tambah = 1,
    rm.boleh_ubah = 1,
    rm.boleh_hapus = 1,
    rm.boleh_posting = 1,
    rm.status_aktif = 1
WHERE m.kode_menu = 'RESET_DATA_PERCOBAAN'
  AND r.nama_role IN ('super_admin', 'admin_entitas');

COMMIT;
