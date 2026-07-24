<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas = keu_id_entitas();
$id_jurnal = (int) ($_GET['id'] ?? 0);

$data = Capsule::table('tb_jurnal')
    ->where('id_entitas', $id_entitas)
    ->where('id_jurnal', $id_jurnal)
    ->first();

if (!$data) {
    set_flash('error', 'Data jurnal tidak ditemukan.');
    redirect_admin('keuangan/jurnal');
}

if ((string) $data->status_jurnal === 'posted') {
    set_flash('error', 'Jurnal sudah posted dan tidak bisa diedit.');
    redirect_admin('keuangan/jurnal/detail&id=' . $id_jurnal);
}

if (!in_array((string) $data->kode_jenis_transaksi, ['JURNAL_MANUAL', 'SALDO_AWAL_COA'], true)) {
    set_flash('error', 'Jurnal dari transaksi sumber tidak boleh diedit manual.');
    redirect_admin('keuangan/jurnal/detail&id=' . $id_jurnal);
}

$detail = Capsule::table('tb_jurnal_detail')
    ->where('id_jurnal', $id_jurnal)
    ->orderBy('urutan', 'asc')
    ->orderBy('id_jurnal_detail', 'asc')
    ->get();

$is_edit = true;

require __DIR__ . '/_form.php';