<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_biaya_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/biaya');
}

$row = BiayaProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_biaya_produksi);

if (!$row) {
    set_flash('error', 'Data biaya produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Biaya produksi yang sudah diposting tidak bisa diedit.');
    header('Location: ' . admin_url('index.php?menu=produksi/biaya/detail&id=' . $id_biaya_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$perintah_options = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->where('pp.id_entitas', $id_entitas)
    ->where(function ($q) use ($id_entitas, $row) {
        $q->where('pp.id_perintah_produksi', (int) $row->id_perintah_produksi)
            ->orWhere(function ($sub) use ($id_entitas) {
                $sub->where('pp.status_produksi', 'posted')
                    ->whereNotIn('pp.id_perintah_produksi', function ($sub2) use ($id_entitas) {
                        $sub2->from('tb_biaya_produksi as bp')
                            ->where('bp.id_entitas', $id_entitas)
                            ->where('bp.status_posting', 'posted')
                            ->whereNotNull('bp.id_perintah_produksi')
                            ->select('bp.id_perintah_produksi');
                    });
            });
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

$detail_rows = BiayaProduksiDetailORM::query()
    ->where('id_biaya_produksi', $id_biaya_produksi)
    ->orderBy('id_biaya_produksi_detail', 'asc')
    ->get()
    ->map(function ($item) {
        return [
            'jenis_biaya_produksi' => (string) $item->jenis_biaya_produksi,
            'id_coa_lawan'         => (string) $item->id_coa_lawan,
            'jumlah_biaya'         => (string) $item->jumlah_biaya,
            'keterangan'           => (string) ($item->keterangan ?? ''),
            'kode_jenis_transaksi_template' => (string) ($item->kode_jenis_transaksi_template ?? ''),
        ];
    })
    ->toArray();

if (count($detail_rows) === 0) {
    $detail_rows[] = [
        'jenis_biaya_produksi' => 'tenaga_kerja',
        'id_coa_lawan'         => '',
        'jumlah_biaya'         => '0.00',
        'keterangan'           => '',
    ];
}

$data_form = [
    'id_biaya_produksi'    => (int) $row->id_biaya_produksi,
    'no_biaya_produksi'    => (string) $row->no_biaya_produksi,
    'tanggal_biaya'        => (string) $row->tanggal_biaya,
    'id_perintah_produksi' => (string) $row->id_perintah_produksi,
    'keterangan'           => (string) ($row->keterangan ?? ''),
    'no_nota'              => (string) ($row->no_nota ?? ''),
    'file_nota'            => (string) ($row->file_nota ?? ''),
    'jumlah_biaya'         => (string) $row->jumlah_biaya,
    'status_posting'       => (string) $row->status_posting,
    'back_url'             => $back_url,
];

$page_title = 'Edit Biaya Produksi';
$page_subtitle = 'Ubah banyak jenis biaya untuk satu perintah produksi';
$form_action = admin_url('menu/produksi/biaya/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';