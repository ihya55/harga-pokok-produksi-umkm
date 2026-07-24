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

function faktur_simpan_kembali(?int $id_penyerahan = null): void
{
    $url = admin_url('index.php?menu=penjualan/faktur/tambah');
    if ($id_penyerahan && $id_penyerahan > 0) {
        $url .= '&id_penyerahan_penjualan=' . $id_penyerahan;
    }

    header('Location: ' . $url);
    exit;
}

function faktur_simpan_decimal($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function faktur_simpan_int($value): int
{
    return max(0, (int) preg_replace('/[^0-9]/', '', (string) $value));
}

function faktur_generate_nomor(int $id_entitas): string
{
    $prefix = 'FPJ-';

    $last = Capsule::table('tb_faktur_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('no_faktur_penjualan', 'like', $prefix . '%')
        ->orderBy('id_faktur_penjualan', 'desc')
        ->value('no_faktur_penjualan');

    $next = 1;

    if ($last) {
        $angka = preg_replace('/[^0-9]/', '', (string) $last);
        if ($angka !== '') {
            $next = ((int) $angka) + 1;
        }
    }

    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$tanggal_faktur = trim((string) ($_POST['tanggal_faktur'] ?? ''));
$id_penyerahan_penjualan = (int) ($_POST['id_penyerahan_penjualan'] ?? 0);
$id_pelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
$jenis_pembayaran = strtolower(trim((string) ($_POST['jenis_pembayaran'] ?? '')));
$jatuh_tempo = trim((string) ($_POST['jatuh_tempo'] ?? ''));
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$detail = $_POST['detail'] ?? [];

if ($tanggal_faktur === '') {
    set_flash('error', 'Tanggal faktur wajib diisi.');
    faktur_simpan_kembali($id_penyerahan_penjualan);
}

if ($id_penyerahan_penjualan <= 0) {
    set_flash('error', 'Faktur wajib dibuat dari penyerahan penjualan.');
    faktur_simpan_kembali();
}

if (!in_array($jenis_pembayaran, ['tunai', 'transfer', 'kredit'], true)) {
    set_flash('error', 'Jenis pembayaran tidak valid.');
    faktur_simpan_kembali($id_penyerahan_penjualan);
}

if ($jenis_pembayaran === 'kredit' && $jatuh_tempo === '') {
    set_flash('error', 'Jatuh tempo wajib diisi untuk piutang kredit.');
    faktur_simpan_kembali($id_penyerahan_penjualan);
}

if (!is_array($detail) || count($detail) === 0) {
    set_flash('error', 'Detail faktur masih kosong.');
    faktur_simpan_kembali($id_penyerahan_penjualan);
}

try {
    $id_baru = null;

    Capsule::connection()->transaction(function () use (
        &$id_baru,
        $id_entitas,
        $id_pengguna,
        $tanggal_faktur,
        $id_penyerahan_penjualan,
        $id_pelanggan,
        $jenis_pembayaran,
        $jatuh_tempo,
        $catatan,
        $detail
    ) {
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

        $sudahAda = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
            ->whereIn('status_faktur', ['draft', 'posted'])
            ->exists();

        if ($sudahAda) {
            throw new RuntimeException('Penyerahan ini sudah memiliki faktur.');
        }

        $detailValid = [];
        $subtotalHeader = 0.0;

        foreach ($detail as $baris) {
            $id_produk = (int) ($baris['id_produk'] ?? 0);
            $qty = faktur_simpan_int($baris['qty'] ?? 0);
            $harga = faktur_simpan_decimal($baris['harga'] ?? 0);
            $diskon = faktur_simpan_decimal($baris['diskon'] ?? 0);

            if ($id_produk <= 0) {
                throw new RuntimeException('Produk faktur tidak valid.');
            }

            if ($qty <= 0) {
                throw new RuntimeException('Qty faktur wajib lebih dari 0.');
            }

            if ($harga < 0 || $diskon < 0) {
                throw new RuntimeException('Harga dan diskon tidak boleh negatif.');
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
            $diskon = min($diskon, $bruto);
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

        if (count($detailValid) === 0) {
            throw new RuntimeException('Minimal harus ada 1 detail faktur.');
        }

        $subtotalPost = faktur_simpan_decimal($_POST['subtotal'] ?? 0);
        $diskonHeader = faktur_simpan_decimal($_POST['diskon'] ?? 0);
        $ppn = faktur_simpan_decimal($_POST['ppn'] ?? 0);
        $totalPost = faktur_simpan_decimal($_POST['total'] ?? 0);

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
            'id_entitas' => $id_entitas,
            'no_faktur_penjualan' => faktur_generate_nomor($id_entitas),
            'tanggal_faktur' => $tanggal_faktur,
            'id_pelanggan' => $id_pelanggan,
            'id_penyerahan_penjualan' => $id_penyerahan_penjualan,
            'jenis_pembayaran' => $jenis_pembayaran,
            'status_faktur' => 'draft',
            'jatuh_tempo' => $jenis_pembayaran === 'kredit' ? $jatuh_tempo : null,
            'subtotal' => $subtotal,
            'diskon' => round($diskonHeader, 2),
            'ppn' => round($ppn, 2),
            'total' => $totalHitung,
            'sisa_piutang' => $sisaPiutang,
            'catatan' => $catatan !== '' ? $catatan : null,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
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

        $id_baru = Capsule::table('tb_faktur_penjualan')->insertGetId($header);

        foreach ($detailValid as $d) {
            Capsule::table('tb_faktur_penjualan_detail')->insert([
                'id_faktur_penjualan' => $id_baru,
                'id_produk' => $d['id_produk'],
                'qty' => $d['qty'],
                'harga' => $d['harga'],
                'diskon' => $d['diskon'],
                'subtotal' => $d['subtotal'],
            ]);
        }
    });

    set_flash('success', 'Draft piutang pelanggan berhasil dibuat.');
    header('Location: ' . admin_url('index.php?menu=penjualan/faktur/detail&id=' . (int) $id_baru));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    faktur_simpan_kembali($id_penyerahan_penjualan);
}