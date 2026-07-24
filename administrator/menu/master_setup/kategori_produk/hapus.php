<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/KategoriProdukORM.php';

require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas          = (int) (user_login()['id_entitas'] ?? 0);
$id_kategori_produk  = (int) ($_GET['id'] ?? 0);

$row = KategoriProdukORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_kategori_produk);

if (!$row) {
    set_flash('error', 'Data kategori produk tidak ditemukan.');
    redirect_admin('master_setup/kategori_produk');
}

try {
    $row->delete();
    set_flash('success', 'Data kategori produk berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Data kategori produk tidak bisa dihapus karena masih digunakan di tabel lain.');
}

redirect_admin('master_setup/kategori_produk');