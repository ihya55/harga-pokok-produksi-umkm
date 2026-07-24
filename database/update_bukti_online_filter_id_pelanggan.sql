-- Patch filter bukti pembayaran online agar tidak nyasar ke pesanan/customer lain
-- Aman untuk database yang belum punya kolom id_pelanggan.

SET @db_name := DATABASE();

SET @sql := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE tb_pembayaran_online_bukti ADD COLUMN id_pelanggan BIGINT UNSIGNED NULL AFTER id_pesanan_penjualan',
        'SELECT ''Kolom id_pelanggan sudah ada'' AS info'
    )
    FROM information_schema.COLUMNS
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
        'ALTER TABLE tb_pembayaran_online_bukti ADD INDEX idx_bukti_online_pesanan_pelanggan_metode (id_entitas, id_pesanan_penjualan, id_pelanggan, metode_pembayaran)',
        'SELECT ''Index idx_bukti_online_pesanan_pelanggan_metode sudah ada'' AS info'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'tb_pembayaran_online_bukti'
      AND INDEX_NAME = 'idx_bukti_online_pesanan_pelanggan_metode'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Isi id_pelanggan untuk bukti lama hanya jika metode bukti sama dengan metode pesanan saat ini.
-- Bukti lama yang metodenya berbeda, misalnya bukti transfer lama di pesanan COD, sengaja dibiarkan NULL agar tidak tampil.
UPDATE tb_pembayaran_online_bukti b
JOIN tb_pesanan_penjualan p
  ON p.id_pesanan_penjualan = b.id_pesanan_penjualan
 AND p.id_entitas = b.id_entitas
SET b.id_pelanggan = p.id_pelanggan
WHERE b.id_pelanggan IS NULL
  AND LOWER(COALESCE(b.metode_pembayaran, '')) = LOWER(COALESCE(p.metode_pembayaran_online, ''));
