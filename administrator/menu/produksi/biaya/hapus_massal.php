<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/BiayaProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('produksi/biaya');
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$ids = $_POST['id_biaya_produksi'] ?? [];

$back_url = trim((string) ($_POST['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/biaya');
}

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih data biaya produksi yang akan dihapus.');
    header('Location: ' . $back_url);
    exit;
}

$ids = array_values(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
}));

if (count($ids) === 0) {
    set_flash('error', 'ID biaya produksi tidak valid.');
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
        foreach ($ids as $id_biaya_produksi) {
            $row = BiayaProduksiORM::query()
                ->where('id_entitas', $id_entitas)
                ->find((int) $id_biaya_produksi);

            if (!$row) {
                $jumlah_dilewati++;
                continue;
            }

            if ((string) $row->status_posting !== 'draft') {
                $jumlah_dilewati++;
                continue;
            }

            $row->delete();
            $jumlah_terhapus++;
        }
    });

    if ($jumlah_terhapus > 0 && $jumlah_dilewati > 0) {
        set_flash('success', 'Sebagian data berhasil dihapus. Terhapus: ' . $jumlah_terhapus . ', dilewati: ' . $jumlah_dilewati . '. Data posted tidak bisa dihapus.');
    } elseif ($jumlah_terhapus > 0) {
        set_flash('success', 'Data biaya produksi terpilih berhasil dihapus.');
    } else {
        set_flash('error', 'Tidak ada data yang dihapus. Data posted tidak bisa dihapus.');
    }
} catch (Throwable $e) {
    set_flash('error', 'Gagal hapus massal biaya produksi: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;