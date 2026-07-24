<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/BiayaProduksiORM.php';
require_once __DIR__ . '/../../../../orm/BiayaProduksiDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_biaya_produksi = (int) ($_GET['id'] ?? 0);

$row = BiayaProduksiORM::query()
    ->from('tb_biaya_produksi as bp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'bp.id_entitas')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'bp.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->where('bp.id_entitas', $id_entitas)
    ->where('bp.id_biaya_produksi', $id_biaya_produksi)
    ->select([
        'bp.*',

        'e.nama_entitas',
        'e.alamat as alamat_entitas',
        'e.no_hp as no_hp_entitas',
        'e.email as email_entitas',

        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.qty_hasil',
        'pp.status_produksi',

        'pr.kode_produk',
        'pr.nama_produk',
    ])
    ->first();

if (!$row) {
    echo 'Data biaya produksi tidak ditemukan.';
    exit;
}

$detail_rows = BiayaProduksiDetailORM::query()
    ->from('tb_biaya_produksi_detail as bpd')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'bpd.id_coa_lawan')
    ->where('bpd.id_biaya_produksi', $id_biaya_produksi)
    ->select([
        'bpd.*',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderBy('bpd.id_biaya_produksi_detail', 'asc')
    ->get();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Biaya Produksi</title>

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

        .summary {
            width: 42%;
            margin-left: auto;
            margin-top: 12px;
        }

        .summary td {
            padding: 5px;
            border: 1px solid #111;
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
    <p><?= esc($row->alamat_entitas ?? '-') ?></p>
    <p>
        Telp: <?= esc($row->no_hp_entitas ?? '-') ?>
        |
        Email: <?= esc($row->email_entitas ?? '-') ?>
    </p>
</div>

<div class="judul">
    <h3>BIAYA PRODUKSI</h3>
    <p>No: <?= esc($row->no_biaya_produksi ?? '-') ?></p>
</div>

<table class="info">
    <tr>
        <td width="20%">Tanggal Biaya</td>
        <td width="30%">: <?= esc($row->tanggal_biaya ?? '-') ?></td>
        <td width="20%">Status Posting</td>
        <td width="30%">: <?= esc(ucfirst((string) ($row->status_posting ?? '-'))) ?></td>
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
        <td>Status Produksi</td>
        <td>: <?= esc(ucfirst((string) ($row->status_produksi ?? '-'))) ?></td>
    </tr>

    <tr>
        <td>Qty Rencana</td>
        <td>: <?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?></td>
        <td>Qty Hasil</td>
        <td>: <?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></td>
    </tr>

    <tr>
        <td>Keterangan Header</td>
        <td colspan="3">: <?= esc($row->keterangan ?? '-') ?></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="40">No</th>
            <th width="130">Jenis Biaya</th>
            <th>Akun Lawan / Kredit</th>
            <th width="130">Jumlah Biaya</th>
            <th>Keterangan</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($detail_rows->count() > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($detail_rows as $d): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= esc(ucfirst(str_replace('_', ' ', (string) ($d->jenis_biaya_produksi ?? '-')))) ?></td>
                    <td><?= esc(($d->kode_coa ?? '-') . ' - ' . ($d->nama_coa ?? '-')) ?></td>
                    <td class="text-end">Rp <?= esc(number_format((float) ($d->jumlah_biaya ?? 0), 2, '.', ',')) ?></td>
                    <td><?= esc($d->keterangan ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="text-center">Detail biaya produksi belum ada.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td><strong>Total Biaya</strong></td>
        <td class="text-end"><strong>Rp <?= esc(number_format((float) ($row->jumlah_biaya ?? 0), 2, '.', ',')) ?></strong></td>
    </tr>
</table>

<div class="catatan">
    <div class="catatan-title">Keterangan:</div>
    <div><?= nl2br(esc($row->keterangan ?? '-')) ?></div>
</div>

<table class="ttd">
    <tr>
        <td>
            Dibuat Oleh,
            <br><br><br><br>
            ______________________
        </td>
        <td>
            Diperiksa Oleh,
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