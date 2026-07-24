<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

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

function angka_decimal_po($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function qty_int_po($value): int
{
    $qty = (int) preg_replace('/[^0-9]/', '', (string) $value);
    return max(0, $qty);
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$tanggal_pesanan = trim((string) ($_POST['tanggal_pesanan'] ?? ''));
$id_pemasok = (int) ($_POST['id_pemasok'] ?? 0);
$tanggal_datang_rencana = trim((string) ($_POST['tanggal_datang_rencana'] ?? ''));
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$detail = $_POST['detail'] ?? [];

if ($tanggal_pesanan === '') {
    set_flash('error', 'Tanggal pesanan wajib diisi.');
    redirect_admin('pembelian/pesanan/tambah');
}

if ($id_pemasok <= 0 || !PemasokORM::query()->where('id_entitas', $id_entitas)->where('id_pemasok', $id_pemasok)->exists()) {
    set_flash('error', 'Pemasok tidak valid.');
    redirect_admin('pembelian/pesanan/tambah');
}

if (!is_array($detail) || count($detail) === 0) {
    set_flash('error', 'Minimal harus ada 1 baris detail.');
    redirect_admin('pembelian/pesanan/tambah');
}

try {
    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
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
            $qty = qty_int_po($baris['qty'] ?? 0);
            $harga = angka_decimal_po($baris['harga'] ?? 0);
            $diskon = angka_decimal_po($baris['diskon'] ?? 0);

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

        $diskon_header = angka_decimal_po($_POST['diskon_header'] ?? 0);

        if ($diskon_header > $subtotal_header) {
            $diskon_header = $subtotal_header;
        }

        $total_header = round($subtotal_header - $diskon_header, 2);

        $no_pesanan = generate_kode_master(
            'tb_pesanan_pembelian',
            'no_pesanan_pembelian',
            'PO',
            4,
            $id_entitas
        );

        $header = PesananPembelianORM::create([
            'id_entitas'             => $id_entitas,
            'no_pesanan_pembelian'   => $no_pesanan,
            'tanggal_pesanan'        => $tanggal_pesanan,
            'id_pemasok'             => $id_pemasok,
            'status_pesanan'         => 'draft',
            'tanggal_datang_rencana' => $tanggal_datang_rencana !== '' ? $tanggal_datang_rencana : null,
            'catatan'                => $catatan !== '' ? $catatan : null,
            'subtotal'               => round($subtotal_header, 2),
            'diskon'                 => round($diskon_header, 2),
            'total'                  => $total_header,
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
            'dibuat_oleh'            => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'         => null,
            'diubah_oleh'            => null,
        ]);

        foreach ($detail_valid as $item) {
            PesananPembelianDetailORM::create([
                'id_pesanan_pembelian' => $header->id_pesanan_pembelian,
                'id_bahan_baku'        => $item['id_bahan_baku'],
                'qty'                  => $item['qty'],
                'harga'                => $item['harga'],
                'diskon'               => $item['diskon'],
                'subtotal'             => $item['subtotal'],
            ]);
        }
    });

    set_flash('success', 'Pesanan pembelian berhasil disimpan.');
    redirect_admin('pembelian/pesanan');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_admin('pembelian/pesanan/tambah');
}