<?php
declare(strict_types=1);

$data_form = [
    'kode_pajak' => 'PPN',
    'nama_pajak' => 'Pajak Pertambahan Nilai',
    'persentase' => '11',
    'is_default' => '1',
    'status_aktif' => '1',
    'keterangan' => 'Pajak default yang digunakan otomatis pada faktur.',
];

$page_title = 'Tambah Pajak / PPN';
$page_subtitle = 'Input persentase pajak default yang akan digunakan di transaksi.';
$form_action = admin_url('menu/master_setup/pajak/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';
