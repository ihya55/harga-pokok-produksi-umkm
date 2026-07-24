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
$id_penyerahan_penjualan = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? $_POST['back_url'] ?? ''));

function penyerahan_hapus_kembali(string $back_url = ''): void
{
    if ($back_url !== '') {
        header('Location: ' . $back_url);
        exit;
    }

    header('Location: ' . admin_url('index.php?menu=penjualan/penyerahan'));
    exit;
}

if ($id_penyerahan_penjualan <= 0) {
    set_flash('error', 'ID penyerahan penjualan tidak valid.');
    penyerahan_hapus_kembali($back_url);
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $id_penyerahan_penjualan) {
        $penyerahan = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->first();

        if (!$penyerahan) {
            throw new RuntimeException('Data penyerahan penjualan tidak ditemukan.');
        }

        if ((string) $penyerahan->status_penyerahan !== 'draft') {
            throw new RuntimeException('Hanya penyerahan berstatus draft yang boleh dihapus.');
        }

        $sudahDifaktur = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->where('status_faktur', '<>', 'batal')
            ->exists();

        if ($sudahDifaktur) {
            throw new RuntimeException('Penyerahan tidak bisa dihapus karena sudah digunakan pada faktur penjualan.');
        }

        Capsule::table('tb_penyerahan_penjualan_detail')
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->delete();

        Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
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
    });

    set_flash('success', 'Penyerahan penjualan draft berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

penyerahan_hapus_kembali($back_url);