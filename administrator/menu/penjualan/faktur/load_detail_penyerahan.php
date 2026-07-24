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
$id_penyerahan_penjualan = (int) ($_GET['id_penyerahan_penjualan'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id_faktur_penjualan'] ?? 0);

if ($id_penyerahan_penjualan <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Penyerahan penjualan wajib dipilih.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$penyerahan = Capsule::table('tb_penyerahan_penjualan as pp')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select([
        'pp.*',
        'ps.no_pesanan_penjualan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.tempo_hari',
    ])
    ->first();

if (!$penyerahan) {
    echo json_encode([
        'success' => false,
        'message' => 'Penyerahan penjualan tidak ditemukan.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((string) $penyerahan->status_penyerahan !== 'posted') {
    echo json_encode([
        'success' => false,
        'message' => 'Hanya penyerahan yang sudah posted yang bisa dibuat piutang.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$sudahDipakai = Capsule::table('tb_faktur_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->whereIn('status_faktur', ['draft', 'posted'])
    ->when($id_faktur_penjualan > 0, function ($query) use ($id_faktur_penjualan) {
        $query->where('id_faktur_penjualan', '!=', $id_faktur_penjualan);
    })
    ->exists();

if ($sudahDipakai) {
    echo json_encode([
        'success' => false,
        'message' => 'Penyerahan ini sudah memiliki piutang pelanggan.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$rows = Capsule::table('tb_penyerahan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_pesanan_penjualan_detail as psd', function ($join) use ($penyerahan) {
        $join->on('psd.id_produk', '=', 'd.id_produk')
            ->where('psd.id_pesanan_penjualan', '=', (int) ($penyerahan->id_pesanan_penjualan ?? 0));
    })
    ->where('d.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select([
        'd.id_produk',
        'd.qty',
        'p.kode_produk',
        'p.nama_produk',
        'p.harga_jual',
        's.nama_satuan',
        'psd.qty as qty_pesanan',
        'psd.harga as harga_pesanan',
        'psd.diskon as diskon_pesanan',
    ])
    ->orderBy('d.id_penyerahan_penjualan_detail', 'asc')
    ->get();

$detail = [];
$subtotalHeader = 0.0;

foreach ($rows as $item) {
    $qtyKirim = (int) $item->qty;
    $qtyPesanan = (float) ($item->qty_pesanan ?? $qtyKirim);

    $harga = (float) ($item->harga_pesanan ?? 0);
    if ($harga <= 0) {
        $harga = (float) ($item->harga_jual ?? 0);
    }

    $diskonPesanan = (float) ($item->diskon_pesanan ?? 0);
    $diskonDetail = 0.0;

    if ($diskonPesanan > 0 && $qtyPesanan > 0) {
        $diskonDetail = round(($diskonPesanan / $qtyPesanan) * $qtyKirim, 2);
    }

    $bruto = round($qtyKirim * $harga, 2);
    $subtotal = max(0, round($bruto - $diskonDetail, 2));

    $subtotalHeader += $subtotal;

    $detail[] = [
        'id_produk' => (int) $item->id_produk,
        'kode_produk' => (string) ($item->kode_produk ?? ''),
        'nama_produk' => (string) ($item->nama_produk ?? ''),
        'nama_satuan' => (string) ($item->nama_satuan ?? ''),
        'qty' => $qtyKirim,
        'harga' => $harga,
        'diskon' => $diskonDetail,
        'subtotal' => $subtotal,
    ];
}

if (count($detail) === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Detail penyerahan penjualan kosong.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$tempo = (int) ($penyerahan->tempo_hari ?? 30);
if ($tempo <= 0) {
    $tempo = 30;
}

echo json_encode([
    'success' => true,
    'message' => 'Detail penyerahan berhasil dimuat.',
    'penyerahan' => [
        'id_penyerahan_penjualan' => (int) $penyerahan->id_penyerahan_penjualan,
        'no_penyerahan_penjualan' => (string) $penyerahan->no_penyerahan_penjualan,
        'tanggal_penyerahan' => (string) $penyerahan->tanggal_penyerahan,
        'id_pelanggan' => (int) $penyerahan->id_pelanggan,
        'kode_pelanggan' => (string) ($penyerahan->kode_pelanggan ?? ''),
        'nama_pelanggan' => (string) ($penyerahan->nama_pelanggan ?? ''),
        'pelanggan_label' => (string) (($penyerahan->kode_pelanggan ?? '-') . ' - ' . ($penyerahan->nama_pelanggan ?? '-')),
        'jatuh_tempo_default' => date('Y-m-d', strtotime('+' . $tempo . ' days')),
    ],
    'summary' => [
        'subtotal' => round($subtotalHeader, 2),
        'diskon' => 0,
        'ppn' => 0,
        'total' => round($subtotalHeader, 2),
    ],
    'rows' => $detail,
], JSON_UNESCAPED_UNICODE);
exit;