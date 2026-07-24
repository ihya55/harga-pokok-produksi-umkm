-- Update biaya produksi, biaya kirim faktur pembelian, dan konfigurasi akun produksi.
-- Versi FIX terbaru: kompatibel dengan MySQL/MariaDB ONLY_FULL_GROUP_BY
-- dan sesuai struktur tb_coa aplikasi ini (kategori_coa / posisi_saldo_normal).

DELIMITER $$

DROP PROCEDURE IF EXISTS add_col_if_missing_hpp$$
CREATE PROCEDURE add_col_if_missing_hpp(
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
        SET @sql_add_col = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN ', p_definition);
        PREPARE stmt_add_col FROM @sql_add_col;
        EXECUTE stmt_add_col;
        DEALLOCATE PREPARE stmt_add_col;
    END IF;
END$$

CALL add_col_if_missing_hpp('tb_biaya_produksi', 'no_nota', '`no_nota` VARCHAR(100) NULL AFTER `keterangan`')$$
CALL add_col_if_missing_hpp('tb_biaya_produksi', 'file_nota', '`file_nota` VARCHAR(255) NULL AFTER `no_nota`')$$
CALL add_col_if_missing_hpp('tb_biaya_produksi_detail', 'kode_jenis_transaksi_template', '`kode_jenis_transaksi_template` VARCHAR(50) NULL AFTER `id_coa_lawan`')$$
CALL add_col_if_missing_hpp('tb_faktur_pembelian', 'ada_biaya_kirim', '`ada_biaya_kirim` TINYINT(1) NOT NULL DEFAULT 0 AFTER `ppn_persen`')$$
CALL add_col_if_missing_hpp('tb_faktur_pembelian', 'biaya_kirim', '`biaya_kirim` DECIMAL(18,2) NOT NULL DEFAULT 0.00 AFTER `ada_biaya_kirim`')$$
CALL add_col_if_missing_hpp('tb_faktur_pembelian', 'id_coa_biaya_kirim', '`id_coa_biaya_kirim` BIGINT UNSIGNED NULL AFTER `biaya_kirim`')$$

DROP PROCEDURE IF EXISTS add_col_if_missing_hpp$$

DELIMITER ;

-- 1) Buat akun biaya kirim pembelian jika belum ada.
--    Struktur tb_coa aplikasi ini memakai kategori_coa dan posisi_saldo_normal.
INSERT INTO `tb_coa`
(`id_entitas`, `kode_coa`, `nama_coa`, `id_coa_induk`, `level_coa`, `kategori_coa`, `posisi_saldo_normal`, `boleh_transaksi`, `status_aktif`, `tanggal_dibuat`)
SELECT
    e.id_entitas,
    CONCAT('5129', LPAD(e.id_entitas, 2, '0')) AS kode_coa,
    'Biaya Kirim Pembelian' AS nama_coa,
    (
        SELECT p.id_coa
        FROM tb_coa p
        WHERE p.id_entitas = e.id_entitas
          AND (p.nama_coa LIKE '%Bahan Baku Terpakai%' OR p.nama_coa LIKE '%HARGA POKOK PRODUKSI%' OR p.kategori_coa = 'hpp')
        ORDER BY
          CASE
            WHEN p.nama_coa LIKE '%Bahan Baku Terpakai%' THEN 1
            WHEN p.nama_coa LIKE '%HARGA POKOK PRODUKSI%' THEN 2
            ELSE 3
          END,
          p.level_coa DESC,
          p.id_coa ASC
        LIMIT 1
    ) AS id_coa_induk,
    3 AS level_coa,
    'hpp' AS kategori_coa,
    'debit' AS posisi_saldo_normal,
    1 AS boleh_transaksi,
    1 AS status_aktif,
    NOW() AS tanggal_dibuat
FROM tb_entitas e
WHERE NOT EXISTS (
    SELECT 1
    FROM tb_coa c
    WHERE c.id_entitas = e.id_entitas
      AND (c.nama_coa = 'Biaya Kirim Pembelian' OR c.nama_coa = 'Biaya Angkut Pembelian')
)
AND NOT EXISTS (
    SELECT 1
    FROM tb_coa c2
    WHERE c2.id_entitas = e.id_entitas
      AND c2.kode_coa = CONCAT('5129', LPAD(e.id_entitas, 2, '0'))
);

