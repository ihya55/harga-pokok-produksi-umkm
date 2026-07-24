<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_penyerahan_penjualan = (int) ($_GET['id'] ?? 0);

if ($id_penyerahan_penjualan <= 0) {
    exit('ID penyerahan penjualan tidak valid.');
}

$entitas = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

$penyerahan = Capsule::table('tb_penyerahan_penjualan as pp')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
    ->leftJoin('tb_gudang as gd', 'gd.id_gudang', '=', 'pp.id_gudang')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select([
        'pp.*',
        'ps.no_pesanan_penjualan',
        'ps.tanggal_pesanan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.email as email_pelanggan',
        'gd.kode_gudang',
        'gd.nama_gudang',
        'gd.alamat as alamat_gudang',
    ])
    ->first();

if (!$penyerahan) {
    exit('Data penyerahan penjualan tidak ditemukan.');
}

$detail = Capsule::table('tb_penyerahan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_penyerahan_penjualan_detail', 'asc')
    ->get();

$total_qty = 0;
$total_hpp = 0;

foreach ($detail as $d) {
    $total_qty += (float) $d->qty;
    $total_hpp += (float) $d->hpp_total;
}

function cetak_penyerahan_esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cetak_penyerahan_qty($value): string
{
    $number = (float) $value;

    if (floor($number) == $number) {
        return number_format($number, 0, ',', '.');
    }

    return rtrim(rtrim(number_format($number, 3, ',', '.'), '0'), ',');
}

function cetak_penyerahan_uang($value): string
{
    return number_format((float) $value, 2, ',', '.');
}

$status_label = strtoupper((string) ($penyerahan->status_penyerahan ?? 'draft'));
$alamat_tujuan = (string) ($penyerahan->alamat_tujuan ?? '');
if ($alamat_tujuan === '') {
    $alamat_tujuan = (string) ($penyerahan->alamat_pelanggan ?? '-');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Penyerahan Penjualan - <?= cetak_penyerahan_esc($penyerahan->no_penyerahan_penjualan) ?></title>
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
            letter-spacing: .3px;
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
            font-size: 13px;
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

        .note {
            margin-top: 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            min-height: 60px;
        }

        .signature {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
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
            text-decoration: none;
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
                padding: 0;
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
            <h1><?= cetak_penyerahan_esc($entitas->nama_entitas ?? 'Nama Entitas') ?></h1>
            <div><?= nl2br(cetak_penyerahan_esc($entitas->alamat ?? '-')) ?></div>
            <div>Telp: <?= cetak_penyerahan_esc($entitas->no_hp ?? '-') ?></div>
            <div>Email: <?= cetak_penyerahan_esc($entitas->email ?? '-') ?></div>
        </div>

        <div class="doc-title">
            <h2>SURAT JALAN</h2>
            <div class="badge"><?= cetak_penyerahan_esc($status_label) ?></div>
            <div style="margin-top:10px; font-weight:bold;">
                <?= cetak_penyerahan_esc($penyerahan->no_penyerahan_penjualan) ?>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="box">
            <div class="box-title">Informasi Dokumen</div>

            <div class="row">
                <div class="label">No Penyerahan</div>
                <div><?= cetak_penyerahan_esc($penyerahan->no_penyerahan_penjualan) ?></div>
            </div>

            <div class="row">
                <div class="label">Tanggal</div>
                <div><?= cetak_penyerahan_esc($penyerahan->tanggal_penyerahan) ?></div>
            </div>

            <div class="row">
                <div class="label">No Pesanan</div>
                <div><?= cetak_penyerahan_esc($penyerahan->no_pesanan_penjualan ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">Tgl Pesanan</div>
                <div><?= cetak_penyerahan_esc($penyerahan->tanggal_pesanan ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">Gudang</div>
                <div><?= cetak_penyerahan_esc(($penyerahan->kode_gudang ?? '-') . ' - ' . ($penyerahan->nama_gudang ?? '-')) ?></div>
            </div>
        </div>

        <div class="box">
            <div class="box-title">Tujuan Pengiriman</div>

            <div class="row">
                <div class="label">Pelanggan</div>
                <div><?= cetak_penyerahan_esc(($penyerahan->kode_pelanggan ?? '-') . ' - ' . ($penyerahan->nama_pelanggan ?? '-')) ?></div>
            </div>

            <div class="row">
                <div class="label">No HP</div>
                <div><?= cetak_penyerahan_esc($penyerahan->no_hp_pelanggan ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">Jenis Alamat</div>
                <div><?= cetak_penyerahan_esc(ucwords(str_replace('_', ' ', (string) ($penyerahan->jenis_alamat_tujuan ?? 'pelanggan')))) ?></div>
            </div>

            <div class="row">
                <div class="label">Alamat Tujuan</div>
                <div><?= nl2br(cetak_penyerahan_esc($alamat_tujuan)) ?></div>
            </div>

            <div class="row">
                <div class="label">Kurir</div>
                <div><?= cetak_penyerahan_esc($penyerahan->kurir_pengiriman ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">No Resi/Driver</div>
                <div><?= cetak_penyerahan_esc($penyerahan->no_resi_pengiriman ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">Biaya Pengiriman</div>
                <div>Rp <?= cetak_penyerahan_uang($penyerahan->biaya_pengiriman ?? 0) ?></div>
            </div>

        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="40" class="text-center">No</th>
                <th>Produk</th>
                <th width="90">Satuan</th>
                <th width="90" class="text-end">Qty</th>
                <th width="130" class="text-end">HPP Satuan</th>
                <th width="140" class="text-end">HPP Total</th>
                <th width="150">Catatan</th>
            </tr>
        </thead>

        <tbody>
            <?php if ($detail->count() === 0): ?>
                <tr>
                    <td colspan="7" class="text-center">Detail produk belum tersedia.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($detail as $i => $d): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= cetak_penyerahan_esc($d->nama_produk ?? '-') ?></strong><br>
                        <span><?= cetak_penyerahan_esc($d->kode_produk ?? '-') ?></span>
                    </td>
                    <td><?= cetak_penyerahan_esc($d->nama_satuan ?? '-') ?></td>
                    <td class="text-end"><?= cetak_penyerahan_qty($d->qty ?? 0) ?></td>
                    <td class="text-end"><?= cetak_penyerahan_uang($d->hpp_satuan ?? 0) ?></td>
                    <td class="text-end"><?= cetak_penyerahan_uang($d->hpp_total ?? 0) ?></td>
                    <td><?= cetak_penyerahan_esc($d->catatan ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <th colspan="3" class="text-end">Total</th>
                <th class="text-end"><?= cetak_penyerahan_qty($total_qty) ?></th>
                <th></th>
                <th class="text-end"><?= cetak_penyerahan_uang($total_hpp) ?></th>
                <th></th>
            </tr>
        </tfoot>
    </table>

    <div class="note">
        <strong>Catatan:</strong><br>
        <?= nl2br(cetak_penyerahan_esc($penyerahan->catatan ?? '-')) ?>
    </div>

    <div class="signature">
        <div class="sign-box">
            <div>Dibuat Oleh,</div>
            <div class="sign-space"></div>
            <div>(__________________)</div>
        </div>

        <div class="sign-box">
            <div>Diserahkan Oleh,</div>
            <div class="sign-space"></div>
            <div>(__________________)</div>
        </div>

        <div class="sign-box">
            <div>Diterima Oleh,</div>
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