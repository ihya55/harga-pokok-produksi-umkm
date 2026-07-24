<?php
require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id_pesanan_penjualan'] ?? 0);
$id_gudang_scan = (int) ($_GET['id_gudang'] ?? 0);
$auto_load_scan = (int) ($_GET['auto_load'] ?? 0) === 1;
$auto_cod_online = (int) ($_GET['auto_cod'] ?? 0) === 1;
$kode_pesanan_online_scan = trim((string) ($_GET['kode'] ?? ''));
$id_coa_biaya_pengiriman_default = penjualan_default_akun_biaya_pengiriman($id_entitas);

$entitas = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

$pesanan_options = Capsule::table('tb_pesanan_penjualan as ps')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
    ->where('ps.id_entitas', $id_entitas)
    ->where(function ($q) {
        $q->where('ps.status_pesanan', 'terkonfirmasi')
          ->orWhere(function ($cod) {
              $cod->where('ps.sumber_pesanan', 'website')
                  ->where('ps.metode_pembayaran_online', 'cod')
                  ->whereIn('ps.status_pesanan', ['draft', 'terkonfirmasi', 'diproses']);
          });
    })
    ->whereNotExists(function ($sub) use ($id_entitas) {
        $sub->selectRaw('1')
            ->from('tb_penyerahan_penjualan as pp')
            ->whereColumn('pp.id_pesanan_penjualan', 'ps.id_pesanan_penjualan')
            ->where('pp.id_entitas', $id_entitas)
            ->whereIn('pp.status_penyerahan', ['draft', 'posted']);
    })
    ->select([
        'ps.id_pesanan_penjualan',
        'ps.no_pesanan_penjualan',
        'ps.tanggal_pesanan',
        'ps.id_pelanggan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'ps.sumber_pesanan',
        'ps.metode_pembayaran_online',
        'ps.status_pembayaran_online',
        'ps.total',
        'ps.nominal_pembayaran_online',
    ])
    ->orderBy('ps.tanggal_pesanan', 'desc')
    ->orderBy('ps.id_pesanan_penjualan', 'desc')
    ->get();

$gudang_options = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$data_form = [
    'id_penyerahan_penjualan' => null,
    'no_penyerahan_penjualan' => 'Otomatis saat disimpan',
    'tanggal_penyerahan' => date('Y-m-d'),
    'id_pesanan_penjualan' => $id_pesanan_penjualan ?: '',
    'id_pelanggan' => '',
    'pelanggan_label' => '',
    'id_gudang' => $id_gudang_scan > 0 ? (string) $id_gudang_scan : '',
    'jenis_alamat_tujuan' => 'pelanggan',
    'alamat_tujuan' => '',
    'alamat_pelanggan' => '',
    'alamat_toko' => (string) ($entitas->alamat ?? ''),
    'status_penyerahan' => 'draft',
    'kurir_pengiriman' => '',
    'no_resi_pengiriman' => '',
    'biaya_pengiriman' => '0.00',
    'id_coa_biaya_pengiriman' => $id_coa_biaya_pengiriman_default > 0 ? (string) $id_coa_biaya_pengiriman_default : '',
    'auto_load_scan' => $auto_load_scan,
    'auto_cod_online' => $auto_cod_online,
    'kode_pesanan_online' => $kode_pesanan_online_scan,
    'metode_pembayaran_online' => '',
    'total_pesanan' => '0.00',
    'catatan' => '',
];

if ($id_pesanan_penjualan > 0) {
    $pesanan = Capsule::table('tb_pesanan_penjualan as ps')
        ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
        ->where('ps.id_entitas', $id_entitas)
        ->where('ps.id_pesanan_penjualan', $id_pesanan_penjualan)
        ->where(function ($q) {
            $q->where('ps.status_pesanan', 'terkonfirmasi')
              ->orWhere(function ($cod) {
                  $cod->where('ps.sumber_pesanan', 'website')
                      ->where('ps.metode_pembayaran_online', 'cod')
                      ->whereIn('ps.status_pesanan', ['draft', 'terkonfirmasi', 'diproses']);
              });
        })
        ->select([
            'ps.id_pelanggan',
            'ps.no_pesanan_penjualan',
            'pl.kode_pelanggan',
            'pl.nama_pelanggan',
            'pl.alamat as alamat_pelanggan',
            'ps.metode_pembayaran_online',
            'ps.nominal_pembayaran_online',
            'ps.total',
        ])
        ->first();

    if ($pesanan) {
        $data_form['id_pelanggan'] = (int) $pesanan->id_pelanggan;
        $data_form['pelanggan_label'] = ($pesanan->kode_pelanggan ?? '-') . ' - ' . ($pesanan->nama_pelanggan ?? '-');
        $data_form['alamat_pelanggan'] = (string) ($pesanan->alamat_pelanggan ?? '');
        $data_form['alamat_tujuan'] = (string) ($pesanan->alamat_pelanggan ?? '');
        $data_form['metode_pembayaran_online'] = (string) ($pesanan->metode_pembayaran_online ?? '');
        $data_form['total_pesanan'] = (string) (($pesanan->nominal_pembayaran_online ?? null) ?: ($pesanan->total ?? 0));
        if ($data_form['kode_pesanan_online'] === '') {
            $data_form['kode_pesanan_online'] = (string) ($pesanan->no_pesanan_penjualan ?? '');
        }
    }
}

$detail_rows = [];
$produk_options = [];

$form_action = admin_page_url('penjualan/penyerahan/simpan');
$page_subtitle = 'Gunakan menu ini saat barang pesanan sudah diambil/dikirim. Untuk COD, kasir langsung menerima uang di popup pembayaran.';

require __DIR__ . '/_form.php';