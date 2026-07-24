<?php
require_once __DIR__ . '/../../../../orm/PemasokORM.php';

$id_pemasok = (int) ($_GET['id'] ?? 0);

$row = PemasokORM::query()
    ->where('id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->find($id_pemasok);

if (!$row) {
    set_flash('error', 'Data pemasok tidak ditemukan.');
    redirect_admin('master_setup/pemasok');
}

$data_form = [
    'id_pemasok'    => (int) $row->id_pemasok,
    'kode_pemasok'  => (string) $row->kode_pemasok,
    'nama_pemasok'  => (string) $row->nama_pemasok,
    'alamat'        => (string) ($row->alamat ?? ''),
    'no_hp'         => (string) ($row->no_hp ?? ''),
    'email'         => (string) ($row->email ?? ''),
    'tempo_hari'    => (string) ($row->tempo_hari ?? '0'),
    'status_aktif'  => (string) $row->status_aktif,
];

$page_subtitle = 'Ubah data pemasok';
$form_action = admin_url('menu/master_setup/pemasok/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';