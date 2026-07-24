<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_faktur_pembelian = (int) ($_GET['id_faktur_pembelian'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('pembelian/pembayaran');
}

$faktur_options = FakturPembelianORM::query()
    ->from('tb_faktur_pembelian as fp')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
    ->where('fp.id_entitas', $id_entitas)
    ->where('fp.status_faktur', 'posted')
    ->where('fp.jenis_pembayaran', 'kredit')
    ->where('fp.sisa_utang', '>', 0)
    ->select([
        'fp.id_faktur_pembelian',
        'fp.no_faktur_pembelian',
        'fp.tanggal_faktur',
        'fp.id_pemasok',
        'fp.total',
        'fp.sisa_utang',
        'p.kode_pemasok',
        'p.nama_pemasok',
    ])
    ->orderBy('fp.tanggal_faktur', 'desc')
    ->get();

$coa_kas_bank_options = CoaORM::query()
    ->from('tb_template_jurnal as tj')
    ->join('tb_template_jurnal_detail as tjd', 'tjd.id_template_jurnal', '=', 'tj.id_template_jurnal')
    ->join('tb_coa as c', 'c.id_coa', '=', 'tjd.id_coa_default')
    ->where('tj.id_entitas', $id_entitas)
    ->where('tj.status_aktif', 1)
    ->whereIn('tj.kode_jenis_transaksi', [
        'FAKTUR_PEMBELIAN_TUNAI',
        'FAKTUR_PEMBELIAN_TRANSFER',
    ])
    ->where('tjd.posisi_dc', 'kredit')
    ->where('c.boleh_transaksi', 1)
    ->where('c.status_aktif', 1)
    ->select([
        'c.id_coa',
        'c.kode_coa',
        'c.nama_coa',
        'tj.kode_jenis_transaksi',
    ])
    ->orderBy('tj.kode_jenis_transaksi', 'asc')
    ->orderBy('c.kode_coa', 'asc')
    ->get()
    ->map(function ($item) {
        if ((string) $item->kode_jenis_transaksi === 'FAKTUR_PEMBELIAN_TUNAI') {
            $item->metode_pembayaran = 'tunai';
        } elseif ((string) $item->kode_jenis_transaksi === 'FAKTUR_PEMBELIAN_TRANSFER') {
            $item->metode_pembayaran = 'transfer';
        } else {
            $item->metode_pembayaran = '';
        }

        return $item;
    });

$selected_faktur = null;

if ($id_faktur_pembelian > 0) {
    $selected_faktur = FakturPembelianORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->where('jenis_pembayaran', 'kredit')
        ->where('sisa_utang', '>', 0)
        ->find($id_faktur_pembelian);

    if (!$selected_faktur) {
        set_flash('error', 'Faktur pembelian tidak valid, bukan kredit, belum posted, atau sudah lunas.');
        header('Location: ' . $back_url);
        exit;
    }
}

$data_form = [
    'no_pembayaran_pembelian' => 'Otomatis saat disimpan',
    'tanggal_pembayaran'      => date('Y-m-d'),
    'id_faktur_pembelian'     => $selected_faktur ? (string) $selected_faktur->id_faktur_pembelian : '',
    'id_pemasok'              => $selected_faktur ? (string) $selected_faktur->id_pemasok : '',
    'metode_pembayaran'       => 'transfer',
    'id_coa_kas_bank'         => '',
    'jumlah_bayar'            => $selected_faktur ? (string) $selected_faktur->sisa_utang : '0.00',
    'catatan'                 => '',
    'status_posting'          => 'draft',
    'back_url'                => $back_url,
];

$page_title = 'Tambah Pembayaran Pembelian';
$page_subtitle = 'Input pembayaran utang faktur pembelian kredit';
$form_action = admin_url('menu/pembelian/pembayaran/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';