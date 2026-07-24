<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_penerimaan_pembelian = (int) ($_GET['id'] ?? 0);

$row = PenerimaanPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_penerimaan_pembelian);

if (!$row) {
    set_flash('error', 'Data penerimaan pembelian tidak ditemukan.');
    redirect_admin('pembelian/penerimaan');
}

if ((string) $row->status_penerimaan !== 'draft') {
    set_flash('error', 'Penerimaan yang sudah diposting tidak bisa diedit.');
    redirect_admin('pembelian/penerimaan/detail&id=' . $id_penerimaan_pembelian);
}

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
    ->orderBy('nama_gudang', 'asc')
    ->get();

$bahan_options = BahanBakuORM::query()
    ->from('tb_bahan_baku as b')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('b.id_entitas', $id_entitas)
    ->select([
        'b.id_bahan_baku',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        'b.harga_standar',
        's.nama_satuan',
    ])
    ->orderBy('b.nama_bahan_baku', 'asc')
    ->get();

$detail_rows = PenerimaanPembelianDetailORM::query()
    ->where('id_penerimaan_pembelian', $id_penerimaan_pembelian)
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

$data_form = [
    'id_penerimaan_pembelian' => (int) $row->id_penerimaan_pembelian,
    'no_penerimaan_pembelian' => (string) $row->no_penerimaan_pembelian,
    'tanggal_penerimaan'      => (string) $row->tanggal_penerimaan,
    'id_pesanan_pembelian'    => (string) ($row->id_pesanan_pembelian ?? ''),
    'id_pemasok'              => (string) $row->id_pemasok,
    'id_gudang'               => (string) $row->id_gudang,
    'status_penerimaan'       => (string) $row->status_penerimaan,
    'catatan'                 => (string) ($row->catatan ?? ''),
];

$page_title = 'Edit Penerimaan Pembelian';
$page_subtitle = 'Ubah penerimaan bahan baku';
$form_action = admin_url('menu/pembelian/penerimaan/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';