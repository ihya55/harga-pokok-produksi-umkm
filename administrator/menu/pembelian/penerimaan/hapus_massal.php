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
$ids = $_POST['id_penerimaan_pembelian'] ?? [];

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih data penerimaan yang akan dihapus.');
    redirect_admin('pembelian/penerimaan');
}

try {
    Capsule::connection()->transaction(function () use ($ids, $id_entitas) {
        foreach ($ids as $id) {
            $id_penerimaan_pembelian = (int) $id;

            $row = PenerimaanPembelianORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('status_penerimaan', 'draft')
                ->find($id_penerimaan_pembelian);

            if (!$row) {
                continue;
            }

            PenerimaanPembelianDetailORM::query()
                ->where('id_penerimaan_pembelian', $id_penerimaan_pembelian)
                ->delete();

            $row->delete();
        }
    });

    set_flash('success', 'Data penerimaan terpilih berhasil dihapus. Data yang sudah diposting otomatis dilewati.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal hapus massal penerimaan pembelian: ' . $e->getMessage());
}

redirect_admin('pembelian/penerimaan');