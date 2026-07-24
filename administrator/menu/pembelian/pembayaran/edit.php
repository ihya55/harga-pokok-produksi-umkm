<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pembayaran_pembelian = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('pembelian/pembayaran');
}

$row = PembayaranPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pembayaran_pembelian);

if (!$row) {
    set_flash('error', 'Data pembayaran pembelian tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Pembayaran yang sudah diposting tidak bisa diedit.');
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
}

$faktur_options = FakturPembelianORM::query()
    ->from('tb_faktur_pembelian as fp')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
    ->where('fp.id_entitas', $id_entitas)
    ->where('fp.status_faktur', 'posted')
    ->where('fp.jenis_pembayaran', 'kredit')
    ->where(function ($sub) use ($row) {
        $sub->where('fp.sisa_utang', '>', 0)
            ->orWhere('fp.id_faktur_pembelian', (int) $row->id_faktur_pembelian);
    })
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

$data_form = [
    'id_pembayaran_pembelian' => (int) $row->id_pembayaran_pembelian,
    'no_pembayaran_pembelian' => (string) $row->no_pembayaran_pembelian,
    'tanggal_pembayaran'      => (string) $row->tanggal_pembayaran,
    'id_faktur_pembelian'     => (string) $row->id_faktur_pembelian,
    'id_pemasok'              => (string) $row->id_pemasok,
    'metode_pembayaran'       => (string) $row->metode_pembayaran,
    'id_coa_kas_bank'         => (string) $row->id_coa_kas_bank,
    'jumlah_bayar'            => (string) $row->jumlah_bayar,
    'catatan'                 => (string) ($row->catatan ?? ''),
    'status_posting'          => (string) $row->status_posting,
    'back_url'                => $back_url,
];

$page_title = 'Edit Pembayaran Pembelian';
$page_subtitle = 'Ubah pembayaran utang faktur pembelian';
$form_action = admin_url('menu/pembelian/pembayaran/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';