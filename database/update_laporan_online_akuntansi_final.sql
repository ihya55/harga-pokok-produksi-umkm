-- Update final laporan akuntansi pesanan online + arus kas + audit penyerahan.
-- Jalankan sekali di phpMyAdmin setelah update aplikasi.

SET @db := DATABASE();

-- 1) Kolom audit penyerahan penjualan jika belum ada.
SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tb_penyerahan_penjualan' AND COLUMN_NAME='tanggal_diubah') = 0,
  'ALTER TABLE `tb_penyerahan_penjualan` ADD COLUMN `tanggal_diubah` DATETIME NULL AFTER `diposting_oleh`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='tb_penyerahan_penjualan' AND COLUMN_NAME='diubah_oleh') = 0,
  'ALTER TABLE `tb_penyerahan_penjualan` ADD COLUMN `diubah_oleh` BIGINT UNSIGNED NULL AFTER `tanggal_diubah`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Pastikan akun Bank juga terdaftar sebagai akun kas/bank arus kas.
INSERT INTO `tb_mapping_akun`
(`id_entitas`, `kode_jenis_transaksi`, `jenis_objek`, `id_objek`, `peran_akun`, `id_coa`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT c.id_entitas, 'ARUS_KAS', 'global', 0, 'akun_kas_bank', c.id_coa, 1, NOW(), 1
FROM tb_coa c
WHERE c.status_aktif = 1
  AND c.boleh_transaksi = 1
  AND c.kategori_coa = 'aset'
  AND (c.kode_coa LIKE '111%' OR c.kode_coa LIKE '112%' OR c.nama_coa LIKE '%Kas%' OR c.nama_coa LIKE '%Bank%' OR c.nama_coa LIKE '%QRIS%')
  AND NOT EXISTS (
      SELECT 1 FROM tb_mapping_akun ma
      WHERE ma.id_entitas = c.id_entitas
        AND ma.kode_jenis_transaksi = 'ARUS_KAS'
        AND ma.jenis_objek = 'global'
        AND ma.id_objek = 0
        AND ma.peran_akun = 'akun_kas_bank'
        AND ma.id_coa = c.id_coa
  );

-- 3) Tambahkan kode transaksi pembayaran online ke Laporan Arus Kas > Penerimaan kas penjualan.
INSERT INTO `tb_mapping_laporan_detail`
(`id_mapping_laporan`, `id_entitas`, `tipe_sumber`, `nilai_sumber`, `id_coa`, `arah_saldo`, `operator_nilai`, `keterangan`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT ml.id_mapping_laporan, ml.id_entitas, 'KODE_JENIS_TRANSAKSI', 'PEMBAYARAN_ONLINE_DITERIMA', NULL, 'KAS_MASUK', 'TAMBAH', 'Penerimaan uang pesanan online dari customer', 1, NOW(), 1
FROM tb_mapping_laporan ml
WHERE ml.kode_laporan = 'ARUS_KAS'
  AND ml.sub_kode = 'penerimaan-penjualan'
  AND ml.status_aktif = 1
  AND NOT EXISTS (
      SELECT 1 FROM tb_mapping_laporan_detail d
      WHERE d.id_mapping_laporan = ml.id_mapping_laporan
        AND d.id_entitas = ml.id_entitas
        AND d.tipe_sumber = 'KODE_JENIS_TRANSAKSI'
        AND d.nilai_sumber = 'PEMBAYARAN_ONLINE_DITERIMA'
        AND d.status_aktif = 1
  );

-- 4) Tambahkan kode transaksi refund online ke Laporan Arus Kas > Pengeluaran kas lainnya.
INSERT INTO `tb_mapping_laporan_detail`
(`id_mapping_laporan`, `id_entitas`, `tipe_sumber`, `nilai_sumber`, `id_coa`, `arah_saldo`, `operator_nilai`, `keterangan`, `status_aktif`, `tanggal_dibuat`, `dibuat_oleh`)
SELECT ml.id_mapping_laporan, ml.id_entitas, 'KODE_JENIS_TRANSAKSI', 'REFUND_PEMBAYARAN_ONLINE', NULL, 'KAS_KELUAR', 'KURANG', 'Pengembalian uang/refund pesanan online', 1, NOW(), 1
FROM tb_mapping_laporan ml
WHERE ml.kode_laporan = 'ARUS_KAS'
  AND ml.sub_kode = 'kas-keluar-lain'
  AND ml.status_aktif = 1
  AND NOT EXISTS (
      SELECT 1 FROM tb_mapping_laporan_detail d
      WHERE d.id_mapping_laporan = ml.id_mapping_laporan
        AND d.id_entitas = ml.id_entitas
        AND d.tipe_sumber = 'KODE_JENIS_TRANSAKSI'
        AND d.nilai_sumber = 'REFUND_PEMBAYARAN_ONLINE'
        AND d.status_aktif = 1
  );

