-- Ubah nama menu supaya UMKM tidak bingung dengan istilah faktur.
-- Route/program tetap memakai penjualan/faktur agar tidak perlu mengubah struktur tabel dan kode lama.
UPDATE tb_menu
SET nama_menu = 'Daftar Piutang Pelanggan',
    keterangan = 'Daftar pelanggan yang membeli kredit, sisa piutang, dan tombol bayar',
    ikon = 'fa fa-money-bill-wave'
WHERE url = '/penjualan/faktur' OR kode_menu = 'FAKTUR_PENJUALAN';
