<?php
require_once __DIR__ . '/../../../../orm/KategoriProdukORM.php';

$id_kategori_produk = (int) ($_GET['id'] ?? 0);

$row = KategoriProdukORM::query()
    ->where('id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->find($id_kategori_produk);

if (!$row) {
    set_flash('error', 'Data kategori produk tidak ditemukan.');
    redirect_admin('master_setup/kategori_produk');
}

$data_form = [
    'id_kategori_produk'   => (int) $row->id_kategori_produk,
    'kode_kategori_produk' => (string) $row->kode_kategori_produk,
    'nama_kategori_produk' => (string) $row->nama_kategori_produk,
    'keterangan'           => (string) ($row->keterangan ?? ''),
    'status_aktif'         => (string) $row->status_aktif,
];

$page_subtitle = 'Ubah data kategori produk';
$form_action = admin_url('menu/master_setup/kategori_produk/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';