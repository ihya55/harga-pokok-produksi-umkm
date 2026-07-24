<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id'] ?? 0);

if (!function_exists('cetak_pesanan_rupiah')) {
    function cetak_pesanan_rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('cetak_pesanan_qty')) {
    function cetak_pesanan_qty($value): string
    {
        return number_format((int) round((float) $value), 0, '.', ',');
    }
}

if (!function_exists('cetak_pesanan_tanggal')) {
    function cetak_pesanan_tanggal($tanggal): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date('d/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('cetak_pesanan_diskon_persen')) {
    function cetak_pesanan_diskon_persen($subtotal_kotor, $diskon): string
    {
        $subtotal_kotor = (float) $subtotal_kotor;
        $diskon = (float) $diskon;

        if ($subtotal_kotor <= 0 || $diskon <= 0) {
            return '0%';
        }

        return number_format((int) round(($diskon / $subtotal_kotor) * 100), 0, '.', ',') . '%';
    }
}

$pesanan = Capsule::table('tb_pesanan_penjualan as pp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pp.id_entitas')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'pp.*',

        'e.kode_entitas',
        'e.nama_entitas',
        'e.nama_pemilik',
        'e.alamat as alamat_entitas',
        'e.no_hp as no_hp_entitas',
        'e.email as email_entitas',

        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.alamat as alamat_pelanggan',
    ])
    ->first();

if (!$pesanan) {
    echo 'Data pesanan penjualan tidak ditemukan.';
    exit;
}

$detail_rows = Capsule::table('tb_pesanan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'd.id_pesanan_penjualan_detail',
        'd.id_produk',
        'd.qty',
        'd.harga',
        'd.diskon',
        'd.subtotal',
        'd.catatan',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_pesanan_penjualan_detail', 'asc')
    ->get();

$total_qty = 0;

foreach ($detail_rows as $d) {
    $total_qty += (int) round((float) ($d->qty ?? 0));
}

$diskon_header_persen = cetak_pesanan_diskon_persen(
    (float) ($pesanan->subtotal ?? 0),
    (float) ($pesanan->diskon ?? 0)
);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Pesanan Penjualan</title>

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
    <h2><?= esc($pesanan->nama_entitas ?? 'Nama Entitas') ?></h2>
    <p><?= esc($pesanan->alamat_entitas ?? '-') ?></p>
    <p>
        Telp: <?= esc($pesanan->no_hp_entitas ?? '-') ?>
        |
        Email: <?= esc($pesanan->email_entitas ?? '-') ?>
    </p>
</div>

<div class="judul">
    <h3>PESANAN PENJUALAN</h3>
    <p>No: <?= esc($pesanan->no_pesanan_penjualan ?? '-') ?></p>
</div>

<table class="info">
    <tr>
        <td width="20%">Tanggal Pesanan</td>
        <td width="30%">: <?= esc(cetak_pesanan_tanggal($pesanan->tanggal_pesanan ?? null)) ?></td>
        <td width="20%">Status</td>
        <td width="30%">:
            <span class="status-box"><?= esc(ucfirst((string) ($pesanan->status_pesanan ?? '-'))) ?></span>
        </td>
    </tr>

    <tr>
        <td>Tanggal Kirim Rencana</td>
        <td>: <?= esc(cetak_pesanan_tanggal($pesanan->tanggal_kirim_rencana ?? null)) ?></td>
        <td>Sumber Pesanan</td>
        <td>: <?= esc(ucwords((string) ($pesanan->sumber_pesanan ?? '-'))) ?></td>
    </tr>

    <tr>
        <td>Pelanggan</td>
        <td>: <?= esc(($pesanan->kode_pelanggan ?? '-') . ' - ' . ($pesanan->nama_pelanggan ?? '-')) ?></td>
        <td>No HP</td>
        <td>: <?= esc($pesanan->no_hp_pelanggan ?? '-') ?></td>
    </tr>

    <tr>
        <td>Alamat Pelanggan</td>
        <td colspan="3">: <?= esc($pesanan->alamat_pelanggan ?? '-') ?></td>
    </tr>

    <tr>
        <td>Catatan</td>
        <td colspan="3">: <?= esc($pesanan->catatan ?? '-') ?></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="40">No</th>
            <th>Produk</th>
            <th width="80">Qty</th>
            <th width="80">Satuan</th>
            <th width="120">Harga</th>
            <th width="95">Diskon</th>
            <th width="130">Subtotal</th>
            <th>Keterangan</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($detail_rows->count() > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($detail_rows as $detail): ?>
                <?php
                $qty = (int) round((float) ($detail->qty ?? 0));
                $harga = (float) ($detail->harga ?? 0);
                $diskon = (float) ($detail->diskon ?? 0);
                $subtotal_kotor = $qty * $harga;
                $diskon_persen = cetak_pesanan_diskon_persen($subtotal_kotor, $diskon);
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= esc(($detail->kode_produk ?? '-') . ' - ' . ($detail->nama_produk ?? '-')) ?></td>
                    <td class="text-end"><?= esc(cetak_pesanan_qty($detail->qty ?? 0)) ?></td>
                    <td><?= esc($detail->nama_satuan ?? '-') ?></td>
                    <td class="text-end"><?= esc(cetak_pesanan_rupiah($detail->harga ?? 0)) ?></td>
                    <td class="text-end">
                        <?= esc($diskon_persen) ?>
                        <br>
                        <small><?= esc(cetak_pesanan_rupiah($diskon)) ?></small>
                    </td>
                    <td class="text-end"><?= esc(cetak_pesanan_rupiah($detail->subtotal ?? 0)) ?></td>
                    <td><?= esc($detail->catatan ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">Detail produk belum tersedia.</td>
            </tr>
        <?php endif; ?>
    </tbody>

    <?php if ($detail_rows->count() > 0): ?>
        <tfoot>
            <tr>
                <th colspan="2" class="text-end">Total Qty</th>
                <th class="text-end"><?= esc(cetak_pesanan_qty($total_qty)) ?></th>
                <th colspan="5"></th>
            </tr>
        </tfoot>
    <?php endif; ?>
</table>

<table class="summary">
    <tr>
        <td><strong>Subtotal</strong></td>
        <td class="text-end"><strong><?= esc(cetak_pesanan_rupiah($pesanan->subtotal ?? 0)) ?></strong></td>
    </tr>

    <tr>
        <td><strong>Diskon Header</strong></td>
        <td class="text-end">
            <strong><?= esc($diskon_header_persen) ?></strong>
            <br>
            <small><?= esc(cetak_pesanan_rupiah($pesanan->diskon ?? 0)) ?></small>
        </td>
    </tr>

    <tr>
        <td><strong>Total</strong></td>
        <td class="text-end"><strong><?= esc(cetak_pesanan_rupiah($pesanan->total ?? 0)) ?></strong></td>
    </tr>
</table>

<div class="catatan">
    <div class="catatan-title">Catatan:</div>
    <div><?= nl2br(esc($pesanan->catatan ?? '-')) ?></div>
</div>

<table class="ttd">
    <tr>
        <td>
            Dibuat Oleh,
            <br><br><br><br>
            ______________________
            <br>
            Admin Penjualan
        </td>
        <td>
            Diperiksa Oleh,
            <br><br><br><br>
            ______________________
            <br>
            Penjualan
        </td>
        <td>
            Disetujui Oleh,
            <br><br><br><br>
            ______________________
            <br>
            Pemilik / Supervisor
        </td>
    </tr>
</table>

<script>
window.print();
</script>

</body>
</html>