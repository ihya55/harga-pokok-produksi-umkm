-- Patch filter chat dan bukti pembayaran online berdasarkan id_pelanggan.
-- Wajib diimport agar data chat/bukti tidak tercampur saat ID pesanan pernah terpakai ulang.

SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE tb_pesanan_online_chat ADD COLUMN id_pelanggan BIGINT UNSIGNED NULL AFTER id_pesanan_penjualan',
        'SELECT ''Kolom id_pelanggan pada tb_pesanan_online_chat sudah ada'' AS info'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'tb_pesanan_online_chat'
      AND COLUMN_NAME = 'id_pelanggan'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE tb_pembayaran_online_bukti ADD COLUMN id_pelanggan BIGINT UNSIGNED NULL AFTER id_pesanan_penjualan',
        'SELECT ''Kolom id_pelanggan pada tb_pembayaran_online_bukti sudah ada'' AS info'
    )
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'tb_pembayaran_online_bukti'
      AND COLUMN_NAME = 'id_pelanggan'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'CREATE INDEX idx_po_chat_pesanan_pelanggan ON tb_pesanan_online_chat (id_entitas, id_pesanan_penjualan, id_pelanggan)',
        'SELECT ''Index idx_po_chat_pesanan_pelanggan sudah ada'' AS info'
    )
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'tb_pesanan_online_chat'
      AND INDEX_NAME = 'idx_po_chat_pesanan_pelanggan'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'CREATE INDEX idx_bukti_online_pesanan_pelanggan ON tb_pembayaran_online_bukti (id_entitas, id_pesanan_penjualan, id_pelanggan, metode_pembayaran)',
        'SELECT ''Index idx_bukti_online_pesanan_pelanggan sudah ada'' AS info'
    )
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'tb_pembayaran_online_bukti'
      AND INDEX_NAME = 'idx_bukti_online_pesanan_pelanggan'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
