<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

function kasir_barcode_json(array $payload, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function kasir_normalisasi_barcode_scan(string $barcode): string
{
    $barcode = trim($barcode);
    $barcode = preg_replace('/[\r\n\t]+/', '', $barcode) ?? '';
    $barcode = preg_replace('/\s+/', '', $barcode) ?? '';

    return strtoupper($barcode);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    kasir_barcode_json([
        'success' => false,
        'message' => 'Metode request tidak valid.',
    ], 405);
}

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);
$barcode_raw = (string) ($_GET['barcode'] ?? '');
$barcode = kasir_normalisasi_barcode_scan($barcode_raw);

if ($id_entitas <= 0) {
    kasir_barcode_json([
        'success' => false,
        'message' => 'Entitas pengguna tidak valid.',
    ], 403);
}

if ($id_gudang <= 0) {
    kasir_barcode_json([
        'success' => false,
        'message' => 'Gudang belum dipilih.',
    ], 422);
}

if ($barcode === '') {
    kasir_barcode_json([
        'success' => false,
        'message' => 'Kode barcode kosong.',
    ], 422);
}

$row = Capsule::table('tb_saldo_stok as ss')
    ->join('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'ss.id_referensi_barang')
            ->where('ss.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
    ->leftJoin('tb_kategori_produk as kp', 'kp.id_kategori_produk', '=', 'p.id_kategori_produk')
    ->where('ss.id_entitas', $id_entitas)
    ->where('p.id_entitas', $id_entitas)
    ->where('ss.id_gudang', $id_gudang)
    ->where('p.status_produk', 1)
    ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->where(function ($q) use ($barcode) {
        $q->whereRaw("UPPER(REPLACE(COALESCE(p.barcode_produk, ''), ' ', '')) = ?", [$barcode])
            ->orWhereRaw("UPPER(REPLACE(COALESCE(p.kode_produk, ''), ' ', '')) = ?", [$barcode]);
    })
    ->select([
        'ss.id_gudang',
        'ss.qty_saldo',
        'ss.hpp_rata_rata',
        'p.id_produk',
        'p.kode_produk',
        'p.barcode_produk',
        'p.nama_produk',
        'p.jenis_produk',
        'p.harga_jual',
        'p.gambar_produk',
        's.nama_satuan',
        'g.kode_gudang',
        'g.nama_gudang',
        'kp.nama_kategori_produk',
    ])
    ->orderByDesc('ss.qty_saldo')
    ->first();

if (!$row) {
    kasir_barcode_json([
        'success' => false,
        'message' => 'Barcode/kode produk tidak ditemukan pada gudang terpilih.',
        'barcode' => $barcode,
    ], 404);
}

$gambar_url = '';

if (!empty($row->gambar_produk)) {
    $gambar_url = (string) $row->gambar_produk;

    if (!preg_match('/^(https?:)?\/\//i', $gambar_url) && strpos($gambar_url, '/') !== 0) {
        $gambar_url = '../uploads/produk/' . ltrim($gambar_url, '/');
    }
}

kasir_barcode_json([
    'success' => true,
    'message' => 'Produk ditemukan.',
    'produk' => [
        'id_gudang' => (int) $row->id_gudang,
        'id_produk' => (int) $row->id_produk,
        'kode_produk' => (string) ($row->kode_produk ?? ''),
        'barcode_produk' => (string) ($row->barcode_produk ?? ''),
        'nama_produk' => (string) ($row->nama_produk ?? ''),
        'jenis_produk' => (string) ($row->jenis_produk ?? ''),
        'kategori' => (string) ($row->nama_kategori_produk ?? 'Tanpa Kategori'),
        'nama_satuan' => (string) ($row->nama_satuan ?? ''),
        'harga_jual' => (float) ($row->harga_jual ?? 0),
        'hpp_rata_rata' => (float) ($row->hpp_rata_rata ?? 0),
        'qty_saldo' => (float) ($row->qty_saldo ?? 0),
        'gambar_produk' => $gambar_url,
        'nama_gudang' => (string) ($row->nama_gudang ?? ''),
    ],
]);
