-- Update logo UMKM untuk struk thermal 58mm dan laporan cetak.
-- Jalankan file ini jika database lama sudah terpasang.

ALTER TABLE tb_entitas
  ADD COLUMN logo_umkm varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'uploads/logo_umkm/logo_umkm_default.svg' AFTER jenis_usaha;

UPDATE tb_entitas
SET logo_umkm = COALESCE(NULLIF(logo_umkm, ''), 'uploads/logo_umkm/logo_umkm_default.svg')
WHERE id_entitas = 1;

-- Penegasan nama menu supaya kasir tidak memakai istilah faktur untuk piutang pelanggan.
UPDATE tb_menu
SET nama_menu = 'Daftar Piutang Pelanggan',
    keterangan = 'Daftar pelanggan yang membeli kredit, sisa piutang, dan tombol bayar',
    ikon = 'fa fa-money-bill-wave'
WHERE url = '/penjualan/faktur' OR kode_menu = 'FAKTUR_PENJUALAN';
