<?php
require_once __DIR__ . '/helpers_produk.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_produk = (int) ($_GET['id'] ?? 0);

$row = ProdukORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_produk);

if (!$row) {
    set_flash('error', 'Data produk tidak ditemukan.');
    redirect_admin('master_setup/produk');
}

$kategori_options = KategoriProdukORM::query()
    ->where('id_entitas', $id_entitas)
    ->orderBy('nama_kategori_produk', 'asc')
    ->get();

$satuan_options = SatuanORM::query()
    ->orderBy('nama_satuan', 'asc')
    ->get();

$coa_options = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('boleh_transaksi', 1)
    ->orderBy('kode_coa', 'asc')
    ->get();

$jenis_produk_options = opsi_jenis_produk();
$default_coa_produk = mapping_default_coa_produk($id_entitas);

$data_form = [
    'id_produk'            => (int) $row->id_produk,
    'kode_produk'          => (string) $row->kode_produk,
    'barcode_produk'       => (string) ($row->barcode_produk ?? ''),
    'nama_produk'          => (string) $row->nama_produk,
    'id_kategori_produk'   => (string) ($row->id_kategori_produk ?? ''),
    'id_satuan'            => (string) $row->id_satuan,
    'jenis_produk'         => (string) $row->jenis_produk,
    'harga_jual'           => (string) $row->harga_jual,
    'hpp_standar'          => (string) $row->hpp_standar,
    'stok_minimum'         => (string) ((int) $row->stok_minimum),
    'berat_bersih'         => (string) ($row->berat_bersih ?? ''),
    'satuan_berat_bersih'  => (string) ($row->satuan_berat_bersih ?? 'gram'),
    'gambar_produk'        => (string) ($row->gambar_produk ?? ''),
    'id_coa_penjualan'     => (string) ($row->id_coa_penjualan ?? ''),
    'id_coa_hpp'           => (string) ($row->id_coa_hpp ?? ''),
    'id_coa_persediaan'    => (string) ($row->id_coa_persediaan ?? ''),
    'status_produk'        => (string) $row->status_produk,
];

$page_subtitle = 'Ubah data produk';
$form_action = admin_url('menu/master_setup/produk/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';