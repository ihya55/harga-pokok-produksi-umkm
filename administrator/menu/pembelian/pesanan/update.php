<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PesananPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PesananPembelianDetailORM.php';
require_once __DIR__ . '/../../../../orm/PemasokORM.php';
require_once __DIR__ . '/../../../../orm/BahanBakuORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('pembelian/pesanan');
}

function angka_decimal_po_update($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function qty_int_po_update($value): int
{
    $qty = (int) preg_replace('/[^0-9]/', '', (string) $value);
    return max(0, $qty);
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$id_pesanan_pembelian = (int) ($_POST['id_pesanan_pembelian'] ?? 0);
$tanggal_pesanan = trim((string) ($_POST['tanggal_pesanan'] ?? ''));
$id_pemasok = (int) ($_POST['id_pemasok'] ?? 0);
$tanggal_datang_rencana = trim((string) ($_POST['tanggal_datang_rencana'] ?? ''));
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$detail = $_POST['detail'] ?? [];

$row = PesananPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pesanan_pembelian);

if (!$row) {
    set_flash('error', 'Data pesanan pembelian tidak ditemukan.');
    redirect_admin('pembelian/pesanan');
}

if ((string) $row->status_pesanan !== 'draft') {
    set_flash('error', 'Pesanan pembelian yang sudah terkonfirmasi tidak bisa diubah.');
    redirect_admin('pembelian/pesanan/detail&id=' . $id_pesanan_pembelian);
}

if ($tanggal_pesanan === '') {
    set_flash('error', 'Tanggal pesanan wajib diisi.');
    redirect_admin('pembelian/pesanan/edit&id=' . $id_pesanan_pembelian);
}

if ($id_pemasok <= 0 || !PemasokORM::query()->where('id_entitas', $id_entitas)->where('id_pemasok', $id_pemasok)->exists()) {
    set_flash('error', 'Pemasok tidak valid.');
    redirect_admin('pembelian/pesanan/edit&id=' . $id_pesanan_pembelian);
}

if (!is_array($detail) || count($detail) === 0) {
    set_flash('error', 'Minimal harus ada 1 baris detail.');
    redirect_admin('pembelian/pesanan/edit&id=' . $id_pesanan_pembelian);
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $id_entitas,
        $id_pengguna,
        $id_pesanan_pembelian,
        $tanggal_pesanan,
        $id_pemasok,
        $tanggal_datang_rencana,
        $catatan,
        $detail
    ) {
        $detail_valid = [];
        $subtotal_header = 0.0;

        foreach ($detail as $baris) {
            $id_bahan_baku = (int) ($baris['id_bahan_baku'] ?? 0);
            $qty = qty_int_po_update($baris['qty'] ?? 0);
            $harga = angka_decimal_po_update($baris['harga'] ?? 0);
            $diskon = angka_decimal_po_update($baris['diskon'] ?? 0);

            if ($id_bahan_baku <= 0 || !BahanBakuORM::query()->where('id_entitas', $id_entitas)->where('id_bahan_baku', $id_bahan_baku)->exists()) {
                throw new RuntimeException('Bahan baku tidak valid.');
            }

            if ($qty <= 0) {
                throw new RuntimeException('Qty wajib lebih besar dari 0 dan harus bilangan bulat.');
            }

            if ($harga < 0) {
                throw new RuntimeException('Harga tidak valid.');
            }

            $bruto = $qty * $harga;

            if ($diskon > $bruto) {
                $diskon = $bruto;
            }

            $subtotal = round($bruto - $diskon, 2);
            $subtotal_header += $subtotal;

            $detail_valid[] = [
                'id_bahan_baku' => $id_bahan_baku,
                'qty'           => $qty,
                'harga'         => $harga,
                'diskon'        => $diskon,
                'subtotal'      => $subtotal,
            ];
        }

        if (count($detail_valid) === 0) {
            throw new RuntimeException('Minimal harus ada 1 detail yang valid.');
        }

        $diskon_header = angka_decimal_po_update($_POST['diskon_header'] ?? 0);

        if ($diskon_header > $subtotal_header) {
            $diskon_header = $subtotal_header;
        }

        $total_header = round($subtotal_header - $diskon_header, 2);

        $row->update([
            'tanggal_pesanan'        => $tanggal_pesanan,
            'id_pemasok'             => $id_pemasok,
            'tanggal_datang_rencana' => $tanggal_datang_rencana !== '' ? $tanggal_datang_rencana : null,
            'catatan'                => $catatan !== '' ? $catatan : null,
            'subtotal'               => round($subtotal_header, 2),
            'diskon'                 => round($diskon_header, 2),
            'total'                  => $total_header,
            'tanggal_diubah'         => date('Y-m-d H:i:s'),
            'diubah_oleh'            => $id_pengguna > 0 ? $id_pengguna : null,
        ]);

        PesananPembelianDetailORM::query()
            ->where('id_pesanan_pembelian', $id_pesanan_pembelian)
            ->delete();

        foreach ($detail_valid as $item) {
            PesananPembelianDetailORM::create([
                'id_pesanan_pembelian' => $id_pesanan_pembelian,
                'id_bahan_baku'        => $item['id_bahan_baku'],
                'qty'                  => $item['qty'],
                'harga'                => $item['harga'],
                'diskon'               => $item['diskon'],
                'subtotal'             => $item['subtotal'],
            ]);
        }
    });

    set_flash('success', 'Pesanan pembelian berhasil diperbarui.');
    redirect_admin('pembelian/pesanan/detail&id=' . $id_pesanan_pembelian);
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_admin('pembelian/pesanan/edit&id=' . $id_pesanan_pembelian);
}