<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pengambilan_bahan = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/pengambilan-bahan');
}

$row = PengambilanBahanORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pengambilan_bahan);

if (!$row) {
    set_flash('error', 'Data pengambilan bahan tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting === 'posted') {
    set_flash('error', 'Pengambilan bahan yang sudah posted tidak bisa diedit.');
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
}

$perintah_options = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
    ->where('pp.id_entitas', $id_entitas)
    ->where(function ($sub) use ($row, $id_entitas) {
        $sub->where(function ($q) use ($id_entitas) {
            $q->where('pp.status_produksi', 'posted')
                ->whereNotExists(function ($cek) use ($id_entitas) {
                    $cek->select(Capsule::raw(1))
                        ->from('tb_pengambilan_bahan as pb')
                        ->whereColumn('pb.id_perintah_produksi', 'pp.id_perintah_produksi')
                        ->where('pb.id_entitas', $id_entitas)
                        ->where('pb.status_posting', 'posted');
                });
        })
        ->orWhere('pp.id_perintah_produksi', (int) $row->id_perintah_produksi);
    })
    ->select([
        'pp.id_perintah_produksi',
        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.id_produk',
        'pp.id_resep',
        'pp.qty_rencana',
        'pp.qty_hasil',
        'pp.status_produksi',
        'pr.kode_produk',
        'pr.nama_produk',
        'r.kode_resep',
        'r.nama_resep',
        'r.jumlah_hasil',
    ])
    ->orderBy('pp.tanggal_perintah', 'desc')
    ->get();

$gudang_options = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->select([
        'id_gudang',
        'kode_gudang',
        'nama_gudang',
    ])
    ->orderBy('nama_gudang', 'asc')
    ->get();

$bahan_options = Capsule::table('tb_bahan_baku as b')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('b.id_entitas', $id_entitas)
    ->where('b.status_aktif', 1)
    ->select([
        'b.id_bahan_baku',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        'b.id_satuan',
        'b.harga_standar',
        's.nama_satuan',
    ])
    ->orderBy('b.nama_bahan_baku', 'asc')
    ->get();

$detail_rows = PengambilanBahanDetailORM::query()
    ->where('id_pengambilan_bahan', $id_pengambilan_bahan)
    ->orderBy('id_pengambilan_bahan_detail', 'asc')
    ->get()
    ->map(function ($item) {
        return [
            'id_bahan_baku' => (string) $item->id_bahan_baku,
            'qty'           => number_format((float) $item->qty, 3, '.', ''),
            'harga_satuan'  => number_format((float) $item->harga_satuan, 2, '.', ''),
            'subtotal'      => number_format((float) $item->subtotal, 2, '.', ''),
        ];
    })
    ->toArray();

if (count($detail_rows) === 0) {
    $detail_rows[] = [
        'id_bahan_baku' => '',
        'qty'           => '0.000',
        'harga_satuan'  => '0.00',
        'subtotal'      => '0.00',
    ];
}

$data_form = [
    'id_pengambilan_bahan' => (int) $row->id_pengambilan_bahan,
    'no_pengambilan_bahan' => (string) $row->no_pengambilan_bahan,
    'tanggal_pengambilan'  => (string) $row->tanggal_pengambilan,
    'id_perintah_produksi' => (string) $row->id_perintah_produksi,
    'id_gudang'            => (string) $row->id_gudang,
    'status_posting'       => (string) $row->status_posting,
    'catatan'              => (string) ($row->catatan ?? ''),
    'back_url'             => $back_url,
];

$page_title = 'Edit Pengambilan Bahan';
$page_subtitle = 'Ubah bon pengambilan bahan draft / diperiksa';
$form_action = admin_url('menu/produksi/pengambilan_bahan/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';