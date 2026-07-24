<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_periode = (int) ($_GET['id'] ?? 0);

$row = PeriodeAkuntansiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_periode);

if (!$row) {
    set_flash('error', 'Data periode akuntansi tidak ditemukan.');
    redirect_admin('master_setup/periode_akuntansi');
}

$data_form = [
    'id_periode'      => (int) $row->id_periode,
    'tahun'           => (string) $row->tahun,
    'bulan'           => (string) $row->bulan,
    'tanggal_mulai'   => (string) $row->tanggal_mulai,
    'tanggal_selesai' => (string) $row->tanggal_selesai,
    'status_periode'  => (string) $row->status_periode,
];

$page_subtitle = 'Ubah data periode akuntansi';
$form_action = admin_url('menu/master_setup/periode_akuntansi/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';