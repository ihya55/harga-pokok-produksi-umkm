<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pesanan_pembelian = (int) ($_GET['id_pesanan_pembelian'] ?? 0);

$po_options = PesananPembelianORM::query()
    ->from('tb_pesanan_pembelian as po')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'po.id_pemasok')
    ->where('po.id_entitas', $id_entitas)
    ->where('po.status_pesanan', 'terkonfirmasi')
    ->select([
        'po.id_pesanan_pembelian',
        'po.no_pesanan_pembelian',
        'po.tanggal_pesanan',
        'po.id_pemasok',
        'p.kode_pemasok',
        'p.nama_pemasok',
    ])
    ->orderBy('po.tanggal_pesanan', 'desc')
    ->get();


$pemasok_options = PemasokORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_pemasok', 'asc')
    ->get();

$gudang_options = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$bahan_options = BahanBakuORM::query()
    ->from('tb_bahan_baku as b')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('b.id_entitas', $id_entitas)
    ->where('b.status_aktif', 1)
    ->select([
        'b.id_bahan_baku',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        'b.harga_standar',
        's.nama_satuan',
    ])
    ->orderBy('b.nama_bahan_baku', 'asc')
    ->get();

$selected_po = null;
$detail_rows = [];

if ($id_pesanan_pembelian > 0) {
    $selected_po = PesananPembelianORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('status_pesanan', 'terkonfirmasi')
        ->find($id_pesanan_pembelian);

    if ($selected_po) {
        $detail_rows = PesananPembelianDetailORM::query()
            ->where('id_pesanan_pembelian', $id_pesanan_pembelian)
            ->get()
            ->map(function ($item) {
                return [
                    'id_bahan_baku' => (string) $item->id_bahan_baku,
                    'qty'           => (string) ((int) $item->qty),
                    'harga'         => (string) $item->harga,
                    'subtotal'      => (string) $item->subtotal,
                ];
            })
            ->toArray();
    }
}

$data_form = [
    'no_penerimaan_pembelian' => 'Otomatis saat disimpan',
    'tanggal_penerimaan'      => date('Y-m-d'),
    'id_pesanan_pembelian'    => $selected_po ? (string) $selected_po->id_pesanan_pembelian : '',
    'id_pemasok'              => $selected_po ? (string) $selected_po->id_pemasok : '',
    'id_gudang'               => '',
    'status_penerimaan'       => 'draft',
    'catatan'                 => '',
];

$page_title = 'Tambah Penerimaan Pembelian';
$page_subtitle = 'Input penerimaan bahan baku dari pesanan pembelian';
$form_action = admin_url('menu/pembelian/penerimaan/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';