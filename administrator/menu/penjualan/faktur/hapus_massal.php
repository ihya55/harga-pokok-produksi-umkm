<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$ids = $_POST['id_faktur_penjualan'] ?? [];
$back_url = trim((string) ($_POST['back_url'] ?? ''));

function faktur_hapus_massal_kembali(string $back_url = ''): void
{
    if ($back_url !== '') {
        header('Location: ' . $back_url);
        exit;
    }

    header('Location: ' . admin_url('index.php?menu=penjualan/faktur'));
    exit;
}

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih minimal 1 piutang draft yang ingin dihapus.');
    faktur_hapus_massal_kembali($back_url);
}

$ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));

if (count($ids) === 0) {
    set_flash('error', 'ID piutang yang dipilih tidak valid.');
    faktur_hapus_massal_kembali($back_url);
}

try {
    $jumlah_hapus = 0;
    $jumlah_gagal = 0;

    Capsule::connection()->transaction(function () use ($id_entitas, $ids, &$jumlah_hapus, &$jumlah_gagal) {
        $fakturList = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->whereIn('id_faktur_penjualan', $ids)
            ->get();

        foreach ($fakturList as $faktur) {
            if ((string) $faktur->status_faktur !== 'draft') {
                $jumlah_gagal++;
                continue;
            }

            $adaPembayaran = Capsule::table('tb_pembayaran_penjualan')
                ->where('id_entitas', $id_entitas)
                ->where('id_faktur_penjualan', (int) $faktur->id_faktur_penjualan)
                ->where('status_posting', '<>', 'batal')
                ->exists();

            if ($adaPembayaran) {
                $jumlah_gagal++;
                continue;
            }

            Capsule::table('tb_faktur_penjualan_detail')
                ->where('id_faktur_penjualan', (int) $faktur->id_faktur_penjualan)
                ->delete();

            Capsule::table('tb_faktur_penjualan')
                ->where('id_entitas', $id_entitas)
                ->where('id_faktur_penjualan', (int) $faktur->id_faktur_penjualan)
                ->delete();

            $jumlah_hapus++;
        }
    });

    if ($jumlah_hapus > 0 && $jumlah_gagal > 0) {
        set_flash('warning', $jumlah_hapus . ' piutang berhasil dihapus. ' . $jumlah_gagal . ' piutang tidak bisa dihapus.');
    } elseif ($jumlah_hapus > 0) {
        set_flash('success', $jumlah_hapus . ' piutang draft berhasil dihapus.');
    } else {
        set_flash('error', 'Tidak ada piutang yang bisa dihapus.');
    }
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

faktur_hapus_massal_kembali($back_url);