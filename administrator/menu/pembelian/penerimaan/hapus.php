<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PenerimaanPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PenerimaanPembelianDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_penerimaan_pembelian = (int) ($_GET['id'] ?? 0);

$row = PenerimaanPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_penerimaan_pembelian);

if (!$row) {
    set_flash('error', 'Data penerimaan pembelian tidak ditemukan.');
    redirect_admin('pembelian/penerimaan');
}

if ((string) $row->status_penerimaan !== 'draft') {
    set_flash('error', 'Penerimaan yang sudah diposting tidak bisa dihapus.');
    redirect_admin('pembelian/penerimaan');
}

try {
    Capsule::connection()->transaction(function () use ($id_penerimaan_pembelian, $row) {
        PenerimaanPembelianDetailORM::query()
            ->where('id_penerimaan_pembelian', $id_penerimaan_pembelian)
            ->delete();

        $row->delete();
    });

    set_flash('success', 'Penerimaan pembelian berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal menghapus penerimaan pembelian: ' . $e->getMessage());
}

redirect_admin('pembelian/penerimaan');