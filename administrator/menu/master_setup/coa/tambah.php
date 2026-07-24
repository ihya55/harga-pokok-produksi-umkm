<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$coa_induk_options = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('kode_coa', 'asc')
    ->get();

$data_form = [
    'kode_coa'             => '',
    'nama_coa'             => '',
    'id_coa_induk'         => '',
    'level_coa'            => '1',
    'kategori_coa'         => '',
    'posisi_saldo_normal'  => 'debit',
    'boleh_transaksi'      => '1',
    'status_aktif'         => '1',
];

$page_subtitle = 'Tambah data COA baru';
$form_action = admin_url('menu/master_setup/coa/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';