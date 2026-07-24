<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PengambilanBahanORM.php';
require_once __DIR__ . '/../../../../orm/PengambilanBahanDetailORM.php';
require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('produksi/pengambilan-bahan');
}

function angka_decimal_pengambilan_bahan_update($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function angka_int_pengambilan_bahan_update($value): int
{
    return (int) preg_replace('/[^0-9]/', '', (string) $value);
}

function redirect_back_pengambilan_bahan_update(string $back_url): void
{
    header('Location: ' . $back_url);
    exit;
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/pengambilan-bahan');
}

$id_pengambilan_bahan = (int) ($_POST['id_pengambilan_bahan'] ?? 0);
$tanggal_pengambilan = trim((string) ($_POST['tanggal_pengambilan'] ?? ''));
$id_perintah_produksi = (int) ($_POST['id_perintah_produksi'] ?? 0);
$id_gudang = (int) ($_POST['id_gudang'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));

$id_bahan_baku_arr = $_POST['id_bahan_baku'] ?? [];
$qty_arr = $_POST['qty'] ?? [];
$harga_arr = $_POST['harga_satuan'] ?? [];

$row = PengambilanBahanORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pengambilan_bahan);

if (!$row) {
    set_flash('error', 'Data pengambilan bahan tidak ditemukan.');
    redirect_back_pengambilan_bahan_update($back_url);
}

if ((string) $row->status_posting === 'posted') {
    set_flash('error', 'Pengambilan bahan yang sudah posted tidak bisa diubah.');
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
}

if ($tanggal_pengambilan === '') {
    set_flash('error', 'Tanggal pengambilan wajib diisi.');
    redirect_back_pengambilan_bahan_update($back_url);
}

$perintah = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', $id_perintah_produksi)
    ->where('status_produksi', 'posted')
    ->first();

if (!$perintah) {
    set_flash('error', 'Perintah produksi tidak valid atau belum posted.');
    redirect_back_pengambilan_bahan_update($back_url);
}

$gudang_valid = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('id_gudang', $id_gudang)
    ->where('status_aktif', 1)
    ->exists();

if (!$gudang_valid) {
    set_flash('error', 'Gudang tidak valid atau tidak aktif.');
    redirect_back_pengambilan_bahan_update($back_url);
}

$detail_valid = [];
$cek_duplikat = [];

foreach ($id_bahan_baku_arr as $i => $id_bahan_baku) {
    $id_bahan_baku = (int) $id_bahan_baku;
    $qty = angka_int_pengambilan_bahan_update($qty_arr[$i] ?? 0);
    $harga_satuan = angka_decimal_pengambilan_bahan_update($harga_arr[$i] ?? 0);

    if ($id_bahan_baku <= 0 || $qty <= 0) {
        continue;
    }

    if (in_array($id_bahan_baku, $cek_duplikat, true)) {
        set_flash('error', 'Bahan baku tidak boleh duplikat dalam satu bon.');
        redirect_back_pengambilan_bahan_update($back_url);
    }

    $bahan = Capsule::table('tb_bahan_baku')
        ->where('id_entitas', $id_entitas)
        ->where('id_bahan_baku', $id_bahan_baku)
        ->where('status_aktif', 1)
        ->first();

    if (!$bahan) {
        set_flash('error', 'Ada bahan baku yang tidak valid atau tidak aktif.');
        redirect_back_pengambilan_bahan_update($back_url);
    }

    if ($harga_satuan <= 0) {
        $saldo_hpp = Capsule::table('tb_saldo_stok')
            ->where('id_entitas', $id_entitas)
            ->where('jenis_barang', 'bahan_baku')
            ->where('id_referensi_barang', $id_bahan_baku)
            ->where('id_gudang', $id_gudang)
            ->value('hpp_rata_rata');

        $harga_satuan = (float) ($saldo_hpp ?: ($bahan->harga_standar ?? 0));
    }

    $cek_duplikat[] = $id_bahan_baku;

    $detail_valid[] = [
        'id_bahan_baku' => $id_bahan_baku,
        'qty'           => $qty,
        'harga_satuan'  => round($harga_satuan, 2),
        'subtotal'      => round($qty * $harga_satuan, 2),
    ];
}

if (count($detail_valid) === 0) {
    set_flash('error', 'Detail bahan belum valid. Minimal 1 bahan dengan qty lebih dari 0.');
    redirect_back_pengambilan_bahan_update($back_url);
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $id_pengambilan_bahan,
        $id_pengguna,
        $tanggal_pengambilan,
        $id_perintah_produksi,
        $id_gudang,
        $catatan,
        $detail_valid
    ) {
        $row->update([
            'tanggal_pengambilan'    => $tanggal_pengambilan,
            'id_perintah_produksi'   => $id_perintah_produksi,
            'id_gudang'              => $id_gudang,
            'status_posting'         => 'draft',
            'catatan'                => $catatan !== '' ? $catatan : null,
            'tanggal_diubah'         => date('Y-m-d H:i:s'),
            'diubah_oleh'            => $id_pengguna > 0 ? $id_pengguna : null,
        ]);

        PengambilanBahanDetailORM::query()
            ->where('id_pengambilan_bahan', $id_pengambilan_bahan)
            ->delete();

        foreach ($detail_valid as $detail) {
            PengambilanBahanDetailORM::create([
                'id_pengambilan_bahan' => $id_pengambilan_bahan,
                'id_bahan_baku'        => $detail['id_bahan_baku'],
                'qty'                  => $detail['qty'],
                'harga_satuan'         => $detail['harga_satuan'],
                'subtotal'             => $detail['subtotal'],
            ]);
        }
    });

    set_flash('success', 'Pengambilan bahan berhasil diperbarui. Status dikembalikan ke draft dan perlu diperiksa ulang.');
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_back_pengambilan_bahan_update($back_url);
}