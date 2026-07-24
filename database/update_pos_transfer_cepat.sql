-- Patch POS transfer cepat: referensi transfer + bukti opsional
-- Jalankan jika database lama sudah terpasang.

ALTER TABLE `tb_pembayaran_penjualan`
  ADD COLUMN `no_referensi_transfer` varchar(100) NULL AFTER `jumlah_bayar`,
  ADD COLUMN `kode_cek_transfer` varchar(30) NULL AFTER `no_referensi_transfer`,
  ADD COLUMN `nama_pengirim_transfer` varchar(150) NULL AFTER `kode_cek_transfer`,
  ADD COLUMN `bukti_bayar_pos` varchar(255) NULL AFTER `nama_pengirim_transfer`;

-- Catatan alur:
-- 1. Untuk POS metode Transfer, kasir wajib isi nomor referensi/kode cek minimal 4 digit.
-- 2. Upload bukti transfer bersifat opsional agar antrian kasir tetap cepat.
-- 3. Foto bukti dapat diisi hanya jika pelanggan mudah menyerahkan file/HP sedang sepi.
