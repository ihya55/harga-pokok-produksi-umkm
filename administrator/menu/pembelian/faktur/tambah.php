<?php
use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_penerimaan_pembelian = (int) ($_GET['id_penerimaan_pembelian'] ?? 0);
$default_ppn_persen = pajak_default_persen($id_entitas, 'PPN', 11.0);

$penerimaan_options = PenerimaanPembelianORM::query()
    ->from('tb_penerimaan_pembelian as pn')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'pn.id_pemasok')
    ->where('pn.id_entitas', $id_entitas)
    ->where('pn.status_penerimaan', 'posted')
    ->whereNotExists(function ($sub) use ($id_entitas) {
        $sub->selectRaw('1')
            ->from('tb_faktur_pembelian as fp')
            ->whereColumn('fp.id_penerimaan_pembelian', 'pn.id_penerimaan_pembelian')
            ->where('fp.id_entitas', $id_entitas)
            ->whereIn('fp.status_faktur', ['draft', 'posted']);
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

$selected_penerimaan = null;
$detail_rows = [];

if ($id_penerimaan_pembelian > 0) {
    $sudah_ada_faktur = FakturPembelianORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('id_penerimaan_pembelian', $id_penerimaan_pembelian)
        ->whereIn('status_faktur', ['draft', 'posted'])
        ->exists();

    if ($sudah_ada_faktur) {
        set_flash('error', 'Penerimaan pembelian ini sudah pernah dibuatkan faktur, baik draft maupun posted.');
        header('Location: ' . admin_url('index.php?menu=pembelian/faktur/tambah'));
        exit;
    }

    $selected_penerimaan = PenerimaanPembelianORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('status_penerimaan', 'posted')
        ->find($id_penerimaan_pembelian);

    if (!$selected_penerimaan) {
        set_flash('error', 'Penerimaan pembelian tidak valid atau belum diposting.');
        header('Location: ' . admin_url('index.php?menu=pembelian/faktur/tambah'));
        exit;
    }

    $detail_rows = PenerimaanPembelianDetailORM::query()
        ->where('id_penerimaan_pembelian', $id_penerimaan_pembelian)
        ->get()
        ->map(function ($item) {
            return [
                'id_bahan_baku' => (string) $item->id_bahan_baku,
                'qty'           => (string) ((int) $item->qty),
                'harga'         => (string) $item->harga,
                'diskon'        => '0.00',
                'subtotal'      => (string) $item->subtotal,
            ];
        })
        ->toArray();
}

$data_form = [
    'no_faktur_pembelian'     => 'Otomatis saat disimpan',
    'tanggal_faktur'          => date('Y-m-d'),
    'id_penerimaan_pembelian' => $selected_penerimaan ? (string) $selected_penerimaan->id_penerimaan_pembelian : '',
    'id_pemasok'              => $selected_penerimaan ? (string) $selected_penerimaan->id_pemasok : '',
    'jenis_pembayaran'        => 'kredit',
    'id_coa_kas_bank'         => '',
    'status_faktur'           => 'draft',
    'jatuh_tempo'             => date('Y-m-d', strtotime('+30 days')),
    'subtotal'                => '0.00',
    'diskon'                  => '0.00',
    'diskon_persen'           => '0',
    'ppn'                     => '0.00',
    'ppn_persen'              => (string) $default_ppn_persen,
    'ada_biaya_kirim'        => 0,
    'biaya_kirim'            => '0.00',
    'id_coa_biaya_kirim'     => '',
    'total'                   => '0.00',
    'sisa_utang'              => '0.00',
    'catatan'                 => '',
];

$page_title = 'Tambah Faktur Pembelian';
$page_subtitle = 'Buat faktur pembelian dari penerimaan pembelian';
$form_action = admin_url('menu/pembelian/faktur/simpan.php');
$button_label = 'Simpan';

require __DIR__ . '/_form.php';