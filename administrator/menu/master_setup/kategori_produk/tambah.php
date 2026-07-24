<?php
$data_form = [
    'kode_kategori_produk' => 'Otomatis saat disimpan',
    'nama_kategori_produk' => '',
    'keterangan'           => '',
    'status_aktif'         => '1',
];

$page_subtitle = 'Tambah data kategori produk baru';
$form_action = admin_url('menu/master_setup/kategori_produk/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';