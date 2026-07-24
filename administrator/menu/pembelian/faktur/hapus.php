<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/FakturPembelianORM.php';
require_once __DIR__ . '/../../../../orm/FakturPembelianDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_faktur_pembelian = (int) ($_GET['id'] ?? 0);

$row = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_faktur_pembelian);

if (!$row) {
    set_flash('error', 'Data faktur pembelian tidak ditemukan.');
    redirect_admin('pembelian/faktur');
}

if ((string) $row->status_faktur !== 'draft') {
    set_flash('error', 'Faktur pembelian yang sudah diposting tidak bisa dihapus.');
    redirect_admin('pembelian/faktur');
}

try {
    Capsule::connection()->transaction(function () use ($id_faktur_pembelian, $row) {
        FakturPembelianDetailORM::query()
            ->where('id_faktur_pembelian', $id_faktur_pembelian)
            ->delete();

        $row->delete();
    });

    set_flash('success', 'Faktur pembelian berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal menghapus faktur pembelian: ' . $e->getMessage());
}

redirect_admin('pembelian/faktur');