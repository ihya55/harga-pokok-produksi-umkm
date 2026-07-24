-- Update Pesanan Online: metode pembayaran Transfer/QRIS dan pengaturan QRIS nominal
-- Jalankan sekali di phpMyAdmin sebelum menggunakan fitur baru.

CREATE TABLE IF NOT EXISTS `tb_pengaturan_pembayaran_online` (
  `id_pengaturan_pembayaran_online` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entitas` BIGINT UNSIGNED NOT NULL,
  `nama_bank` VARCHAR(100) DEFAULT NULL,
  `nomor_rekening` VARCHAR(100) DEFAULT NULL,
  `atas_nama_rekening` VARCHAR(150) DEFAULT NULL,
  `qris_nama_merchant` VARCHAR(150) DEFAULT NULL,
  `qris_static_payload` TEXT DEFAULT NULL,
  `catatan_pembayaran` TEXT DEFAULT NULL,
  `status_aktif` TINYINT(1) NOT NULL DEFAULT 1,
  `tanggal_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_diubah` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_pengaturan_pembayaran_online`),
  UNIQUE KEY `uk_pengaturan_pembayaran_online_entitas` (`id_entitas`),
  KEY `idx_pengaturan_pembayaran_online_entitas` (`id_entitas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `tb_pesanan_penjualan`
  ADD COLUMN IF NOT EXISTS `metode_pembayaran_online` VARCHAR(30) NOT NULL DEFAULT 'cod' AFTER `sumber_pesanan`,
  ADD COLUMN IF NOT EXISTS `nominal_pembayaran_online` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `total`,
  ADD COLUMN IF NOT EXISTS `status_pembayaran_online` VARCHAR(30) NOT NULL DEFAULT 'belum_bayar' AFTER `nominal_pembayaran_online`;

-- Data awal. Silakan ubah nama bank, rekening, nama merchant, dan qris_static_payload sesuai QRIS usaha.
INSERT INTO `tb_pengaturan_pembayaran_online` (
  `id_entitas`,
  `nama_bank`,
  `nomor_rekening`,
  `atas_nama_rekening`,
  `qris_nama_merchant`,
  `qris_static_payload`,
  `catatan_pembayaran`,
  `status_aktif`
)
SELECT
  e.`id_entitas`,
  'Isi Nama Bank',
  'Isi Nomor Rekening',
  e.`nama_entitas`,
  e.`nama_entitas`,
  '',
  'Transfer/QRIS harus sesuai nominal total pesanan.',
  1
FROM `tb_entitas` e
WHERE NOT EXISTS (
  SELECT 1
  FROM `tb_pengaturan_pembayaran_online` p
  WHERE p.`id_entitas` = e.`id_entitas`
);
