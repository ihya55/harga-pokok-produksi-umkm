-- RESET LANGSUNG DATA DUMMY TRANSAKSI/STOK/JURNAL
-- Master data tidak dihapus. Jalankan hanya setelah backup database.

START TRANSACTION;
SET FOREIGN_KEY_CHECKS=0;

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

ALTER TABLE tb_pembatalan_transaksi AUTO_INCREMENT = 1;
ALTER TABLE tb_log_jurnal_sumber AUTO_INCREMENT = 1;
ALTER TABLE tb_jurnal_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_jurnal AUTO_INCREMENT = 1;
ALTER TABLE tb_kas_masuk AUTO_INCREMENT = 1;
ALTER TABLE tb_kas_keluar AUTO_INCREMENT = 1;
ALTER TABLE tb_saldo_stok AUTO_INCREMENT = 1;
ALTER TABLE tb_mutasi_stok AUTO_INCREMENT = 1;
ALTER TABLE tb_saldo_awal_stok_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_saldo_awal_stok AUTO_INCREMENT = 1;
ALTER TABLE tb_stok_opname_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_stok_opname AUTO_INCREMENT = 1;
ALTER TABLE tb_pembayaran_pembelian AUTO_INCREMENT = 1;
ALTER TABLE tb_faktur_pembelian_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_faktur_pembelian AUTO_INCREMENT = 1;
ALTER TABLE tb_penerimaan_pembelian_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_penerimaan_pembelian AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan_pembelian_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan_pembelian AUTO_INCREMENT = 1;
ALTER TABLE tb_pembayaran_penjualan AUTO_INCREMENT = 1;
ALTER TABLE tb_faktur_penjualan_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_faktur_penjualan AUTO_INCREMENT = 1;
ALTER TABLE tb_penyerahan_penjualan_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_penyerahan_penjualan AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan_penjualan_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_pesanan_penjualan AUTO_INCREMENT = 1;
ALTER TABLE tb_biaya_produksi_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_biaya_produksi AUTO_INCREMENT = 1;
ALTER TABLE tb_hasil_produksi AUTO_INCREMENT = 1;
ALTER TABLE tb_pengambilan_bahan_detail AUTO_INCREMENT = 1;
ALTER TABLE tb_pengambilan_bahan AUTO_INCREMENT = 1;
ALTER TABLE tb_perintah_produksi AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS=1;
COMMIT;