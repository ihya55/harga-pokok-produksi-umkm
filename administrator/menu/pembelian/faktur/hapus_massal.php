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
$ids = $_POST['id_faktur_pembelian'] ?? [];

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih data faktur yang akan dihapus.');
    redirect_admin('pembelian/faktur');
}

try {
    Capsule::connection()->transaction(function () use ($ids, $id_entitas) {
        foreach ($ids as $id) {
            $id_faktur_pembelian = (int) $id;

            $row = FakturPembelianORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('status_faktur', 'draft')
                ->find($id_faktur_pembelian);

            if (!$row) {
                continue;
            }

            FakturPembelianDetailORM::query()
                ->where('id_faktur_pembelian', $id_faktur_pembelian)
                ->delete();

            $row->delete();
        }
    });

    set_flash('success', 'Data faktur terpilih berhasil dihapus. Data yang sudah diposting otomatis dilewati.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal hapus massal faktur pembelian: ' . $e->getMessage());
}

redirect_admin('pembelian/faktur');