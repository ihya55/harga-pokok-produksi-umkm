<?php
$id_pelanggan = (int) ($_GET['id'] ?? 0);

$row = PelangganORM::query()
    ->where('id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->find($id_pelanggan);

if (!$row) {
    set_flash('error', 'Data pelanggan tidak ditemukan.');
    redirect_admin('master_setup/pelanggan');
}

$data_form = [
    'id_pelanggan'    => (int) $row->id_pelanggan,
    'kode_pelanggan'  => (string) $row->kode_pelanggan,
    'nama_pelanggan'  => (string) $row->nama_pelanggan,
    'alamat'          => (string) ($row->alamat ?? ''),
    'no_hp'           => (string) ($row->no_hp ?? ''),
    'email'           => (string) ($row->email ?? ''),
    'jenis_pelanggan' => (string) ($row->jenis_pelanggan ?? 'umum'),
    'batas_piutang'   => (string) ($row->batas_piutang ?? '0'),
    'tempo_hari'      => (string) ($row->tempo_hari ?? '0'),
    'status_aktif'    => (string) $row->status_aktif,
];

$page_subtitle = 'Ubah data pelanggan';
$form_action = admin_url('menu/master_setup/pelanggan/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';