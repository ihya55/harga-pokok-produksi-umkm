<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id_faktur_penjualan'] ?? 0);

$faktur_options = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'f.id_pelanggan')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.status_faktur', 'posted')
    ->where('f.jenis_pembayaran', 'kredit')
    ->where('f.sisa_piutang', '>', 0)
    ->select([
        'f.id_faktur_penjualan',
        'f.no_faktur_penjualan',
        'f.tanggal_faktur',
        'f.jatuh_tempo',
        'f.id_pelanggan',
        'f.total',
        'f.sisa_piutang',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
    ])
    ->orderBy('f.tanggal_faktur', 'asc')
    ->orderBy('f.id_faktur_penjualan', 'asc')
    ->get();

$akun_options = Capsule::table('tb_coa')
    ->where('id_entitas', $id_entitas)
    ->where('boleh_transaksi', 1)
    ->where('status_aktif', 1)
    ->where(function ($query) {
        $query->where('kode_coa', 'like', '1110%')
            ->orWhere('kode_coa', 'like', '1120%')
            ->orWhere('nama_coa', 'like', '%Kas%')
            ->orWhere('nama_coa', 'like', '%Bank%');
    })
    ->orderBy('kode_coa', 'asc')
    ->get();

$data_form = [
    'id_pembayaran_penjualan' => null,
    'no_pembayaran_penjualan' => 'Otomatis saat disimpan',
    'tanggal_pembayaran' => date('Y-m-d'),
    'id_faktur_penjualan' => $id_faktur_penjualan ?: '',
    'id_pelanggan' => '',
    'pelanggan_label' => '',
    'metode_pembayaran' => 'tunai',
    'id_coa_kas_bank' => '',
    'jumlah_bayar' => 0,
    'sisa_piutang' => 0,
    'total_faktur' => 0,
    'status_posting' => 'draft',
    'catatan' => '',
];

if ($id_faktur_penjualan > 0) {
    $faktur = Capsule::table('tb_faktur_penjualan as f')
        ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'f.id_pelanggan')
        ->where('f.id_entitas', $id_entitas)
        ->where('f.id_faktur_penjualan', $id_faktur_penjualan)
        ->where('f.status_faktur', 'posted')
        ->where('f.jenis_pembayaran', 'kredit')
        ->where('f.sisa_piutang', '>', 0)
        ->select([
            'f.*',
            'pl.kode_pelanggan',
            'pl.nama_pelanggan',
        ])
        ->first();

    if ($faktur) {
        $data_form['id_pelanggan'] = (int) $faktur->id_pelanggan;
        $data_form['pelanggan_label'] = ($faktur->kode_pelanggan ?? '-') . ' - ' . ($faktur->nama_pelanggan ?? '-');
        $data_form['jumlah_bayar'] = (float) $faktur->sisa_piutang;
        $data_form['sisa_piutang'] = (float) $faktur->sisa_piutang;
        $data_form['total_faktur'] = (float) $faktur->total;
    }
}

$form_action = admin_page_url('penjualan/pembayaran/simpan');
$page_subtitle = 'Terima pembayaran dari piutang pelanggan kredit yang sudah posted dan masih memiliki sisa piutang.';

require __DIR__ . '/_form.php';