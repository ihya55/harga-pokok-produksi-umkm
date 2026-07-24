<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas = keu_id_entitas();
$id = (int) ($_GET['id'] ?? 0);

$data = Capsule::table('tb_kas_masuk')
    ->where('id_entitas', $id_entitas)
    ->where('id_kas_masuk', $id)
    ->first();

if (!$data) {
    set_flash('error', 'Data kas masuk tidak ditemukan.');
    redirect_admin('keuangan/kas-masuk');
}

if ((string) $data->status_posting === 'posted') {
    set_flash('error', 'Data sudah posted dan tidak bisa diedit.');
    redirect_admin('keuangan/kas-masuk/detail&id=' . $id);
}

$mode_kas = 'masuk';
$is_edit = true;

require __DIR__ . '/../_kas_manual_form.php';