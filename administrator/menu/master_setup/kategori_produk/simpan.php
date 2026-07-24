<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/KategoriProdukORM.php';

require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/kategori_produk');
}

$id_entitas            = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna           = (int) (user_login()['id_pengguna'] ?? 0);
$nama_kategori_produk  = trim((string) ($_POST['nama_kategori_produk'] ?? ''));
$keterangan            = trim((string) ($_POST['keterangan'] ?? ''));
$status_aktif          = (int) ($_POST['status_aktif'] ?? 1);

if ($nama_kategori_produk === '') {
    set_flash('error', 'Nama kategori produk wajib diisi.');
    redirect_admin('master_setup/kategori_produk/tambah');
}

$kode_kategori_produk = generate_kode_master(
    'tb_kategori_produk',
    'kode_kategori_produk',
    'KAT',
    4,
    $id_entitas
);

KategoriProdukORM::create([
    'id_entitas'            => $id_entitas,
    'kode_kategori_produk'  => $kode_kategori_produk,
    'nama_kategori_produk'  => $nama_kategori_produk,
    'keterangan'            => $keterangan !== '' ? $keterangan : null,
    'status_aktif'          => $status_aktif,
    'tanggal_dibuat'        => date('Y-m-d H:i:s'),
    'dibuat_oleh'           => $id_pengguna > 0 ? $id_pengguna : null,
    'tanggal_diubah'        => null,
    'diubah_oleh'           => null,
]);

set_flash('success', 'Data kategori produk berhasil disimpan dengan kode ' . $kode_kategori_produk . '.');
redirect_admin('master_setup/kategori_produk');