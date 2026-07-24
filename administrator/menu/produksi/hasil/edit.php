<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_hasil_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/hasil');
}

$row = HasilProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_hasil_produksi);

if (!$row) {
    set_flash('error', 'Data hasil produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Hasil produksi yang sudah posted tidak bisa diedit.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$perintah_options = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
    ->where('pp.id_entitas', $id_entitas)
    ->where(function ($q) use ($id_entitas, $row) {
        $q->where('pp.id_perintah_produksi', (int) $row->id_perintah_produksi)
            ->orWhere(function ($sub) use ($id_entitas) {
                $sub->where('pp.status_produksi', 'posted')
                    ->whereNotIn('pp.id_perintah_produksi', function ($sub2) use ($id_entitas) {
                        $sub2->from('tb_hasil_produksi as hp')
                            ->where('hp.id_entitas', $id_entitas)
                            ->select('hp.id_perintah_produksi');
                    });
            });
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

$data_form = [
    'id_hasil_produksi'        => (int) $row->id_hasil_produksi,
    'no_hasil_produksi'        => (string) $row->no_hasil_produksi,
    'tanggal_hasil'            => (string) $row->tanggal_hasil,
    'id_perintah_produksi'     => (string) $row->id_perintah_produksi,
    'id_produk'                => (string) $row->id_produk,
    'id_gudang'                => (string) $row->id_gudang,
    'qty_hasil'                => (string) ((int) $row->qty_hasil),
    'total_biaya_bahan'        => number_format((float) $row->total_biaya_bahan, 2, '.', ''),
    'total_biaya_tenaga_kerja' => number_format((float) $row->total_biaya_tenaga_kerja, 2, '.', ''),
    'total_biaya_bop'          => number_format((float) $row->total_biaya_bop, 2, '.', ''),
    'total_hpp'                => number_format((float) $row->total_hpp, 2, '.', ''),
    'hpp_per_unit'             => number_format((float) $row->hpp_per_unit, 2, '.', ''),
    'status_posting'           => (string) $row->status_posting,
    'catatan'                  => (string) ($row->catatan ?? ''),
    'back_url'                 => $back_url,
];

$page_title = 'Edit Hasil Produksi';
$page_subtitle = 'Ubah hasil produksi draft';
$form_action = admin_url('menu/produksi/hasil/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';