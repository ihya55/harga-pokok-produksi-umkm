-- Update iPaymu QRIS Dinamis + tetap gunakan QRIS manual dari Mapping Akun > Rekening / QRIS
-- Jalankan di phpMyAdmin setelah file aplikasi ditimpa.

CREATE TABLE IF NOT EXISTS `tb_payment_gateway_setting` (
  `id_payment_gateway_setting` INT(11) NOT NULL AUTO_INCREMENT,
  `id_entitas` INT(11) NOT NULL DEFAULT 1,
  `provider` VARCHAR(40) NOT NULL,
  `mode` ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox',
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `server_key` TEXT NULL,
  `client_key` TEXT NULL,
  `merchant_id` VARCHAR(150) NULL,
  `enabled_payments` VARCHAR(100) NULL,
  `tanggal_dibuat` DATETIME NULL,
  `dibuat_oleh` INT(11) NULL,
  `tanggal_diubah` DATETIME NULL,
  `diubah_oleh` INT(11) NULL,
  PRIMARY KEY (`id_payment_gateway_setting`),
  UNIQUE KEY `uk_gateway_entitas_provider` (`id_entitas`, `provider`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP PROCEDURE IF EXISTS `sp_add_col_if_not_exists`;
DELIMITER $$
CREATE PROCEDURE `sp_add_col_if_not_exists`(
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

CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'id_entitas', 'INT(11) NOT NULL DEFAULT 1 AFTER `id_payment_gateway_setting`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'provider', 'VARCHAR(40) NOT NULL AFTER `id_entitas`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'mode', "ENUM('sandbox','production') NOT NULL DEFAULT 'sandbox' AFTER `provider`");
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `mode`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'server_key', 'TEXT NULL AFTER `is_active`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'client_key', 'TEXT NULL AFTER `server_key`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'merchant_id', 'VARCHAR(150) NULL AFTER `client_key`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'enabled_payments', 'VARCHAR(100) NULL AFTER `merchant_id`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'tanggal_dibuat', 'DATETIME NULL AFTER `enabled_payments`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'dibuat_oleh', 'INT(11) NULL AFTER `tanggal_dibuat`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'tanggal_diubah', 'DATETIME NULL AFTER `dibuat_oleh`');
CALL sp_add_col_if_not_exists('tb_payment_gateway_setting', 'diubah_oleh', 'INT(11) NULL AFTER `tanggal_diubah`');

CALL sp_add_col_if_not_exists('tb_pesanan_penjualan', 'ipaymu_order_id', 'VARCHAR(100) NULL');
CALL sp_add_col_if_not_exists('tb_pesanan_penjualan', 'ipaymu_session_id', 'VARCHAR(150) NULL');
CALL sp_add_col_if_not_exists('tb_pesanan_penjualan', 'ipaymu_payment_url', 'TEXT NULL');
CALL sp_add_col_if_not_exists('tb_pesanan_penjualan', 'ipaymu_transaction_id', 'VARCHAR(150) NULL');
CALL sp_add_col_if_not_exists('tb_pesanan_penjualan', 'ipaymu_status', 'VARCHAR(80) NULL');
CALL sp_add_col_if_not_exists('tb_pesanan_penjualan', 'ipaymu_response_json', 'LONGTEXT NULL');
CALL sp_add_col_if_not_exists('tb_pesanan_penjualan', 'ipaymu_error_message', 'TEXT NULL');
CALL sp_add_col_if_not_exists('tb_pesanan_penjualan', 'ipaymu_paid_at', 'DATETIME NULL');

DROP PROCEDURE IF EXISTS `sp_add_col_if_not_exists`;

INSERT INTO `tb_payment_gateway_setting`
(`id_entitas`, `provider`, `mode`, `is_active`, `server_key`, `client_key`, `merchant_id`, `enabled_payments`, `tanggal_dibuat`)
SELECT 1, 'ipaymu', 'sandbox', 0, NULL, NULL, NULL, 'qris', NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `tb_payment_gateway_setting` WHERE `id_entitas` = 1 AND `provider` = 'ipaymu'
);
