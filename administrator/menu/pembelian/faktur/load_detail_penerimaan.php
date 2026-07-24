<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PenerimaanPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PenerimaanPembelianDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

header('Content-Type: application/json; charset=utf-8');

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_penerimaan_pembelian = (int) ($_GET['id_penerimaan_pembelian'] ?? 0);

$penerimaan = PenerimaanPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_penerimaan', 'posted')
    ->find($id_penerimaan_pembelian);

if (!$penerimaan) {
    echo json_encode([
        'success' => false,
        'message' => 'Penerimaan pembelian tidak valid atau belum posted.',
        'rows' => [],
    ]);
    exit;
}

$rows = PenerimaanPembelianDetailORM::query()
    ->from('tb_penerimaan_pembelian_detail as pnd')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'pnd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('pnd.id_penerimaan_pembelian', $id_penerimaan_pembelian)
    ->select([
        'pnd.id_bahan_baku',
        'pnd.qty',
        'pnd.harga',
        'pnd.subtotal',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('pnd.id_penerimaan_pembelian_detail', 'asc')
    ->get()
    ->map(function ($item) {
        return [
            'id_bahan_baku'   => (int) $item->id_bahan_baku,
            'kode_bahan_baku' => (string) ($item->kode_bahan_baku ?? ''),
            'nama_bahan_baku' => (string) ($item->nama_bahan_baku ?? ''),
            'nama_satuan'     => (string) ($item->nama_satuan ?? ''),
            'qty'             => (int) $item->qty,
            'harga'           => (float) $item->harga,
            'diskon'          => 0,
            'subtotal'        => (float) $item->subtotal,
        ];
    })
    ->toArray();

echo json_encode([
    'success' => true,
    'message' => 'Detail penerimaan berhasil dimuat.',
    'rows' => $rows,
], JSON_UNESCAPED_UNICODE);
exit;