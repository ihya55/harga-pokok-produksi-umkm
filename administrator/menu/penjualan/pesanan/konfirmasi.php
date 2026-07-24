<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id'] ?? 0);

if ($id_entitas <= 0 || $id_pesanan_penjualan <= 0) {
    set_flash('error', 'Data pesanan tidak valid.');
    penjualan_redirect(admin_page_url('penjualan/pesanan'));
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $id_pesanan_penjualan) {
        $pesanan = Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$pesanan) {
            throw new RuntimeException('Pesanan penjualan tidak ditemukan.');
        }

        if ((string) ($pesanan->status_pesanan ?? '') !== 'draft') {
            throw new RuntimeException('Hanya pesanan berstatus draft yang bisa dikonfirmasi.');
        }

        $jumlah_detail = Capsule::table('tb_pesanan_penjualan_detail')
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->count();

        if ($jumlah_detail <= 0) {
            throw new RuntimeException('Pesanan tidak memiliki detail produk.');
        }

        if ((float) ($pesanan->total ?? 0) <= 0) {
            throw new RuntimeException('Total pesanan harus lebih dari nol.');
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

    set_flash('success', 'Pesanan penjualan berhasil dikonfirmasi.');
    penjualan_redirect(admin_page_url('penjualan/pesanan/detail') . '&id=' . $id_pesanan_penjualan);
} catch (Throwable $e) {
    set_flash('error', 'Gagal mengonfirmasi pesanan: ' . $e->getMessage());
    penjualan_redirect(admin_page_url('penjualan/pesanan/detail') . '&id=' . $id_pesanan_penjualan);
}