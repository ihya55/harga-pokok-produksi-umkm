<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$pemasok_options = PemasokORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_pemasok', 'asc')
    ->get();

$bahan_options = BahanBakuORM::query()
    ->from('tb_bahan_baku as bb')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'bb.id_satuan')
    ->where('bb.id_entitas', $id_entitas)
    ->where('bb.status_aktif', 1)
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

$data_form = [
    'no_pesanan_pembelian'   => 'Otomatis saat disimpan',
    'tanggal_pesanan'        => date('Y-m-d'),
    'id_pemasok'             => '',
    'status_pesanan'         => 'draft',
    'tanggal_datang_rencana' => '',
    'catatan'                => '',
    'subtotal'               => '0.00',
    'diskon'                 => '0.00',
    'total'                  => '0.00',
];

$detail_rows = [];

$page_title = 'Tambah Pesanan Pembelian';
$page_subtitle = 'Tambah dokumen pesanan pembelian bahan baku';
$form_action = admin_url('menu/pembelian/pesanan/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';