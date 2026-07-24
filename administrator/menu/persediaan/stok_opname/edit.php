<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_stok_opname = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/stok-opname');
}

$row = StokOpnameORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_stok_opname', $id_stok_opname)
    ->first();

if (!$row) {
    set_flash('error', 'Data stok opname tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Stok opname yang sudah posted tidak bisa diedit.');
    header('Location: ' . admin_page_url('persediaan/stok-opname/detail') . '&id=' . $id_stok_opname . '&back_url=' . urlencode($back_url));
    exit;
}

$gudang_options = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$saldo_bahan = SaldoStokORM::query()
    ->from('tb_saldo_stok as ss')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'ss.id_referensi_barang')
            ->where('ss.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
    ->where('ss.id_entitas', $id_entitas)
    ->where('ss.jenis_barang', 'bahan_baku')
    ->where('b.status_aktif', 1)
    ->selectRaw("
        ss.id_saldo_stok,
        ss.id_gudang,
        ss.jenis_barang,
        ss.id_referensi_barang,
        ss.qty_saldo,
        ss.hpp_rata_rata,
        ss.nilai_saldo,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
        s.nama_satuan,
        g.kode_gudang,
        g.nama_gudang
    ");

$saldo_produk = SaldoStokORM::query()
    ->from('tb_saldo_stok as ss')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'ss.id_referensi_barang')
            ->where('ss.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
    ->where('ss.id_entitas', $id_entitas)
    ->where('ss.jenis_barang', 'produk')
    ->where('p.status_produk', 1)
    ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->selectRaw("
        ss.id_saldo_stok,
        ss.id_gudang,
        ss.jenis_barang,
        ss.id_referensi_barang,
        ss.qty_saldo,
        ss.hpp_rata_rata,
        ss.nilai_saldo,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
        s.nama_satuan,
        g.kode_gudang,
        g.nama_gudang
    ");

$saldo_options = SaldoStokORM::query()
    ->fromSub($saldo_bahan->unionAll($saldo_produk), 'x')
    ->orderBy('nama_gudang', 'asc')
    ->orderBy('jenis_barang', 'asc')
    ->orderBy('nama_barang', 'asc')
    ->get();

$detail_rows = StokOpnameDetailORM::query()
    ->where('id_stok_opname', $id_stok_opname)
    ->orderBy('id_stok_opname_detail', 'asc')
    ->get();

$data_form = [
    'id_stok_opname' => (int) $row->id_stok_opname,
    'no_stok_opname' => (string) $row->no_stok_opname,
    'tanggal_stok_opname' => (string) $row->tanggal_stok_opname,
    'id_gudang' => (int) $row->id_gudang,
    'status_posting' => (string) $row->status_posting,
    'catatan' => (string) ($row->catatan ?? ''),
    'back_url' => $back_url,
];

$form_action = admin_url('index.php?menu=persediaan/stok-opname/update');

include __DIR__ . '/_form.php';