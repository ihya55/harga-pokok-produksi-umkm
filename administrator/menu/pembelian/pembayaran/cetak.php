<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PembayaranPembelianORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pembayaran_pembelian = (int) ($_GET['id'] ?? 0);

$row = PembayaranPembelianORM::query()
    ->from('tb_pembayaran_pembelian as pb')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pb.id_entitas')
    ->leftJoin('tb_faktur_pembelian as fp', 'fp.id_faktur_pembelian', '=', 'pb.id_faktur_pembelian')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'pb.id_pemasok')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'pb.id_coa_kas_bank')
    ->where('pb.id_entitas', $id_entitas)
    ->where('pb.id_pembayaran_pembelian', $id_pembayaran_pembelian)
    ->select([
        'pb.*',

        'e.nama_entitas',
        'e.alamat as alamat_entitas',
        'e.no_hp as no_hp_entitas',
        'e.email as email_entitas',

        'fp.no_faktur_pembelian',
        'fp.tanggal_faktur',
        'fp.total as total_faktur',
        'fp.sisa_utang',
        'fp.jatuh_tempo',

        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat as alamat_pemasok',
        'p.no_hp as no_hp_pemasok',
        'p.email as email_pemasok',

        'c.kode_coa',
        'c.nama_coa',
    ])
    ->first();

if (!$row) {
    echo 'Data pembayaran pembelian tidak ditemukan.';
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Pembayaran Pembelian</title>

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

        .box {
            border: 1px solid #111;
            padding: 10px;
            margin-top: 14px;
        }

        .box-title {
            font-weight: bold;
            margin-bottom: 8px;
        }

        .amount {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
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
    <h3>BUKTI PEMBAYARAN PEMBELIAN</h3>
    <p>No: <?= esc($row->no_pembayaran_pembelian ?? '-') ?></p>
</div>

<table class="info">
    <tr>
        <td width="20%">Tanggal Pembayaran</td>
        <td width="30%">: <?= esc($row->tanggal_pembayaran ?? '-') ?></td>
        <td width="20%">No Faktur</td>
        <td width="30%">: <?= esc($row->no_faktur_pembelian ?? '-') ?></td>
    </tr>

    <tr>
        <td>Tanggal Faktur</td>
        <td>: <?= esc($row->tanggal_faktur ?? '-') ?></td>
        <td>Jatuh Tempo</td>
        <td>: <?= esc($row->jatuh_tempo ?? '-') ?></td>
    </tr>

    <tr>
        <td>Pemasok</td>
        <td>: <?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></td>
        <td>Status Posting</td>
        <td>: <?= esc(ucfirst((string) ($row->status_posting ?? '-'))) ?></td>
    </tr>

    <tr>
        <td>Metode Pembayaran</td>
        <td>: <?= esc(ucfirst((string) ($row->metode_pembayaran ?? '-'))) ?></td>
        <td>Akun Kas/Bank</td>
        <td>: <?= esc(($row->kode_coa ?? '-') . ' - ' . ($row->nama_coa ?? '-')) ?></td>
    </tr>

    <tr>
        <td>No HP Pemasok</td>
        <td>: <?= esc($row->no_hp_pemasok ?? '-') ?></td>
        <td>Email Pemasok</td>
        <td>: <?= esc($row->email_pemasok ?? '-') ?></td>
    </tr>

    <tr>
        <td>Alamat Pemasok</td>
        <td colspan="3">: <?= esc($row->alamat_pemasok ?? '-') ?></td>
    </tr>
</table>

<div class="box">
    <div class="box-title">Jumlah Pembayaran</div>
    <div class="amount">Rp <?= esc(number_format((float) ($row->jumlah_bayar ?? 0), 2, '.', ',')) ?></div>
</div>

<table class="summary">
    <tr>
        <td>Total Faktur</td>
        <td class="text-end">Rp <?= esc(number_format((float) ($row->total_faktur ?? 0), 2, '.', ',')) ?></td>
    </tr>
    <tr>
        <td>Jumlah Bayar</td>
        <td class="text-end">Rp <?= esc(number_format((float) ($row->jumlah_bayar ?? 0), 2, '.', ',')) ?></td>
    </tr>
    <tr>
        <td><strong>Sisa Utang Saat Ini</strong></td>
        <td class="text-end"><strong>Rp <?= esc(number_format((float) ($row->sisa_utang ?? 0), 2, '.', ',')) ?></strong></td>
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
        </td>
        <td>
            Dibayar Oleh,
            <br><br><br><br>
            ______________________
        </td>
        <td>
            Diterima Oleh,
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