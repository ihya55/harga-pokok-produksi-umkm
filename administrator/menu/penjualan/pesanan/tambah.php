<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_fungsi_penjualan.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$pesanan = null;
$detail_rows = collect();

$pelanggan_options = Capsule::table('tb_pelanggan')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_pelanggan', 'asc')
    ->get();

$produk_options = Capsule::table('tb_produk as p')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_kategori_produk as kp', 'kp.id_kategori_produk', '=', 'p.id_kategori_produk')
    ->where('p.id_entitas', $id_entitas)
    ->where('p.status_produk', 1)
    ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->select([
        'p.id_produk',
        'p.kode_produk',
        'p.barcode_produk',
        'p.nama_produk',
        'p.jenis_produk',
        'p.harga_jual',
        'p.hpp_standar',
        's.nama_satuan',
        'kp.nama_kategori_produk',
    ])
    ->orderBy('kp.nama_kategori_produk', 'asc')
    ->orderBy('p.nama_produk', 'asc')
    ->get();

$mode_form = 'tambah';
$judul_halaman = 'Tambah Pesanan Penjualan';
$subtitle_halaman = 'Input pesanan pelanggan sebelum proses penyerahan dan faktur.';
$action_url = admin_url('index.php?menu=penjualan/pesanan/simpan');

require __DIR__ . '/_form.php';