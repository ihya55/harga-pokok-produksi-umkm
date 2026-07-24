<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_coa = (int) ($_GET['id'] ?? 0);

$row = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_coa);

if (!$row) {
    set_flash('error', 'Data COA tidak ditemukan.');
    redirect_admin('master_setup/coa');
}

$coa_induk_options = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->where('id_coa', '!=', $id_coa)
    ->orderBy('kode_coa', 'asc')
    ->get();

$data_form = [
    'id_coa'               => (int) $row->id_coa,
    'kode_coa'             => (string) $row->kode_coa,
    'nama_coa'             => (string) $row->nama_coa,
    'id_coa_induk'         => (string) ($row->id_coa_induk ?? ''),
    'level_coa'            => (string) $row->level_coa,
    'kategori_coa'         => (string) $row->kategori_coa,
    'posisi_saldo_normal'  => (string) $row->posisi_saldo_normal,
    'boleh_transaksi'      => (string) $row->boleh_transaksi,
    'status_aktif'         => (string) $row->status_aktif,
];

$page_subtitle = 'Ubah data COA';
$form_action = admin_url('menu/master_setup/coa/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';