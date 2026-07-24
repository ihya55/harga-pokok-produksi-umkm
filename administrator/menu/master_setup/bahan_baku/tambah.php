<?php
require_once __DIR__ . '/helpers_bahan_baku.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$satuan_options = SatuanORM::query()
    ->orderBy('nama_satuan', 'asc')
    ->get();

$coa_options = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->where('boleh_transaksi', 1)
    ->orderBy('kode_coa', 'asc')
    ->get();

$data_form = [
    'kode_bahan_baku'    => 'Otomatis saat disimpan',
    'nama_bahan_baku'    => '',
    'id_satuan'          => '',
    'harga_standar'      => '0.00',
    'stok_minimum'       => '0',
    'id_coa_persediaan'  => (string) (default_coa_bahan_baku($id_entitas) ?? ''),
    'status_aktif'       => '1',
];

$page_subtitle = 'Tambah data bahan baku baru';
$form_action = admin_url('menu/master_setup/bahan_baku/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';