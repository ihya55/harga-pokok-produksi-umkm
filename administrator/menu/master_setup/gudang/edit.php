<?php
$id_gudang = (int) ($_GET['id'] ?? 0);

$row = GudangORM::query()
    ->where('id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->find($id_gudang);

if (!$row) {
    set_flash('error', 'Data gudang tidak ditemukan.');
    redirect_admin('master_setup/gudang');
}

$data_form = [
    'id_gudang'    => (int) $row->id_gudang,
    'kode_gudang'  => (string) $row->kode_gudang,
    'nama_gudang'  => (string) $row->nama_gudang,
    'jenis_gudang' => (string) $row->jenis_gudang,
    'alamat'       => (string) ($row->alamat ?? ''),
    'status_aktif' => (string) $row->status_aktif,
];

$page_subtitle = 'Ubah data gudang';
$form_action = admin_url('menu/master_setup/gudang/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';