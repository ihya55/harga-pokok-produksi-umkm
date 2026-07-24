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
$id_pesanan_penjualan = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

function pesanan_online_batal_kembali(int $id = 0): void
{
    if ($id > 0) {
        header('Location: ' . admin_url('index.php?menu=penjualan/pesanan-online/detail&id=' . $id));
        exit;
    }

    header('Location: ' . admin_url('index.php?menu=penjualan/pesanan-online'));
    exit;
}

if ($id_pesanan_penjualan <= 0) {
    set_flash('error', 'ID pesanan online tidak valid.');
    pesanan_online_batal_kembali();
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $id_pesanan_penjualan) {
        $pesanan = Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->where('sumber_pesanan', 'website')
            ->lockForUpdate()
            ->first();

        if (!$pesanan) {
            throw new RuntimeException('Pesanan online tidak ditemukan.');
        }

        if (!in_array((string) $pesanan->status_pesanan, ['draft', 'terkonfirmasi'], true)) {
            throw new RuntimeException('Pesanan online tidak bisa dibatalkan karena sudah diproses/selesai.');
        }

        $sudahAdaPenyerahan = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->whereIn('status_penyerahan', ['draft', 'posted'])
            ->exists();

        if ($sudahAdaPenyerahan) {
            throw new RuntimeException('Pesanan tidak bisa dibatalkan karena sudah memiliki penyerahan.');
        }

        $sudahAdaProduksi = Capsule::table('tb_perintah_produksi')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->where('status_produksi', '<>', 'batal')
            ->exists();

        if ($sudahAdaProduksi) {
            throw new RuntimeException('Pesanan tidak bisa dibatalkan karena sudah terkait perintah produksi.');
        }

        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->update([
                'status_pesanan' => 'batal',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'Pesanan online berhasil dibatalkan.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

pesanan_online_batal_kembali($id_pesanan_penjualan);