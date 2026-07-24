<?php
$data_form = [
    'kode_gudang'  => 'Otomatis saat disimpan',
    'nama_gudang'  => '',
    'jenis_gudang' => '',
    'alamat'       => '',
    'status_aktif' => '1',
];

$page_subtitle = 'Tambahkan data gudang baru';
$form_action = admin_url('menu/master_setup/gudang/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';