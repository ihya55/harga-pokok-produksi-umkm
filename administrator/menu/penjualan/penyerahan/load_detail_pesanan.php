<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

header('Content-Type: application/json; charset=utf-8');

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id_pesanan_penjualan'] ?? 0);
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);
$id_penyerahan_penjualan = (int) ($_GET['id_penyerahan_penjualan'] ?? 0);

if ($id_pesanan_penjualan <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Pesanan penjualan wajib dipilih.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($id_gudang <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Gudang wajib dipilih.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$gudangValid = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('id_gudang', $id_gudang)
    ->where('status_aktif', 1)
    ->exists();

if (!$gudangValid) {
    echo json_encode([
        'success' => false,
        'message' => 'Gudang tidak valid atau tidak aktif.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pesanan = Capsule::table('tb_pesanan_penjualan as ps')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
    ->where('ps.id_entitas', $id_entitas)
    ->where('ps.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'ps.*',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
    ])
    ->first();

if (!$pesanan) {
    echo json_encode([
        'success' => false,
        'message' => 'Pesanan penjualan tidak ditemukan.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array((string) $pesanan->status_pesanan, ['terkonfirmasi', 'diproses'], true)) {
    echo json_encode([
        'success' => false,
        'message' => 'Hanya pesanan berstatus terkonfirmasi/diproses yang bisa dibuat penyerahan.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sudahDipakai = Capsule::table('tb_penyerahan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
    ->whereIn('status_penyerahan', ['draft', 'posted'])
    ->when($id_penyerahan_penjualan > 0, function ($query) use ($id_penyerahan_penjualan) {
        $query->where('id_penyerahan_penjualan', '!=', $id_penyerahan_penjualan);
    })
    ->exists();

if ($sudahDipakai) {
    echo json_encode([
        'success' => false,
        'message' => 'Pesanan ini sudah dipakai pada penyerahan lain.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$detailPesanan = Capsule::table('tb_pesanan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_saldo_stok as ss', function ($join) use ($id_entitas, $id_gudang) {
        $join->on('ss.id_referensi_barang', '=', 'd.id_produk')
            ->where('ss.id_entitas', '=', $id_entitas)
            ->where('ss.jenis_barang', '=', 'produk')
            ->where('ss.id_gudang', '=', $id_gudang);
    })
    ->where('d.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'd.id_produk',
        'd.qty as qty_pesanan',
        'd.catatan',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
        'ss.qty_saldo',
        'ss.hpp_rata_rata',
    ])
    ->orderBy('d.id_pesanan_penjualan_detail', 'asc')
    ->get();

$produkOptions = [];
$rows = [];
$tanpaStok = [];

foreach ($detailPesanan as $item) {
    $qtySaldo = (float) ($item->qty_saldo ?? 0);
    $qtyPesanan = (float) ($item->qty_pesanan ?? 0);
    $hppRataRata = (float) ($item->hpp_rata_rata ?? 0);

    if ($qtySaldo <= 0) {
        $tanpaStok[] = ($item->kode_produk ?? '-') . ' - ' . ($item->nama_produk ?? '-');
        continue;
    }

    $qtyKirim = (int) min(floor($qtySaldo), floor($qtyPesanan));
    if ($qtyKirim < 1) {
        $tanpaStok[] = ($item->kode_produk ?? '-') . ' - ' . ($item->nama_produk ?? '-');
        continue;
    }

    $label = ($item->kode_produk ?? '-') . ' - ' . ($item->nama_produk ?? '-') . ' - Stok: ' . rtrim(rtrim(number_format($qtySaldo, 3, '.', ''), '0'), '.');

    $produkOptions[] = [
        'id_produk' => (int) $item->id_produk,
        'kode_produk' => (string) ($item->kode_produk ?? ''),
        'nama_produk' => (string) ($item->nama_produk ?? ''),
        'nama_satuan' => (string) ($item->nama_satuan ?? ''),
        'qty_saldo' => $qtySaldo,
        'qty_pesanan' => $qtyPesanan,
        'hpp_rata_rata' => $hppRataRata,
        'label' => $label,
    ];

    $rows[] = [
        'id_produk' => (int) $item->id_produk,
        'kode_produk' => (string) ($item->kode_produk ?? ''),
        'nama_produk' => (string) ($item->nama_produk ?? ''),
        'nama_satuan' => (string) ($item->nama_satuan ?? ''),
        'qty' => $qtyKirim,
        'qty_pesanan' => $qtyPesanan,
        'qty_saldo' => $qtySaldo,
        'hpp_satuan' => $hppRataRata,
        'hpp_rata_rata' => $hppRataRata,
        'hpp_total' => $qtyKirim * $hppRataRata,
        'catatan' => (string) ($item->catatan ?? ''),
    ];
}

if (count($rows) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada produk pada pesanan ini yang memiliki saldo stok di gudang terpilih. Posting hasil produksi/stok barang jadi terlebih dahulu.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$warning = '';
if (count($tanpaStok) > 0) {
    $warning = 'Beberapa produk pesanan tidak dimuat karena stok gudang 0: ' . implode(', ', $tanpaStok);
}

echo json_encode([
    'success' => true,
    'message' => 'Detail pesanan berhasil dimuat.',
    'warning' => $warning,
    'pesanan' => [
        'id_pesanan_penjualan' => (int) $pesanan->id_pesanan_penjualan,
        'no_pesanan_penjualan' => (string) $pesanan->no_pesanan_penjualan,
        'id_pelanggan' => (int) $pesanan->id_pelanggan,
        'kode_pelanggan' => (string) ($pesanan->kode_pelanggan ?? ''),
        'nama_pelanggan' => (string) ($pesanan->nama_pelanggan ?? ''),
        'pelanggan_label' => (string) (($pesanan->kode_pelanggan ?? '-') . ' - ' . ($pesanan->nama_pelanggan ?? '-')),
        'alamat_pelanggan' => (string) ($pesanan->alamat_pelanggan ?? ''),
    ],
    'produk_options' => $produkOptions,
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
exit;