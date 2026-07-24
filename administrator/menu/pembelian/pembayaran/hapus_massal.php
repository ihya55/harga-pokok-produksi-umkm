<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PembayaranPembelianORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$ids = $_POST['id_pembayaran_pembelian'] ?? [];

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=pembelian/pembayaran');
}

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih data pembayaran yang akan dihapus.');
    header('Location: ' . $back_url);
    exit;
}

try {
    Capsule::connection()->transaction(function () use ($ids, $id_entitas) {
        foreach ($ids as $id) {
            $id_pembayaran_pembelian = (int) $id;

            $row = PembayaranPembelianORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('status_posting', 'draft')
                ->find($id_pembayaran_pembelian);

            if (!$row) {
                continue;
            }

            $row->delete();
        }
    });

    set_flash('success', 'Data pembayaran terpilih berhasil dihapus. Data yang sudah diposting otomatis dilewati.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal hapus massal pembayaran pembelian: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;