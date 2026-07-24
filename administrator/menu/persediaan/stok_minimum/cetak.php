<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/SaldoStokORM.php';

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$jenis_barang = trim((string) ($_GET['jenis_barang'] ?? 'semua'));
$id_referensi_barang = (int) ($_GET['id_referensi_barang'] ?? 0);
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);
$status_stok = trim((string) ($_GET['status_stok'] ?? 'semua'));

$allowedJenisBarang = ['semua', 'bahan_baku', 'produk'];
$allowedStatusStok = ['semua', 'kosong', 'minimum', 'aman'];

if (!in_array($jenis_barang, $allowedJenisBarang, true)) $jenis_barang = 'semua';
if ($jenis_barang === 'semua') $id_referensi_barang = 0;
if (!in_array($status_stok, $allowedStatusStok, true)) $status_stok = 'semua';

function format_qty_cetak_stok_minimum($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

function label_status_cetak_stok_minimum($qty, $stok_minimum): string
{
    $qty = (float) $qty;
    $stok_minimum = (float) $stok_minimum;

    if ($stok_minimum <= 0) {
        return 'Tidak Diatur';
    }

    if ($qty <= 0) {
        return 'Kosong';
    }

    if ($qty <= $stok_minimum) {
        return 'Minimum';
    }

    return 'Aman';
}

$queryBahan = SaldoStokORM::query()
    ->from('tb_saldo_stok as ss')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'ss.id_referensi_barang')
            ->where('ss.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'b.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'ss.id_entitas')
    ->where('ss.id_entitas', $id_entitas)
    ->where('ss.jenis_barang', 'bahan_baku')
    ->where('b.status_aktif', 1)
    ->selectRaw("
        ss.id_saldo_stok,
        ss.id_entitas,
        ss.jenis_barang,
        ss.id_referensi_barang,
        ss.id_gudang,
        ss.qty_saldo,
        ss.nilai_saldo,
        ss.hpp_rata_rata,
        ss.tanggal_update,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
        b.stok_minimum,
        st.nama_satuan,
        g.kode_gudang,
        g.nama_gudang,
        e.kode_entitas,
        e.nama_entitas,
        e.nama_pemilik,
        e.alamat,
        e.no_hp,
        e.email
    ");

$queryProduk = SaldoStokORM::query()
    ->from('tb_saldo_stok as ss')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'ss.id_referensi_barang')
            ->where('ss.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'ss.id_entitas')
    ->where('ss.id_entitas', $id_entitas)
    ->where('ss.jenis_barang', 'produk')
    ->where('p.status_produk', 1)
    ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->selectRaw("
        ss.id_saldo_stok,
        ss.id_entitas,
        ss.jenis_barang,
        ss.id_referensi_barang,
        ss.id_gudang,
        ss.qty_saldo,
        ss.nilai_saldo,
        ss.hpp_rata_rata,
        ss.tanggal_update,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
        p.stok_minimum,
        st.nama_satuan,
        g.kode_gudang,
        g.nama_gudang,
        e.kode_entitas,
        e.nama_entitas,
        e.nama_pemilik,
        e.alamat,
        e.no_hp,
        e.email
    ");

if ($jenis_barang === 'bahan_baku') {
    $query = $queryBahan;
} elseif ($jenis_barang === 'produk') {
    $query = $queryProduk;
} else {
    $query = $queryBahan->unionAll($queryProduk);
}

$dataQuery = SaldoStokORM::query()->fromSub($query, 'x');

if ($id_referensi_barang > 0 && $jenis_barang !== 'semua') {
    $dataQuery->where('id_referensi_barang', $id_referensi_barang);
}

if ($id_gudang > 0) {
    $dataQuery->where('id_gudang', $id_gudang);
}

if ($q !== '') {
    $dataQuery->where(function ($sub) use ($q) {
        $sub->where('kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('nama_gudang', 'like', '%' . $q . '%')
            ->orWhere('kode_barang', 'like', '%' . $q . '%')
            ->orWhere('nama_barang', 'like', '%' . $q . '%');
    });
}

if ($status_stok === 'kosong') {
    $dataQuery->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= 0')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0');
} elseif ($status_stok === 'minimum') {
    $dataQuery->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) > 0')
        ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= CAST(stok_minimum AS DECIMAL(18,3))')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0');
} elseif ($status_stok === 'aman') {
    $dataQuery->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) > CAST(stok_minimum AS DECIMAL(18,3))')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0');
} else {
    $dataQuery->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0');
}

