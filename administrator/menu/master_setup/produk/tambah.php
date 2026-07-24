<?php
require_once __DIR__ . '/helpers_produk.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$kategori_options = KategoriProdukORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_kategori_produk', 'asc')
    ->get();

$satuan_options = SatuanORM::query()
    ->orderBy('nama_satuan', 'asc')
    ->get();

$coa_options = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->where('boleh_transaksi', 1)
    ->orderBy('kode_coa', 'asc')
    ->get();

$jenis_produk_options = opsi_jenis_produk();
$default_coa_produk = mapping_default_coa_produk($id_entitas);

$data_form = [
    'kode_produk'          => 'Otomatis saat disimpan',
    'barcode_produk'       => '',
    'nama_produk'          => '',
    'id_kategori_produk'   => '',
    'id_satuan'            => '',
    'jenis_produk'         => 'barang_jadi',
    'harga_jual'           => '0',
    'hpp_standar'          => '0',
    'stok_minimum'         => '0',
    'berat_bersih'         => '',
    'satuan_berat_bersih'  => 'gram',
    'gambar_produk'        => '',
    'id_coa_penjualan'     => (string) ($default_coa_produk['barang_jadi']['id_coa_penjualan'] ?? ''),
    'id_coa_hpp'           => (string) ($default_coa_produk['barang_jadi']['id_coa_hpp'] ?? ''),
    'id_coa_persediaan'    => (string) ($default_coa_produk['barang_jadi']['id_coa_persediaan'] ?? ''),
    'status_produk'        => '1',
];

$page_subtitle = 'Tambah data produk baru';
$form_action = admin_url('menu/master_setup/produk/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';