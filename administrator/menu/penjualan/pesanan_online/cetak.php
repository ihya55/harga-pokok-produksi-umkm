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

if ($id_pesanan_penjualan <= 0) {
    exit('ID pesanan online tidak valid.');
}

$entitas = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

$pesanan = Capsule::table('tb_pesanan_penjualan as ps')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
    ->where('ps.id_entitas', $id_entitas)
    ->where('ps.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->where('ps.sumber_pesanan', 'website')
    ->select([
        'ps.*',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.email as email_pelanggan',
    ])
    ->first();

if (!$pesanan) {
    exit('Data pesanan online tidak ditemukan.');
}

$detail = Capsule::table('tb_pesanan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_pesanan_penjualan_detail', 'asc')
    ->get();

function cetak_pesanan_online_esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cetak_pesanan_online_qty($value): string
{
    $number = (float) $value;

    if (floor($number) == $number) {
        return number_format($number, 0, ',', '.');
    }

    return rtrim(rtrim(number_format($number, 3, ',', '.'), '0'), ',');
}

function cetak_pesanan_online_uang($value): string
{
    return number_format((float) $value, 2, ',', '.');
}

function cetak_pesanan_online_tanggal($tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }

    return date('d/m/Y', strtotime((string) $tanggal));
}

$total_qty = 0;

foreach ($detail as $d) {
    $total_qty += (float) $d->qty;
}

$status_label = strtoupper((string) ($pesanan->status_pesanan ?? 'draft'));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Pesanan Online - <?= cetak_pesanan_online_esc($pesanan->no_pesanan_penjualan) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 24px;
            background: #ffffff;
        }

        .sheet {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 18px;
        }

        .company h1 {
            margin: 0 0 6px;
            font-size: 20px;
        }

        .company div {
            line-height: 1.5;
        }

        .doc-title {
            text-align: right;
        }

        .doc-title h2 {
            margin: 0 0 8px;
            font-size: 22px;
            letter-spacing: .4px;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border: 1px solid #111827;
            border-radius: 999px;
            font-weight: bold;
            font-size: 11px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 18px;
        }

        .box {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
        }

        .box-title {
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
        }

        .row {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 8px;
            margin-bottom: 5px;
            line-height: 1.45;
        }

        .label {
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        tfoot th,
        tfoot td {
            background: #f9fafb;
            font-weight: bold;
        }

        .summary {
            width: 340px;
            margin-left: auto;
            margin-top: 16px;
        }

        .note {
            margin-top: 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            min-height: 60px;
        }

        .signature {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 80px;
            margin-top: 42px;
        }

        .sign-box {
            text-align: center;
        }

        .sign-space {
            height: 70px;
        }

        .print-actions {
            max-width: 900px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }

        .btn {
            border: 1px solid #d1d5db;
            background: #ffffff;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            color: #111827;
            font-size: 12px;
        }

        .btn-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #ffffff;
        }

        @media print {
            body {
                padding: 0;
            }

            .print-actions {
                display: none;
            }

            .sheet {
                max-width: none;
                margin: 0;
            }

            @page {
                size: A4;
                margin: 14mm;
            }
        }
    </style>
</head>
<body>

<div class="print-actions">
    <button class="btn btn-primary" onclick="window.print()">Cetak</button>
    <button class="btn" onclick="window.close()">Tutup</button>
</div>

