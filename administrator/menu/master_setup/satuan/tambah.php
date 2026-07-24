<?php
$data_form = [
    'nama_satuan' => '',
    'keterangan'  => '',
];

$page_subtitle = 'Tambahkan data satuan baru';
$form_action = admin_url('menu/master_setup/satuan/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';