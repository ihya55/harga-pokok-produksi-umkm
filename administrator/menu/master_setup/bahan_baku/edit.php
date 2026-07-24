<?php
require_once __DIR__ . '/helpers_bahan_baku.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_bahan_baku = (int) ($_GET['id'] ?? 0);

$row = BahanBakuORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_bahan_baku);

if (!$row) {
    set_flash('error', 'Data bahan baku tidak ditemukan.');
    redirect_admin('master_setup/bahan_baku');
}

$satuan_options = SatuanORM::query()
    ->orderBy('nama_satuan', 'asc')
    ->get();

$coa_options = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('boleh_transaksi', 1)
    ->orderBy('kode_coa', 'asc')
    ->get();

$data_form = [
    'id_bahan_baku'      => (int) $row->id_bahan_baku,
    'kode_bahan_baku'    => (string) $row->kode_bahan_baku,
    'nama_bahan_baku'    => (string) $row->nama_bahan_baku,
    'id_satuan'          => (string) $row->id_satuan,
    'harga_standar'      => number_format((float) $row->harga_standar, 2, '.', ''),
    'stok_minimum'       => (string) ((int) $row->stok_minimum),
    'id_coa_persediaan'  => (string) ($row->id_coa_persediaan ?? (default_coa_bahan_baku($id_entitas) ?? '')),
    'status_aktif'       => (string) $row->status_aktif,
];

$page_subtitle = 'Ubah data bahan baku';
$form_action = admin_url('menu/master_setup/bahan_baku/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';