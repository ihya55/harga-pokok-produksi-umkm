<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/PenerimaanPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PenerimaanPembelianDetailORM.php';
require_once __DIR__ . '/../../../../orm/PesananPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PemasokORM.php';
require_once __DIR__ . '/../../../../orm/GudangORM.php';
require_once __DIR__ . '/../../../../orm/BahanBakuORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('pembelian/penerimaan');
}

function angka_decimal_terima($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function qty_int_terima($value): int
{
    $qty = (int) preg_replace('/[^0-9]/', '', (string) $value);
    return max(0, $qty);
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$tanggal_penerimaan = trim((string) ($_POST['tanggal_penerimaan'] ?? ''));
$id_pesanan_pembelian = (int) ($_POST['id_pesanan_pembelian'] ?? 0);
$id_pemasok = (int) ($_POST['id_pemasok'] ?? 0);
$id_gudang = (int) ($_POST['id_gudang'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$detail = $_POST['detail'] ?? [];

if ($tanggal_penerimaan === '') {
    set_flash('error', 'Tanggal penerimaan wajib diisi.');
    redirect_admin('pembelian/penerimaan/tambah');
}

if ($id_pesanan_pembelian > 0) {
    $po_valid = PesananPembelianORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('status_pesanan', 'terkonfirmasi')
        ->where('id_pesanan_pembelian', $id_pesanan_pembelian)
        ->exists();

    if (!$po_valid) {
        set_flash('error', 'Pesanan pembelian tidak valid atau belum terkonfirmasi.');
        redirect_admin('pembelian/penerimaan/tambah');
    }
}

if ($id_pemasok <= 0 || !PemasokORM::query()->where('id_entitas', $id_entitas)->where('id_pemasok', $id_pemasok)->exists()) {
    set_flash('error', 'Pemasok tidak valid.');
    redirect_admin('pembelian/penerimaan/tambah');
}

if ($id_gudang <= 0 || !GudangORM::query()->where('id_entitas', $id_entitas)->where('id_gudang', $id_gudang)->exists()) {
    set_flash('error', 'Gudang tidak valid.');
    redirect_admin('pembelian/penerimaan/tambah');
}

if (!is_array($detail) || count($detail) === 0) {
    set_flash('error', 'Minimal harus ada 1 baris detail.');
    redirect_admin('pembelian/penerimaan/tambah');
}

try {
    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $tanggal_penerimaan,
        $id_pesanan_pembelian,
        $id_pemasok,
        $id_gudang,
        $catatan,
        $detail
    ) {
        $detail_valid = [];

        foreach ($detail as $baris) {
            $id_bahan_baku = (int) ($baris['id_bahan_baku'] ?? 0);
            $qty = qty_int_terima($baris['qty'] ?? 0);
            $harga = angka_decimal_terima($baris['harga'] ?? 0);

            if ($id_bahan_baku <= 0 || !BahanBakuORM::query()->where('id_entitas', $id_entitas)->where('id_bahan_baku', $id_bahan_baku)->exists()) {
                throw new RuntimeException('Bahan baku tidak valid.');
            }

            if ($qty <= 0) {
                throw new RuntimeException('Qty wajib lebih besar dari 0 dan harus bilangan bulat.');
            }

            if ($harga < 0) {
                throw new RuntimeException('Harga tidak valid.');
            }

            $subtotal = round($qty * $harga, 2);

            $detail_valid[] = [
                'id_bahan_baku' => $id_bahan_baku,
                'qty'           => $qty,
                'harga'         => $harga,
                'subtotal'      => $subtotal,
            ];
        }

        if (count($detail_valid) === 0) {
            throw new RuntimeException('Minimal harus ada 1 detail yang valid.');
        }

        $no_penerimaan = generate_kode_master(
            'tb_penerimaan_pembelian',
            'no_penerimaan_pembelian',
            'PBM',
            4,
            $id_entitas
        );

        $header = PenerimaanPembelianORM::create([
            'id_entitas'                  => $id_entitas,
            'no_penerimaan_pembelian'     => $no_penerimaan,
            'tanggal_penerimaan'          => $tanggal_penerimaan,
            'id_pesanan_pembelian'        => $id_pesanan_pembelian > 0 ? $id_pesanan_pembelian : null,
            'id_pemasok'                  => $id_pemasok,
            'id_gudang'                   => $id_gudang,
            'status_penerimaan'           => 'draft',
            'catatan'                     => $catatan !== '' ? $catatan : null,
            'tanggal_dibuat'              => date('Y-m-d H:i:s'),
            'dibuat_oleh'                 => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_posting'             => null,
            'diposting_oleh'              => null,
            'tanggal_diubah'              => null,
            'diubah_oleh'                 => null,
        ]);

        foreach ($detail_valid as $item) {
            PenerimaanPembelianDetailORM::create([
                'id_penerimaan_pembelian' => $header->id_penerimaan_pembelian,
                'id_bahan_baku'           => $item['id_bahan_baku'],
                'qty'                     => $item['qty'],
                'harga'                   => $item['harga'],
                'subtotal'                => $item['subtotal'],
            ]);
        }
    });

    set_flash('success', 'Penerimaan pembelian berhasil disimpan.');
    redirect_admin('pembelian/penerimaan');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    redirect_admin('pembelian/penerimaan/tambah');
}