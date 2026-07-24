<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../../../../helpers/pajak.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('index.php?menu=penjualan/faktur'));
    exit;
}

function faktur_update_kembali(int $id): void
{
    header('Location: ' . admin_url('index.php?menu=penjualan/faktur/edit&id=' . $id));
    exit;
}

function faktur_update_decimal($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function faktur_update_int($value): int
{
    return max(0, (int) preg_replace('/[^0-9]/', '', (string) $value));
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$id_faktur_penjualan = (int) ($_POST['id_faktur_penjualan'] ?? 0);
$tanggal_faktur = trim((string) ($_POST['tanggal_faktur'] ?? ''));
$id_penyerahan_penjualan = (int) ($_POST['id_penyerahan_penjualan'] ?? 0);
$id_pelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
$jenis_pembayaran = strtolower(trim((string) ($_POST['jenis_pembayaran'] ?? '')));
$jatuh_tempo = trim((string) ($_POST['jatuh_tempo'] ?? ''));
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$detail = $_POST['detail'] ?? [];

if ($id_faktur_penjualan <= 0) {
    set_flash('error', 'ID faktur tidak valid.');
    header('Location: ' . admin_url('index.php?menu=penjualan/faktur'));
    exit;
}

if ($tanggal_faktur === '') {
    set_flash('error', 'Tanggal faktur wajib diisi.');
    faktur_update_kembali($id_faktur_penjualan);
}

if ($id_penyerahan_penjualan <= 0) {
    set_flash('error', 'Penyerahan penjualan wajib dipilih.');
    faktur_update_kembali($id_faktur_penjualan);
}

if (!in_array($jenis_pembayaran, ['tunai', 'transfer', 'kredit'], true)) {
    set_flash('error', 'Jenis pembayaran tidak valid.');
    faktur_update_kembali($id_faktur_penjualan);
}

if ($jenis_pembayaran === 'kredit' && $jatuh_tempo === '') {
    set_flash('error', 'Jatuh tempo wajib diisi untuk piutang kredit.');
    faktur_update_kembali($id_faktur_penjualan);
}

try {
    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $id_faktur_penjualan,
        $tanggal_faktur,
        $id_penyerahan_penjualan,
        $id_pelanggan,
        $jenis_pembayaran,
        $jatuh_tempo,
        $catatan,
        $detail
    ) {
        $faktur = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$faktur) {
            throw new RuntimeException('Data piutang tidak ditemukan.');
        }

        if ((string) $faktur->status_faktur !== 'draft') {
            throw new RuntimeException('Faktur yang sudah posted atau batal tidak bisa diubah.');
        }

        $penyerahan = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->where('status_penyerahan', 'posted')
            ->first();

        if (!$penyerahan) {
            throw new RuntimeException('Penyerahan tidak ditemukan atau belum posted.');
        }

        if ($id_pelanggan <= 0) {
            $id_pelanggan = (int) $penyerahan->id_pelanggan;
        }

        if ((int) $penyerahan->id_pelanggan !== $id_pelanggan) {
            throw new RuntimeException('Pelanggan faktur tidak sesuai dengan penyerahan.');
        }

        $sudahDipakai = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->where('id_faktur_penjualan', '!=', $id_faktur_penjualan)
            ->whereIn('status_faktur', ['draft', 'posted'])
            ->exists();

        if ($sudahDipakai) {
            throw new RuntimeException('Penyerahan ini sudah dipakai pada faktur lain.');
        }

        if (!is_array($detail) || count($detail) === 0) {
            throw new RuntimeException('Detail faktur masih kosong.');
        }

        $detailValid = [];
        $subtotalHeader = 0.0;

        foreach ($detail as $baris) {
            $id_produk = (int) ($baris['id_produk'] ?? 0);
            $qty = faktur_update_int($baris['qty'] ?? 0);
            $harga = faktur_update_decimal($baris['harga'] ?? 0);
            $diskon = faktur_update_decimal($baris['diskon'] ?? 0);

            if ($id_produk <= 0 || $qty <= 0) {
                throw new RuntimeException('Produk dan qty detail faktur tidak valid.');
            }

            $detailPenyerahan = Capsule::table('tb_penyerahan_penjualan_detail')
                ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
                ->where('id_produk', $id_produk)
                ->first();

            if (!$detailPenyerahan) {
                throw new RuntimeException('Produk faktur tidak sesuai dengan detail penyerahan.');
            }

            if ($qty > (float) $detailPenyerahan->qty) {
                throw new RuntimeException('Qty faktur tidak boleh melebihi qty penyerahan.');
            }

            $bruto = round($qty * $harga, 2);
            $diskon = min(max(0, $diskon), $bruto);
            $subtotal = max(0, round($bruto - $diskon, 2));

            $subtotalHeader += $subtotal;

            $detailValid[] = [
                'id_produk' => $id_produk,
                'qty' => $qty,
                'harga' => round($harga, 2),
                'diskon' => round($diskon, 2),
                'subtotal' => $subtotal,
            ];
        }

        $subtotalPost = faktur_update_decimal($_POST['subtotal'] ?? 0);
        $diskonHeader = faktur_update_decimal($_POST['diskon'] ?? 0);
        $ppn = faktur_update_decimal($_POST['ppn'] ?? 0);
        $totalPost = faktur_update_decimal($_POST['total'] ?? 0);

        $subtotal = round($subtotalHeader, 2);
        $diskonHeader = min(max(0, $diskonHeader), $subtotal);
        $totalHitung = max(0, round(($subtotal - $diskonHeader) + $ppn, 2));

        if (abs($subtotalPost - $subtotal) > 1) {
            throw new RuntimeException('Subtotal faktur tidak sesuai dengan detail.');
        }

        if (abs($totalPost - $totalHitung) > 1) {
            throw new RuntimeException('Total faktur tidak sesuai dengan perhitungan.');
        }

        $sisaPiutang = $jenis_pembayaran === 'kredit' ? $totalHitung : 0;

        $header = [
            'tanggal_faktur' => $tanggal_faktur,
            'id_pelanggan' => $id_pelanggan,
            'id_penyerahan_penjualan' => $id_penyerahan_penjualan,
            'jenis_pembayaran' => $jenis_pembayaran,
            'jatuh_tempo' => $jenis_pembayaran === 'kredit' ? $jatuh_tempo : null,
            'subtotal' => $subtotal,
            'diskon' => round($diskonHeader, 2),
            'ppn' => round($ppn, 2),
            'total' => $totalHitung,
            'sisa_piutang' => $sisaPiutang,
            'catatan' => $catatan !== '' ? $catatan : null,
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh' => $id_pengguna ?: null,
        ];

        $columns = Capsule::schema()->getColumnListing('tb_faktur_penjualan');

        if (in_array('sumber_faktur', $columns, true)) {
            $header['sumber_faktur'] = 'penyerahan';
        }

        if (in_array('is_pos', $columns, true)) {
            $header['is_pos'] = 0;
        }

        if (in_array('diskon_persen', $columns, true)) {
            $header['diskon_persen'] = (int) ($_POST['diskon_persen_header'] ?? 0);
        }

        if (in_array('ppn_persen', $columns, true)) {
            $ppn_persen_header = $_POST['ppn_persen_header'] ?? null;
            if ($ppn_persen_header === null || trim((string) $ppn_persen_header) === '') {
                $ppn_persen_header = pajak_default_persen($id_entitas, 'PPN', 11.0);
            }
            $header['ppn_persen'] = round((float) $ppn_persen_header, 2);
        }

        Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->update($header);

        Capsule::table('tb_faktur_penjualan_detail')
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->delete();

        foreach ($detailValid as $d) {
            Capsule::table('tb_faktur_penjualan_detail')->insert([
                'id_faktur_penjualan' => $id_faktur_penjualan,
                'id_produk' => $d['id_produk'],
                'qty' => $d['qty'],
                'harga' => $d['harga'],
                'diskon' => $d['diskon'],
                'subtotal' => $d['subtotal'],
            ]);
        }
    });

    set_flash('success', 'Draft piutang pelanggan berhasil diperbarui.');
    header('Location: ' . admin_url('index.php?menu=penjualan/faktur/detail&id=' . $id_faktur_penjualan));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    faktur_update_kembali($id_faktur_penjualan);
}