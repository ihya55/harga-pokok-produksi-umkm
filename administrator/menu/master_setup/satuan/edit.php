<?php
$id_satuan = (int) ($_GET['id'] ?? 0);

$row = SatuanORM::find($id_satuan);

if (!$row) {
    set_flash('error', 'Data satuan tidak ditemukan.');
    redirect_admin('master_setup/satuan');
}

$data_form = [
    'id_satuan'   => (int) $row->id_satuan,
    'nama_satuan' => (string) $row->nama_satuan,
    'keterangan'  => (string) ($row->keterangan ?? ''),
];

$page_subtitle = 'Ubah data satuan';
$form_action = admin_url('menu/master_setup/satuan/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';