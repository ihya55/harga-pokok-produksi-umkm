<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/perintah');
}

$produk_options = Capsule::table('tb_produk')
    ->where('id_entitas', $id_entitas)
    ->where('status_produk', 1)
    ->whereIn('jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->select([
        'id_produk',
        'kode_produk',
        'nama_produk',
        'jenis_produk',
    ])
    ->orderBy('nama_produk', 'asc')
    ->get();

$resep_options = Capsule::table('tb_resep')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->select([
        'id_resep',
        'id_produk',
        'kode_resep',
        'nama_resep',
        'jumlah_hasil',
        'versi_resep',
    ])
    ->orderBy('nama_resep', 'asc')
    ->get();

$pesanan_options = Capsule::table('tb_pesanan_penjualan as ps')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
    ->where('ps.id_entitas', $id_entitas)
    ->select([
        'ps.id_pesanan_penjualan',
        'ps.no_pesanan_penjualan',
        'ps.tanggal_pesanan',
        'ps.status_pesanan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
    ])
    ->orderBy('ps.tanggal_pesanan', 'desc')
    ->limit(100)
    ->get();

$data_form = [
    'no_perintah_produksi' => 'Otomatis saat disimpan',
    'tanggal_perintah'     => date('Y-m-d'),
    'id_produk'            => '',
    'id_resep'             => '',
    'qty_rencana'          => '0',
    'qty_hasil'            => '0',
    'status_produksi'      => 'draft',
    'tanggal_mulai'        => date('Y-m-d'),
    'tanggal_selesai'      => '',
    'id_pesanan_penjualan' => '',
    'catatan'              => '',
    'back_url'             => $back_url,
];

$page_title = 'Tambah Perintah Produksi';
$page_subtitle = 'Buat rencana produksi berdasarkan produk dan resep/BOM';
$form_action = admin_url('menu/produksi/perintah/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';