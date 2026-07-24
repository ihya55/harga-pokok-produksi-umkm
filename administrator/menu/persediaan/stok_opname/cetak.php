<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/StokOpnameORM.php';
require_once __DIR__ . '/../../../../orm/StokOpnameDetailORM.php';

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);

$id_stok_opname = (int) ($_GET['id'] ?? 0);

function format_qty_cetak_stok_opname($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

$row = null;
$detail_rows = collect();
$total_nilai_selisih = 0;

if ($id_stok_opname > 0) {
    $row = StokOpnameORM::query()
        ->from('tb_stok_opname as so')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'so.id_gudang')
        ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'so.id_entitas')
        ->where('so.id_entitas', $id_entitas)
        ->where('so.id_stok_opname', $id_stok_opname)
        ->select([
            'so.*',
            'g.kode_gudang',
            'g.nama_gudang',
            'e.kode_entitas',
            'e.nama_entitas',
            'e.nama_pemilik',
            'e.alamat',
            'e.no_hp',
            'e.email',
        ])
        ->first();

    if (!$row) {
        echo 'Data stok opname tidak ditemukan.';
        exit;
    }

    $detail_bahan = StokOpnameDetailORM::query()
        ->from('tb_stok_opname_detail as d')
        ->leftJoin('tb_bahan_baku as b', function ($join) {
            $join->on('b.id_bahan_baku', '=', 'd.id_referensi_barang')
                ->where('d.jenis_barang', '=', 'bahan_baku');
        })
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
        ->where('d.id_stok_opname', $id_stok_opname)
        ->where('d.jenis_barang', 'bahan_baku')
        ->selectRaw("
            d.id_stok_opname_detail,
            d.id_stok_opname,
            d.jenis_barang,
            d.id_referensi_barang,
            d.qty_sistem,
            d.qty_fisik,
            d.selisih_qty,
            d.harga_satuan,
            d.nilai_selisih,
            d.keterangan,
            b.kode_bahan_baku as kode_barang,
            b.nama_bahan_baku as nama_barang,
            s.nama_satuan
        ");

    $detail_produk = StokOpnameDetailORM::query()
        ->from('tb_stok_opname_detail as d')
        ->leftJoin('tb_produk as p', function ($join) {
            $join->on('p.id_produk', '=', 'd.id_referensi_barang')
                ->where('d.jenis_barang', '=', 'produk');
        })
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
        ->where('d.id_stok_opname', $id_stok_opname)
        ->where('d.jenis_barang', 'produk')
        ->selectRaw("
            d.id_stok_opname_detail,
            d.id_stok_opname,
            d.jenis_barang,
            d.id_referensi_barang,
            d.qty_sistem,
            d.qty_fisik,
            d.selisih_qty,
            d.harga_satuan,
            d.nilai_selisih,
            d.keterangan,
            p.kode_produk as kode_barang,
            p.nama_produk as nama_barang,
            s.nama_satuan
        ");

    $detail_rows = StokOpnameDetailORM::query()
        ->fromSub($detail_bahan->unionAll($detail_produk), 'x')
        ->orderBy('jenis_barang', 'asc')
        ->orderBy('nama_barang', 'asc')
        ->get();

    foreach ($detail_rows as $detail) {
        $total_nilai_selisih += (float) ($detail->nilai_selisih ?? 0);
    }
} else {
    echo 'ID stok opname tidak valid.';
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Stok Opname</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
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
            padding: 5px;
        }

        .detail th {
            background: #f2f2f2;
            text-align: center;
        }

        .summary {
            width: 45%;
            margin-left: auto;
            margin-top: 12px;
        }

        .summary td {
            padding: 5px;
            border: 1px solid #111;
        }

        .text-end { text-align: right; }
        .text-center { text-align: center; }

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
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>

<body>

<div class="no-print">
    <button onclick="window.print()">Print</button>
    <button onclick="window.close()">Tutup</button>
</div>

<div class="kop">
    <h2><?= esc((string) ($row->nama_entitas ?? 'Nama Entitas')) ?></h2>
    <p><?= esc((string) ($row->alamat ?? '-')) ?></p>
    <p>
        Telp: <?= esc((string) ($row->no_hp ?? '-')) ?>
        |
        Email: <?= esc((string) ($row->email ?? '-')) ?>
    </p>
</div>

<div class="judul">
    <h3>BUKTI STOK OPNAME</h3>
    <p>No: <?= esc((string) ($row->no_stok_opname ?? '-')) ?></p>
</div>

<table class="info">
    <tr>
        <td width="18%">No Opname</td>
        <td width="32%">: <?= esc((string) ($row->no_stok_opname ?? '-')) ?></td>
        <td width="18%">Tanggal</td>
        <td width="32%">: <?= esc(date('d/m/Y', strtotime((string) $row->tanggal_stok_opname))) ?></td>
    </tr>
    <tr>
        <td>Gudang</td>
        <td>: <?= esc((string) (($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-'))) ?></td>
        <td>Status</td>
        <td>: <span class="status-box"><?= esc((string) $row->status_posting) ?></span></td>
    </tr>
    <tr>
        <td>Tanggal Cetak</td>
        <td>: <?= esc(date('d/m/Y H:i')) ?></td>
        <td>Total Item</td>
        <td>: <?= number_format($detail_rows->count(), 0, '.', ',') ?> item</td>
    </tr>
    <tr>
        <td>Catatan</td>
        <td colspan="3">: <?= esc((string) ($row->catatan ?? '-')) ?></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="28">No</th>
            <th width="70">Jenis</th>
            <th width="75">Kode</th>
            <th>Barang</th>
            <th width="65">Satuan</th>
            <th width="65">Sistem</th>
            <th width="65">Fisik</th>
            <th width="65">Selisih</th>
            <th width="85">Harga</th>
            <th width="95">Nilai Selisih</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($detail_rows->count() === 0): ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada detail barang.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($detail_rows as $i => $detail): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td><?= esc((string) ($detail->jenis_barang === 'bahan_baku' ? 'Bahan' : 'Produk')) ?></td>
                    <td><?= esc((string) ($detail->kode_barang ?? '-')) ?></td>
                    <td><?= esc((string) ($detail->nama_barang ?? '-')) ?></td>
                    <td><?= esc((string) ($detail->nama_satuan ?? '-')) ?></td>
                    <td class="text-end"><?= esc(format_qty_cetak_stok_opname($detail->qty_sistem ?? 0)) ?></td>
                    <td class="text-end"><?= esc(format_qty_cetak_stok_opname($detail->qty_fisik ?? 0)) ?></td>
                    <td class="text-end"><?= esc(format_qty_cetak_stok_opname($detail->selisih_qty ?? 0)) ?></td>
                    <td class="text-end"><?= number_format((float) ($detail->harga_satuan ?? 0), 2, '.', ',') ?></td>
                    <td class="text-end"><?= number_format((float) ($detail->nilai_selisih ?? 0), 2, '.', ',') ?></td>
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
        <td>Total Nilai Selisih</td>
        <td class="text-end">Rp <?= number_format((float) $total_nilai_selisih, 2, '.', ',') ?></td>
    </tr>
</table>

<table class="ttd">
    <tr>
        <td>Mengetahui,</td>
        <td>Diperiksa Oleh,</td>
        <td>Dicetak Oleh,</td>
    </tr>
    <tr>
        <td style="padding-top: 60px;">____________________</td>
        <td style="padding-top: 60px;">____________________</td>
        <td style="padding-top: 60px;"><?= esc((string) ($user_login['nama_lengkap'] ?? 'Administrator')) ?></td>
    </tr>
</table>

<script>
    window.addEventListener('load', function () {
        window.print();
    });
</script>

</body>
</html>