-- 5) Reklasifikasi data lama: uang online yang sudah diterima dan barang sudah diserahkan,
--    dari Pendapatan Diterima Dimuka menjadi Pendapatan Penjualan.
INSERT INTO `tb_jurnal`
(`id_entitas`, `no_jurnal`, `tanggal_jurnal`, `id_periode`, `kode_jenis_transaksi`, `keterangan`, `tabel_sumber`, `id_sumber`, `no_sumber`, `status_jurnal`, `total_debit`, `total_kredit`, `tanggal_dibuat`, `dibuat_oleh`, `tanggal_posting`, `diposting_oleh`)
SELECT
  py.id_entitas,
  CONCAT('JRN-ONL-', py.id_penyerahan_penjualan),
  py.tanggal_penyerahan,
  pr.id_periode,
  'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN',
  CONCAT('Pengakuan pendapatan pesanan online saat penyerahan ', py.no_penyerahan_penjualan),
  'tb_penyerahan_penjualan',
  py.id_penyerahan_penjualan,
  py.no_penyerahan_penjualan,
  'posted',
  ps.total,
  ps.total,
  NOW(),
  COALESCE(py.diposting_oleh, 1),
  NOW(),
  COALESCE(py.diposting_oleh, 1)
FROM tb_penyerahan_penjualan py
JOIN tb_pesanan_penjualan ps ON ps.id_entitas = py.id_entitas AND ps.id_pesanan_penjualan = py.id_pesanan_penjualan
JOIN tb_periode_akuntansi pr ON pr.id_entitas = py.id_entitas AND pr.tanggal_mulai <= py.tanggal_penyerahan AND pr.tanggal_selesai >= py.tanggal_penyerahan AND pr.status_periode IN ('terbuka','aktif')
WHERE py.status_penyerahan = 'posted'
  AND ps.sumber_pesanan = 'website'
  AND ps.status_pembayaran_online IN ('lunas','paid','settlement')
  AND ps.total > 0
  AND NOT EXISTS (
    SELECT 1 FROM tb_log_jurnal_sumber l
    WHERE l.id_entitas = py.id_entitas
      AND l.tabel_sumber = 'tb_penyerahan_penjualan'
      AND l.id_sumber = py.id_penyerahan_penjualan
      AND l.kode_jenis_transaksi = 'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN'
  )
  AND NOT EXISTS (
    SELECT 1 FROM tb_jurnal j
    WHERE j.id_entitas = py.id_entitas
      AND j.tabel_sumber = 'tb_penyerahan_penjualan'
      AND j.id_sumber = py.id_penyerahan_penjualan
      AND j.kode_jenis_transaksi = 'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN'
  );

-- Detail debit Pendapatan Diterima Dimuka.
INSERT INTO `tb_jurnal_detail`
(`id_jurnal`, `urutan`, `id_coa`, `debit`, `kredit`, `keterangan_baris`, `id_pelanggan`, `id_pemasok`, `id_produk`, `id_bahan_baku`, `id_gudang`)
SELECT
  j.id_jurnal,
  1,
  COALESCE(
    (SELECT c.id_coa FROM tb_coa c WHERE c.id_entitas = j.id_entitas AND c.boleh_transaksi = 1 AND c.status_aktif = 1 AND (c.nama_coa LIKE '%Pendapatan Diterima Dimuka%' OR c.nama_coa LIKE '%Uang Muka Penjualan%' OR c.kode_coa = '2150') ORDER BY c.kode_coa LIMIT 1),
    (SELECT c.id_coa FROM tb_coa c WHERE c.id_entitas = j.id_entitas AND c.kategori_coa = 'liabilitas' AND c.boleh_transaksi = 1 AND c.status_aktif = 1 ORDER BY c.kode_coa LIMIT 1)
  ),
  ps.total,
  0,
  'Membalik uang muka/pendapatan diterima dimuka pesanan online',
  py.id_pelanggan,
  NULL,
  NULL,
  NULL,
  NULL
FROM tb_jurnal j
JOIN tb_penyerahan_penjualan py ON py.id_entitas = j.id_entitas AND py.id_penyerahan_penjualan = j.id_sumber
JOIN tb_pesanan_penjualan ps ON ps.id_entitas = py.id_entitas AND ps.id_pesanan_penjualan = py.id_pesanan_penjualan
WHERE j.kode_jenis_transaksi = 'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN'
  AND j.tabel_sumber = 'tb_penyerahan_penjualan'
  AND NOT EXISTS (SELECT 1 FROM tb_jurnal_detail d WHERE d.id_jurnal = j.id_jurnal AND d.urutan = 1);

