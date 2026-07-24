<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=penjualan/pesanan/detail&id=' . $id_pesanan_penjualan);
}

if ($id_entitas <= 0 || $id_pesanan_penjualan <= 0) {
    set_flash('error', 'Data pesanan tidak valid.');
    header('Location: ' . admin_url('index.php?menu=penjualan/pesanan'));
    exit;
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

        $status = (string) ($pesanan->status_pesanan ?? '');

        if ($status === 'batal') {
            throw new RuntimeException('Pesanan ini sudah berstatus batal.');
        }

        if ($status === 'selesai') {
            throw new RuntimeException('Pesanan selesai tidak bisa dibatalkan dari menu pesanan.');
        }

        $sudah_dipakai_penyerahan = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->exists();

        if ($sudah_dipakai_penyerahan) {
            throw new RuntimeException('Pesanan tidak bisa dibatalkan karena sudah dipakai pada penyerahan penjualan.');
        }

        $sudah_dipakai_produksi_posted = Capsule::table('tb_perintah_produksi')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->where('status_produksi', 'posted')
            ->exists();

        if ($sudah_dipakai_produksi_posted) {
            throw new RuntimeException('Pesanan tidak bisa dibatalkan karena sudah dipakai pada perintah produksi posted.');
        }

        $tanggal_batal_label = date('d/m/Y H:i');
        $catatan_lama = trim((string) ($pesanan->catatan ?? ''));

        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->update([
                'status_pesanan' => 'batal',
                'catatan' => trim($catatan_lama . ' | DIBATALKAN pada ' . $tanggal_batal_label),
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);

        Capsule::table('tb_perintah_produksi')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->where('status_produksi', 'draft')
            ->update([
                'status_produksi' => 'batal',
                'catatan' => Capsule::raw("CONCAT(COALESCE(catatan, ''), ' | DIBATALKAN karena pesanan dibatalkan pada " . date('d/m/Y H:i') . "')"),
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });

    set_flash('success', 'Pesanan penjualan berhasil dibatalkan.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal membatalkan pesanan penjualan: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;