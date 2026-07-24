-- =========================================================
-- RESET DATA DUMMY DENGAN MASTER OPSIONAL
-- Gunakan dari menu aplikasi agar bisa memilih checkbox.
-- File ini hanya cadangan jika reset dilakukan manual via phpMyAdmin.
-- HAPUS KOMENTAR pada bagian master yang ingin ikut dihapus.
-- =========================================================

SET FOREIGN_KEY_CHECKS=0;

-- 1) TRANSAKSI, STOK, MUTASI, DAN JURNAL
DELETE FROM tb_pembatalan_transaksi;
DELETE FROM tb_log_jurnal_sumber;
DELETE FROM tb_jurnal_detail;
DELETE FROM tb_jurnal;
DELETE FROM tb_kas_masuk;
DELETE FROM tb_kas_keluar;
DELETE FROM tb_saldo_stok;
DELETE FROM tb_mutasi_stok;
DELETE FROM tb_saldo_awal_stok_detail;
DELETE FROM tb_saldo_awal_stok;
DELETE FROM tb_stok_opname_detail;
DELETE FROM tb_stok_opname;
DELETE FROM tb_pembayaran_pembelian;
DELETE FROM tb_faktur_pembelian_detail;
DELETE FROM tb_faktur_pembelian;
DELETE FROM tb_penerimaan_pembelian_detail;
DELETE FROM tb_penerimaan_pembelian;
DELETE FROM tb_pesanan_pembelian_detail;
DELETE FROM tb_pesanan_pembelian;
DELETE FROM tb_pembayaran_penjualan;
DELETE FROM tb_faktur_penjualan_detail;
DELETE FROM tb_faktur_penjualan;
DELETE FROM tb_penyerahan_penjualan_detail;
DELETE FROM tb_penyerahan_penjualan;
DELETE FROM tb_pesanan_penjualan_detail;
DELETE FROM tb_pesanan_penjualan;
DELETE FROM tb_biaya_produksi_detail;
DELETE FROM tb_biaya_produksi;
DELETE FROM tb_hasil_produksi;
DELETE FROM tb_pengambilan_bahan_detail;
DELETE FROM tb_pengambilan_bahan;
DELETE FROM tb_perintah_produksi;

-- 2) OPSIONAL: PRODUK, BAHAN BAKU, KATEGORI, DAN RESEP
-- DELETE FROM tb_resep_detail;
-- DELETE FROM tb_resep;
-- DELETE FROM tb_produk;
-- DELETE FROM tb_bahan_baku;
-- DELETE FROM tb_kategori_produk;

-- 3) OPSIONAL: PELANGGAN
-- DELETE FROM tb_pelanggan;

-- 4) OPSIONAL: PEMASOK
-- DELETE FROM tb_pemasok;

-- 5) OPSIONAL: GUDANG
-- DELETE FROM tb_gudang;

-- 6) OPSIONAL: SATUAN
-- DELETE FROM tb_satuan;

-- 7) OPSIONAL: PAJAK / PPN
-- DELETE FROM tb_pajak;

-- 8) OPSIONAL LANJUTAN: AKUN, MAPPING, PERIODE, DAN TEMPLATE JURNAL
-- Hati-hati, bagian ini membuat setting akuntansi harus dibuat ulang.
-- DELETE FROM tb_konfigurasi_akun;
-- DELETE FROM tb_mapping_akun;
-- DELETE FROM tb_mapping_laporan_detail;
-- DELETE FROM tb_mapping_laporan;
-- DELETE FROM tb_template_jurnal_detail;
-- DELETE FROM tb_template_jurnal;
-- DELETE FROM tb_periode_akuntansi;
-- DELETE FROM tb_coa;

SET FOREIGN_KEY_CHECKS=1;