-- Detail kredit penjualan.
INSERT INTO `tb_jurnal_detail`
(`id_jurnal`, `urutan`, `id_coa`, `debit`, `kredit`, `keterangan_baris`, `id_pelanggan`, `id_pemasok`, `id_produk`, `id_bahan_baku`, `id_gudang`)
SELECT
  j.id_jurnal,
  2,
  COALESCE(
    (SELECT k.id_coa FROM tb_konfigurasi_akun k WHERE k.id_entitas = j.id_entitas AND k.status_aktif = 1 AND k.kode_konfigurasi IN ('penjualan_produk','PENJUALAN_PRODUK','PENDAPATAN_PENJUALAN') ORDER BY k.id_konfigurasi_akun LIMIT 1),
    (SELECT c.id_coa FROM tb_coa c WHERE c.id_entitas = j.id_entitas AND c.boleh_transaksi = 1 AND c.status_aktif = 1 AND (c.nama_coa LIKE '%Penjualan Produk%' OR c.nama_coa LIKE '%Penjualan Tunai%' OR c.kode_coa IN ('4100','4110')) ORDER BY CASE WHEN c.kode_coa='4100' THEN 0 WHEN c.nama_coa LIKE '%Penjualan Produk%' THEN 1 ELSE 2 END, c.kode_coa LIMIT 1),
    (SELECT c.id_coa FROM tb_coa c WHERE c.id_entitas = j.id_entitas AND c.kategori_coa = 'pendapatan' AND c.boleh_transaksi = 1 AND c.status_aktif = 1 ORDER BY c.kode_coa LIMIT 1)
  ),
  0,
  CASE WHEN (ps.subtotal - ps.diskon) > 0 THEN (ps.subtotal - ps.diskon) ELSE (ps.total - COALESCE(ps.ppn,0)) END,
  'Pendapatan penjualan online diakui saat barang diserahkan',
  py.id_pelanggan,
  NULL,
  NULL,
  NULL,
  NULL
FROM tb_jurnal j
JOIN tb_penyerahan_penjualan py ON py.id_entitas = j.id_entitas AND py.id_penyerahan_penjualan = j.id_sumber
JOIN tb_pesanan_penjualan ps ON ps.id_entitas = py.id_entitas AND ps.id_pesanan_penjualan = py.id_pesanan_penjualan
WHERE j.kode_jenis_transaksi = 'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN'
  AND j.tabel_sumber = 'tb_penyerahan_penjualan'
  AND NOT EXISTS (SELECT 1 FROM tb_jurnal_detail d WHERE d.id_jurnal = j.id_jurnal AND d.urutan = 2);

-- Detail kredit PPN bila ada.
INSERT INTO `tb_jurnal_detail`
(`id_jurnal`, `urutan`, `id_coa`, `debit`, `kredit`, `keterangan_baris`, `id_pelanggan`, `id_pemasok`, `id_produk`, `id_bahan_baku`, `id_gudang`)
SELECT
  j.id_jurnal,
  3,
  (SELECT c.id_coa FROM tb_coa c WHERE c.id_entitas = j.id_entitas AND c.boleh_transaksi = 1 AND c.status_aktif = 1 AND (c.nama_coa LIKE '%Pajak Keluaran%' OR c.nama_coa LIKE '%PPN Keluaran%' OR c.kode_coa = '2141') ORDER BY c.kode_coa LIMIT 1),
  0,
  ps.ppn,
  'Pajak keluaran pesanan online',
  py.id_pelanggan,
  NULL,
  NULL,
  NULL,
  NULL
FROM tb_jurnal j
JOIN tb_penyerahan_penjualan py ON py.id_entitas = j.id_entitas AND py.id_penyerahan_penjualan = j.id_sumber
JOIN tb_pesanan_penjualan ps ON ps.id_entitas = py.id_entitas AND ps.id_pesanan_penjualan = py.id_pesanan_penjualan
WHERE j.kode_jenis_transaksi = 'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN'
  AND j.tabel_sumber = 'tb_penyerahan_penjualan'
  AND COALESCE(ps.ppn,0) > 0
  AND NOT EXISTS (SELECT 1 FROM tb_jurnal_detail d WHERE d.id_jurnal = j.id_jurnal AND d.urutan = 3);

-- Log sumber jurnal reklasifikasi.
INSERT INTO `tb_log_jurnal_sumber`
(`id_entitas`, `id_jurnal`, `tabel_sumber`, `id_sumber`, `no_sumber`, `kode_jenis_transaksi`, `tanggal_dibuat`)
SELECT
  j.id_entitas,
  j.id_jurnal,
  'tb_penyerahan_penjualan',
  j.id_sumber,
  j.no_sumber,
  'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN',
  NOW()
FROM tb_jurnal j
WHERE j.kode_jenis_transaksi = 'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN'
  AND j.tabel_sumber = 'tb_penyerahan_penjualan'
  AND NOT EXISTS (
    SELECT 1 FROM tb_log_jurnal_sumber l
    WHERE l.id_entitas = j.id_entitas
      AND l.id_jurnal = j.id_jurnal
      AND l.kode_jenis_transaksi = 'PENYERAHAN_PENJUALAN_ONLINE_PENDAPATAN'
  );
