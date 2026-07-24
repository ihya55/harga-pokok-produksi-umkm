<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/biaya');
}

/*
|--------------------------------------------------------------------------
| Perintah produksi
|--------------------------------------------------------------------------
| Perintah produksi yang sudah punya biaya produksi posted tidak ditampilkan.
|--------------------------------------------------------------------------
*/
$perintah_options = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.status_produksi', 'posted')
    ->whereNotIn('pp.id_perintah_produksi', function ($sub) use ($id_entitas) {
        $sub->from('tb_biaya_produksi as bp')
            ->where('bp.id_entitas', $id_entitas)
            ->where('bp.status_posting', 'posted')
            ->whereNotNull('bp.id_perintah_produksi')
            ->select('bp.id_perintah_produksi');
    })
    ->select([
        'pp.id_perintah_produksi',
        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.qty_hasil',
        'pp.status_produksi',
        'pr.kode_produk',
        'pr.nama_produk',
    ])
    ->orderBy('pp.tanggal_perintah', 'desc')
    ->get();

/*
|--------------------------------------------------------------------------
| Template jurnal biaya produksi dari database
|--------------------------------------------------------------------------
| Tidak hardcode jenis biaya. Pilihan diambil dari tb_template_jurnal yang
| aktif, kode_jenis_transaksi BIAYA_*, dan memiliki baris debit+kredit.
|--------------------------------------------------------------------------
*/
$template_biaya_options = CoaORM::query()
    ->from('tb_template_jurnal as tj')
    ->join('tb_template_jurnal_detail as td', function ($join) {
        $join->on('td.id_template_jurnal', '=', 'tj.id_template_jurnal')
            ->where('td.posisi_dc', '=', 'debit');
    })
    ->join('tb_template_jurnal_detail as tk', function ($join) {
        $join->on('tk.id_template_jurnal', '=', 'tj.id_template_jurnal')
            ->where('tk.posisi_dc', '=', 'kredit');
    })
    ->join('tb_coa as cd', 'cd.id_coa', '=', 'td.id_coa_default')
    ->join('tb_coa as ck', 'ck.id_coa', '=', 'tk.id_coa_default')
    ->where('tj.id_entitas', $id_entitas)
    ->where('tj.status_aktif', 1)
    ->where('tj.kode_jenis_transaksi', 'like', 'BIAYA_%')
    ->where('cd.boleh_transaksi', 1)
    ->where('ck.boleh_transaksi', 1)
    ->where('cd.status_aktif', 1)
    ->where('ck.status_aktif', 1)
    ->select([
        'tj.id_template_jurnal',
        'tj.kode_template_jurnal',
        'tj.nama_template_jurnal',
        'tj.kode_jenis_transaksi',
        'td.id_coa_default as id_coa_debit',
        'cd.kode_coa as kode_coa_debit',
        'cd.nama_coa as nama_coa_debit',
        'tk.id_coa_default as id_coa_kredit',
        'ck.id_coa as id_coa',
        'ck.kode_coa as kode_coa_kredit',
        'ck.kode_coa',
        'ck.nama_coa as nama_coa_kredit',
        'ck.nama_coa',
        'tk.sumber_nominal',
        'tk.rumus_nominal',
    ])
    ->orderBy('tj.kode_template_jurnal', 'asc')
    ->get();

$default_template = $template_biaya_options->first();

$data_form = [
    'no_biaya_produksi'    => 'Otomatis saat disimpan',
    'tanggal_biaya'        => date('Y-m-d'),
    'id_perintah_produksi' => '',
    'keterangan'           => '',
    'jumlah_biaya'         => '0.00',
    'status_posting'       => 'draft',
    'back_url'             => $back_url,
];

$detail_rows = [
    [
        'jenis_biaya_produksi' => $default_template ? (string) $default_template->kode_jenis_transaksi : '',
        'id_coa_lawan'         => $default_template ? (string) $default_template->id_coa_kredit : '',
        'jumlah_biaya'         => '0.00',
        'keterangan'           => '',
    ],
];

$page_title = 'Tambah Biaya Produksi';
$page_subtitle = 'Input biaya produksi berdasarkan template jurnal yang aktif di database';
$form_action = admin_url('menu/produksi/biaya/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';
