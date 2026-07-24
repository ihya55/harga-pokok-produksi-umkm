-- Update alur Pesanan Online: refund, bukti pengembalian uang, dan status pembayaran lanjutan.
-- Aman untuk MySQL/MariaDB lama karena memakai INFORMATION_SCHEMA + prepared statement.

SET @db_name := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tb_pesanan_penjualan' AND COLUMN_NAME='status_refund_online') = 0,
  'ALTER TABLE `tb_pesanan_penjualan` ADD COLUMN `status_refund_online` VARCHAR(30) NULL AFTER `status_pembayaran_online`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tb_pesanan_penjualan' AND COLUMN_NAME='nominal_refund_online') = 0,
  'ALTER TABLE `tb_pesanan_penjualan` ADD COLUMN `nominal_refund_online` DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER `status_refund_online`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tb_pesanan_penjualan' AND COLUMN_NAME='bukti_refund_online') = 0,
  'ALTER TABLE `tb_pesanan_penjualan` ADD COLUMN `bukti_refund_online` VARCHAR(255) NULL AFTER `nominal_refund_online`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tb_pesanan_penjualan' AND COLUMN_NAME='tanggal_refund_online') = 0,
  'ALTER TABLE `tb_pesanan_penjualan` ADD COLUMN `tanggal_refund_online` DATETIME NULL AFTER `bukti_refund_online`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='tb_pesanan_penjualan' AND COLUMN_NAME='catatan_refund_online') = 0,
  'ALTER TABLE `tb_pesanan_penjualan` ADD COLUMN `catatan_refund_online` TEXT NULL AFTER `tanggal_refund_online`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
