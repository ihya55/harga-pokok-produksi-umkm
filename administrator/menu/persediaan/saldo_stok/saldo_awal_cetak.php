<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/SaldoAwalStokORM.php';
require_once __DIR__ . '/../../../../orm/SaldoAwalStokDetailORM.php';

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_saldo_awal_stok = (int) ($_GET['id'] ?? 0);

function format_qty_cetak_saldo_awal_stok($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

$row = SaldoAwalStokORM::query()
    ->from('tb_saldo_awal_stok as sa')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'sa.id_gudang')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'sa.id_coa_lawan')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'sa.id_entitas')
    ->where('sa.id_entitas', $id_entitas)
    ->where('sa.id_saldo_awal_stok', $id_saldo_awal_stok)
    ->select([
        'sa.*',
        'g.kode_gudang',
        'g.nama_gudang',
        'c.kode_coa',
        'c.nama_coa',
        'e.kode_entitas',
        'e.nama_entitas',
        'e.nama_pemilik',
        'e.alamat',
        'e.no_hp',
        'e.email',
    ])
    ->first();

if (!$row) {
    echo 'Data saldo awal stok tidak ditemukan.';
    exit;
}

$detail_bahan = SaldoAwalStokDetailORM::query()
    ->from('tb_saldo_awal_stok_detail as d')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'd.id_referensi_barang')
            ->where('d.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_saldo_awal_stok', $id_saldo_awal_stok)
    ->where('d.jenis_barang', 'bahan_baku')
    ->selectRaw("
        d.id_saldo_awal_stok_detail,
        d.id_saldo_awal_stok,
        d.jenis_barang,
        d.id_referensi_barang,
        d.qty_awal,
        d.harga_satuan,
        d.nilai_total,
        d.keterangan,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
        s.nama_satuan
    ");

$detail_produk = SaldoAwalStokDetailORM::query()
    ->from('tb_saldo_awal_stok_detail as d')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'd.id_referensi_barang')
            ->where('d.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_saldo_awal_stok', $id_saldo_awal_stok)
    ->where('d.jenis_barang', 'produk')
    ->selectRaw("
        d.id_saldo_awal_stok_detail,
        d.id_saldo_awal_stok,
        d.jenis_barang,
        d.id_referensi_barang,
        d.qty_awal,
        d.harga_satuan,
        d.nilai_total,
        d.keterangan,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
        s.nama_satuan
    ");

$detail_rows = SaldoAwalStokDetailORM::query()
    ->fromSub($detail_bahan->unionAll($detail_produk), 'x')
    ->orderBy('jenis_barang', 'asc')
    ->orderBy('nama_barang', 'asc')
    ->get();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Saldo Awal Stok</title>

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
    <h3>BUKTI SALDO AWAL STOK</h3>
    <p>No: <?= esc($row->no_saldo_awal_stok ?? '-') ?></p>
</div>

<table class="info">
    <tr>
        <td width="18%">No Saldo Awal</td>
        <td width="32%">: <?= esc($row->no_saldo_awal_stok ?? '-') ?></td>
        <td width="18%">Tanggal</td>
        <td width="32%">: <?= esc(date('d/m/Y', strtotime((string) $row->tanggal_saldo_awal))) ?></td>
    </tr>
    <tr>
        <td>Gudang</td>
        <td>: <?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></td>
        <td>Status</td>
        <td>: <span class="status-box"><?= esc((string) $row->status_posting) ?></span></td>
    </tr>
    <tr>
        <td>Akun Lawan</td>
        <td>: <?= esc(($row->kode_coa ?? '-') . ' - ' . ($row->nama_coa ?? '-')) ?></td>
        <td>Tanggal Cetak</td>
        <td>: <?= esc(date('d/m/Y H:i')) ?></td>
    </tr>
    <tr>
        <td>Catatan</td>
        <td colspan="3">: <?= esc($row->catatan ?? '-') ?></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="35">No</th>
            <th width="80">Jenis</th>
            <th width="90">Kode</th>
            <th>Nama Barang</th>
            <th width="70">Satuan</th>
            <th width="85">Qty</th>
            <th width="105">Harga Satuan</th>
            <th width="115">Nilai Total</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($detail_rows->count() === 0): ?>
            <tr>
                <td colspan="8" class="text-center">Tidak ada detail barang.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($detail_rows as $i => $detail): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td><?= esc($detail->jenis_barang === 'bahan_baku' ? 'Bahan' : 'Produk') ?></td>
                    <td><?= esc($detail->kode_barang ?? '-') ?></td>
                    <td><?= esc($detail->nama_barang ?? '-') ?></td>
                    <td><?= esc($detail->nama_satuan ?? '-') ?></td>
                    <td class="text-end"><?= esc(format_qty_cetak_saldo_awal_stok($detail->qty_awal ?? 0)) ?></td>
                    <td class="text-end"><?= number_format((float) ($detail->harga_satuan ?? 0), 2, '.', ',') ?></td>
                    <td class="text-end"><?= number_format((float) ($detail->nilai_total ?? 0), 2, '.', ',') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td>Total Item</td>
        <td class="text-end"><?= number_format($detail_rows->count(), 0, '.', ',') ?> item</td>
    </tr>
    <tr>
        <td>Total Nilai</td>
        <td class="text-end">Rp <?= number_format((float) ($row->total_nilai ?? 0), 2, '.', ',') ?></td>
    </tr>
</table>

<table class="ttd">
    <tr>
        <td>Mengetahui,</td>
        <td></td>
        <td>Dicetak Oleh,</td>
    </tr>
    <tr>
        <td style="padding-top: 60px;">____________________</td>
        <td></td>
        <td style="padding-top: 60px;"><?= esc($user_login['nama_lengkap'] ?? 'Administrator') ?></td>
    </tr>
</table>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>

</body>
</html>