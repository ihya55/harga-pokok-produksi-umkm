<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id'] ?? 0);
$default_ppn_persen = pajak_default_persen($id_entitas, 'PPN', 11.0);

$row = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_penyerahan_penjualan as pp', 'pp.id_penyerahan_penjualan', '=', 'f.id_penyerahan_penjualan')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'f.id_pelanggan')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'f.*',
        'pp.no_penyerahan_penjualan',
        'pp.tanggal_penyerahan',
        'ps.no_pesanan_penjualan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data piutang pelanggan tidak ditemukan.');
    redirect_admin('penjualan/faktur');
}

if ((string) $row->status_faktur !== 'draft') {
    set_flash('error', 'Faktur yang sudah posted atau batal tidak bisa diedit.');
    redirect_admin('penjualan/faktur');
}

$penyerahan_options = Capsule::table('tb_penyerahan_penjualan as pp')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_penyerahan_penjualan', (int) $row->id_penyerahan_penjualan)
    ->select([
        'pp.id_penyerahan_penjualan',
        'pp.no_penyerahan_penjualan',
        'pp.tanggal_penyerahan',
        'pp.id_pelanggan',
        'ps.no_pesanan_penjualan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.tempo_hari',
    ])
    ->get();

$data_form = [
    'id_faktur_penjualan' => (int) $row->id_faktur_penjualan,
    'no_faktur_penjualan' => (string) $row->no_faktur_penjualan,
    'tanggal_faktur' => (string) $row->tanggal_faktur,
    'id_penyerahan_penjualan' => (int) $row->id_penyerahan_penjualan,
    'id_pelanggan' => (int) $row->id_pelanggan,
    'pelanggan_label' => ($row->kode_pelanggan ?? '-') . ' - ' . ($row->nama_pelanggan ?? '-'),
    'jenis_pembayaran' => (string) $row->jenis_pembayaran,
    'status_faktur' => (string) $row->status_faktur,
    'jatuh_tempo' => (string) ($row->jatuh_tempo ?? ''),
    'subtotal' => (float) $row->subtotal,
    'diskon' => (float) $row->diskon,
    'ppn' => (float) $row->ppn,
    'ppn_persen' => (float) ($row->ppn_persen ?? $default_ppn_persen),
    'total' => (float) $row->total,
    'sisa_piutang' => (float) $row->sisa_piutang,
    'catatan' => (string) ($row->catatan ?? ''),
];

$detail_rows = Capsule::table('tb_faktur_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_faktur_penjualan_detail', 'asc')
    ->get()
    ->map(function ($item) {
        return [
            'id_produk' => (int) $item->id_produk,
            'kode_produk' => (string) ($item->kode_produk ?? ''),
            'nama_produk' => (string) ($item->nama_produk ?? ''),
            'nama_satuan' => (string) ($item->nama_satuan ?? ''),
            'qty' => (int) $item->qty,
            'harga' => (float) $item->harga,
            'diskon' => (float) $item->diskon,
            'subtotal' => (float) $item->subtotal,
        ];
    })
    ->toArray();

$form_action = admin_page_url('penjualan/faktur/update');
$page_subtitle = 'Edit draft piutang pelanggan. Faktur posted tidak bisa diubah.';

require __DIR__ . '/_form.php';