<?php
require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_penyerahan_penjualan = (int) ($_GET['id'] ?? 0);

$entitas = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

$row = Capsule::table('tb_penyerahan_penjualan as pp')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select([
        'pp.*',
        'ps.no_pesanan_penjualan',
        'ps.status_pesanan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data penyerahan penjualan tidak ditemukan.');
    redirect_admin('penjualan/penyerahan');
}

if ((string) $row->status_penyerahan !== 'draft') {
    set_flash('error', 'Penyerahan yang sudah posted atau batal tidak bisa diedit.');
    redirect_admin('penjualan/penyerahan');
}

if (empty($row->id_pesanan_penjualan)) {
    set_flash('error', 'Penyerahan lama ini tidak memiliki referensi pesanan. Buat ulang penyerahan dari pesanan penjualan.');
    redirect_admin('penjualan/penyerahan');
}

$pesanan_options = Capsule::table('tb_pesanan_penjualan as ps')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
    ->where('ps.id_entitas', $id_entitas)
    ->where('ps.id_pesanan_penjualan', (int) $row->id_pesanan_penjualan)
    ->select([
        'ps.id_pesanan_penjualan',
        'ps.no_pesanan_penjualan',
        'ps.tanggal_pesanan',
        'ps.id_pelanggan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
    ])
    ->get();

$gudang_options = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$produk_options = Capsule::table('tb_pesanan_penjualan_detail as psd')
    ->join('tb_produk as p', 'p.id_produk', '=', 'psd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->join('tb_saldo_stok as ss', function ($join) use ($id_entitas, $row) {
        $join->on('ss.id_referensi_barang', '=', 'p.id_produk')
            ->where('ss.id_entitas', '=', $id_entitas)
            ->where('ss.jenis_barang', '=', 'produk')
            ->where('ss.id_gudang', '=', (int) $row->id_gudang)
            ->where('ss.qty_saldo', '>', 0);
    })
    ->where('psd.id_pesanan_penjualan', (int) $row->id_pesanan_penjualan)
    ->where('p.id_entitas', $id_entitas)
    ->where('p.status_produk', 1)
    ->select([
        'p.id_produk',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
        'ss.qty_saldo',
        'ss.hpp_rata_rata',
        'psd.qty as qty_pesanan',
    ])
    ->orderBy('p.nama_produk', 'asc')
    ->get();

$data_form = [
    'id_penyerahan_penjualan' => (int) $row->id_penyerahan_penjualan,
    'no_penyerahan_penjualan' => (string) $row->no_penyerahan_penjualan,
    'tanggal_penyerahan' => (string) $row->tanggal_penyerahan,
    'id_pesanan_penjualan' => (int) $row->id_pesanan_penjualan,
    'id_pelanggan' => (int) $row->id_pelanggan,
    'pelanggan_label' => ($row->kode_pelanggan ?? '-') . ' - ' . ($row->nama_pelanggan ?? '-'),
    'id_gudang' => (int) $row->id_gudang,
    'jenis_alamat_tujuan' => (string) ($row->jenis_alamat_tujuan ?? 'pelanggan'),
    'alamat_tujuan' => (string) ($row->alamat_tujuan ?? $row->alamat_pelanggan ?? ''),
    'alamat_pelanggan' => (string) ($row->alamat_pelanggan ?? ''),
    'alamat_toko' => (string) ($entitas->alamat ?? ''),
    'status_penyerahan' => (string) $row->status_penyerahan,
    'kurir_pengiriman' => (string) ($row->kurir_pengiriman ?? ''),
    'no_resi_pengiriman' => (string) ($row->no_resi_pengiriman ?? ''),
    'biaya_pengiriman' => (string) ($row->biaya_pengiriman ?? '0.00'),
    'id_coa_biaya_pengiriman' => (string) (($row->id_coa_biaya_pengiriman ?? 0) ?: penjualan_default_akun_biaya_pengiriman($id_entitas)),
    'auto_load_scan' => false,
    'catatan' => (string) ($row->catatan ?? ''),
];

$detail_rows = Capsule::table('tb_penyerahan_penjualan_detail as d')
    ->leftJoin('tb_saldo_stok as ss', function ($join) use ($id_entitas, $row) {
        $join->on('ss.id_referensi_barang', '=', 'd.id_produk')
            ->where('ss.id_entitas', '=', $id_entitas)
            ->where('ss.jenis_barang', '=', 'produk')
            ->where('ss.id_gudang', '=', (int) $row->id_gudang);
    })
    ->leftJoin('tb_pesanan_penjualan_detail as psd', function ($join) use ($row) {
        $join->on('psd.id_produk', '=', 'd.id_produk')
            ->where('psd.id_pesanan_penjualan', '=', (int) $row->id_pesanan_penjualan);
    })
    ->where('d.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select([
        'd.id_produk',
        'd.qty',
        'd.hpp_satuan',
        'd.hpp_total',
        'd.catatan',
        'ss.qty_saldo',
        'ss.hpp_rata_rata',
        'psd.qty as qty_pesanan',
    ])
    ->orderBy('d.id_penyerahan_penjualan_detail', 'asc')
    ->get()
    ->map(function ($item) {
        $hpp = (float) ($item->hpp_rata_rata ?? 0);

        return [
            'id_produk' => (int) $item->id_produk,
            'qty' => (int) $item->qty,
            'qty_pesanan' => (int) ($item->qty_pesanan ?? 0),
            'qty_saldo' => (float) ($item->qty_saldo ?? 0),
            'hpp_satuan' => $hpp > 0 ? $hpp : (float) $item->hpp_satuan,
            'hpp_total' => ((float) $item->qty) * ($hpp > 0 ? $hpp : (float) $item->hpp_satuan),
            'catatan' => (string) ($item->catatan ?? ''),
        ];
    })
    ->toArray();

$form_action = admin_page_url('penjualan/penyerahan/update');
$page_subtitle = 'Edit draft penyerahan. Produk dan HPP mengikuti saldo stok gudang terpilih.';

require __DIR__ . '/_form.php';