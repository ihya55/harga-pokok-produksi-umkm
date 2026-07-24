<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? $_POST['back_url'] ?? ''));

function faktur_hapus_kembali(string $back_url = ''): void
{
    if ($back_url !== '') {
        header('Location: ' . $back_url);
        exit;
    }

    header('Location: ' . admin_url('index.php?menu=penjualan/faktur'));
    exit;
}

if ($id_faktur_penjualan <= 0) {
    set_flash('error', 'ID piutang pelanggan tidak valid.');
    faktur_hapus_kembali($back_url);
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_faktur_penjualan) {
        $faktur = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->first();

        if (!$faktur) {
            throw new RuntimeException('Data piutang pelanggan tidak ditemukan.');
        }

        if ((string) $faktur->status_faktur !== 'draft') {
            throw new RuntimeException('Hanya piutang draft yang boleh dihapus.');
        }

        $adaPembayaran = Capsule::table('tb_pembayaran_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->where('status_posting', '<>', 'batal')
            ->exists();

        if ($adaPembayaran) {
            throw new RuntimeException('Faktur tidak bisa dihapus karena sudah memiliki pembayaran.');
        }

        Capsule::table('tb_faktur_penjualan_detail')
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->delete();

        Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->delete();
    });

    set_flash('success', 'Piutang pelanggan draft berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

faktur_hapus_kembali($back_url);