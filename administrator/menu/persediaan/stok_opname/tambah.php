<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/stok-opname');
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

$data_form = [
    'id_stok_opname' => null,
    'no_stok_opname' => '',
    'tanggal_stok_opname' => date('Y-m-d'),
    'id_gudang' => '',
    'status_posting' => 'draft',
    'catatan' => '',
    'back_url' => $back_url,
];

$detail_rows = [];
$form_action = admin_url('index.php?menu=persediaan/stok-opname/simpan');

include __DIR__ . '/_form.php';