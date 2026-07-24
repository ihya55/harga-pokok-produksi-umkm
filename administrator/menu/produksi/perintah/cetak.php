<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_perintah_produksi = (int) ($_GET['id'] ?? 0);

$row = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pp.id_entitas')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_perintah_produksi', $id_perintah_produksi)
    ->select([
        'pp.*',

        'e.kode_entitas',
        'e.nama_entitas',
        'e.nama_pemilik',
        'e.alamat',
        'e.no_hp',
        'e.email',

        'pr.kode_produk',
        'pr.nama_produk',
        'pr.jenis_produk',

        'r.kode_resep',
        'r.nama_resep',
        'r.jumlah_hasil',
        'r.versi_resep',

        'ps.no_pesanan_penjualan',
    ])
    ->first();

if (!$row) {
    echo 'Data perintah produksi tidak ditemukan.';
    exit;
}

$resep_detail_rows = Capsule::table('tb_resep_detail as rd')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'rd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'rd.id_satuan')
    ->where('rd.id_resep', (int) $row->id_resep)
    ->select([
        'rd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('rd.id_resep_detail', 'asc')
    ->get();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Perintah Produksi</title>

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
            padding: 6px;
        }

        .detail th {
            background: #f2f2f2;
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
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
    <h3>PERINTAH PRODUKSI</h3>
    <p>No: <?= esc($row->no_perintah_produksi ?? '-') ?></p>
</div>

<table class="info">
    <tr>
        <td width="20%">Tanggal Perintah</td>
        <td width="30%">: <?= esc($row->tanggal_perintah ?? '-') ?></td>
        <td width="20%">Status Produksi</td>
        <td width="30%">: <?= esc(ucfirst((string) ($row->status_produksi ?? '-'))) ?></td>
    </tr>

    <tr>
        <td>Produk</td>
        <td>: <?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></td>
        <td>Jenis Produk</td>
        <td>: <?= esc(str_replace('_', ' ', (string) ($row->jenis_produk ?? '-'))) ?></td>
    </tr>

    <tr>
        <td>Resep / BOM</td>
        <td>: <?= esc(($row->kode_resep ?? '-') . ' - ' . ($row->nama_resep ?? '-')) ?></td>
        <td>Versi Resep</td>
        <td>: <?= esc($row->versi_resep ?? '-') ?></td>
    </tr>

    <tr>
        <td>Qty Rencana</td>
        <td>: <?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?></td>
        <td>Qty Hasil</td>
        <td>: <?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></td>
    </tr>

    <tr>
        <td>Tanggal Mulai</td>
        <td>: <?= esc($row->tanggal_mulai ?? '-') ?></td>
        <td>Tanggal Selesai</td>
        <td>: <?= esc($row->tanggal_selesai ?? '-') ?></td>
    </tr>

    <tr>
        <td>Pesanan Penjualan</td>
        <td colspan="3">: <?= esc($row->no_pesanan_penjualan ?? '-') ?></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="40">No</th>
            <th>Bahan Baku</th>
            <th width="120">Jumlah Pakai</th>
            <th width="100">Satuan</th>
            <th>Keterangan</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($resep_detail_rows->count() > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($resep_detail_rows as $d): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= esc(($d->kode_bahan_baku ?? '-') . ' - ' . ($d->nama_bahan_baku ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($d->jumlah_pakai ?? 0), 3, '.', ',')) ?></td>
                    <td><?= esc($d->nama_satuan ?? '-') ?></td>
                    <td><?= esc($d->keterangan ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">Detail resep belum tersedia.</td>
            </tr>
        <?php endif; ?>
    </tbody>
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
        </td>
        <td>
            Kepala Produksi,
            <br><br><br><br>
            ______________________
        </td>
        <td>
            Disetujui Oleh,
            <br><br><br><br>
            ______________________
        </td>
    </tr>
</table>

<script>
window.print();
</script>

</body>
</html>