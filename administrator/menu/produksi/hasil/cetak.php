<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/HasilProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_hasil_produksi = (int) ($_GET['id'] ?? 0);

$row = HasilProduksiORM::query()
    ->from('tb_hasil_produksi as hp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'hp.id_entitas')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'hp.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'hp.id_produk')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'hp.id_gudang')
    ->where('hp.id_entitas', $id_entitas)
    ->where('hp.id_hasil_produksi', $id_hasil_produksi)
    ->select([
        'hp.*',

        'e.kode_entitas',
        'e.nama_entitas',
        'e.nama_pemilik',
        'e.alamat',
        'e.no_hp',
        'e.email',

        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.status_produksi',

        'pr.kode_produk',
        'pr.nama_produk',
        'pr.jenis_produk',

        'g.kode_gudang',
        'g.nama_gudang',
        'g.jenis_gudang',
    ])
    ->first();

if (!$row) {
    echo 'Data hasil produksi tidak ditemukan.';
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Hasil Produksi</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111;
        }

        .kop {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .kop h2 {
            margin: 0 0 4px;
            font-size: 20px;
            text-transform: uppercase;
        }

        .kop p {
            margin: 2px 0;
        }

        .judul {
            text-align: center;
            margin-bottom: 18px;
        }

        .judul h3 {
            margin: 0;
            text-decoration: underline;
            font-size: 16px;
            text-transform: uppercase;
        }

        .judul p {
            margin: 4px 0 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .info {
            margin-bottom: 16px;
        }

        .info td {
            padding: 5px 2px;
            vertical-align: top;
        }

        .detail th,
        .detail td {
            border: 1px solid #111;
            padding: 7px;
        }

        .detail th {
            background: #f2f2f2;
            text-align: center;
        }

        .summary {
            width: 45%;
            margin-left: auto;
            margin-top: 12px;
        }

        .summary td {
            border: 1px solid #111;
            padding: 6px;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .status-box {
            display: inline-block;
            border: 1px solid #111;
            padding: 3px 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .catatan {
            margin-top: 16px;
        }

        .catatan-title {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .ttd {
            margin-top: 70px;
        }

        .ttd td {
            width: 33.33%;
            text-align: center;
        }

        .no-print {
            margin-bottom: 14px;
        }

        @media print {
            .no-print {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>

<div class="no-print">
    <button onclick="window.print()">Print</button>
    <button onclick="window.close()">Tutup</button>
</div>

<div class="kop">
    <h2><?= esc($row->nama_entitas ?? 'Nama Entitas') ?></h2>
    <p><?= esc($row->alamat ?? '-') ?></p>
    <p>
        Telp: <?= esc($row->no_hp ?? '-') ?>
        |
        Email: <?= esc($row->email ?? '-') ?>
    </p>
</div>

<div class="judul">
    <h3>HASIL PRODUKSI</h3>
    <p>No: <?= esc($row->no_hasil_produksi ?? '-') ?></p>
</div>

<table class="info">
    <tr>
        <td width="20%">Tanggal Hasil</td>
        <td width="30%">: <?= esc($row->tanggal_hasil ?? '-') ?></td>
        <td width="20%">Status</td>
        <td width="30%">:
            <span class="status-box"><?= esc(ucfirst((string) ($row->status_posting ?? '-'))) ?></span>
        </td>
    </tr>

    <tr>
        <td>No Perintah Produksi</td>
        <td>: <?= esc($row->no_perintah_produksi ?? '-') ?></td>
        <td>Tanggal Perintah</td>
        <td>: <?= esc($row->tanggal_perintah ?? '-') ?></td>
    </tr>

    <tr>
        <td>Produk</td>
        <td>: <?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></td>
        <td>Jenis Produk</td>
        <td>: <?= esc(str_replace('_', ' ', (string) ($row->jenis_produk ?? '-'))) ?></td>
    </tr>

    <tr>
        <td>Gudang Hasil</td>
        <td>: <?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></td>
        <td>Jenis Gudang</td>
        <td>: <?= esc(str_replace('_', ' ', (string) ($row->jenis_gudang ?? '-'))) ?></td>
    </tr>

    <tr>
        <td>Qty Rencana</td>
        <td>: <?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?></td>
        <td>Qty Hasil</td>
        <td>: <?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th>Keterangan Biaya</th>
            <th width="180">Nilai</th>
        </tr>
    </thead>

    <tbody>
        <tr>
            <td>Biaya Bahan Baku</td>
            <td class="text-end">Rp <?= esc(number_format((float) ($row->total_biaya_bahan ?? 0), 2, '.', ',')) ?></td>
        </tr>

        <tr>
            <td>Biaya Tenaga Kerja</td>
            <td class="text-end">Rp <?= esc(number_format((float) ($row->total_biaya_tenaga_kerja ?? 0), 2, '.', ',')) ?></td>
        </tr>

        <tr>
            <td>Biaya Overhead Pabrik / BOP</td>
            <td class="text-end">Rp <?= esc(number_format((float) ($row->total_biaya_bop ?? 0), 2, '.', ',')) ?></td>
        </tr>

        <tr>
            <td><strong>Total HPP Produksi</strong></td>
            <td class="text-end"><strong>Rp <?= esc(number_format((float) ($row->total_hpp ?? 0), 2, '.', ',')) ?></strong></td>
        </tr>

        <tr>
            <td><strong>HPP per Unit</strong></td>
            <td class="text-end"><strong>Rp <?= esc(number_format((float) ($row->hpp_per_unit ?? 0), 2, '.', ',')) ?></strong></td>
        </tr>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td><strong>Qty Hasil</strong></td>
        <td class="text-end"><strong><?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></strong></td>
    </tr>

    <tr>
        <td><strong>Total HPP</strong></td>
        <td class="text-end"><strong>Rp <?= esc(number_format((float) ($row->total_hpp ?? 0), 2, '.', ',')) ?></strong></td>
    </tr>

    <tr>
        <td><strong>HPP per Unit</strong></td>
        <td class="text-end"><strong>Rp <?= esc(number_format((float) ($row->hpp_per_unit ?? 0), 2, '.', ',')) ?></strong></td>
    </tr>
</table>

<div class="catatan">
    <div class="catatan-title">Catatan:</div>
    <div><?= nl2br(esc($row->catatan ?? '-')) ?></div>
</div>

<table class="ttd">
    <tr>
        <td>
            Dibuat Oleh,
            <br><br><br><br>
            ______________________
            <br>
            Produksi
        </td>
        <td>
            Diperiksa Oleh,
            <br><br><br><br>
            ______________________
            <br>
            Kepala Produksi
        </td>
        <td>
            Disetujui Oleh,
            <br><br><br><br>
            ______________________
            <br>
            Manajemen
        </td>
    </tr>
</table>

<script>
window.print();
</script>

</body>
</html>