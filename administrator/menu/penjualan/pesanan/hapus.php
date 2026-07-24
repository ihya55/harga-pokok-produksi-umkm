<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=penjualan/pesanan');
}

if ($id_entitas <= 0 || $id_pesanan_penjualan <= 0) {
    set_flash('error', 'Data pesanan tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pesanan_penjualan) {
        $pesanan = Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$pesanan) {
            throw new RuntimeException('Pesanan penjualan tidak ditemukan.');
        }

        if ((string) ($pesanan->status_pesanan ?? '') !== 'draft') {
            throw new RuntimeException('Hanya pesanan draft yang boleh dihapus.');
        }

        $sudah_dipakai_penyerahan = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->exists();

        if ($sudah_dipakai_penyerahan) {
            throw new RuntimeException('Pesanan tidak bisa dihapus karena sudah dipakai pada penyerahan penjualan.');
        }

        $sudah_dipakai_produksi = Capsule::table('tb_perintah_produksi')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->exists();

        if ($sudah_dipakai_produksi) {
            throw new RuntimeException('Pesanan tidak bisa dihapus karena sudah dipakai pada perintah produksi.');
        }

        Capsule::table('tb_pesanan_penjualan_detail')
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->delete();

        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->delete();
    });

    set_flash('success', 'Pesanan penjualan draft berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal menghapus pesanan penjualan: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;