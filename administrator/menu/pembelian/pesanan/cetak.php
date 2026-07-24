<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PesananPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PesananPembelianDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pesanan_pembelian = (int) ($_GET['id'] ?? 0);

$row = PesananPembelianORM::query()
    ->from('tb_pesanan_pembelian as pp')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'pp.id_pemasok')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pp.id_entitas')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_pesanan_pembelian', $id_pesanan_pembelian)
    ->select([
        'pp.*',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat as alamat_pemasok',
        'p.no_hp as no_hp_pemasok',
        'p.email as email_pemasok',
        'e.nama_entitas',
        'e.alamat as alamat_entitas',
        'e.no_hp as no_hp_entitas',
        'e.email as email_entitas',
    ])
    ->first();

if (!$row) {
    echo 'Data pesanan pembelian tidak ditemukan.';
    exit;
}

$detail = PesananPembelianDetailORM::query()
    ->from('tb_pesanan_pembelian_detail as d')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_pesanan_pembelian', $id_pesanan_pembelian)
    ->select([
        'd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('d.id_pesanan_pembelian_detail', 'asc')
    ->get();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Pesanan Pembelian</title>
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
        }

        table {
            width: 100%;
            border-collapse: collapse;
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
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .summary {
            width: 40%;
            margin-left: auto;
            margin-top: 12px;
        }

        .summary td {
            padding: 5px;
            border: 1px solid #111;
        }

        .ttd {
            margin-top: 70px;
        }

        .ttd td {
            width: 50%;
            text-align: center;
        }

        .no-print {
            margin-bottom: 14px;
        }

        @media print {
            .no-print {
                display: none;
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
    <p>Telp: <?= esc($row->no_hp_entitas ?? '-') ?> | Email: <?= esc($row->email_entitas ?? '-') ?></p>
</div>

<div class="judul">
    <h3>PESANAN PEMBELIAN</h3>
    <p>No: <?= esc($row->no_pesanan_pembelian) ?></p>
</div>

<table class="info" style="margin-bottom: 16px;">
    <tr>
        <td width="18%">Tanggal Pesanan</td>
        <td width="32%">: <?= esc($row->tanggal_pesanan) ?></td>
        <td width="18%">Pemasok</td>
        <td width="32%">: <?= esc($row->kode_pemasok . ' - ' . $row->nama_pemasok) ?></td>
    </tr>
    <tr>
        <td>Tgl Datang Rencana</td>
        <td>: <?= esc($row->tanggal_datang_rencana ?? '-') ?></td>
        <td>Alamat</td>
        <td>: <?= esc($row->alamat_pemasok ?? '-') ?></td>
    </tr>
    <tr>
        <td>Status</td>
        <td>: <?= esc(ucfirst((string) $row->status_pesanan)) ?></td>
        <td>No HP</td>
        <td>: <?= esc($row->no_hp_pemasok ?? '-') ?></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="40">No</th>
            <th>Kode Bahan</th>
            <th>Nama Bahan</th>
            <th width="70">Qty</th>
            <th width="90">Satuan</th>
            <th width="110">Harga</th>
            <th width="110">Diskon</th>
            <th width="120">Subtotal</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($detail->count() > 0): ?>
            <?php $no = 1; ?>
            <?php foreach ($detail as $item): ?>
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
                <td colspan="8" class="text-center">Detail belum ada.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td>Subtotal</td>
        <td class="text-end">Rp <?= esc(number_format((float) $row->subtotal, 2, '.', ',')) ?></td>
    </tr>
    <tr>
        <td>Diskon</td>
        <td class="text-end">Rp <?= esc(number_format((float) $row->diskon, 2, '.', ',')) ?></td>
    </tr>
    <tr>
        <td><strong>Total</strong></td>
        <td class="text-end"><strong>Rp <?= esc(number_format((float) $row->total, 2, '.', ',')) ?></strong></td>
    </tr>
</table>

<table class="ttd">
    <tr>
        <td>
            Dibuat Oleh,
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