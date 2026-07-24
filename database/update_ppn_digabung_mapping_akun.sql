-- Update menu PPN agar tidak tampil sebagai menu terpisah.
-- Pengaturan PPN sekarang dibuka dari: Master Setup > Mapping Akun > tab Pajak / PPN.

SET @menu_pajak := (SELECT id_menu FROM tb_menu WHERE kode_menu = 'PAJAK_PPN' LIMIT 1);
SET @menu_mapping := (SELECT id_menu FROM tb_menu WHERE kode_menu = 'MAPPING_AKUN' LIMIT 1);

-- Sembunyikan menu Pajak / PPN yang berdiri sendiri dari navbar/sidebar.
UPDATE tb_menu
SET status_aktif = 0,
    tanggal_diubah = NOW(),
    diubah_oleh = 1,
    keterangan = 'Dinonaktifkan sebagai menu terpisah. PPN digabung ke Mapping Akun > Pajak / PPN.'
WHERE kode_menu = 'PAJAK_PPN';

UPDATE tb_role_menu
SET status_aktif = 0,
    tanggal_diubah = NOW(),
    diubah_oleh = 1
WHERE id_menu = @menu_pajak;

-- Pastikan menu Mapping Akun tetap aktif.
UPDATE tb_menu
SET status_aktif = 1,
    tanggal_diubah = NOW(),
    diubah_oleh = 1
WHERE kode_menu = 'MAPPING_AKUN';

-- Pastikan semua role admin bisa melihat Mapping Akun.
INSERT INTO tb_role_menu (
    id_role, id_menu, boleh_lihat, boleh_tambah, boleh_ubah, boleh_hapus,
    boleh_posting, boleh_approve, boleh_cetak, boleh_export, status_aktif,
    tanggal_dibuat, dibuat_oleh
)
SELECT r.id_role, @menu_mapping, 1, 1, 1, 0, 0, 0, 1, 1, 1, NOW(), 1
FROM tb_role r
WHERE @menu_mapping IS NOT NULL
  AND r.nama_role IN ('super_admin','admin_entitas','admin_keuangan')
ON DUPLICATE KEY UPDATE
    boleh_lihat = VALUES(boleh_lihat),
    boleh_tambah = VALUES(boleh_tambah),
    boleh_ubah = VALUES(boleh_ubah),
    boleh_cetak = VALUES(boleh_cetak),
    boleh_export = VALUES(boleh_export),
    status_aktif = 1,
    tanggal_diubah = NOW(),
    diubah_oleh = 1;

-- Pastikan tabel pajak tersedia untuk pengaturan PPN default.
CREATE TABLE IF NOT EXISTS tb_pajak (
  id_pajak bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  id_entitas bigint UNSIGNED NOT NULL,
  kode_pajak varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  nama_pajak varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  persentase decimal(5,2) NOT NULL DEFAULT 0.00,
  is_default tinyint(1) NOT NULL DEFAULT 0,
  status_aktif tinyint(1) NOT NULL DEFAULT 1,
  keterangan text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  tanggal_dibuat datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dibuat_oleh bigint UNSIGNED DEFAULT NULL,
  tanggal_diubah datetime DEFAULT NULL,
  diubah_oleh bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id_pajak),
  KEY idx_tb_pajak_entitas (id_entitas),
  KEY idx_tb_pajak_kode (kode_pajak)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pastikan setiap entitas punya PPN default awal.
INSERT INTO tb_pajak (
    id_entitas, kode_pajak, nama_pajak, persentase, is_default, status_aktif,
    keterangan, tanggal_dibuat, dibuat_oleh
)
SELECT e.id_entitas, 'PPN', 'Pajak Pertambahan Nilai', 11.00, 1, 1,
       'Pajak default yang digunakan otomatis pada faktur.', NOW(), 1
FROM tb_entitas e
WHERE NOT EXISTS (
    SELECT 1 FROM tb_pajak p
    WHERE p.id_entitas = e.id_entitas
      AND p.kode_pajak = 'PPN'
      AND p.is_default = 1
);
