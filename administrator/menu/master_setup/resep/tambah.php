<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$produk_options = ProdukORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_produk', 1)
    ->orderBy('nama_produk', 'asc')
    ->get();

$bahan_options = BahanBakuORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_bahan_baku', 'asc')
    ->get();

$satuan_options = SatuanORM::query()
    ->orderBy('nama_satuan', 'asc')
    ->get();

$data_form = [
    'kode_resep'    => 'Otomatis saat disimpan',
    'id_produk'     => '',
    'nama_resep'    => '',
    'jumlah_hasil'  => '1',
    'versi_resep'   => 'V1',
    'status_aktif'  => '1',
];

$detail_rows = [];

$page_subtitle = 'Tambah data resep baru';
$form_action = admin_url('menu/master_setup/resep/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';