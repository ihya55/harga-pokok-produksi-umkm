<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pesanan_pembelian = (int) ($_GET['id'] ?? 0);

$row = PesananPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pesanan_pembelian);

if (!$row) {
    set_flash('error', 'Data pesanan pembelian tidak ditemukan.');
    redirect_admin('pembelian/pesanan');
}

if ((string) $row->status_pesanan !== 'draft') {
    set_flash('error', 'Pesanan pembelian yang sudah terkonfirmasi tidak bisa diedit.');
    redirect_admin('pembelian/pesanan/detail&id=' . $id_pesanan_pembelian);
}

$pemasok_options = PemasokORM::query()
    ->where('id_entitas', $id_entitas)
    ->orderBy('nama_pemasok', 'asc')
    ->get();

$bahan_options = BahanBakuORM::query()
    ->from('tb_bahan_baku as bb')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'bb.id_satuan')
    ->where('bb.id_entitas', $id_entitas)
    ->select([
        'bb.id_bahan_baku',
        'bb.kode_bahan_baku',
        'bb.nama_bahan_baku',
        'bb.harga_standar',
        'bb.id_satuan',
        's.nama_satuan',
    ])
    ->orderBy('bb.nama_bahan_baku', 'asc')
    ->get();

$detail_rows = PesananPembelianDetailORM::query()
    ->where('id_pesanan_pembelian', $id_pesanan_pembelian)
    ->get()
    ->map(function ($item) {
        return [
            'id_bahan_baku' => (string) $item->id_bahan_baku,
            'qty'           => (string) ((int) $item->qty),
            'harga'         => (string) $item->harga,
            'tipe_diskon'   => 'nominal',
            'diskon'        => (string) $item->diskon,
            'subtotal'      => (string) $item->subtotal,
        ];
    })
    ->toArray();

$data_form = [
    'id_pesanan_pembelian'   => (int) $row->id_pesanan_pembelian,
    'no_pesanan_pembelian'   => (string) $row->no_pesanan_pembelian,
    'tanggal_pesanan'        => (string) $row->tanggal_pesanan,
    'id_pemasok'             => (string) $row->id_pemasok,
    'status_pesanan'         => (string) $row->status_pesanan,
    'tanggal_datang_rencana' => (string) ($row->tanggal_datang_rencana ?? ''),
    'catatan'                => (string) ($row->catatan ?? ''),
    'subtotal'               => (string) $row->subtotal,
    'diskon'                 => (string) $row->diskon,
    'total'                  => (string) $row->total,
];

$page_title = 'Edit Pesanan Pembelian';
$page_subtitle = 'Ubah dokumen pesanan pembelian bahan baku';
$form_action = admin_url('menu/pembelian/pesanan/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';