-- 2) Masukkan konfigurasi akun BIAYA_KIRIM_PEMBELIAN.
--    Prioritas akun: Biaya Kirim Pembelian -> Biaya Angkut Pembelian -> Ongkir/Pengiriman/Transportasi.
INSERT INTO `tb_konfigurasi_akun`
(`id_entitas`, `kode_konfigurasi`, `nama_konfigurasi`, `digunakan_di_menu`, `id_coa`, `keterangan`, `status_aktif`, `tanggal_dibuat`)
SELECT
    x.id_entitas,
    'BIAYA_KIRIM_PEMBELIAN',
    'Akun Biaya Kirim Pembelian',
    'Pembelian > Faktur Pembelian',
    x.id_coa,
    'Dipakai saat faktur pembelian memiliki ongkir/biaya kirim.',
    1,
    NOW()
FROM (
    SELECT
        c.id_entitas,
        MIN(c.id_coa) AS id_coa
    FROM tb_coa c
    WHERE c.boleh_transaksi = 1
      AND c.status_aktif = 1
      AND (
          c.nama_coa = 'Biaya Kirim Pembelian'
          OR c.nama_coa = 'Biaya Angkut Pembelian'
          OR c.nama_coa LIKE '%Ongkir%'
          OR c.nama_coa LIKE '%Pengiriman%'
          OR c.nama_coa LIKE '%Transportasi%'
      )
    GROUP BY c.id_entitas
) x
WHERE NOT EXISTS (
    SELECT 1
    FROM tb_konfigurasi_akun k
    WHERE k.id_entitas = x.id_entitas
      AND k.kode_konfigurasi = 'BIAYA_KIRIM_PEMBELIAN'
);

UPDATE tb_konfigurasi_akun
SET status_aktif = 1,
    tanggal_diubah = NOW()
WHERE kode_konfigurasi = 'BIAYA_KIRIM_PEMBELIAN';

-- 3) Isi kode template pada detail biaya lama agar posting tetap aman.
UPDATE tb_biaya_produksi_detail
SET kode_jenis_transaksi_template = CASE
  WHEN jenis_biaya_produksi = 'tenaga_kerja' THEN 'BIAYA_TENAGA_KERJA_LANGSUNG'
  WHEN jenis_biaya_produksi IN ('overhead','listrik','kemasan','lain_lain') THEN 'BIAYA_OVERHEAD_PABRIK'
  ELSE kode_jenis_transaksi_template
END
WHERE kode_jenis_transaksi_template IS NULL OR kode_jenis_transaksi_template = '';

-- 4) Template produksi tambahan untuk pilihan overhead: listrik/air dan kuota/internet.
INSERT INTO tb_template_jurnal (id_entitas, kode_template_jurnal, nama_template_jurnal, kode_jenis_transaksi, keterangan, status_aktif, tanggal_dibuat)
SELECT e.id_entitas, CONCAT('BPL-', LPAD(e.id_entitas, 4, '0')), 'Template Biaya Listrik dan Air Produksi', 'BIAYA_LISTRIK_AIR_PRODUKSI', 'Template overhead produksi untuk listrik/air.', 1, NOW()
FROM tb_entitas e
WHERE NOT EXISTS (SELECT 1 FROM tb_template_jurnal t WHERE t.id_entitas=e.id_entitas AND t.kode_jenis_transaksi='BIAYA_LISTRIK_AIR_PRODUKSI');

INSERT INTO tb_template_jurnal (id_entitas, kode_template_jurnal, nama_template_jurnal, kode_jenis_transaksi, keterangan, status_aktif, tanggal_dibuat)
SELECT e.id_entitas, CONCAT('BPK-', LPAD(e.id_entitas, 4, '0')), 'Template Biaya Kuota/Internet Produksi', 'BIAYA_KUOTA_PRODUKSI', 'Template overhead produksi untuk kuota/internet.', 1, NOW()
FROM tb_entitas e
WHERE NOT EXISTS (SELECT 1 FROM tb_template_jurnal t WHERE t.id_entitas=e.id_entitas AND t.kode_jenis_transaksi='BIAYA_KUOTA_PRODUKSI');

