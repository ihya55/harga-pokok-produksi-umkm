<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$back_url = (string) ($_GET['back_url'] ?? admin_page_url('persediaan/saldo-stok/saldo-awal'));

$gudang_options = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$coa_options = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->where('boleh_transaksi', 1)
    ->where('kategori_coa', 'ekuitas')
    ->orderBy('kode_coa', 'asc')
    ->get();

$bahan_options = BahanBakuORM::query()
    ->from('tb_bahan_baku as b')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('b.id_entitas', $id_entitas)
    ->where('b.status_aktif', 1)
    ->select([
        'b.*',
        's.nama_satuan',
    ])
    ->orderBy('b.nama_bahan_baku', 'asc')
    ->get();

$produk_options = ProdukORM::query()
    ->from('tb_produk as p')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('p.id_entitas', $id_entitas)
    ->where('p.status_produk', 1)
    ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->select([
        'p.*',
        's.nama_satuan',
    ])
    ->orderBy('p.nama_produk', 'asc')
    ->get();

$data_form = [
    'id_saldo_awal_stok' => null,
    'no_saldo_awal_stok' => '',
    'tanggal_saldo_awal' => date('Y-m-d'),
    'id_gudang' => '',
    'id_coa_lawan' => '',
    'catatan' => '',
    'status_posting' => 'draft',
    'back_url' => $back_url,
];

$detail_rows = [];
$form_action = admin_url('index.php?menu=persediaan/saldo-stok/saldo-awal/simpan');

include __DIR__ . '/saldo_awal_form.php';