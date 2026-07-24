<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_resep = (int) ($_GET['id'] ?? 0);

$row = ResepORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_resep);

if (!$row) {
    set_flash('error', 'Data resep tidak ditemukan.');
    redirect_admin('master_setup/resep');
}

$produk_options = ProdukORM::query()
    ->where('id_entitas', $id_entitas)
    ->orderBy('nama_produk', 'asc')
    ->get();

$bahan_options = BahanBakuORM::query()
    ->where('id_entitas', $id_entitas)
    ->orderBy('nama_bahan_baku', 'asc')
    ->get();

$satuan_options = SatuanORM::query()
    ->orderBy('nama_satuan', 'asc')
    ->get();

$detail_rows = ResepDetailORM::query()
    ->where('id_resep', $id_resep)
    ->get()
    ->map(function ($item) {
        return [
            'id_bahan_baku' => (string) $item->id_bahan_baku,
            'jumlah_pakai'  => (string) $item->jumlah_pakai,
            'id_satuan'     => (string) $item->id_satuan,
            'keterangan'    => (string) ($item->keterangan ?? ''),
        ];
    })
    ->toArray();

$data_form = [
    'id_resep'      => (int) $row->id_resep,
    'kode_resep'    => (string) $row->kode_resep,
    'id_produk'     => (string) $row->id_produk,
    'nama_resep'    => (string) $row->nama_resep,
    'jumlah_hasil'  => (string) $row->jumlah_hasil,
    'versi_resep'   => (string) ($row->versi_resep ?? ''),
    'status_aktif'  => (string) $row->status_aktif,
];

$page_subtitle = 'Ubah data resep';
$form_action = admin_url('menu/master_setup/resep/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';