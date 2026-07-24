<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PengambilanBahanORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('produksi/pengambilan-bahan');
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$ids = $_POST['id_pengambilan_bahan'] ?? [];

$back_url = trim((string) ($_POST['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/pengambilan-bahan');
}

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih data pengambilan bahan yang akan dihapus.');
    header('Location: ' . $back_url);
    exit;
}

$ids = array_values(array_filter(array_map('intval', $ids), function ($id) {
    return $id > 0;
}));

try {
    $jumlah_terhapus = 0;
    $jumlah_dilewati = 0;

    Capsule::connection()->transaction(function () use (
        $ids,
        $id_entitas,
        &$jumlah_terhapus,
        &$jumlah_dilewati
    ) {
        foreach ($ids as $id_pengambilan_bahan) {
            $row = PengambilanBahanORM::query()
                ->where('id_entitas', $id_entitas)
                ->find((int) $id_pengambilan_bahan);

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
        set_flash('success', 'Sebagian data berhasil dihapus. Terhapus: ' . $jumlah_terhapus . ', dilewati: ' . $jumlah_dilewati . '. Hanya draft yang bisa dihapus.');
    } elseif ($jumlah_terhapus > 0) {
        set_flash('success', 'Data pengambilan bahan terpilih berhasil dihapus.');
    } else {
        set_flash('error', 'Tidak ada data yang dihapus. Hanya draft yang bisa dihapus.');
    }
} catch (Throwable $e) {
    set_flash('error', 'Gagal hapus massal pengambilan bahan: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;