-- 5) Detail template listrik/air produksi.
INSERT INTO tb_template_jurnal_detail (id_template_jurnal, urutan, posisi_dc, sumber_akun, id_coa_default, sumber_nominal, rumus_nominal, keterangan_baris)
SELECT t.id_template_jurnal, 1, 'debit', 'coa_default', COALESCE(c1.id_coa, c2.id_coa), 'total_biaya', NULL, 'Biaya listrik/air produksi'
FROM tb_template_jurnal t
LEFT JOIN (
    SELECT id_entitas, MIN(id_coa) AS id_coa
    FROM tb_coa
    WHERE status_aktif=1 AND boleh_transaksi=1 AND nama_coa LIKE '%Listrik%'
    GROUP BY id_entitas
) c1 ON c1.id_entitas=t.id_entitas
LEFT JOIN (
    SELECT id_entitas, MIN(id_coa) AS id_coa
    FROM tb_coa
    WHERE status_aktif=1 AND boleh_transaksi=1 AND (nama_coa LIKE '%Overhead%' OR nama_coa LIKE '%Biaya Lain%Produksi%')
    GROUP BY id_entitas
) c2 ON c2.id_entitas=t.id_entitas
WHERE t.kode_jenis_transaksi='BIAYA_LISTRIK_AIR_PRODUKSI'
  AND NOT EXISTS (SELECT 1 FROM tb_template_jurnal_detail d WHERE d.id_template_jurnal=t.id_template_jurnal AND d.urutan=1);

INSERT INTO tb_template_jurnal_detail (id_template_jurnal, urutan, posisi_dc, sumber_akun, id_coa_default, sumber_nominal, rumus_nominal, keterangan_baris)
SELECT t.id_template_jurnal, 2, 'kredit', 'coa_default', c.id_coa, 'total_biaya', NULL, 'Pengeluaran kas listrik/air produksi'
FROM tb_template_jurnal t
JOIN (
    SELECT id_entitas, MIN(id_coa) AS id_coa
    FROM tb_coa
    WHERE status_aktif=1 AND boleh_transaksi=1 AND (nama_coa='Kas' OR kode_coa LIKE '111%')
    GROUP BY id_entitas
) c ON c.id_entitas=t.id_entitas
WHERE t.kode_jenis_transaksi='BIAYA_LISTRIK_AIR_PRODUKSI'
  AND NOT EXISTS (SELECT 1 FROM tb_template_jurnal_detail d WHERE d.id_template_jurnal=t.id_template_jurnal AND d.urutan=2);

-- 6) Detail template kuota/internet produksi.
INSERT INTO tb_template_jurnal_detail (id_template_jurnal, urutan, posisi_dc, sumber_akun, id_coa_default, sumber_nominal, rumus_nominal, keterangan_baris)
SELECT t.id_template_jurnal, 1, 'debit', 'coa_default', COALESCE(c1.id_coa, c2.id_coa), 'total_biaya', NULL, 'Biaya kuota/internet produksi'
FROM tb_template_jurnal t
LEFT JOIN (
    SELECT id_entitas, MIN(id_coa) AS id_coa
    FROM tb_coa
    WHERE status_aktif=1 AND boleh_transaksi=1 AND (nama_coa LIKE '%Kuota%' OR nama_coa LIKE '%Internet%')
    GROUP BY id_entitas
) c1 ON c1.id_entitas=t.id_entitas
LEFT JOIN (
    SELECT id_entitas, MIN(id_coa) AS id_coa
    FROM tb_coa
    WHERE status_aktif=1 AND boleh_transaksi=1 AND (nama_coa LIKE '%Overhead%' OR nama_coa LIKE '%Biaya Lain%Produksi%')
    GROUP BY id_entitas
) c2 ON c2.id_entitas=t.id_entitas
WHERE t.kode_jenis_transaksi='BIAYA_KUOTA_PRODUKSI'
  AND NOT EXISTS (SELECT 1 FROM tb_template_jurnal_detail d WHERE d.id_template_jurnal=t.id_template_jurnal AND d.urutan=1);

INSERT INTO tb_template_jurnal_detail (id_template_jurnal, urutan, posisi_dc, sumber_akun, id_coa_default, sumber_nominal, rumus_nominal, keterangan_baris)
SELECT t.id_template_jurnal, 2, 'kredit', 'coa_default', c.id_coa, 'total_biaya', NULL, 'Pengeluaran kas kuota/internet produksi'
FROM tb_template_jurnal t
JOIN (
    SELECT id_entitas, MIN(id_coa) AS id_coa
    FROM tb_coa
    WHERE status_aktif=1 AND boleh_transaksi=1 AND (nama_coa='Kas' OR kode_coa LIKE '111%')
    GROUP BY id_entitas
) c ON c.id_entitas=t.id_entitas
WHERE t.kode_jenis_transaksi='BIAYA_KUOTA_PRODUKSI'
  AND NOT EXISTS (SELECT 1 FROM tb_template_jurnal_detail d WHERE d.id_template_jurnal=t.id_template_jurnal AND d.urutan=2);
