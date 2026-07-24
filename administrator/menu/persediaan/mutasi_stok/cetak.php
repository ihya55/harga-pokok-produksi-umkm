<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/MutasiStokORM.php';

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$jenis_barang = trim((string) ($_GET['jenis_barang'] ?? 'semua'));
$id_referensi_barang = (int) ($_GET['id_referensi_barang'] ?? 0);
$jenis_mutasi = trim((string) ($_GET['jenis_mutasi'] ?? 'semua'));
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);
$tanggal_awal = trim((string) ($_GET['tanggal_awal'] ?? ''));
$tanggal_akhir = trim((string) ($_GET['tanggal_akhir'] ?? ''));

$allowedJenisBarang = ['semua', 'bahan_baku', 'produk'];

if (!in_array($jenis_barang, $allowedJenisBarang, true)) {
    $jenis_barang = 'semua';
}

if ($jenis_barang === 'semua') {
    $id_referensi_barang = 0;
}

function format_qty_cetak_mutasi_stok($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

$queryBahan = MutasiStokORM::query()
    ->from('tb_mutasi_stok as ms')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'ms.id_referensi_barang')
            ->where('ms.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'b.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ms.id_gudang')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'ms.id_entitas')
    ->where('ms.id_entitas', $id_entitas)
    ->where('ms.jenis_barang', 'bahan_baku')
    ->selectRaw("
        ms.id_mutasi_stok,
        ms.id_entitas,
        ms.tanggal_mutasi,
        ms.jenis_barang,
        ms.id_referensi_barang,
        ms.id_gudang,
        ms.jenis_mutasi,
        ms.qty_masuk,
        ms.qty_keluar,
        ms.harga_satuan,
        ms.nilai_total,
        ms.tabel_sumber,
        ms.id_sumber,
        ms.no_sumber,
        ms.keterangan,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
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

$queryProduk = MutasiStokORM::query()
    ->from('tb_mutasi_stok as ms')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'ms.id_referensi_barang')
            ->where('ms.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ms.id_gudang')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'ms.id_entitas')
    ->where('ms.id_entitas', $id_entitas)
    ->where('ms.jenis_barang', 'produk')
    ->selectRaw("
        ms.id_mutasi_stok,
        ms.id_entitas,
        ms.tanggal_mutasi,
        ms.jenis_barang,
        ms.id_referensi_barang,
        ms.id_gudang,
        ms.jenis_mutasi,
        ms.qty_masuk,
        ms.qty_keluar,
        ms.harga_satuan,
        ms.nilai_total,
        ms.tabel_sumber,
        ms.id_sumber,
        ms.no_sumber,
        ms.keterangan,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
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

$dataQuery = MutasiStokORM::query()->fromSub($query, 'x');

if ($id_referensi_barang > 0 && $jenis_barang !== 'semua') {
    $dataQuery->where('id_referensi_barang', $id_referensi_barang);
}

if ($q !== '') {
    $dataQuery->where(function ($sub) use ($q) {
        $sub->where('kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('nama_gudang', 'like', '%' . $q . '%')
            ->orWhere('jenis_mutasi', 'like', '%' . $q . '%')
            ->orWhere('no_sumber', 'like', '%' . $q . '%')
            ->orWhere('keterangan', 'like', '%' . $q . '%')
            ->orWhere('tabel_sumber', 'like', '%' . $q . '%');
    });
}

if ($jenis_mutasi !== 'semua') {
    $dataQuery->where('jenis_mutasi', $jenis_mutasi);
}

if ($id_gudang > 0) {
    $dataQuery->where('id_gudang', $id_gudang);
}

if ($tanggal_awal !== '') {
    $dataQuery->whereDate('tanggal_mutasi', '>=', $tanggal_awal);
}

if ($tanggal_akhir !== '') {
    $dataQuery->whereDate('tanggal_mutasi', '<=', $tanggal_akhir);
}

$data_rows = $dataQuery
    ->orderBy('tanggal_mutasi', 'asc')
    ->orderBy('id_mutasi_stok', 'asc')
    ->get();

$total_nilai = 0;

foreach ($data_rows as $row) {
    $total_nilai += (float) ($row->nilai_total ?? 0);
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

$labelJenisMutasi = $jenis_mutasi === 'semua'
    ? 'Semua'
    : ucwords(str_replace('_', ' ', $jenis_mutasi));

$periodeCetak = '-';
if ($tanggal_awal !== '' || $tanggal_akhir !== '') {
    $periodeCetak = ($tanggal_awal !== '' ? date('d/m/Y', strtotime($tanggal_awal)) : 'Awal') .
        ' s/d ' .
        ($tanggal_akhir !== '' ? date('d/m/Y', strtotime($tanggal_akhir)) : 'Akhir');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Mutasi Stok</title>

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

        .text-end {
            text-align: right;
        }

        .text-center {
            text-align: center;
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
    <h2><?= esc($entitas->nama_entitas ?? 'Nama Entitas') ?></h2>
    <p><?= esc($entitas->alamat ?? '-') ?></p>
    <p>
        Telp: <?= esc($entitas->no_hp ?? '-') ?>
        |
        Email: <?= esc($entitas->email ?? '-') ?>
    </p>
</div>

<div class="judul">
    <h3>LAPORAN MUTASI STOK</h3>
    <p>Tanggal Cetak: <?= esc(date('d/m/Y H:i')) ?></p>
</div>

<table class="info">
    <tr>
        <td width="18%">Periode</td>
        <td width="32%">: <?= esc($periodeCetak) ?></td>
        <td width="18%">Jenis Barang</td>
        <td width="32%">: <?= esc($labelJenisBarang) ?></td>
    </tr>
    <tr>
        <td>Barang</td>
        <td>: <?= esc($labelBarang) ?></td>
        <td>Jenis Mutasi</td>
        <td>: <?= esc($labelJenisMutasi) ?></td>
    </tr>
    <tr>
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
        <td>Kata Kunci</td>
        <td>: <?= esc($q !== '' ? $q : '-') ?></td>
    </tr>
    <tr>
        <td>Total Data</td>
        <td>: <?= number_format($data_rows->count(), 0, '.', ',') ?> baris</td>
        <td></td>
        <td></td>
    </tr>
</table>

<table class="detail">
    <thead>
        <tr>
            <th width="28">No</th>
            <th width="70">Tanggal</th>
            <th width="65">Jenis</th>
            <th width="75">Kode</th>
            <th>Barang</th>
            <th width="85">Gudang</th>
            <th width="75">Mutasi</th>
            <th width="65">Masuk</th>
            <th width="65">Keluar</th>
            <th width="85">Harga</th>
            <th width="95">Nilai</th>
            <th width="90">Sumber</th>
        </tr>
    </thead>

    <tbody>
        <?php if ($data_rows->count() === 0): ?>
            <tr>
                <td colspan="12" class="text-center">Tidak ada data mutasi stok.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($data_rows as $i => $row): ?>
                <tr>
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td><?= esc(!empty($row->tanggal_mutasi) ? date('d/m/Y', strtotime((string) $row->tanggal_mutasi)) : '-') ?></td>
                    <td><?= esc($row->jenis_barang === 'bahan_baku' ? 'Bahan' : 'Produk') ?></td>
                    <td><?= esc($row->kode_barang ?? '-') ?></td>
                    <td><?= esc($row->nama_barang ?? '-') ?></td>
                    <td><?= esc($row->nama_gudang ?? '-') ?></td>
                    <td><?= esc(ucwords(str_replace('_', ' ', (string) ($row->jenis_mutasi ?? '-')))) ?></td>
                    <td class="text-end"><?= (float) ($row->qty_masuk ?? 0) > 0 ? esc(format_qty_cetak_mutasi_stok($row->qty_masuk)) : '-' ?></td>
                    <td class="text-end"><?= (float) ($row->qty_keluar ?? 0) > 0 ? esc(format_qty_cetak_mutasi_stok($row->qty_keluar)) : '-' ?></td>
                    <td class="text-end"><?= number_format((float) ($row->harga_satuan ?? 0), 2, '.', ',') ?></td>
                    <td class="text-end"><?= number_format((float) ($row->nilai_total ?? 0), 2, '.', ',') ?></td>
                    <td><?= esc($row->no_sumber ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<table class="summary">
    <tr>
        <td>Total Baris Mutasi</td>
        <td class="text-end"><?= number_format($data_rows->count(), 0, '.', ',') ?> baris</td>
    </tr>
    <tr>
        <td>Total Nilai Mutasi</td>
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