<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_perintah_produksi = (int) ($_GET['id_perintah_produksi'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/pengambilan-bahan');
}

$perintah_options = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.status_produksi', 'posted')
    ->whereNotExists(function ($sub) use ($id_entitas) {
        $sub->select(Capsule::raw(1))
            ->from('tb_pengambilan_bahan as pb')
            ->whereColumn('pb.id_perintah_produksi', 'pp.id_perintah_produksi')
            ->where('pb.id_entitas', $id_entitas)
            ->where('pb.status_posting', 'posted');
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

$selected_perintah = null;
$detail_rows = [];

if ($id_perintah_produksi > 0) {
    $selected_perintah = PerintahProduksiORM::query()
        ->from('tb_perintah_produksi as pp')
        ->where('pp.id_entitas', $id_entitas)
        ->where('pp.status_produksi', 'posted')
        ->whereNotExists(function ($sub) use ($id_entitas) {
            $sub->select(Capsule::raw(1))
                ->from('tb_pengambilan_bahan as pb')
                ->whereColumn('pb.id_perintah_produksi', 'pp.id_perintah_produksi')
                ->where('pb.id_entitas', $id_entitas)
                ->where('pb.status_posting', 'posted');
        })
        ->where('pp.id_perintah_produksi', $id_perintah_produksi)
        ->first();

    if ($selected_perintah) {
        $detail_rows = Capsule::table('tb_resep_detail as rd')
            ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'rd.id_bahan_baku')
            ->where('rd.id_resep', (int) $selected_perintah->id_resep)
            ->select([
                'rd.id_bahan_baku',
                'rd.id_satuan',
                'rd.jumlah_pakai',
                'b.harga_standar',
            ])
            ->orderBy('rd.id_resep_detail', 'asc')
            ->get()
            ->map(function ($item) use ($selected_perintah) {
                $qty_rencana = (float) ($selected_perintah->qty_rencana ?? 0);
                $jumlah_hasil_resep = (float) ($selected_perintah->jumlah_hasil ?? 0);
                $qty_resep = (float) ($item->jumlah_pakai ?? 0);
                $harga = (float) ($item->harga_standar ?? 0);
                $faktor = $jumlah_hasil_resep > 0 ? ($qty_rencana / $jumlah_hasil_resep) : $qty_rencana;
                $qty = round($qty_resep * $faktor, 3);

                return [
                    'id_bahan_baku' => (string) $item->id_bahan_baku,
                    'id_satuan'     => (string) $item->id_satuan,
                    'qty'           => number_format($qty, 3, '.', ''),
                    'harga'         => number_format($harga, 2, '.', ''),
                    'subtotal'      => number_format($qty * $harga, 2, '.', ''),
                    'catatan'       => '',
                ];
            })
            ->toArray();
    }
}

if (count($detail_rows) === 0) {
    $detail_rows[] = [
        'id_bahan_baku' => '',
        'id_satuan'     => '',
        'qty'           => '0.000',
        'harga'         => '0.00',
        'subtotal'      => '0.00',
        'catatan'       => '',
    ];
}

$data_form = [
    'no_pengambilan_bahan' => 'Otomatis saat disimpan',
    'tanggal_pengambilan'  => date('Y-m-d'),
    'id_perintah_produksi' => $selected_perintah ? (string) $selected_perintah->id_perintah_produksi : '',
    'id_gudang'            => '',
    'status_posting'       => 'draft',
    'catatan'              => '',
    'back_url'             => $back_url,
];

$page_title = 'Tambah Pengambilan Bahan';
$page_subtitle = 'Buat bon pengambilan bahan dari perintah produksi posted';
$form_action = admin_url('menu/produksi/pengambilan_bahan/simpan.php');
$button_label = 'Simpan Draft';

require __DIR__ . '/_form.php';