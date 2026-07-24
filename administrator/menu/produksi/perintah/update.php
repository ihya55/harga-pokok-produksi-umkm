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

function redirect_back_perintah_produksi_update(string $back_url): void
{
    header('Location: ' . $back_url);
    exit;
}

function int_perintah_produksi_update($value): int
{
    return max(0, (int) preg_replace('/[^0-9]/', '', (string) $value));
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/perintah');
}

$id_perintah_produksi = (int) ($_POST['id_perintah_produksi'] ?? 0);
$tanggal_perintah = trim((string) ($_POST['tanggal_perintah'] ?? ''));
$id_produk = (int) ($_POST['id_produk'] ?? 0);
$id_resep = (int) ($_POST['id_resep'] ?? 0);
$qty_rencana = int_perintah_produksi_update($_POST['qty_rencana'] ?? 0);
$tanggal_mulai = trim((string) ($_POST['tanggal_mulai'] ?? ''));
$tanggal_selesai = trim((string) ($_POST['tanggal_selesai'] ?? ''));
$id_pesanan_penjualan = (int) ($_POST['id_pesanan_penjualan'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));

$row = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_perintah_produksi);

if (!$row) {
    set_flash('error', 'Data perintah produksi tidak ditemukan.');
    redirect_back_perintah_produksi_update($back_url);
}

if ((string) $row->status_produksi !== 'draft') {
    set_flash('error', 'Perintah produksi yang sudah posted tidak bisa diubah.');
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

if ($tanggal_perintah === '' || $qty_rencana <= 0) {
    set_flash('error', 'Tanggal perintah dan qty rencana wajib valid.');
    redirect_back_perintah_produksi_update($back_url);
}

$produk_valid = Capsule::table('tb_produk')
    ->where('id_entitas', $id_entitas)
    ->where('id_produk', $id_produk)
    ->where('status_produk', 1)
    ->whereIn('jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->exists();

if (!$produk_valid) {
    set_flash('error', 'Produk tidak valid atau tidak aktif.');
    redirect_back_perintah_produksi_update($back_url);
}

$resep_valid = Capsule::table('tb_resep')
    ->where('id_entitas', $id_entitas)
    ->where('id_resep', $id_resep)
    ->where('id_produk', $id_produk)
    ->where('status_aktif', 1)
    ->exists();

if (!$resep_valid) {
    set_flash('error', 'Resep/BOM tidak valid atau tidak sesuai dengan produk.');
    redirect_back_perintah_produksi_update($back_url);
}

if ($id_pesanan_penjualan > 0) {
    $pesanan_valid = Capsule::table('tb_pesanan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
        ->exists();

    if (!$pesanan_valid) {
        set_flash('error', 'Pesanan penjualan tidak valid.');
        redirect_back_perintah_produksi_update($back_url);
    }
}

if ($tanggal_mulai !== '' && $tanggal_selesai !== '' && $tanggal_selesai < $tanggal_mulai) {
    set_flash('error', 'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.');
    redirect_back_perintah_produksi_update($back_url);
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $id_pengguna,
        $tanggal_perintah,
        $id_produk,
        $id_resep,
        $qty_rencana,
        $tanggal_mulai,
        $tanggal_selesai,
        $id_pesanan_penjualan,
        $catatan
    ) {
        $row->update([
            'tanggal_perintah'        => $tanggal_perintah,
            'id_produk'               => $id_produk,
            'id_resep'                => $id_resep,
            'qty_rencana'             => $qty_rencana,
            'tanggal_mulai'           => $tanggal_mulai !== '' ? $tanggal_mulai : null,
            'tanggal_selesai'         => $tanggal_selesai !== '' ? $tanggal_selesai : null,
            'id_pesanan_penjualan'    => $id_pesanan_penjualan > 0 ? $id_pesanan_penjualan : null,
            'catatan'                 => $catatan !== '' ? $catatan : null,
            'tanggal_diubah'          => date('Y-m-d H:i:s'),
            'diubah_oleh'             => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Perintah produksi berhasil diperbarui.');
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_back_perintah_produksi_update($back_url);
}