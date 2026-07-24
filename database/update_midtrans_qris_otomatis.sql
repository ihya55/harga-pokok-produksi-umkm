-- Update Midtrans QRIS Otomatis untuk Pesanan Online
-- Jalankan di phpMyAdmin setelah backup database.

CREATE TABLE IF NOT EXISTS `tb_payment_gateway_setting` (
  `id_payment_gateway_setting` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_entitas` BIGINT UNSIGNED NOT NULL,
  `provider` VARCHAR(50) NOT NULL DEFAULT 'midtrans',
  `mode` VARCHAR(20) NOT NULL DEFAULT 'sandbox',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `server_key` VARCHAR(255) DEFAULT NULL,
  `client_key` VARCHAR(255) DEFAULT NULL,
  `merchant_id` VARCHAR(100) DEFAULT NULL,
  `enabled_payments` VARCHAR(100) NOT NULL DEFAULT 'qris',
  `tanggal_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dibuat_oleh` BIGINT UNSIGNED DEFAULT NULL,
  `tanggal_diubah` DATETIME DEFAULT NULL,
  `diubah_oleh` BIGINT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id_payment_gateway_setting`),
  UNIQUE KEY `uq_gateway_entitas_provider` (`id_entitas`, `provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DELIMITER $$

DROP PROCEDURE IF EXISTS add_col_if_not_exists_midtrans$$
CREATE PROCEDURE add_col_if_not_exists_midtrans(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'metode_pembayaran_online', 'VARCHAR(30) NULL AFTER `total`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'nominal_pembayaran_online', 'DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `metode_pembayaran_online`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'status_pembayaran_online', 'VARCHAR(30) NOT NULL DEFAULT \'belum_bayar\' AFTER `nominal_pembayaran_online`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'midtrans_order_id', 'VARCHAR(100) NULL AFTER `status_pembayaran_online`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'midtrans_snap_token', 'VARCHAR(255) NULL AFTER `midtrans_order_id`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'midtrans_redirect_url', 'TEXT NULL AFTER `midtrans_snap_token`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'midtrans_transaction_status', 'VARCHAR(50) NULL AFTER `midtrans_redirect_url`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'midtrans_fraud_status', 'VARCHAR(50) NULL AFTER `midtrans_transaction_status`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'midtrans_paid_at', 'DATETIME NULL AFTER `midtrans_fraud_status`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'midtrans_error_message', 'TEXT NULL AFTER `midtrans_paid_at`');
CALL add_col_if_not_exists_midtrans('tb_pesanan_penjualan', 'midtrans_response_json', 'LONGTEXT NULL AFTER `midtrans_error_message`');

DROP PROCEDURE IF EXISTS add_col_if_not_exists_midtrans;

INSERT INTO `tb_payment_gateway_setting` (`id_entitas`, `provider`, `mode`, `is_active`, `enabled_payments`, `tanggal_dibuat`)
SELECT e.`id_entitas`, 'midtrans', 'sandbox', 0, 'qris', NOW()
FROM `tb_entitas` e
WHERE NOT EXISTS (
    SELECT 1
    FROM `tb_payment_gateway_setting` pg
    WHERE pg.`id_entitas` = e.`id_entitas`
      AND pg.`provider` = 'midtrans'
);
