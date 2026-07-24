<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_perintah_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/perintah');
}

$row = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_perintah_produksi);

if (!$row) {
    set_flash('error', 'Data perintah produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_produksi !== 'draft') {
    set_flash('error', 'Hanya perintah produksi draft yang bisa diposting.');
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$produk_valid = Capsule::table('tb_produk')
    ->where('id_entitas', $id_entitas)
    ->where('id_produk', (int) $row->id_produk)
    ->where('status_produk', 1)
    ->exists();

if (!$produk_valid) {
    set_flash('error', 'Produk pada perintah produksi tidak valid.');
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$resep_valid = Capsule::table('tb_resep')
    ->where('id_entitas', $id_entitas)
    ->where('id_resep', (int) $row->id_resep)
    ->where('id_produk', (int) $row->id_produk)
    ->where('status_aktif', 1)
    ->exists();

if (!$resep_valid) {
    set_flash('error', 'Resep/BOM pada perintah produksi tidak valid.');
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

if ((float) $row->qty_rencana <= 0) {
    set_flash('error', 'Qty rencana wajib lebih besar dari 0.');
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

try {
    Capsule::connection()->transaction(function () use ($row, $id_pengguna) {
        $row->update([
            'status_produksi' => 'posted',
            'tanggal_diubah'  => date('Y-m-d H:i:s'),
            'diubah_oleh'     => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Perintah produksi berhasil diposting dan siap digunakan.');
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}