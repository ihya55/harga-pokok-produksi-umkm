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
$id_kategori_produk    = (int) ($_POST['id_kategori_produk'] ?? 0);
$nama_kategori_produk  = trim((string) ($_POST['nama_kategori_produk'] ?? ''));
$keterangan            = trim((string) ($_POST['keterangan'] ?? ''));
$status_aktif          = (int) ($_POST['status_aktif'] ?? 1);

$row = KategoriProdukORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_kategori_produk);

if (!$row) {
    set_flash('error', 'Data kategori produk tidak ditemukan.');
    redirect_admin('master_setup/kategori_produk');
}

if ($nama_kategori_produk === '') {
    set_flash('error', 'Nama kategori produk wajib diisi.');
    redirect_admin('master_setup/kategori_produk/edit&id=' . $id_kategori_produk);
}

$row->nama_kategori_produk = $nama_kategori_produk;
$row->keterangan           = $keterangan !== '' ? $keterangan : null;
$row->status_aktif         = $status_aktif;
$row->tanggal_diubah       = date('Y-m-d H:i:s');
$row->diubah_oleh          = $id_pengguna > 0 ? $id_pengguna : null;
$row->save();

set_flash('success', 'Data kategori produk berhasil diperbarui.');
redirect_admin('master_setup/kategori_produk');