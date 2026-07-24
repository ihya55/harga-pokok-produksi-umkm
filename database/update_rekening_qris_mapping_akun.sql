-- Update rekening bank dan QRIS pada akun COA untuk Pesanan Online
-- Jalankan dari phpMyAdmin sebelum membuka tab Mapping Akun > Rekening / QRIS.

ALTER TABLE `tb_coa`
  ADD COLUMN IF NOT EXISTS `nama_bank` VARCHAR(100) NULL AFTER `status_aktif`,
  ADD COLUMN IF NOT EXISTS `nomor_rekening` VARCHAR(80) NULL AFTER `nama_bank`,
  ADD COLUMN IF NOT EXISTS `atas_nama_rekening` VARCHAR(150) NULL AFTER `nomor_rekening`,
  ADD COLUMN IF NOT EXISTS `qris_nama_merchant` VARCHAR(150) NULL AFTER `atas_nama_rekening`,
  ADD COLUMN IF NOT EXISTS `qris_static_payload` TEXT NULL AFTER `qris_nama_merchant`,
  ADD COLUMN IF NOT EXISTS `is_akun_qris` TINYINT(1) NOT NULL DEFAULT 0 AFTER `qris_static_payload`;

CREATE TABLE IF NOT EXISTS `tb_pengaturan_pembayaran_online` (
  `id_pengaturan_pembayaran_online` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entitas` BIGINT UNSIGNED NOT NULL,
  `nama_bank` VARCHAR(100) NULL,
  `nomor_rekening` VARCHAR(80) NULL,
  `atas_nama_rekening` VARCHAR(150) NULL,
  `qris_nama_merchant` VARCHAR(150) NULL,
  `qris_static_payload` TEXT NULL,
  `catatan_pembayaran` TEXT NULL,
  `tanggal_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` BIGINT UNSIGNED NULL,
  `tanggal_diubah` DATETIME NULL,
  `diubah_oleh` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id_pengaturan_pembayaran_online`),
  UNIQUE KEY `uk_pengaturan_pembayaran_online_entitas` (`id_entitas`),
  KEY `idx_pengaturan_pembayaran_online_entitas` (`id_entitas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tandai akun bank pertama sebagai akun QRIS utama bila belum ada yang dipilih.
UPDATE `tb_coa` c
JOIN (
  SELECT MIN(`id_coa`) AS id_coa
  FROM `tb_coa`
  WHERE `status_aktif` = 1
    AND `boleh_transaksi` = 1
    AND (`nama_coa` LIKE '%Bank%' OR `kode_coa` LIKE '112%')
  GROUP BY `id_entitas`
) x ON x.id_coa = c.id_coa
SET c.`is_akun_qris` = 1
WHERE NOT EXISTS (
  SELECT 1 FROM `tb_coa` q
  WHERE q.`id_entitas` = c.`id_entitas`
    AND q.`is_akun_qris` = 1
);
