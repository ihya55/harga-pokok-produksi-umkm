<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_penyerahan_penjualan = (int) ($_GET['id_penyerahan_penjualan'] ?? 0);
$default_ppn_persen = pajak_default_persen($id_entitas, 'PPN', 11.0);

$penyerahan_options = Capsule::table('tb_penyerahan_penjualan as pp')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.status_penyerahan', 'posted')
    ->whereNotExists(function ($sub) use ($id_entitas) {
        $sub->selectRaw('1')
            ->from('tb_faktur_penjualan as f')
            ->whereColumn('f.id_penyerahan_penjualan', 'pp.id_penyerahan_penjualan')
            ->where('f.id_entitas', $id_entitas)
            ->whereIn('f.status_faktur', ['draft', 'posted']);
    })
    ->select([
        'pp.id_penyerahan_penjualan',
        'pp.no_penyerahan_penjualan',
        'pp.tanggal_penyerahan',
        'pp.id_pelanggan',
        'ps.no_pesanan_penjualan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.tempo_hari',
    ])
    ->orderBy('pp.tanggal_penyerahan', 'desc')
    ->orderBy('pp.id_penyerahan_penjualan', 'desc')
    ->get();

$data_form = [
    'id_faktur_penjualan' => null,
    'no_faktur_penjualan' => 'Otomatis saat disimpan',
    'tanggal_faktur' => date('Y-m-d'),
    'id_penyerahan_penjualan' => $id_penyerahan_penjualan ?: '',
    'id_pelanggan' => '',
    'pelanggan_label' => '',
    'jenis_pembayaran' => 'kredit',
    'status_faktur' => 'draft',
    'jatuh_tempo' => date('Y-m-d', strtotime('+30 days')),
    'subtotal' => 0,
    'diskon' => 0,
    'ppn' => 0,
    'ppn_persen' => $default_ppn_persen,
    'total' => 0,
    'sisa_piutang' => 0,
    'catatan' => '',
];

if ($id_penyerahan_penjualan > 0) {
    $penyerahan = Capsule::table('tb_penyerahan_penjualan as pp')
        ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
        ->where('pp.id_entitas', $id_entitas)
        ->where('pp.id_penyerahan_penjualan', $id_penyerahan_penjualan)
        ->where('pp.status_penyerahan', 'posted')
        ->select([
            'pp.id_pelanggan',
            'pl.kode_pelanggan',
            'pl.nama_pelanggan',
            'pl.tempo_hari',
        ])
        ->first();

    if ($penyerahan) {
        $tempo = (int) ($penyerahan->tempo_hari ?? 30);
        if ($tempo <= 0) {
            $tempo = 30;
        }

        $data_form['id_pelanggan'] = (int) $penyerahan->id_pelanggan;
        $data_form['pelanggan_label'] = ($penyerahan->kode_pelanggan ?? '-') . ' - ' . ($penyerahan->nama_pelanggan ?? '-');
        $data_form['jatuh_tempo'] = date('Y-m-d', strtotime('+' . $tempo . ' days'));
    }
}

$detail_rows = [];

$form_action = admin_page_url('penjualan/faktur/simpan');
$page_subtitle = 'Buat faktur dari penyerahan penjualan yang sudah posted.';

require __DIR__ . '/_form.php';