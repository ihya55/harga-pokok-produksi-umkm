<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PesananPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PesananPembelianDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pesanan_pembelian = (int) ($_GET['id'] ?? 0);

$row = PesananPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pesanan_pembelian);

if (!$row) {
    set_flash('error', 'Data pesanan pembelian tidak ditemukan.');
    redirect_admin('pembelian/pesanan');
}

if ((string) $row->status_pesanan !== 'draft') {
    set_flash('error', 'Pesanan pembelian yang sudah terkonfirmasi tidak bisa dihapus.');
    redirect_admin('pembelian/pesanan');
}

try {
    Capsule::connection()->transaction(function () use ($id_pesanan_pembelian, $row) {
        PesananPembelianDetailORM::query()
            ->where('id_pesanan_pembelian', $id_pesanan_pembelian)
            ->delete();

        $row->delete();
    });

    set_flash('success', 'Pesanan pembelian berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

redirect_admin('pembelian/pesanan');