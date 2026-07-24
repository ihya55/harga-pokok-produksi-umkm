<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PengambilanBahanORM.php';
require_once __DIR__ . '/../../../../orm/PengambilanBahanDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengambilan_bahan = (int) ($_GET['id'] ?? 0);

$row = PengambilanBahanORM::query()
    ->from('tb_pengambilan_bahan as pb')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pb.id_entitas')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'pb.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'pb.id_gudang')
    ->where('pb.id_entitas', $id_entitas)
    ->where('pb.id_pengambilan_bahan', $id_pengambilan_bahan)
    ->select([
        'pb.*',

        'e.kode_entitas',
        'e.nama_entitas',
        'e.nama_pemilik',
        'e.alamat',
        'e.no_hp',
        'e.email',

        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.qty_hasil',
        'pp.status_produksi',

        'pr.kode_produk',
        'pr.nama_produk',

        'g.kode_gudang',
        'g.nama_gudang',
    ])
    ->first();

if (!$row) {
    echo 'Data pengambilan bahan tidak ditemukan.';
    exit;
}

$detail_rows = PengambilanBahanDetailORM::query()
    ->from('tb_pengambilan_bahan_detail as pbd')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'pbd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('pbd.id_pengambilan_bahan', $id_pengambilan_bahan)
    ->select([
        'pbd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('pbd.id_pengambilan_bahan_detail', 'asc')
    ->get();

$total_bahan = 0;

foreach ($detail_rows as $detail) {
    $total_bahan += (float) $detail->subtotal;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Pengambilan Bahan</title>

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

        .status-box {
            display: inline-block;
            border: 1px solid #111;
            padding: 3px 8px;
            font-weight: bold;
            text-transform: uppercase;
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
    <h3>BON PENGAMBILAN BAHAN PRODUKSI</h3>
    <p>No: <?= esc($row->no_pengambilan_bahan ?? '-') ?></p>
</div>

<table class="info">
    <tr>
        <td width="20%">Tanggal Pengambilan</td>
        <td width="30%">: <?= esc($row->tanggal_pengambilan ?? '-') ?></td>
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
        <td>Gudang</td>
        <td>: <?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></td>
    </tr>

    <tr>
        <td>Qty Rencana</td>
        <td>: <?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?></td>
        <td>Qty Hasil</td>
        <td>: <?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></td>
    </tr>

    <tr>
        <td>Catatan</td>
        <td colspan="3">: <?= esc($row->catatan ?? '-') ?></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="40">No</th>
            <th>Bahan Baku</th>
            <th width="110">Qty</th>
            <th width="90">Satuan</th>
            <th width="130">Harga</th>
            <th width="130">Subtotal</th>
            <th>Keterangan</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($detail_rows->count() > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($detail_rows as $detail): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= esc(($detail->kode_bahan_baku ?? '-') . ' - ' . ($detail->nama_bahan_baku ?? '-')) ?></td>
                    <td class="text-end"><?= esc(number_format((float) ($detail->qty ?? 0))) ?></td>
                    <td><?= esc($detail->nama_satuan ?? '-') ?></td>
                    <td class="text-end">Rp <?= esc(number_format((float) ($detail->harga_satuan ?? 0), 2, '.', ',')) ?></td>
                    <td class="text-end">Rp <?= esc(number_format((float) ($detail->subtotal ?? 0), 2, '.', ',')) ?></td>
                    <td><?= esc($detail->catatan ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="text-center">Detail bahan belum tersedia.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td><strong>Total Nilai Bahan</strong></td>
        <td class="text-end"><strong>Rp <?= esc(number_format($total_bahan, 2, '.', ',')) ?></strong></td>
    </tr>
</table>

<div class="catatan">
    <div class="catatan-title">Catatan:</div>
    <div><?= nl2br(esc($row->catatan ?? '-')) ?></div>
</div>

<table class="ttd">
    <tr>
        <td>
            Diminta Oleh,
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
            Gudang
        </td>
        <td>
            Disetujui Oleh,
            <br><br><br><br>
            ______________________
            <br>
            Kepala Produksi
        </td>
    </tr>
</table>

<script>
window.print();
</script>

</body>
</html>