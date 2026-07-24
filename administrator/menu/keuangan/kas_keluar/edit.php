<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas = keu_id_entitas();
$id = (int) ($_GET['id'] ?? 0);

$data = Capsule::table('tb_kas_keluar')
    ->where('id_entitas', $id_entitas)
    ->where('id_kas_keluar', $id)
    ->first();

if (!$data) {
    set_flash('error', 'Data kas keluar tidak ditemukan.');
    redirect_admin('keuangan/kas-keluar');
}

if ((string) $data->status_posting === 'posted') {
    set_flash('error', 'Data sudah posted dan tidak bisa diedit.');
    redirect_admin('keuangan/kas-keluar/detail&id=' . $id);
}

$mode_kas = 'keluar';
$is_edit = true;

require __DIR__ . '/../_kas_manual_form.php';