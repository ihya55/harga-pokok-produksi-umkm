-- =========================================================
-- UPDATE FITUR RESET DATA PERCOBAAN DENGAN CHECKBOX MASTER
-- Jalankan jika sebelumnya menu reset belum pernah dibuat.
-- Jika menu sudah ada, SQL ini tetap aman dijalankan ulang.
-- =========================================================

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
SELECT @id_master_setup, 'RESET_DATA_PERCOBAAN', 'Reset Data Percobaan', 'menu', '/master_setup/reset_data', 'fa fa-trash', 37, 2, 1, 1, 'Reset data dummy transaksi, stok, jurnal, dan master pilihan', NOW(), NULL
WHERE @id_master_setup IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM tb_menu WHERE kode_menu = 'RESET_DATA_PERCOBAAN');

UPDATE tb_menu
SET nama_menu = 'Reset Data Percobaan',
    url = '/master_setup/reset_data',
    ikon = 'fa fa-trash',
    keterangan = 'Reset data dummy transaksi, stok, jurnal, dan master pilihan',
    status_aktif = 1
WHERE kode_menu = 'RESET_DATA_PERCOBAAN';

SET @id_menu_reset := (SELECT id_menu FROM tb_menu WHERE kode_menu = 'RESET_DATA_PERCOBAAN' LIMIT 1);

-- Berikan akses ke super_admin dan admin_entitas/admin utama.
INSERT INTO `tb_role_menu`
(`id_role`, `id_menu`, `boleh_lihat`, `boleh_tambah`, `boleh_ubah`, `boleh_hapus`, `boleh_posting`, `boleh_approve`, `boleh_cetak`, `boleh_export`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT r.id_role, @id_menu_reset, 1, 1, 1, 1, 1, 1, 1, 1, 1, NOW(), NULL
FROM tb_role r
WHERE @id_menu_reset IS NOT NULL
  AND (LOWER(r.nama_role) IN ('super_admin', 'admin_entitas') OR LOWER(COALESCE(r.kode_role, '')) IN ('super_admin', 'admin_entitas'))
  AND NOT EXISTS (
      SELECT 1 FROM tb_role_menu rm
      WHERE rm.id_role = r.id_role AND rm.id_menu = @id_menu_reset
  );

UPDATE tb_role_menu rm
JOIN tb_role r ON r.id_role = rm.id_role
SET rm.boleh_lihat = 1,
    rm.boleh_tambah = 1,
    rm.boleh_ubah = 1,
    rm.boleh_hapus = 1,
    rm.boleh_posting = 1,
    rm.boleh_approve = 1,
    rm.boleh_cetak = 1,
    rm.boleh_export = 1,
    rm.status_aktif = 1
WHERE rm.id_menu = @id_menu_reset
  AND (LOWER(r.nama_role) IN ('super_admin', 'admin_entitas') OR LOWER(COALESCE(r.kode_role, '')) IN ('super_admin', 'admin_entitas'));
