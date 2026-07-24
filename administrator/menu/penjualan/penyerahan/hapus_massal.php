<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$ids = $_POST['id_penyerahan_penjualan'] ?? [];
$back_url = trim((string) ($_POST['back_url'] ?? ''));

function penyerahan_hapus_massal_kembali(string $back_url = ''): void
{
    if ($back_url !== '') {
        header('Location: ' . $back_url);
        exit;
    }

    header('Location: ' . admin_url('index.php?menu=penjualan/penyerahan'));
    exit;
}

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih minimal 1 penyerahan draft yang ingin dihapus.');
    penyerahan_hapus_massal_kembali($back_url);
}

$ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));

if (count($ids) === 0) {
    set_flash('error', 'ID penyerahan yang dipilih tidak valid.');
    penyerahan_hapus_massal_kembali($back_url);
}

try {
    $jumlah_hapus = 0;
    $jumlah_gagal = 0;

    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $ids,
        &$jumlah_hapus,
        &$jumlah_gagal
    ) {
        $penyerahanList = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->whereIn('id_penyerahan_penjualan', $ids)
            ->get();

        foreach ($penyerahanList as $penyerahan) {
            if ((string) $penyerahan->status_penyerahan !== 'draft') {
                $jumlah_gagal++;
                continue;
            }

            $sudahDifaktur = Capsule::table('tb_faktur_penjualan')
                ->where('id_entitas', $id_entitas)
                ->where('id_penyerahan_penjualan', (int) $penyerahan->id_penyerahan_penjualan)
                ->where('status_faktur', '<>', 'batal')
                ->exists();

            if ($sudahDifaktur) {
                $jumlah_gagal++;
                continue;
            }

            Capsule::table('tb_penyerahan_penjualan_detail')
                ->where('id_penyerahan_penjualan', (int) $penyerahan->id_penyerahan_penjualan)
                ->delete();

            Capsule::table('tb_penyerahan_penjualan')
                ->where('id_entitas', $id_entitas)
                ->where('id_penyerahan_penjualan', (int) $penyerahan->id_penyerahan_penjualan)
                ->delete();

            if (!empty($penyerahan->id_pesanan_penjualan)) {
                $masihDipakai = Capsule::table('tb_penyerahan_penjualan')
                    ->where('id_entitas', $id_entitas)
                    ->where('id_pesanan_penjualan', (int) $penyerahan->id_pesanan_penjualan)
                    ->whereIn('status_penyerahan', ['draft', 'posted'])
                    ->exists();

                if (!$masihDipakai) {
                    Capsule::table('tb_pesanan_penjualan')
                        ->where('id_entitas', $id_entitas)
                        ->where('id_pesanan_penjualan', (int) $penyerahan->id_pesanan_penjualan)
                        ->where('status_pesanan', 'diproses')
                        ->update([
                            'status_pesanan' => 'terkonfirmasi',
                            'tanggal_diubah' => date('Y-m-d H:i:s'),
                            'diubah_oleh' => $id_pengguna ?: null,
                        ]);
                }
            }

            $jumlah_hapus++;
        }
    });

    if ($jumlah_hapus > 0 && $jumlah_gagal > 0) {
        set_flash('warning', $jumlah_hapus . ' data berhasil dihapus. ' . $jumlah_gagal . ' data tidak bisa dihapus karena bukan draft atau sudah difaktur.');
    } elseif ($jumlah_hapus > 0) {
        set_flash('success', $jumlah_hapus . ' data penyerahan draft berhasil dihapus.');
    } else {
        set_flash('error', 'Tidak ada data yang bisa dihapus. Hanya penyerahan draft dan belum difaktur yang boleh dihapus.');
    }
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

penyerahan_hapus_massal_kembali($back_url);