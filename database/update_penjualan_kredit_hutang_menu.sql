-- Update label menu Faktur Penjualan menjadi Daftar Piutang Pelanggan.
-- Jalankan setelah import database utama jika ingin menu di navbar ikut berubah.
UPDATE tb_menu
SET nama_menu = 'Daftar Piutang Pelanggan',
    keterangan = 'Daftar pelanggan yang membeli kredit, sisa piutang, dan tombol bayar',
    ikon = 'fa fa-money-bill-wave'
WHERE url = '/penjualan/faktur' OR kode_menu = 'FAKTUR_PENJUALAN';
