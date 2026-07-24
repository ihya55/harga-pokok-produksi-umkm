
-- Revisi Penyerahan Penjualan: scan barcode dari menu, surat jalan, biaya pengiriman, dan alur aplikasi lain.
-- Jalankan setelah database utama.

DELIMITER $$
DROP PROCEDURE IF EXISTS add_col_hpp_penyerahan$$
CREATE PROCEDURE add_col_hpp_penyerahan(IN p_table VARCHAR(64), IN p_col VARCHAR(64), IN p_def TEXT)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_col
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_def);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL add_col_hpp_penyerahan('tb_penyerahan_penjualan', 'kurir_pengiriman', '`kurir_pengiriman` VARCHAR(100) NULL AFTER `alamat_tujuan`');
CALL add_col_hpp_penyerahan('tb_penyerahan_penjualan', 'no_resi_pengiriman', '`no_resi_pengiriman` VARCHAR(100) NULL AFTER `kurir_pengiriman`');
CALL add_col_hpp_penyerahan('tb_penyerahan_penjualan', 'biaya_pengiriman', '`biaya_pengiriman` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `no_resi_pengiriman`');
CALL add_col_hpp_penyerahan('tb_penyerahan_penjualan', 'id_coa_biaya_pengiriman', '`id_coa_biaya_pengiriman` BIGINT UNSIGNED NULL AFTER `biaya_pengiriman`');

DROP PROCEDURE IF EXISTS add_col_hpp_penyerahan;

-- Akun beban pengiriman/transportasi untuk surat jalan jika belum ada.
INSERT INTO tb_coa (
    id_entitas, kode_coa, nama_coa, id_coa_induk, level_coa, kategori_coa,
    posisi_saldo_normal, boleh_transaksi, status_aktif, tanggal_dibuat, dibuat_oleh
)
SELECT e.id_entitas, '6310', 'Beban Pengiriman Penjualan',
       (SELECT id_coa FROM tb_coa c WHERE c.id_entitas=e.id_entitas AND c.kode_coa='6000' LIMIT 1),
       2, 'beban', 'debit', 1, 1, NOW(), 1
FROM tb_entitas e
WHERE NOT EXISTS (
    SELECT 1 FROM tb_coa c WHERE c.id_entitas=e.id_entitas AND c.kode_coa='6310'
);

-- Konfigurasi akun agar form otomatis mengambil akun biaya pengiriman.
CREATE TABLE IF NOT EXISTS tb_konfigurasi_akun (
  id_konfigurasi_akun BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_entitas BIGINT UNSIGNED NOT NULL,
  kode_konfigurasi VARCHAR(80) NOT NULL,
  nama_konfigurasi VARCHAR(150) NOT NULL,
  id_coa BIGINT UNSIGNED NOT NULL,
  keterangan VARCHAR(255) NULL,
  status_aktif TINYINT(1) NOT NULL DEFAULT 1,
  tanggal_dibuat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dibuat_oleh BIGINT UNSIGNED NULL,
  tanggal_diubah DATETIME NULL,
  diubah_oleh BIGINT UNSIGNED NULL,
  PRIMARY KEY (id_konfigurasi_akun),
  UNIQUE KEY uk_konfig_entitas_kode (id_entitas, kode_konfigurasi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tb_konfigurasi_akun (id_entitas, kode_konfigurasi, nama_konfigurasi, id_coa, keterangan, status_aktif, tanggal_dibuat, dibuat_oleh)
SELECT e.id_entitas, 'BIAYA_PENGIRIMAN_PENJUALAN', 'Biaya Pengiriman Penjualan', c.id_coa,
       'Dipakai saat penyerahan/surat jalan memiliki ongkir yang dibayar UMKM.', 1, NOW(), 1
FROM tb_entitas e
JOIN tb_coa c ON c.id_entitas=e.id_entitas AND c.kode_coa='6310'
ON DUPLICATE KEY UPDATE id_coa=VALUES(id_coa), status_aktif=1, tanggal_diubah=NOW();
