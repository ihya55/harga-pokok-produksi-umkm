<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pembayaran_penjualan = (int) ($_GET['id'] ?? 0);

if ($id_pembayaran_penjualan <= 0) {
    exit('ID pembayaran penjualan tidak valid.');
}

$entitas = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

$pembayaran = Capsule::table('tb_pembayaran_penjualan as p')
    ->leftJoin('tb_faktur_penjualan as f', 'f.id_faktur_penjualan', '=', 'p.id_faktur_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'p.id_pelanggan')
    ->leftJoin('tb_coa as coa', 'coa.id_coa', '=', 'p.id_coa_kas_bank')
    ->where('p.id_entitas', $id_entitas)
    ->where('p.id_pembayaran_penjualan', $id_pembayaran_penjualan)
    ->select([
        'p.*',
        'f.no_faktur_penjualan',
        'f.tanggal_faktur',
        'f.jatuh_tempo',
        'f.total as total_faktur',
        'f.sisa_piutang',
        'f.status_faktur',
        'f.jenis_pembayaran',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.email as email_pelanggan',
        'coa.kode_coa',
        'coa.nama_coa',
    ])
    ->first();

if (!$pembayaran) {
    exit('Data pembayaran penjualan tidak ditemukan.');
}

function cetak_pembayaran_esc($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function cetak_pembayaran_uang($value): string
{
    return number_format((float) $value, 2, ',', '.');
}

function cetak_pembayaran_tanggal($tanggal): string
{
    if (empty($tanggal)) {
        return '-';
    }

    return date('d/m/Y', strtotime((string) $tanggal));
}

$status_label = strtoupper((string) ($pembayaran->status_posting ?? 'draft'));
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Pembayaran - <?= cetak_pembayaran_esc($pembayaran->no_pembayaran_penjualan) ?></title>
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
            align-items: flex-start;
            gap: 24px;
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 18px;
        }

        .company {
            max-width: 58%;
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
            min-width: 260px;
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
            align-items: stretch;
            margin-bottom: 16px;
        }

        .box {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            height: 100%;
        }

        .box-title {
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 13px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: .2px;
        }

        .row {
            display: grid;
            grid-template-columns: 125px 1fr;
            gap: 8px;
            margin-bottom: 6px;
            line-height: 1.45;
        }

        .label {
            color: #6b7280;
        }

        .value {
            font-weight: 500;
        }

        .amount-box {
            border: 2px solid #111827;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            margin: 16px 0;
            background: #f9fafb;
        }

        .amount-label {
            color: #6b7280;
            margin-bottom: 6px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .amount-value {
            font-size: 28px;
            font-weight: bold;
            line-height: 1.2;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        .summary-table th {
            background: #f3f4f6;
            text-align: left;
            width: 35%;
        }

        .summary-table td {
            text-align: right;
            font-weight: 600;
        }

        .note {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
            min-height: 58px;
        }

        .signature {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 90px;
            margin-top: 42px;
        }

        .sign-box {
            text-align: center;
        }

        .sign-space {
            height: 72px;
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

            .box {
                break-inside: avoid;
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
            <h1><?= cetak_pembayaran_esc($entitas->nama_entitas ?? 'Nama Entitas') ?></h1>
            <div><?= nl2br(cetak_pembayaran_esc($entitas->alamat ?? '-')) ?></div>
            <div>Telp: <?= cetak_pembayaran_esc($entitas->no_hp ?? '-') ?></div>
            <div>Email: <?= cetak_pembayaran_esc($entitas->email ?? '-') ?></div>
        </div>

        <div class="doc-title">
            <h2>BUKTI PEMBAYARAN</h2>
            <div class="badge"><?= cetak_pembayaran_esc($status_label) ?></div>
            <div style="margin-top:10px; font-weight:bold;">
                <?= cetak_pembayaran_esc($pembayaran->no_pembayaran_penjualan) ?>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="box">
            <div class="box-title">Informasi Pembayaran</div>

            <div class="row">
                <div class="label">No Pembayaran</div>
                <div class="value"><?= cetak_pembayaran_esc($pembayaran->no_pembayaran_penjualan) ?></div>
            </div>

            <div class="row">
                <div class="label">Tanggal Bayar</div>
                <div class="value"><?= cetak_pembayaran_esc(cetak_pembayaran_tanggal($pembayaran->tanggal_pembayaran ?? null)) ?></div>
            </div>

            <div class="row">
                <div class="label">Metode</div>
                <div class="value"><?= cetak_pembayaran_esc(ucfirst((string) ($pembayaran->metode_pembayaran ?? '-'))) ?></div>
            </div>

            <div class="row">
                <div class="label">Akun Kas/Bank</div>
                <div>
                    <div class="value"><?= cetak_pembayaran_esc((string) ($pembayaran->kode_coa ?? '-')) ?></div>
                    <div><?= cetak_pembayaran_esc((string) ($pembayaran->nama_coa ?? '-')) ?></div>
                </div>
            </div>

            <div class="row">
                <div class="label">Status</div>
                <div class="value"><?= cetak_pembayaran_esc($status_label) ?></div>
            </div>
        </div>

        <div class="box">
            <div class="box-title">Pelanggan</div>

            <div class="row">
                <div class="label">Kode/Nama</div>
                <div>
                    <div class="value"><?= cetak_pembayaran_esc((string) ($pembayaran->kode_pelanggan ?? '-')) ?></div>
                    <div><?= cetak_pembayaran_esc((string) ($pembayaran->nama_pelanggan ?? '-')) ?></div>
                </div>
            </div>

            <div class="row">
                <div class="label">No HP</div>
                <div class="value"><?= cetak_pembayaran_esc($pembayaran->no_hp_pelanggan ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">Email</div>
                <div class="value"><?= cetak_pembayaran_esc($pembayaran->email_pelanggan ?? '-') ?></div>
            </div>

            <div class="row">
                <div class="label">Alamat</div>
                <div><?= nl2br(cetak_pembayaran_esc($pembayaran->alamat_pelanggan ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="amount-box">
        <div class="amount-label">Jumlah Diterima</div>
        <div class="amount-value">Rp <?= cetak_pembayaran_uang($pembayaran->jumlah_bayar ?? 0) ?></div>
    </div>

    <div class="box">
        <div class="box-title">Ringkasan Faktur</div>

        <div class="info-grid" style="margin-bottom:0;">
            <div>
                <div class="row">
                    <div class="label">No Piutang</div>
                    <div class="value"><?= cetak_pembayaran_esc($pembayaran->no_faktur_penjualan ?? '-') ?></div>
                </div>

                <div class="row">
                    <div class="label">Tanggal Piutang</div>
                    <div class="value"><?= cetak_pembayaran_esc(cetak_pembayaran_tanggal($pembayaran->tanggal_faktur ?? null)) ?></div>
                </div>

                <div class="row">
                    <div class="label">Jatuh Tempo</div>
                    <div class="value"><?= cetak_pembayaran_esc(cetak_pembayaran_tanggal($pembayaran->jatuh_tempo ?? null)) ?></div>
                </div>

                <div class="row">
                    <div class="label">Jenis Faktur</div>
                    <div class="value"><?= cetak_pembayaran_esc(ucfirst((string) ($pembayaran->jenis_pembayaran ?? '-'))) ?></div>
                </div>
            </div>

            <div>
                <table class="summary-table">
                    <tr>
                        <th>Total Belanja Kredit</th>
                        <td>Rp <?= cetak_pembayaran_uang($pembayaran->total_faktur ?? 0) ?></td>
                    </tr>
                    <tr>
                        <th>Jumlah Bayar</th>
                        <td>Rp <?= cetak_pembayaran_uang($pembayaran->jumlah_bayar ?? 0) ?></td>
                    </tr>
                    <tr>
                        <th>Sisa Piutang</th>
                        <td>Rp <?= cetak_pembayaran_uang($pembayaran->sisa_piutang ?? 0) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="note">
        <strong>Catatan:</strong><br>
        <?= nl2br(cetak_pembayaran_esc($pembayaran->catatan ?? '-')) ?>
    </div>

    <div class="signature">
        <div class="sign-box">
            <div>Diterima Oleh,</div>
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