<div class="sheet">
    <div class="header">
        <div class="company">
            <h1><?= cetak_pesanan_online_esc($entitas->nama_entitas ?? 'Nama Entitas') ?></h1>
            <div><?= nl2br(cetak_pesanan_online_esc($entitas->alamat ?? '-')) ?></div>
            <div>Telp: <?= cetak_pesanan_online_esc($entitas->no_hp ?? '-') ?></div>
            <div>Email: <?= cetak_pesanan_online_esc($entitas->email ?? '-') ?></div>
        </div>

        <div class="doc-title">
            <h2>PESANAN ONLINE</h2>
            <div class="badge"><?= cetak_pesanan_online_esc($status_label) ?></div>
            <div style="margin-top:10px; font-weight:bold;">
                <?= cetak_pesanan_online_esc($pesanan->no_pesanan_penjualan) ?>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="box">
            <div class="box-title">Informasi Pesanan</div>

            <div class="row">
                <div class="label">No Pesanan</div>
                <div><?= cetak_pesanan_online_esc($pesanan->no_pesanan_penjualan) ?></div>
            </div>

            <div class="row">
                <div class="label">Tanggal</div>
                <div><?= cetak_pesanan_online_esc(cetak_pesanan_online_tanggal($pesanan->tanggal_pesanan)) ?></div>
            </div>

            <div class="row">
                <div class="label">Rencana Kirim</div>
                <div><?= cetak_pesanan_online_esc(cetak_pesanan_online_tanggal($pesanan->tanggal_kirim_rencana ?? null)) ?></div>
            </div>

            <div class="row">
                <div class="label">Sumber</div>
                <div>Website</div>
            </div>

            <div class="row">
                <div class="label">Status</div>
                <div><?= cetak_pesanan_online_esc($status_label) ?></div>
            </div>
        </div>

        <div class="box">
            <div class="box-title">Pelanggan</div>

            <div class="row">
                <div class="label">Kode/Nama</div>
                <div><?= cetak_pesanan_online_esc(($pesanan->kode_pelanggan ?? '-') . ' - ' . ($pesanan->nama_pelanggan ?? '-')) ?></div>
            </div>

            <div class="row">
                <div class="label">No HP</div>
                <div><?= cetak_pesanan_online_esc($pesanan->no_hp_pelanggan ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">Email</div>
                <div><?= cetak_pesanan_online_esc($pesanan->email_pelanggan ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">Alamat</div>
                <div><?= nl2br(cetak_pesanan_online_esc($pesanan->alamat_pelanggan ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="40" class="text-center">No</th>
                <th>Produk</th>
                <th width="90">Satuan</th>
                <th width="80" class="text-end">Qty</th>
                <th width="130" class="text-end">Harga</th>
                <th width="120" class="text-end">Diskon</th>
                <th width="140" class="text-end">Subtotal</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($detail->count() === 0): ?>
                <tr>
                    <td colspan="7" class="text-center">Detail pesanan masih kosong.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($detail as $i => $d): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= cetak_pesanan_online_esc($d->nama_produk ?? '-') ?></strong><br>
                        <?= cetak_pesanan_online_esc($d->kode_produk ?? '-') ?>
                    </td>
                    <td><?= cetak_pesanan_online_esc($d->nama_satuan ?? '-') ?></td>
                    <td class="text-end"><?= cetak_pesanan_online_qty($d->qty ?? 0) ?></td>
                    <td class="text-end"><?= cetak_pesanan_online_uang($d->harga ?? 0) ?></td>
                    <td class="text-end"><?= cetak_pesanan_online_uang($d->diskon ?? 0) ?></td>
                    <td class="text-end"><?= cetak_pesanan_online_uang($d->subtotal ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total Qty</th>
                <th class="text-end"><?= cetak_pesanan_online_qty($total_qty) ?></th>
                <th colspan="3"></th>
            </tr>
        </tfoot>
    </table>

    <table class="summary">
        <tr>
            <th>Subtotal</th>
            <td class="text-end"><?= cetak_pesanan_online_uang($pesanan->subtotal ?? 0) ?></td>
        </tr>
        <tr>
            <th>Diskon</th>
            <td class="text-end"><?= cetak_pesanan_online_uang($pesanan->diskon ?? 0) ?></td>
        </tr>
        <tr>
            <th>Total</th>
            <td class="text-end"><?= cetak_pesanan_online_uang($pesanan->total ?? 0) ?></td>
        </tr>
    </table>

    <div class="note">
        <strong>Catatan:</strong><br>
        <?= nl2br(cetak_pesanan_online_esc($pesanan->catatan ?? '-')) ?>
    </div>

    <div class="signature">
        <div class="sign-box">
            <div>Diperiksa Oleh,</div>
            <div class="sign-space"></div>
            <div>(__________________)</div>
        </div>

        <div class="sign-box">
            <div>Pelanggan,</div>
            <div class="sign-space"></div>
            <div>(__________________)</div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function () {
    setTimeout(function () {
        window.print();
    }, 300);
});
</script>

</body>
</html>