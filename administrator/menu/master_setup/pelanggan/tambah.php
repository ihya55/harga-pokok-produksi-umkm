<?php
$data_form = [
    'kode_pelanggan'  => 'Otomatis saat disimpan',
    'nama_pelanggan'  => '',
    'alamat'          => '',
    'no_hp'           => '',
    'email'           => '',
    'jenis_pelanggan' => 'umum',
    'batas_piutang'   => '0',
    'tempo_hari'      => '0',
    'status_aktif'    => '1',
];

$page_subtitle = 'Tambahkan data pelanggan baru';
$form_action = admin_url('menu/master_setup/pelanggan/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';