$data_rows = $dataQuery
    ->orderBy('jenis_barang', 'asc')
    ->orderBy('nama_barang', 'asc')
    ->get();

$total_nilai = 0;

foreach ($data_rows as $row) {
    $total_nilai += (float) ($row->nilai_saldo ?? 0);
}

$entitas = $data_rows->first();

$labelJenisBarang = 'Semua';
if ($jenis_barang === 'bahan_baku') {
    $labelJenisBarang = 'Bahan Baku';
} elseif ($jenis_barang === 'produk') {
    $labelJenisBarang = 'Produk';
}

$labelBarang = 'Semua Barang';
if ($id_referensi_barang > 0 && $data_rows->count() > 0) {
    $labelBarang = ($data_rows->first()->kode_barang ?? '-') . ' - ' . ($data_rows->first()->nama_barang ?? '-');
}

$labelStatus = 'Semua';
if ($status_stok === 'kosong') $labelStatus = 'Kosong';
if ($status_stok === 'minimum') $labelStatus = 'Minimum';
if ($status_stok === 'aman') $labelStatus = 'Aman';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Stok Minimum</title>

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
    <h2><?= esc($entitas->nama_entitas ?? 'Nama Entitas') ?></h2>
    <p><?= esc($entitas->alamat ?? '-') ?></p>
    <p>
        Telp: <?= esc($entitas->no_hp ?? '-') ?>
        |
        Email: <?= esc($entitas->email ?? '-') ?>
    </p>
</div>

<div class="judul">
    <h3>LAPORAN STOK MINIMUM</h3>
    <p>Tanggal Cetak: <?= esc(date('d/m/Y H:i')) ?></p>
</div>

<table class="info">
    <tr>
        <td width="18%">Jenis Barang</td>
        <td width="32%">: <?= esc($labelJenisBarang) ?></td>
        <td width="18%">Barang</td>
        <td width="32%">: <?= esc($labelBarang) ?></td>
    </tr>
    <tr>
        <td>Status Stok</td>
        <td>: <?= esc($labelStatus) ?></td>
        <td>Gudang</td>
        <td>:
            <?php
            if ($id_gudang > 0 && $data_rows->count() > 0) {
                echo esc(($data_rows->first()->kode_gudang ?? '-') . ' - ' . ($data_rows->first()->nama_gudang ?? '-'));
            } else {
                echo 'Semua Gudang';
            }
            ?>
        </td>
    </tr>
    <tr>
        <td>Kata Kunci</td>
        <td>: <?= esc($q !== '' ? $q : '-') ?></td>
        <td>Total Data</td>
        <td>: <?= number_format($data_rows->count(), 0, '.', ',') ?> item</td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="28">No</th>
            <th width="70">Jenis</th>
            <th width="75">Kode</th>
            <th>Barang</th>
            <th width="85">Gudang</th>
            <th width="65">Satuan</th>
            <th width="65">Qty</th>
            <th width="65">Min</th>
            <th width="95">Nilai</th>
            <th width="75">Status</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($data_rows->count() === 0): ?>
            <tr>
                <td colspan="10" class="text-center">Tidak ada data stok minimum.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($data_rows as $i => $row): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td><?= esc($row->jenis_barang === 'bahan_baku' ? 'Bahan' : 'Produk') ?></td>
                    <td><?= esc($row->kode_barang ?? '-') ?></td>
                    <td><?= esc($row->nama_barang ?? '-') ?></td>
                    <td><?= esc($row->nama_gudang ?? '-') ?></td>
                    <td><?= esc($row->nama_satuan ?? '-') ?></td>
                    <td class="text-end"><?= esc(format_qty_cetak_stok_minimum($row->qty_saldo ?? 0)) ?></td>
                    <td class="text-end"><?= esc(format_qty_cetak_stok_minimum($row->stok_minimum ?? 0)) ?></td>
                    <td class="text-end"><?= number_format((float) ($row->nilai_saldo ?? 0), 2, '.', ',') ?></td>
                    <td><?= esc(label_status_cetak_stok_minimum($row->qty_saldo ?? 0, $row->stok_minimum ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td>Total Item</td>
        <td class="text-end"><?= number_format($data_rows->count(), 0, '.', ',') ?> item</td>
    </tr>
    <tr>
        <td>Total Nilai</td>
        <td class="text-end">Rp <?= number_format((float) $total_nilai, 2, '.', ',') ?></td>
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