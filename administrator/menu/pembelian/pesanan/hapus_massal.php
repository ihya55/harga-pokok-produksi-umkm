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
$ids = $_POST['id_pesanan_pembelian'] ?? [];

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih data yang akan dihapus.');
    redirect_admin('pembelian/pesanan');
}

try {
    Capsule::connection()->transaction(function () use ($ids, $id_entitas) {
        foreach ($ids as $id) {
            $id_pesanan_pembelian = (int) $id;

            $row = PesananPembelianORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('status_pesanan', 'draft')
                ->find($id_pesanan_pembelian);

            if (!$row) {
                continue;
            }

            PesananPembelianDetailORM::query()
                ->where('id_pesanan_pembelian', $id_pesanan_pembelian)
                ->delete();

            $row->delete();
        }
    });

    set_flash('success', 'Data terpilih berhasil dihapus. Data yang sudah terkonfirmasi otomatis dilewati.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

redirect_admin('pembelian/pesanan');