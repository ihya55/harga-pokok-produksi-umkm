<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id = (int) ($_GET['id'] ?? 0);
$back_url = (string) ($_GET['back_url'] ?? admin_page_url('persediaan/saldo-stok/saldo-awal'));

$row = SaldoAwalStokORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_saldo_awal_stok', $id)
    ->first();

if (!$row) {
    set_flash('error', 'Data saldo awal stok tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Saldo awal stok yang sudah posted tidak bisa diedit.');
    header('Location: ' . admin_page_url('persediaan/saldo-stok/saldo-awal/detail') . '&id=' . $id);
    exit;
}

$detail_rows = SaldoAwalStokDetailORM::query()
    ->where('id_saldo_awal_stok', $id)
    ->orderBy('id_saldo_awal_stok_detail', 'asc')
    ->get();

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
    'id_saldo_awal_stok' => (int) $row->id_saldo_awal_stok,
    'no_saldo_awal_stok' => (string) $row->no_saldo_awal_stok,
    'tanggal_saldo_awal' => (string) $row->tanggal_saldo_awal,
    'id_gudang' => (int) $row->id_gudang,
    'id_coa_lawan' => (int) $row->id_coa_lawan,
    'catatan' => (string) ($row->catatan ?? ''),
    'status_posting' => (string) $row->status_posting,
    'back_url' => $back_url,
];

$form_action = admin_url('index.php?menu=persediaan/saldo-stok/saldo-awal/update');

include __DIR__ . '/saldo_awal_form.php';