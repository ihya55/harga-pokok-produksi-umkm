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

function pesanan_online_konfirmasi_kembali(int $id = 0): void
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
    pesanan_online_konfirmasi_kembali();
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

        if ((string) $pesanan->status_pesanan !== 'draft') {
            throw new RuntimeException('Hanya pesanan online draft yang bisa dikonfirmasi.');
        }

        $jumlahDetail = Capsule::table('tb_pesanan_penjualan_detail')
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->count();

        if ($jumlahDetail <= 0) {
            throw new RuntimeException('Detail pesanan online masih kosong.');
        }

        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->update([
                'status_pesanan' => 'terkonfirmasi',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'Pesanan online berhasil dikonfirmasi.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

pesanan_online_konfirmasi_kembali($id_pesanan_penjualan);