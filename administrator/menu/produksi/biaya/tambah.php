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
| Template jurnal biaya produksi
|--------------------------------------------------------------------------
| Dropdown detail menampilkan nama template sesuai jenis biaya.
| Value yang disimpan adalah id_coa_lawan dari baris kredit template.
|--------------------------------------------------------------------------
*/
$template_biaya_options = CoaORM::query()
    ->from('tb_template_jurnal as tj')
    ->join('tb_template_jurnal_detail as tjd', 'tjd.id_template_jurnal', '=', 'tj.id_template_jurnal')
    ->join('tb_coa as c', 'c.id_coa', '=', 'tjd.id_coa_default')
    ->where('tj.id_entitas', $id_entitas)
    ->where('tj.status_aktif', 1)
    ->where(function ($q) {
        $q->where('tj.kode_jenis_transaksi', 'BIAYA_TENAGA_KERJA_LANGSUNG')
          ->orWhere('tj.kode_jenis_transaksi', 'BIAYA_OVERHEAD_PABRIK')
          ->orWhere(function ($sub) {
              $sub->where('tj.kode_jenis_transaksi', 'like', 'BIAYA_%')
                  ->where('tj.kode_jenis_transaksi', 'like', '%PRODUKSI%');
          });
    })
    ->where('tjd.posisi_dc', 'kredit')
    ->where('c.boleh_transaksi', 1)
    ->where('c.status_aktif', 1)
    ->select([
        'tj.id_template_jurnal',
        'tj.kode_template_jurnal',
        'tj.nama_template_jurnal',
        'tj.kode_jenis_transaksi',
        'c.id_coa',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderBy('tj.kode_template_jurnal', 'asc')
    ->get()
    ->map(function ($item) {
        $kodeJenis = (string) $item->kode_jenis_transaksi;
        $item->jenis_biaya_produksi = ($kodeJenis === 'BIAYA_TENAGA_KERJA_LANGSUNG') ? 'tenaga_kerja' : 'overhead';
        return $item;
    });

$data_form = [
    'no_biaya_produksi'    => 'Otomatis saat disimpan',
    'tanggal_biaya'        => date('Y-m-d'),
    'id_perintah_produksi' => '',
    'keterangan'           => '',
    'no_nota'              => '',
    'file_nota'            => '',
    'jumlah_biaya'         => '0.00',
    'status_posting'       => 'draft',
    'back_url'             => $back_url,
];

$detail_rows = [
    [
        'jenis_biaya_produksi' => 'tenaga_kerja',
        'id_coa_lawan'         => '',
        'jumlah_biaya'         => '0.00',
        'keterangan'           => '',
        'kode_jenis_transaksi_template' => '',
    ],
];

$page_title = 'Tambah Biaya Produksi';
$page_subtitle = 'Input banyak jenis biaya untuk satu perintah produksi';
$form_action = admin_url('menu/produksi/biaya/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';