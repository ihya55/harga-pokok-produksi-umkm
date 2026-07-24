<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_perintah_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/perintah');
}

$row = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_perintah_produksi);

if (!$row) {
    set_flash('error', 'Data perintah produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_produksi !== 'draft') {
    set_flash('error', 'Perintah produksi yang sudah posted tidak bisa diedit.');
    header('Location: ' . admin_url('index.php?menu=produksi/perintah/detail&id=' . $id_perintah_produksi . '&back_url=' . urlencode($back_url)));
    exit;
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
    'id_perintah_produksi' => (int) $row->id_perintah_produksi,
    'no_perintah_produksi' => (string) $row->no_perintah_produksi,
    'tanggal_perintah'     => (string) $row->tanggal_perintah,
    'id_produk'            => (string) $row->id_produk,
    'id_resep'             => (string) ($row->id_resep ?? ''),
    'qty_rencana'          => (string) ((int) $row->qty_rencana),
    'qty_hasil'            => (string) ((int) $row->qty_hasil),
    'status_produksi'      => (string) $row->status_produksi,
    'tanggal_mulai'        => (string) ($row->tanggal_mulai ?? ''),
    'tanggal_selesai'      => (string) ($row->tanggal_selesai ?? ''),
    'id_pesanan_penjualan' => (string) ($row->id_pesanan_penjualan ?? ''),
    'catatan'              => (string) ($row->catatan ?? ''),
    'back_url'             => $back_url,
];

$page_title = 'Edit Perintah Produksi';
$page_subtitle = 'Ubah data perintah produksi draft';
$form_action = admin_url('menu/produksi/perintah/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';