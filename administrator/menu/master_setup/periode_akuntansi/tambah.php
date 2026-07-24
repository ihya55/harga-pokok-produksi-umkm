<?php
$data_form = [
    'tahun'           => date('Y'),
    'bulan'           => date('n'),
    'tanggal_mulai'   => '',
    'tanggal_selesai' => '',
    'status_periode'  => 'terbuka',
];

$page_subtitle = 'Tambah data periode akuntansi baru';
$form_action = admin_url('menu/master_setup/periode_akuntansi/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';