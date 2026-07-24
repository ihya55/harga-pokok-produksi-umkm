<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_faktur_pembelian = (int) ($_GET['id'] ?? 0);
$default_ppn_persen = pajak_default_persen($id_entitas, 'PPN', 11.0);

$row = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_faktur_pembelian);

if (!$row) {
    set_flash('error', 'Data faktur pembelian tidak ditemukan.');
    redirect_admin('pembelian/faktur');
}

if ((string) $row->status_faktur !== 'draft') {
    set_flash('error', 'Faktur pembelian yang sudah diposting tidak bisa diedit.');
    header('Location: ' . admin_url('index.php?menu=pembelian/faktur/detail&id=' . $id_faktur_pembelian));
    exit;
}

$penerimaan_options = PenerimaanPembelianORM::query()
    ->from('tb_penerimaan_pembelian as pn')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'pn.id_pemasok')
    ->where('pn.id_entitas', $id_entitas)
    ->where('pn.status_penerimaan', 'posted')
    ->where(function ($q) use ($id_entitas, $row) {
        $q->where('pn.id_penerimaan_pembelian', (int) $row->id_penerimaan_pembelian)
            ->orWhereNotExists(function ($sub) use ($id_entitas) {
                $sub->selectRaw('1')
                    ->from('tb_faktur_pembelian as fp')
                    ->whereColumn('fp.id_penerimaan_pembelian', 'pn.id_penerimaan_pembelian')
                    ->where('fp.id_entitas', $id_entitas)
                    ->whereIn('fp.status_faktur', ['draft', 'posted']);
            });
    })
    ->select([
        'pn.id_penerimaan_pembelian',
        'pn.no_penerimaan_pembelian',
        'pn.tanggal_penerimaan',
        'pn.id_pemasok',
        'p.kode_pemasok',
        'p.nama_pemasok',
    ])
    ->orderBy('pn.tanggal_penerimaan', 'desc')
    ->get();

$bahan_options = BahanBakuORM::query()
    ->from('tb_bahan_baku as b')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('b.id_entitas', $id_entitas)
    ->where('b.status_aktif', 1)
    ->select([
        'b.id_bahan_baku',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        'b.harga_standar',
        's.nama_satuan',
    ])
    ->orderBy('b.nama_bahan_baku', 'asc')
    ->get();

$id_coa_biaya_kirim_default = 0; // biaya kirim pembelian dikapitalisasi ke persediaan dari Mapping Akun saat posting

$coa_kas_bank_options = Capsule::table('tb_template_jurnal as tj')
    ->join('tb_template_jurnal_detail as tjd', 'tjd.id_template_jurnal', '=', 'tj.id_template_jurnal')
    ->join('tb_coa as c', 'c.id_coa', '=', 'tjd.id_coa_default')
    ->where('tj.id_entitas', $id_entitas)
    ->where('tj.status_aktif', 1)
    ->whereIn('tj.kode_jenis_transaksi', [
        'FAKTUR_PEMBELIAN_TUNAI',
        'FAKTUR_PEMBELIAN_TRANSFER',
    ])
    ->where('tjd.posisi_dc', 'kredit')
    ->where('tjd.rumus_nominal', 'total')
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
        $item->metode_pembayaran = ((string) $item->kode_jenis_transaksi === 'FAKTUR_PEMBELIAN_TUNAI')
            ? 'tunai'
            : 'transfer';

        return $item;
    });

$detail_rows = FakturPembelianDetailORM::query()
    ->where('id_faktur_pembelian', $id_faktur_pembelian)
    ->get()
    ->map(function ($item) {
        return [
            'id_bahan_baku' => (string) $item->id_bahan_baku,
            'qty'           => (string) ((int) $item->qty),
            'harga'         => (string) $item->harga,
            'diskon'        => (string) $item->diskon,
            'subtotal'      => (string) $item->subtotal,
        ];
    })
    ->toArray();

$data_form = [
    'id_faktur_pembelian'     => (int) $row->id_faktur_pembelian,
    'no_faktur_pembelian'     => (string) $row->no_faktur_pembelian,
    'tanggal_faktur'          => (string) $row->tanggal_faktur,
    'id_penerimaan_pembelian' => (string) ($row->id_penerimaan_pembelian ?? ''),
    'id_pemasok'              => (string) $row->id_pemasok,
    'jenis_pembayaran'        => (string) $row->jenis_pembayaran,
    'id_coa_kas_bank'         => (string) ($row->id_coa_kas_bank ?? ''),
    'status_faktur'           => (string) $row->status_faktur,
    'jatuh_tempo'             => (string) ($row->jatuh_tempo ?? ''),
    'subtotal'                => (string) $row->subtotal,
    'diskon'                  => (string) $row->diskon,
    'diskon_persen'           => (string) ((int) ($row->diskon_persen ?? 0)),
    'ppn'                     => (string) $row->ppn,
    'ppn_persen'              => (string) ((float) ($row->ppn_persen ?? $default_ppn_persen)),
    'ada_biaya_kirim'        => (int) ($row->ada_biaya_kirim ?? 0),
    'biaya_kirim'            => (string) ($row->biaya_kirim ?? '0.00'),
    'id_coa_biaya_kirim'     => '',
    'total'                   => (string) $row->total,
    'sisa_utang'              => (string) $row->sisa_utang,
    'catatan'                 => (string) ($row->catatan ?? ''),
];

$page_title = 'Edit Faktur Pembelian';
$page_subtitle = 'Ubah data faktur pembelian';
$form_action = admin_url('menu/pembelian/faktur/update.php');
$button_label = 'Update';

require __DIR__ . '/_form.php';