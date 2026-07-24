<?php
declare(strict_types=1);

require_once __DIR__ . '/_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

header('Content-Type: application/json; charset=utf-8');

function po_json_response(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$id_entitas = po_id_entitas();
$id = (int) ($_REQUEST['id'] ?? $_REQUEST['id_pesanan_penjualan'] ?? 0);
$no_hp = trim((string) ($_REQUEST['no_hp'] ?? ''));

try {
    if (!Capsule::schema()->hasTable('tb_pesanan_online_chat')) {
        throw new RuntimeException('Tabel chat belum tersedia. Jalankan SQL update terlebih dahulu.');
    }
    if (!Capsule::schema()->hasColumn('tb_pesanan_online_chat', 'id_pelanggan')) {
        throw new RuntimeException('Kolom id_pelanggan pada tabel chat belum tersedia. Import SQL patch terlebih dahulu agar chat tidak bercampur.');
    }

    $pesanan = Capsule::table('tb_pesanan_penjualan as pp')
        ->join('tb_pelanggan as p', 'p.id_pelanggan', '=', 'pp.id_pelanggan')
        ->where('pp.id_entitas', $id_entitas)
        ->where('pp.id_pesanan_penjualan', $id)
        ->select(['pp.*', 'p.nama_pelanggan', 'p.no_hp'])
        ->first();

    if (!$pesanan) {
        throw new RuntimeException('Pesanan tidak ditemukan.');
    }

    if ($no_hp !== '' && preg_replace('/\D+/', '', (string) $pesanan->no_hp) !== preg_replace('/\D+/', '', $no_hp)) {
        throw new RuntimeException('No HP tidak cocok dengan pesanan.');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $pesan = trim((string) ($_POST['pesan'] ?? ''));
        if ($pesan === '') {
            throw new RuntimeException('Pesan tidak boleh kosong.');
        }

        Capsule::table('tb_pesanan_online_chat')->insert([
            'id_entitas' => $id_entitas,
            'id_pesanan_penjualan' => (int) $pesanan->id_pesanan_penjualan,
            'id_pelanggan' => (int) ($pesanan->id_pelanggan ?? 0),
            'pengirim_tipe' => 'customer',
            'nama_pengirim' => (string) ($pesanan->nama_pelanggan ?? 'Customer'),
            'pesan' => $pesan,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => null,
        ]);
    }

    $rows = po_chat_rows((int) $pesanan->id_pesanan_penjualan, (int) ($pesanan->id_pelanggan ?? 0))->map(function ($row) {
        return [
            'id_chat' => (int) $row->id_chat,
            'pengirim_tipe' => (string) $row->pengirim_tipe,
            'nama_pengirim' => (string) ($row->nama_pengirim ?? ''),
            'pesan' => (string) $row->pesan,
            'tanggal' => date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)),
        ];
    })->values()->all();

    $produk = Capsule::table('tb_pesanan_penjualan_detail as d')
        ->join('tb_produk as pr', 'pr.id_produk', '=', 'd.id_produk')
        ->where('d.id_pesanan_penjualan', (int) $pesanan->id_pesanan_penjualan)
        ->select(['pr.nama_produk', 'pr.kode_produk', 'd.qty', 'd.harga'])
        ->orderBy('d.id_pesanan_penjualan_detail')
        ->get()
        ->map(fn($p) => [
            'nama_produk' => (string) $p->nama_produk,
            'kode_produk' => (string) $p->kode_produk,
            'qty' => po_qty($p->qty),
            'harga' => po_uang($p->harga),
        ])->values()->all();

    po_json_response([
        'ok' => true,
        'pesanan' => [
            'id' => (int) $pesanan->id_pesanan_penjualan,
            'no' => (string) $pesanan->no_pesanan_penjualan,
            'nama' => (string) ($pesanan->nama_pelanggan ?? 'Customer'),
        ],
        'produk' => $produk,
        'rows' => $rows,
    ]);
} catch (Throwable $e) {
    po_json_response(['ok' => false, 'message' => $e->getMessage()], 400);
}
