<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id'] ?? 0);

if ($id_faktur_penjualan <= 0) {
    exit('ID piutang pelanggan tidak valid.');
}

$entitas = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

$faktur = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_penyerahan_penjualan as pp', 'pp.id_penyerahan_penjualan', '=', 'f.id_penyerahan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'f.id_pelanggan')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'f.*',
        'pp.no_penyerahan_penjualan',
        'pp.tanggal_penyerahan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.email as email_pelanggan',
    ])
    ->first();

if (!$faktur) {
    exit('Data piutang pelanggan tidak ditemukan.');
}

$detail = Capsule::table('tb_faktur_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_faktur_penjualan_detail', 'asc')
    ->get();

function cetak_faktur_esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cetak_faktur_qty($value): string
{
    $number = (float) $value;

    if (floor($number) == $number) {
        return number_format($number, 0, ',', '.');
    }

    return rtrim(rtrim(number_format($number, 3, ',', '.'), '0'), ',');
}

function cetak_faktur_uang($value): string
{
    return number_format((float) $value, 2, ',', '.');
}

$total_qty = 0;
foreach ($detail as $d) {
    $total_qty += (float) $d->qty;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Piutang Pelanggan - <?= cetak_faktur_esc($faktur->no_faktur_penjualan) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 24px;
            background: #fff;
        }
        .sheet { max-width: 900px; margin: 0 auto; }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 18px;
        }
        .company h1 { margin: 0 0 6px; font-size: 20px; }
        .company div { line-height: 1.5; }
        .doc-title { text-align: right; }
        .doc-title h2 { margin: 0 0 8px; font-size: 24px; }
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
            grid-template-columns: 120px 1fr;
            gap: 8px;
            margin-bottom: 5px;
            line-height: 1.45;
        }
        .label { color: #6b7280; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }
        th { background: #f3f4f6; text-align: left; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        tfoot th, tfoot td {
            background: #f9fafb;
            font-weight: bold;
        }
        .summary {
            width: 330px;
            margin-left: auto;
            margin-top: 16px;
        }
        .signature {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 80px;
            margin-top: 42px;
        }
        .sign-box { text-align: center; }
        .sign-space { height: 70px; }
        .print-actions {
            max-width: 900px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn {
            border: 1px solid #d1d5db;
            background: #fff;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            text-decoration: none;
            color: #111827;
            font-size: 12px;
        }
        .btn-primary {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        @media print {
            body { padding: 0; }
            .print-actions { display: none; }
            .sheet { max-width: none; margin: 0; }
            @page { size: A4; margin: 14mm; }
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
            <h1><?= cetak_faktur_esc($entitas->nama_entitas ?? 'Nama Entitas') ?></h1>
            <div><?= nl2br(cetak_faktur_esc($entitas->alamat ?? '-')) ?></div>
            <div>Telp: <?= cetak_faktur_esc($entitas->no_hp ?? '-') ?></div>
            <div>Email: <?= cetak_faktur_esc($entitas->email ?? '-') ?></div>
        </div>

        <div class="doc-title">
            <h2>PIUTANG PELANGGAN</h2>
            <div class="badge"><?= cetak_faktur_esc(strtoupper((string) $faktur->status_faktur)) ?></div>
            <div style="margin-top:10px; font-weight:bold;">
                <?= cetak_faktur_esc($faktur->no_faktur_penjualan) ?>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="box">
            <div class="box-title">Informasi Piutang</div>
            <div class="row"><div class="label">No Piutang</div><div><?= cetak_faktur_esc($faktur->no_faktur_penjualan) ?></div></div>
            <div class="row"><div class="label">Tanggal</div><div><?= cetak_faktur_esc($faktur->tanggal_faktur) ?></div></div>
            <div class="row"><div class="label">Penyerahan</div><div><?= cetak_faktur_esc($faktur->no_penyerahan_penjualan ?? '-') ?></div></div>
            <div class="row"><div class="label">Jenis Bayar</div><div><?= cetak_faktur_esc(ucfirst((string) $faktur->jenis_pembayaran)) ?></div></div>
            <div class="row"><div class="label">Jatuh Tempo</div><div><?= cetak_faktur_esc($faktur->jatuh_tempo ?? '-') ?></div></div>
        </div>

        <div class="box">
            <div class="box-title">Pelanggan</div>
            <div class="row"><div class="label">Kode/Nama</div><div><?= cetak_faktur_esc(($faktur->kode_pelanggan ?? '-') . ' - ' . ($faktur->nama_pelanggan ?? '-')) ?></div></div>
            <div class="row"><div class="label">No HP</div><div><?= cetak_faktur_esc($faktur->no_hp_pelanggan ?? '-') ?></div></div>
            <div class="row"><div class="label">Email</div><div><?= cetak_faktur_esc($faktur->email_pelanggan ?? '-') ?></div></div>
            <div class="row"><div class="label">Alamat</div><div><?= nl2br(cetak_faktur_esc($faktur->alamat_pelanggan ?? '-')) ?></div></div>
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
            <?php foreach ($detail as $i => $d): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= cetak_faktur_esc($d->nama_produk ?? '-') ?></strong><br>
                        <?= cetak_faktur_esc($d->kode_produk ?? '-') ?>
                    </td>
                    <td><?= cetak_faktur_esc($d->nama_satuan ?? '-') ?></td>
                    <td class="text-end"><?= cetak_faktur_qty($d->qty ?? 0) ?></td>
                    <td class="text-end"><?= cetak_faktur_uang($d->harga ?? 0) ?></td>
                    <td class="text-end"><?= cetak_faktur_uang($d->diskon ?? 0) ?></td>
                    <td class="text-end"><?= cetak_faktur_uang($d->subtotal ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <th>Subtotal</th>
            <td class="text-end"><?= cetak_faktur_uang($faktur->subtotal ?? 0) ?></td>
        </tr>
        <tr>
            <th>Diskon</th>
            <td class="text-end"><?= cetak_faktur_uang($faktur->diskon ?? 0) ?></td>
        </tr>
        <tr>
            <th>PPN</th>
            <td class="text-end"><?= cetak_faktur_uang($faktur->ppn ?? 0) ?></td>
        </tr>
        <tr>
            <th>Total</th>
            <td class="text-end"><?= cetak_faktur_uang($faktur->total ?? 0) ?></td>
        </tr>
        <tr>
            <th>Sisa Piutang</th>
            <td class="text-end"><?= cetak_faktur_uang($faktur->sisa_piutang ?? 0) ?></td>
        </tr>
    </table>

    <div style="margin-top:18px;">
        <strong>Catatan:</strong><br>
        <?= nl2br(cetak_faktur_esc($faktur->catatan ?? '-')) ?>
    </div>

    <div class="signature">
        <div class="sign-box">
            <div>Dibuat Oleh,</div>
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