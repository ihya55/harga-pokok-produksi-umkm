<?php
$data_form = [
    'kode_pemasok' => 'Otomatis saat disimpan',
    'nama_pemasok' => '',
    'alamat'       => '',
    'no_hp'        => '',
    'email'        => '',
    'tempo_hari'   => '0',
    'status_aktif' => '1',
];

$page_subtitle = 'Tambah data pemasok baru';
$form_action = admin_url('menu/master_setup/pemasok/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';