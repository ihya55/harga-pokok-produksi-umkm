<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_perintah_produksi = (int) ($_GET['id_perintah_produksi'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/hasil');
}

/*
|--------------------------------------------------------------------------
| Perintah produksi
|--------------------------------------------------------------------------
| Hanya perintah produksi posted dan belum pernah dibuatkan hasil produksi.
|--------------------------------------------------------------------------
*/
$perintah_options = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.status_produksi', 'posted')
    ->whereNotIn('pp.id_perintah_produksi', function ($sub) use ($id_entitas) {
        $sub->from('tb_hasil_produksi as hp')
            ->where('hp.id_entitas', $id_entitas)
            ->select('hp.id_perintah_produksi');
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
        'pr.id_coa_persediaan',
        'pr.id_coa_hpp',
        'r.kode_resep',
        'r.nama_resep',
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
        'jenis_gudang',
    ])
    ->orderBy('nama_gudang', 'asc')
    ->get();

$selected_perintah = null;

if ($id_perintah_produksi > 0) {
    $selected_perintah = PerintahProduksiORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('status_produksi', 'posted')
        ->find($id_perintah_produksi);

    if (!$selected_perintah) {
        set_flash('error', 'Perintah produksi tidak valid atau belum posted.');
        header('Location: ' . admin_page_url('produksi/hasil/tambah'));
        exit;
    }

    $sudah_ada = HasilProduksiORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('id_perintah_produksi', $id_perintah_produksi)
        ->exists();

    if ($sudah_ada) {
        set_flash('error', 'Perintah produksi ini sudah memiliki hasil produksi.');
        header('Location: ' . admin_page_url('produksi/hasil'));
        exit;
    }
}

function hitung_hpp_perintah_produksi_hasil(int $id_entitas, int $id_perintah_produksi): array
{
    $total_bahan = (float) Capsule::table('tb_pengambilan_bahan as pb')
        ->join('tb_pengambilan_bahan_detail as pbd', 'pbd.id_pengambilan_bahan', '=', 'pb.id_pengambilan_bahan')
        ->where('pb.id_entitas', $id_entitas)
        ->where('pb.id_perintah_produksi', $id_perintah_produksi)
        ->where('pb.status_posting', 'posted')
        ->sum('pbd.subtotal');

    $total_tenaga_kerja = (float) Capsule::table('tb_biaya_produksi as bp')
        ->join('tb_biaya_produksi_detail as bpd', 'bpd.id_biaya_produksi', '=', 'bp.id_biaya_produksi')
        ->where('bp.id_entitas', $id_entitas)
        ->where('bp.id_perintah_produksi', $id_perintah_produksi)
        ->where('bp.status_posting', 'posted')
        ->where('bpd.jenis_biaya_produksi', 'tenaga_kerja')
        ->sum('bpd.jumlah_biaya');

    $total_bop = (float) Capsule::table('tb_biaya_produksi as bp')
        ->join('tb_biaya_produksi_detail as bpd', 'bpd.id_biaya_produksi', '=', 'bp.id_biaya_produksi')
        ->where('bp.id_entitas', $id_entitas)
        ->where('bp.id_perintah_produksi', $id_perintah_produksi)
        ->where('bp.status_posting', 'posted')
        ->where('bpd.jenis_biaya_produksi', '!=', 'tenaga_kerja')
        ->sum('bpd.jumlah_biaya');

    $total_hpp = $total_bahan + $total_tenaga_kerja + $total_bop;

    return [
        'total_biaya_bahan'        => round($total_bahan, 2),
        'total_biaya_tenaga_kerja' => round($total_tenaga_kerja, 2),
        'total_biaya_bop'          => round($total_bop, 2),
        'total_hpp'                => round($total_hpp, 2),
    ];
}

$hpp_data = [
    'total_biaya_bahan'        => 0,
    'total_biaya_tenaga_kerja' => 0,
    'total_biaya_bop'          => 0,
    'total_hpp'                => 0,
];

if ($selected_perintah) {
    $hpp_data = hitung_hpp_perintah_produksi_hasil($id_entitas, (int) $selected_perintah->id_perintah_produksi);
}

$data_form = [
    'no_hasil_produksi'        => 'Otomatis saat disimpan',
    'tanggal_hasil'            => date('Y-m-d'),
    'id_perintah_produksi'     => $selected_perintah ? (string) $selected_perintah->id_perintah_produksi : '',
    'id_produk'                => $selected_perintah ? (string) $selected_perintah->id_produk : '',
    'id_gudang'                => '',
    'qty_hasil'                => '0',
    'total_biaya_bahan'        => number_format((float) $hpp_data['total_biaya_bahan'], 2, '.', ''),
    'total_biaya_tenaga_kerja' => number_format((float) $hpp_data['total_biaya_tenaga_kerja'], 2, '.', ''),
    'total_biaya_bop'          => number_format((float) $hpp_data['total_biaya_bop'], 2, '.', ''),
    'total_hpp'                => number_format((float) $hpp_data['total_hpp'], 2, '.', ''),
    'hpp_per_unit'             => '0.00',
    'status_posting'           => 'draft',
    'catatan'                  => '',
    'back_url'                 => $back_url,
];

$page_title = 'Tambah Hasil Produksi';
$page_subtitle = 'Input hasil produksi dan hitung HPP dari bahan, tenaga kerja, dan BOP';
$form_action = admin_url('menu/produksi/hasil/simpan.php');
$button_label = 'Simpan Draft';

require __DIR__ . '/_form.php';