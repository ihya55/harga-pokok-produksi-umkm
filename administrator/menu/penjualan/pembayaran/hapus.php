<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pembayaran_penjualan = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? $_POST['back_url'] ?? ''));

function pembayaran_penjualan_hapus_kembali(string $back_url = ''): void
{
    if ($back_url !== '') {
        header('Location: ' . $back_url);
        exit;
    }

    header('Location: ' . admin_url('index.php?menu=penjualan/pembayaran'));
    exit;
}

if ($id_pembayaran_penjualan <= 0) {
    set_flash('error', 'ID pembayaran penjualan tidak valid.');
    pembayaran_penjualan_hapus_kembali($back_url);
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pembayaran_penjualan) {
        $pembayaran = Capsule::table('tb_pembayaran_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pembayaran_penjualan', $id_pembayaran_penjualan)
            ->first();

        if (!$pembayaran) {
            throw new RuntimeException('Data pembayaran penjualan tidak ditemukan.');
        }

        if ((string) $pembayaran->status_posting !== 'draft') {
            throw new RuntimeException('Hanya pembayaran draft yang boleh dihapus.');
        }

        Capsule::table('tb_pembayaran_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pembayaran_penjualan', $id_pembayaran_penjualan)
            ->delete();
    });

    set_flash('success', 'Pembayaran penjualan draft berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

pembayaran_penjualan_hapus_kembali($back_url);