<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/FakturPembelianORM.php';
require_once __DIR__ . '/../../../../orm/FakturPembelianDetailORM.php';
require_once __DIR__ . '/../../../../orm/PembayaranPembelianORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_faktur_pembelian = (int) ($_GET['id_faktur_pembelian'] ?? 0);

$row = FakturPembelianORM::query()
    ->from('tb_faktur_pembelian as fp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'fp.id_entitas')
    ->leftJoin('tb_penerimaan_pembelian as pn', 'pn.id_penerimaan_pembelian', '=', 'fp.id_penerimaan_pembelian')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
    ->where('fp.id_entitas', $id_entitas)
    ->where('fp.id_faktur_pembelian', $id_faktur_pembelian)
    ->where('fp.status_faktur', 'posted')
    ->where('fp.jenis_pembayaran', 'kredit')
    ->select([
        'fp.*',

        'e.nama_entitas',
        'e.alamat as alamat_entitas',
        'e.no_hp as no_hp_entitas',
        'e.email as email_entitas',

        'pn.no_penerimaan_pembelian',
        'pn.tanggal_penerimaan',

        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat as alamat_pemasok',
        'p.no_hp as no_hp_pemasok',
        'p.email as email_pemasok',
    ])
    ->first();

if (!$row) {
    echo 'Data tagihan faktur pembelian tidak ditemukan.';
    exit;
}

$detail_rows = FakturPembelianDetailORM::query()
    ->from('tb_faktur_pembelian_detail as d')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_faktur_pembelian', $id_faktur_pembelian)
    ->select([
        'd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('d.id_faktur_pembelian_detail', 'asc')
    ->get();

$total_dibayar = PembayaranPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_faktur_pembelian', $id_faktur_pembelian)
    ->where('status_posting', 'posted')
    ->sum('jumlah_bayar');

$status_lunas = ((float) ($row->sisa_utang ?? 0) <= 0);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Tagihan Pembelian</title>

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
            padding: 4px 2px;
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

        .status-box {
            border: 1px solid #111;
            padding: 8px;
            margin-top: 12px;
            font-weight: bold;
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
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
    <h3>TAGIHAN PEMBELIAN</h3>
    <p>No Faktur: <?= esc($row->no_faktur_pembelian ?? '-') ?></p>
</div>

<table class="info">
    <tr>
        <td width="18%">Tanggal Faktur</td>
        <td width="32%">: <?= esc($row->tanggal_faktur ?? '-') ?></td>
        <td width="18%">No Penerimaan</td>
        <td width="32%">: <?= esc($row->no_penerimaan_pembelian ?? '-') ?></td>
    </tr>

    <tr>
        <td>Tanggal Penerimaan</td>
        <td>: <?= esc($row->tanggal_penerimaan ?? '-') ?></td>
        <td>Jatuh Tempo</td>
        <td>: <?= esc($row->jatuh_tempo ?? '-') ?></td>
    </tr>

    <tr>
        <td>Pemasok</td>
        <td>: <?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></td>
        <td>Status Pelunasan</td>
        <td>: <?= $status_lunas ? 'Sudah Lunas' : 'Belum Lunas' ?></td>
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

<table class="detail">
    <thead>
        <tr>
            <th width="40">No</th>
            <th width="130">Kode Bahan</th>
            <th>Nama Bahan</th>
            <th width="80">Qty</th>
            <th width="90">Satuan</th>
            <th width="120">Harga</th>
            <th width="120">Diskon</th>
            <th width="130">Subtotal</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($detail_rows->count() > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($detail_rows as $item): ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= esc($item->kode_bahan_baku ?? '-') ?></td>
                    <td><?= esc($item->nama_bahan_baku ?? '-') ?></td>
                    <td class="text-end"><?= esc(number_format((int) $item->qty, 0, '.', ',')) ?></td>
                    <td><?= esc($item->nama_satuan ?? '-') ?></td>
                    <td class="text-end">Rp <?= esc(number_format((float) $item->harga, 2, '.', ',')) ?></td>
                    <td class="text-end">Rp <?= esc(number_format((float) $item->diskon, 2, '.', ',')) ?></td>
                    <td class="text-end">Rp <?= esc(number_format((float) $item->subtotal, 2, '.', ',')) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="8" class="text-center">Detail faktur belum ada.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td>Subtotal</td>
        <td class="text-end">Rp <?= esc(number_format((float) ($row->subtotal ?? 0), 2, '.', ',')) ?></td>
    </tr>
    <tr>
        <td>Diskon</td>
        <td class="text-end">Rp <?= esc(number_format((float) ($row->diskon ?? 0), 2, '.', ',')) ?></td>
    </tr>
    <tr>
        <td>PPN</td>
        <td class="text-end">Rp <?= esc(number_format((float) ($row->ppn ?? 0), 2, '.', ',')) ?></td>
    </tr>
    <tr>
        <td><strong>Total Faktur</strong></td>
        <td class="text-end"><strong>Rp <?= esc(number_format((float) ($row->total ?? 0), 2, '.', ',')) ?></strong></td>
    </tr>
    <tr>
        <td>Total Dibayar</td>
        <td class="text-end">Rp <?= esc(number_format((float) $total_dibayar, 2, '.', ',')) ?></td>
    </tr>
    <tr>
        <td><strong>Sisa Utang</strong></td>
        <td class="text-end"><strong>Rp <?= esc(number_format((float) ($row->sisa_utang ?? 0), 2, '.', ',')) ?></strong></td>
    </tr>
</table>

<div class="status-box">
    STATUS: <?= $status_lunas ? 'SUDAH LUNAS' : 'BELUM LUNAS' ?>
</div>

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