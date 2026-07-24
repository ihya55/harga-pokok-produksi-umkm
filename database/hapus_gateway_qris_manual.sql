-- Revisi QRIS Manual: nonaktifkan payment gateway QRIS otomatis
-- Jalankan file ini setelah import/update database.

UPDATE tb_payment_gateway_setting
SET is_active = 0,
    tanggal_diubah = NOW()
WHERE provider IN ('ipaymu','duitku','midtrans');

-- Catatan:
-- Menu Gateway QRIS sudah dihapus dari tampilan Mapping Akun.
-- QRIS yang dipakai pelanggan adalah QRIS manual dari Mapping Akun > Rekening / QRIS.
