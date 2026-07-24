<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pajak = (int) ($_GET['id'] ?? 0);

$row = PajakORM::query()->where('id_entitas', $id_entitas)->find($id_pajak);
if (!$row) {
    set_flash('error', 'Data pajak tidak ditemukan.');
    redirect_admin('master_setup/pajak');
}

$data_form = [
    'id_pajak' => (int) $row->id_pajak,
    'kode_pajak' => (string) $row->kode_pajak,
    'nama_pajak' => (string) $row->nama_pajak,
    'persentase' => (string) $row->persentase,
    'is_default' => (string) $row->is_default,
    'status_aktif' => (string) $row->status_aktif,
    'keterangan' => (string) ($row->keterangan ?? ''),
];

$page_title = 'Edit Pajak / PPN';
$page_subtitle = 'Ubah persentase pajak default transaksi.';
$form_action = admin_url('menu/master_setup/pajak/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';
