<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('produksi/perintah');
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$ids = $_POST['id_perintah_produksi'] ?? [];

$back_url = trim((string) ($_POST['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/perintah');
}

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih data perintah produksi yang akan dihapus.');
    header('Location: ' . $back_url);
    exit;
}

$ids = array_values(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
}));

if (count($ids) === 0) {
    set_flash('error', 'ID perintah produksi tidak valid.');
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
        foreach ($ids as $id_perintah_produksi) {
            $row = PerintahProduksiORM::query()
                ->where('id_entitas', $id_entitas)
                ->find((int) $id_perintah_produksi);

            if (!$row) {
                $jumlah_dilewati++;
                continue;
            }

            if ((string) $row->status_produksi !== 'draft') {
                $jumlah_dilewati++;
                continue;
            }

            try {
                $row->delete();
                $jumlah_terhapus++;
            } catch (Throwable $e) {
                $jumlah_dilewati++;
            }
        }
    });

    if ($jumlah_terhapus > 0 && $jumlah_dilewati > 0) {
        set_flash('success', 'Sebagian data berhasil dihapus. Terhapus: ' . $jumlah_terhapus . ', dilewati: ' . $jumlah_dilewati . '. Data posted atau data yang sudah dipakai tidak bisa dihapus.');
    } elseif ($jumlah_terhapus > 0) {
        set_flash('success', 'Data perintah produksi terpilih berhasil dihapus.');
    } else {
        set_flash('error', 'Tidak ada data yang dihapus. Data posted atau data yang sudah dipakai tidak bisa dihapus.');
    }
} catch (Throwable $e) {
    set_flash('error', 'Gagal hapus massal perintah produksi: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;