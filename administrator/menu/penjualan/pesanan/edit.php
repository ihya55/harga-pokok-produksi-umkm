<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_fungsi_penjualan.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id'] ?? 0);

$pesanan = Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
    ->first();

if (!$pesanan) {
    ?>
    <div class="alert alert-danger">
        Data pesanan penjualan tidak ditemukan.
    </div>

    <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <?php
    return;
}

if ((string) ($pesanan->status_pesanan ?? '') !== 'draft') {
    ?>
    <div class="alert alert-warning">
        Pesanan ini tidak bisa diedit karena statusnya bukan draft.
    </div>

    <a href="<?= esc(admin_page_url('penjualan/pesanan/detail') . '&id=' . (int) $pesanan->id_pesanan_penjualan) ?>" class="btn btn-outline-primary">
        <i class="bi bi-eye me-1"></i> Detail
    </a>

    <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <?php
    return;
}

$detail_rows = Capsule::table('tb_pesanan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.barcode_produk',
        'p.nama_produk',
        'p.jenis_produk',
        'p.harga_jual',
        'p.hpp_standar as hpp_standar_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_pesanan_penjualan_detail', 'asc')
    ->get();

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

$mode_form = 'edit';
$judul_halaman = 'Edit Pesanan Penjualan';
$subtitle_halaman = 'Ubah pesanan penjualan selama status masih draft.';
$action_url = admin_url('index.php?menu=penjualan/pesanan/update&id=' . (int) $pesanan->id_pesanan_penjualan);

require __DIR__ . '/_form.php';