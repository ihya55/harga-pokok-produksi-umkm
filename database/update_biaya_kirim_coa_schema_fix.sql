-- FIX SQL: BIAYA_KIRIM_PEMBELIAN sesuai struktur tb_coa aplikasi ini
-- Penyebab error sebelumnya: kolom tb_coa bukan tipe_akun/posisi_normal,
-- tetapi kategori_coa/posisi_saldo_normal.
-- Jalankan file ini setelah SQL update sebelumnya berhenti/error.

-- 1) Buat akun biaya kirim pembelian jika belum ada.
--    Untuk struktur database ini akun yang tepat adalah kategori_coa = hpp,
--    posisi_saldo_normal = debit.
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

-- 3) Kalau konfigurasi sudah ada tapi belum aktif, aktifkan kembali.
UPDATE tb_konfigurasi_akun
SET status_aktif = 1,
    tanggal_diubah = NOW()
WHERE kode_konfigurasi = 'BIAYA_KIRIM_PEMBELIAN';
