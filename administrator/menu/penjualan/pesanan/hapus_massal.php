<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('penjualan/pesanan');
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$ids = $_POST['id_pesanan_penjualan'] ?? [];

$back_url = trim((string) ($_POST['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=penjualan/pesanan');
}

if ($id_entitas <= 0) {
    set_flash('error', 'Sesi entitas tidak valid. Silakan login ulang.');
    header('Location: ' . $back_url);
    exit;
}

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih pesanan penjualan yang akan dihapus.');
    header('Location: ' . $back_url);
    exit;
}

$ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
})));

if (count($ids) === 0) {
    set_flash('error', 'Data pesanan yang dipilih tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

try {
    $jumlah_terhapus = 0;
    $jumlah_dilewati = 0;

    Capsule::connection()->transaction(function () use (
        $ids,
        $id_entitas,
        &$jumlah_terhapus,
        &$jumlah_dilewati
    ) {
        foreach ($ids as $id_pesanan_penjualan) {
            $pesanan = Capsule::table('tb_pesanan_penjualan')
                ->where('id_entitas', $id_entitas)
                ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
                ->lockForUpdate()
                ->first();

            if (!$pesanan) {
                $jumlah_dilewati++;
                continue;
            }

            if ((string) ($pesanan->status_pesanan ?? '') !== 'draft') {
                $jumlah_dilewati++;
                continue;
            }

            $sudah_dipakai_penyerahan = Capsule::table('tb_penyerahan_penjualan')
                ->where('id_entitas', $id_entitas)
                ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
                ->exists();

            if ($sudah_dipakai_penyerahan) {
                $jumlah_dilewati++;
                continue;
            }

            $sudah_dipakai_produksi = Capsule::table('tb_perintah_produksi')
                ->where('id_entitas', $id_entitas)
                ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
                ->exists();

            if ($sudah_dipakai_produksi) {
                $jumlah_dilewati++;
                continue;
            }

            Capsule::table('tb_pesanan_penjualan_detail')
                ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
                ->delete();

            Capsule::table('tb_pesanan_penjualan')
                ->where('id_entitas', $id_entitas)
                ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
                ->delete();

            $jumlah_terhapus++;
        }
    });

    if ($jumlah_terhapus > 0 && $jumlah_dilewati > 0) {
        set_flash(
            'success',
            'Sebagian pesanan berhasil dihapus. Terhapus: ' .
            $jumlah_terhapus .
            ', dilewati: ' .
            $jumlah_dilewati .
            '. Hanya pesanan draft yang belum dipakai yang bisa dihapus.'
        );
    } elseif ($jumlah_terhapus > 0) {
        set_flash('success', 'Pesanan penjualan terpilih berhasil dihapus.');
    } else {
        set_flash('error', 'Tidak ada pesanan yang dihapus. Hanya pesanan draft yang belum dipakai yang bisa dihapus.');
    }
} catch (Throwable $e) {
    set_flash('error', 'Gagal hapus massal pesanan penjualan: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;