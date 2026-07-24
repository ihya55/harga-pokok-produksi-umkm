<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

function rupiah_dashboard_gudang($value): string
{
    return 'Rp ' . number_format((float) $value, 2, '.', ',');
}

function qty_dashboard_gudang($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

function count_dashboard_gudang(callable $callback): int
{
    try {
        return (int) $callback();
    } catch (Throwable $e) {
        return 0;
    }
}

function sum_dashboard_gudang(callable $callback): float
{
    try {
        return (float) $callback();
    } catch (Throwable $e) {
        return 0.0;
    }
}

function get_dashboard_gudang(callable $callback)
{
    try {
        return $callback();
    } catch (Throwable $e) {
        return collect();
    }
}

function badge_jenis_barang_dashboard_gudang(string $jenis): string
{
    if ($jenis === 'bahan_baku') {
        return '<span class="badge bg-info-subtle text-info">Bahan Baku</span>';
    }

    if ($jenis === 'produk') {
        return '<span class="badge bg-success-subtle text-success">Produk</span>';
    }

    return '<span class="badge bg-secondary">-</span>';
}

function badge_mutasi_dashboard_gudang(string $jenis_mutasi): string
{
    $jenis_mutasi = strtolower($jenis_mutasi);
    $label = ucwords(str_replace('_', ' ', $jenis_mutasi));
    $class = 'bg-secondary';

    if (in_array($jenis_mutasi, ['masuk', 'penerimaan_pembelian', 'hasil_produksi', 'saldo_awal', 'opname_plus', 'opname_masuk'], true)) {
        $class = 'bg-success';
    }

    if (in_array($jenis_mutasi, ['keluar', 'pengambilan_bahan', 'penyerahan_penjualan', 'opname_minus', 'opname_keluar'], true)) {
        $class = 'bg-danger';
    }

    return '<span class="badge ' . $class . '">' . esc($label) . '</span>';
}

function badge_status_stok_dashboard_gudang($qty, $stok_minimum): string
{
    $qty = (float) $qty;
    $stok_minimum = (float) $stok_minimum;

    if ($stok_minimum <= 0) {
        return '<span class="badge bg-secondary">Tidak Diatur</span>';
    }

    if ($qty <= 0) {
        return '<span class="badge bg-danger">Kosong</span>';
    }

    if ($qty <= $stok_minimum) {
        return '<span class="badge bg-warning text-dark">Minimum</span>';
    }

    return '<span class="badge bg-success">Aman</span>';
}

/*
|--------------------------------------------------------------------------
| Ringkasan utama
|--------------------------------------------------------------------------
*/

$total_gudang = count_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_gudang')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$total_gudang_aktif = count_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_gudang')
        ->where('id_entitas', $id_entitas)
        ->where('status_aktif', 1)
        ->count();
});

$total_item_saldo = count_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_saldo_stok')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$total_nilai_stok = sum_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_saldo_stok')
        ->where('id_entitas', $id_entitas)
        ->sum('nilai_saldo');
});

$mutasi_hari_ini = count_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_mutasi_stok')
        ->where('id_entitas', $id_entitas)
        ->whereDate('tanggal_mutasi', date('Y-m-d'))
        ->count();
});

$mutasi_7_hari = count_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_mutasi_stok')
        ->where('id_entitas', $id_entitas)
        ->whereDate('tanggal_mutasi', '>=', date('Y-m-d', strtotime('-7 days')))
        ->count();
});

$stok_opname_draft = count_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_stok_opname')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'draft')
        ->count();
});

$stok_opname_posted = count_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_stok_opname')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->count();
});

/*
|--------------------------------------------------------------------------
| Query saldo gabungan bahan baku + produk
|--------------------------------------------------------------------------
*/

$saldo_bahan_base = function () use ($id_entitas) {
    return Capsule::table('tb_saldo_stok as ss')
        ->leftJoin('tb_bahan_baku as b', function ($join) {
            $join->on('b.id_bahan_baku', '=', 'ss.id_referensi_barang')
                ->where('ss.jenis_barang', '=', 'bahan_baku');
        })
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
        ->where('ss.id_entitas', $id_entitas)
        ->where('ss.jenis_barang', 'bahan_baku')
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
            s.nama_satuan,
            g.kode_gudang,
            g.nama_gudang
        ");
};

$saldo_produk_base = function () use ($id_entitas) {
    return Capsule::table('tb_saldo_stok as ss')
        ->leftJoin('tb_produk as p', function ($join) {
            $join->on('p.id_produk', '=', 'ss.id_referensi_barang')
                ->where('ss.jenis_barang', '=', 'produk');
        })
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
        ->where('ss.id_entitas', $id_entitas)
        ->where('ss.jenis_barang', 'produk')
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
            s.nama_satuan,
            g.kode_gudang,
            g.nama_gudang
        ");
};

$stok_minimum_count = count_dashboard_gudang(function () use ($saldo_bahan_base, $saldo_produk_base) {
    $union = $saldo_bahan_base()->unionAll($saldo_produk_base());

    return Capsule::query()
        ->fromSub($union, 'x')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0')
        ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= CAST(stok_minimum AS DECIMAL(18,3))')
        ->count();
});

$stok_kosong_count = count_dashboard_gudang(function () use ($saldo_bahan_base, $saldo_produk_base) {
    $union = $saldo_bahan_base()->unionAll($saldo_produk_base());

    return Capsule::query()
        ->fromSub($union, 'x')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0')
        ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= 0')
        ->count();
});

/*
|--------------------------------------------------------------------------
| Data tabel ringkas
|--------------------------------------------------------------------------
*/

$saldo_per_gudang = get_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_saldo_stok as ss')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
        ->where('ss.id_entitas', $id_entitas)
        ->selectRaw("
            ss.id_gudang,
            g.kode_gudang,
            g.nama_gudang,
            g.jenis_gudang,
            COUNT(*) as jumlah_item,
            COALESCE(SUM(ss.nilai_saldo), 0) as total_nilai,
            MAX(ss.tanggal_update) as terakhir_update
        ")
        ->groupBy('ss.id_gudang', 'g.kode_gudang', 'g.nama_gudang', 'g.jenis_gudang')
        ->orderByDesc('total_nilai')
        ->limit(10)
        ->get();
});

$stok_perlu_perhatian = get_dashboard_gudang(function () use ($saldo_bahan_base, $saldo_produk_base) {
    $union = $saldo_bahan_base()->unionAll($saldo_produk_base());

    return Capsule::query()
        ->fromSub($union, 'x')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0')
        ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= CAST(stok_minimum AS DECIMAL(18,3))')
        ->orderBy('qty_saldo', 'asc')
        ->orderBy('nama_barang', 'asc')
        ->limit(10)
        ->get();
});

$mutasi_terakhir = get_dashboard_gudang(function () use ($id_entitas) {
    $mutasi_bahan = Capsule::table('tb_mutasi_stok as ms')
        ->leftJoin('tb_bahan_baku as b', function ($join) {
            $join->on('b.id_bahan_baku', '=', 'ms.id_referensi_barang')
                ->where('ms.jenis_barang', '=', 'bahan_baku');
        })
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ms.id_gudang')
        ->where('ms.id_entitas', $id_entitas)
        ->where('ms.jenis_barang', 'bahan_baku')
        ->selectRaw("
            ms.id_mutasi_stok,
            ms.tanggal_mutasi,
            ms.jenis_barang,
            ms.jenis_mutasi,
            ms.qty_masuk,
            ms.qty_keluar,
            ms.harga_satuan,
            ms.nilai_total,
            ms.no_sumber,
            ms.tabel_sumber,
            b.kode_bahan_baku as kode_barang,
            b.nama_bahan_baku as nama_barang,
            s.nama_satuan,
            g.kode_gudang,
            g.nama_gudang
        ");

    $mutasi_produk = Capsule::table('tb_mutasi_stok as ms')
        ->leftJoin('tb_produk as p', function ($join) {
            $join->on('p.id_produk', '=', 'ms.id_referensi_barang')
                ->where('ms.jenis_barang', '=', 'produk');
        })
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ms.id_gudang')
        ->where('ms.id_entitas', $id_entitas)
        ->where('ms.jenis_barang', 'produk')
        ->selectRaw("
            ms.id_mutasi_stok,
            ms.tanggal_mutasi,
            ms.jenis_barang,
            ms.jenis_mutasi,
            ms.qty_masuk,
            ms.qty_keluar,
            ms.harga_satuan,
            ms.nilai_total,
            ms.no_sumber,
            ms.tabel_sumber,
            p.kode_produk as kode_barang,
            p.nama_produk as nama_barang,
            s.nama_satuan,
            g.kode_gudang,
            g.nama_gudang
        ");

    $union = $mutasi_bahan->unionAll($mutasi_produk);

    return Capsule::query()
        ->fromSub($union, 'x')
        ->orderBy('tanggal_mutasi', 'desc')
        ->orderBy('id_mutasi_stok', 'desc')
        ->limit(12)
        ->get();
});

$stok_opname_terakhir = get_dashboard_gudang(function () use ($id_entitas) {
    return Capsule::table('tb_stok_opname as so')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'so.id_gudang')
        ->leftJoin('tb_stok_opname_detail as d', 'd.id_stok_opname', '=', 'so.id_stok_opname')
        ->where('so.id_entitas', $id_entitas)
        ->selectRaw("
            so.id_stok_opname,
            so.no_stok_opname,
            so.tanggal_stok_opname,
            so.status_posting,
            so.catatan,
            g.kode_gudang,
            g.nama_gudang,
            COUNT(d.id_stok_opname_detail) as jumlah_item,
            COALESCE(SUM(d.nilai_selisih), 0) as total_nilai_selisih
        ")
        ->groupBy(
            'so.id_stok_opname',
            'so.no_stok_opname',
            'so.tanggal_stok_opname',
            'so.status_posting',
            'so.catatan',
            'g.kode_gudang',
            'g.nama_gudang'
        )
        ->orderBy('so.tanggal_stok_opname', 'desc')
        ->orderBy('so.id_stok_opname', 'desc')
        ->limit(10)
        ->get();
});

?>

<style>
    .dashboard-gudang-table-wrap {
        max-height: 360px;
        overflow: auto;
        border-radius: 0.75rem;
    }

    .dashboard-gudang-table-wrap table {
        min-width: 980px;
    }

    .dashboard-gudang-table-wrap-sm {
        max-height: 300px;
        overflow: auto;
        border-radius: 0.75rem;
    }

    .dashboard-gudang-table-wrap-sm table {
        min-width: 780px;
    }

    .dashboard-gudang-card-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(13, 110, 253, 0.08);
    }

    .dashboard-gudang-table-wrap thead th,
    .dashboard-gudang-table-wrap-sm thead th {
        position: sticky;
        top: 0;
        z-index: 2;
    }
</style>


<style>
    /* HPP dashboard unified theme */
    :root {
        --hpp-blue: #2563eb;
        --hpp-purple: #7c3aed;
        --hpp-orange: #f97316;
        --hpp-slate: #0f172a;
        --hpp-muted: #64748b;
        --hpp-soft: #f8fafc;
        --hpp-border: #e5e7eb;
        --hpp-shadow: 0 14px 34px rgba(15, 23, 42, .08);
        --hpp-shadow-soft: 0 8px 22px rgba(15, 23, 42, .06);
        --hpp-radius: 22px;
    }

    .page-header {
        position: relative;
        overflow: hidden;
        border-radius: 26px;
        padding: 24px 26px;
        margin-bottom: 24px !important;
        color: #fff;
        background:
            radial-gradient(circle at 8% 0%, rgba(255,255,255,.30), transparent 28%),
            radial-gradient(circle at 92% 12%, rgba(249,115,22,.38), transparent 30%),
            linear-gradient(135deg, var(--hpp-blue) 0%, var(--hpp-purple) 58%, var(--hpp-orange) 135%);
        box-shadow: 0 18px 44px rgba(37, 99, 235, .20);
    }

    .page-header::after {
        content: "";
        position: absolute;
        width: 240px;
        height: 240px;
        right: -95px;
        top: -110px;
        border-radius: 999px;
        background: rgba(255,255,255,.14);
    }

    .page-header > * { position: relative; z-index: 1; }
    .page-title { margin: 0 0 6px; color: #fff; font-weight: 800; letter-spacing: -.03em; }
    .page-subtitle { margin: 0; color: rgba(255,255,255,.82); }

    .dashboard-hero,
    .keu-hero {
        border: 0 !important;
        border-radius: 26px !important;
        color: #fff !important;
        background:
            radial-gradient(circle at 8% 0%, rgba(255,255,255,.30), transparent 28%),
            radial-gradient(circle at 92% 12%, rgba(249,115,22,.38), transparent 30%),
            linear-gradient(135deg, var(--hpp-blue) 0%, var(--hpp-purple) 58%, var(--hpp-orange) 135%) !important;
        box-shadow: 0 18px 44px rgba(37, 99, 235, .20) !important;
        overflow: hidden;
    }

    .card {
        border: 0 !important;
        border-radius: var(--hpp-radius) !important;
        box-shadow: var(--hpp-shadow-soft) !important;
    }

    .dashboard-card,
    .dashboard-stat,
    .keu-card,
    .card.border-0.shadow-sm {
        border: 0 !important;
        border-radius: var(--hpp-radius) !important;
        box-shadow: var(--hpp-shadow) !important;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .dashboard-card:hover,
    .dashboard-stat:hover,
    .keu-card:hover,
    .card.border-0.shadow-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 18px 40px rgba(15, 23, 42, .10) !important;
    }

    .card .card-body { padding: 1.2rem; }

    .h3, .h4, .h5, .fw-bold, .fw-semibold { letter-spacing: -.02em; }
    .text-muted { color: var(--hpp-muted) !important; }

    .stat-icon,
    .keu-icon,
    .dashboard-gudang-card-icon,
    .rounded-circle[class*="bg-"] {
        width: 46px !important;
        height: 46px !important;
        border-radius: 16px !important;
        box-shadow: inset 0 1px 0 rgba(255,255,255,.28), 0 10px 18px rgba(15,23,42,.10);
    }

    .bg-blue-gradient, .bg-keu-blue { background: linear-gradient(135deg, #2563eb, #38bdf8) !important; }
    .bg-purple-gradient, .bg-keu-purple { background: linear-gradient(135deg, #7c3aed, #c084fc) !important; }
    .bg-orange-gradient, .bg-keu-orange { background: linear-gradient(135deg, #f97316, #fbbf24) !important; }
    .bg-dark-gradient, .bg-keu-dark { background: linear-gradient(135deg, #111827, #64748b) !important; }

    .bg-primary-subtle, .bg-info-subtle, .bg-success-subtle, .bg-warning-subtle, .bg-danger-subtle {
        background: linear-gradient(135deg, rgba(37,99,235,.12), rgba(124,58,237,.08)) !important;
        color: var(--hpp-blue) !important;
    }

    .table-responsive {
        border-radius: 18px !important;
        border-color: var(--hpp-border) !important;
        background: #fff;
    }

    .table { --bs-table-hover-bg: rgba(37, 99, 235, .035); }
    .table thead th {
        background: #f8fafc !important;
        color: #475569 !important;
        border-bottom: 1px solid var(--hpp-border) !important;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .table tbody td { border-color: #eef2f7 !important; }
    .table tfoot th, .table tfoot td { background: #f8fafc !important; }

    .badge { border-radius: 999px; padding: .42em .72em; font-weight: 700; }
    .btn { border-radius: 12px; font-weight: 700; }
    .btn-primary {
        background: linear-gradient(135deg, var(--hpp-blue), var(--hpp-purple)) !important;
        border: 0 !important;
        box-shadow: 0 10px 20px rgba(37,99,235,.18);
    }

    .btn-outline-primary {
        border-color: rgba(37,99,235,.35) !important;
        color: var(--hpp-blue) !important;
        background: #fff !important;
    }

    .btn-outline-primary:hover {
        background: linear-gradient(135deg, var(--hpp-blue), var(--hpp-purple)) !important;
        color: #fff !important;
    }

    .dashboard-chart,
    .keu-chart {
        border-radius: 22px !important;
        border: 1px solid #e5e7eb;
        background:
            linear-gradient(to bottom, rgba(37,99,235,.06), rgba(249,115,22,.035)),
            repeating-linear-gradient(to bottom, transparent 0, transparent 46px, rgba(15,23,42,.055) 47px) !important;
    }

    .chart-bar.blue, .keu-bar.masuk { background: linear-gradient(180deg, #38bdf8, #2563eb) !important; }
    .chart-bar.purple { background: linear-gradient(180deg, #c084fc, #7c3aed) !important; }
    .chart-bar.orange, .keu-bar.keluar { background: linear-gradient(180deg, #fbbf24, #f97316) !important; }

    .list-group-item { border-color: #eef2f7 !important; }
    a.card { color: inherit; }

    @media (max-width: 768px) {
        .page-header { padding: 20px; border-radius: 22px; }
        .card .card-body { padding: 1rem; }
    }
</style>

<div class="page-header mb-4">
    <h1 class="page-title">Dashboard Gudang</h1>
    <p class="page-subtitle">Ringkasan saldo stok, mutasi stok, stok minimum, dan stok opname.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="text-muted small">Gudang Aktif</div>
                        <div class="h4 mb-0"><?= number_format($total_gudang_aktif, 0, '.', ',') ?></div>
                        <div class="text-muted small">Total gudang: <?= number_format($total_gudang, 0, '.', ',') ?></div>
                    </div>
                    <div class="dashboard-gudang-card-icon text-primary fs-4">
                        <i class="bi bi-building"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="text-muted small">Item Bersaldo</div>
                        <div class="h4 mb-0"><?= number_format($total_item_saldo, 0, '.', ',') ?></div>
                        <div class="text-muted small">Qty tidak ditotal lintas satuan</div>
                    </div>
                    <div class="dashboard-gudang-card-icon text-success fs-4">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="text-muted small">Nilai Persediaan</div>
                        <div class="h4 mb-0"><?= rupiah_dashboard_gudang($total_nilai_stok) ?></div>
                        <div class="text-muted small">Total nilai saldo stok</div>
                    </div>
                    <div class="dashboard-gudang-card-icon text-warning fs-4">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="text-muted small">Stok Perlu Perhatian</div>
                        <div class="h4 mb-0"><?= number_format($stok_minimum_count, 0, '.', ',') ?></div>
                        <div class="text-muted small">Kosong: <?= number_format($stok_kosong_count, 0, '.', ',') ?></div>
                    </div>
                    <div class="dashboard-gudang-card-icon text-danger fs-4">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Mutasi Hari Ini</div>
                <div class="h5 mb-0"><?= number_format($mutasi_hari_ini, 0, '.', ',') ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Mutasi 7 Hari</div>
                <div class="h5 mb-0"><?= number_format($mutasi_7_hari, 0, '.', ',') ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Stok Opname Draft</div>
                <div class="h5 mb-0"><?= number_format($stok_opname_draft, 0, '.', ',') ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Stok Opname Posted</div>
                <div class="h5 mb-0"><?= number_format($stok_opname_posted, 0, '.', ',') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1">Saldo per Gudang</h2>
                        <div class="text-muted small">Maksimal 10 gudang berdasarkan nilai persediaan.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('persediaan/saldo-stok')) ?>" class="btn btn-outline-primary btn-sm">
                        Lihat Saldo
                    </a>
                </div>

                <div class="table-responsive dashboard-gudang-table-wrap-sm border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th>Gudang</th>
                                <th width="130">Jenis</th>
                                <th width="110" class="text-end">Item</th>
                                <th width="170" class="text-end">Nilai</th>
                                <th width="140">Update</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($saldo_per_gudang->count() === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada saldo stok.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($saldo_per_gudang as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_gudang ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_gudang ?? '-')) ?></div>
                                        </td>
                                        <td><?= esc((string) ($row->jenis_gudang ?? '-')) ?></td>
                                        <td class="text-end"><?= number_format((int) ($row->jumlah_item ?? 0), 0, '.', ',') ?></td>
                                        <td class="text-end fw-semibold"><?= rupiah_dashboard_gudang($row->total_nilai ?? 0) ?></td>
                                        <td><?= esc(!empty($row->terakhir_update) ? date('d/m/Y H:i', strtotime((string) $row->terakhir_update)) : '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Catatan: qty tidak dijumlahkan karena satuan barang bisa berbeda.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1">Stok Minimum / Kosong</h2>
                        <div class="text-muted small">Maksimal 10 item yang perlu perhatian.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('persediaan/stok-minimum')) ?>" class="btn btn-outline-danger btn-sm">
                        Lihat Stok Minimum
                    </a>
                </div>

                <div class="table-responsive dashboard-gudang-table-wrap-sm border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th width="110">Jenis</th>
                                <th>Barang</th>
                                <th width="120">Gudang</th>
                                <th width="95">Satuan</th>
                                <th width="95" class="text-end">Qty</th>
                                <th width="95" class="text-end">Min</th>
                                <th width="105" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($stok_perlu_perhatian->count() === 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Tidak ada stok minimum/kosong.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stok_perlu_perhatian as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td><?= badge_jenis_barang_dashboard_gudang((string) ($row->jenis_barang ?? '')) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_barang ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_barang ?? '-')) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_gudang ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_gudang ?? '-')) ?></div>
                                        </td>
                                        <td><?= esc((string) ($row->nama_satuan ?? '-')) ?></td>
                                        <td class="text-end fw-semibold"><?= esc(qty_dashboard_gudang($row->qty_saldo ?? 0)) ?></td>
                                        <td class="text-end"><?= esc(qty_dashboard_gudang($row->stok_minimum ?? 0)) ?></td>
                                        <td class="text-center"><?= badge_status_stok_dashboard_gudang($row->qty_saldo ?? 0, $row->stok_minimum ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Item tampil terbatas supaya halaman dashboard tidak terlalu panjang.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Mutasi Stok Terakhir</h2>
                <div class="text-muted small">Maksimal 12 mutasi terbaru. Tabel ini memakai scroll internal.</div>
            </div>
            <a href="<?= esc(admin_page_url('persediaan/mutasi-stok')) ?>" class="btn btn-outline-primary btn-sm">
                Lihat Mutasi
            </a>
        </div>

        <div class="table-responsive dashboard-gudang-table-wrap border">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="135">Tanggal</th>
                        <th width="115">Jenis</th>
                        <th>Barang</th>
                        <th width="150">Gudang</th>
                        <th width="130">Mutasi</th>
                        <th width="110" class="text-end">Masuk</th>
                        <th width="110" class="text-end">Keluar</th>
                        <th width="145" class="text-end">Nilai</th>
                        <th width="145">Sumber</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($mutasi_terakhir->count() === 0): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Belum ada mutasi stok.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($mutasi_terakhir as $i => $row): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold">
                                        <?= esc(!empty($row->tanggal_mutasi) ? date('d/m/Y', strtotime((string) $row->tanggal_mutasi)) : '-') ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= esc(!empty($row->tanggal_mutasi) ? date('H:i', strtotime((string) $row->tanggal_mutasi)) : '-') ?>
                                    </div>
                                </td>
                                <td><?= badge_jenis_barang_dashboard_gudang((string) ($row->jenis_barang ?? '')) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->nama_barang ?? '-')) ?></div>
                                    <div class="text-muted small">
                                        <?= esc((string) ($row->kode_barang ?? '-')) ?>
                                        ·
                                        <?= esc((string) ($row->nama_satuan ?? '-')) ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->nama_gudang ?? '-')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($row->kode_gudang ?? '-')) ?></div>
                                </td>
                                <td><?= badge_mutasi_dashboard_gudang((string) ($row->jenis_mutasi ?? '')) ?></td>
                                <td class="text-end text-success fw-semibold">
                                    <?= (float) ($row->qty_masuk ?? 0) > 0 ? esc(qty_dashboard_gudang($row->qty_masuk)) : '-' ?>
                                </td>
                                <td class="text-end text-danger fw-semibold">
                                    <?= (float) ($row->qty_keluar ?? 0) > 0 ? esc(qty_dashboard_gudang($row->qty_keluar)) : '-' ?>
                                </td>
                                <td class="text-end fw-semibold"><?= rupiah_dashboard_gudang($row->nilai_total ?? 0) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->no_sumber ?? '-')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($row->tabel_sumber ?? '-')) ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-muted small mt-2">
            Qty hanya ditampilkan per baris, tidak dijumlahkan lintas satuan.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Stok Opname Terakhir</h2>
                <div class="text-muted small">Maksimal 10 dokumen stok opname terbaru.</div>
            </div>
            <a href="<?= esc(admin_page_url('persediaan/stok-opname')) ?>" class="btn btn-outline-primary btn-sm">
                Lihat Stok Opname
            </a>
        </div>

        <div class="table-responsive dashboard-gudang-table-wrap-sm border">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="150">No Opname</th>
                        <th width="125">Tanggal</th>
                        <th>Gudang</th>
                        <th width="110" class="text-end">Item</th>
                        <th width="170" class="text-end">Nilai Selisih</th>
                        <th width="110" class="text-center">Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($stok_opname_terakhir->count() === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada stok opname.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stok_opname_terakhir as $i => $row): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= esc((string) ($row->no_stok_opname ?? '-')) ?></td>
                                <td><?= esc(!empty($row->tanggal_stok_opname) ? date('d/m/Y', strtotime((string) $row->tanggal_stok_opname)) : '-') ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->nama_gudang ?? '-')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($row->kode_gudang ?? '-')) ?></div>
                                </td>
                                <td class="text-end"><?= number_format((int) ($row->jumlah_item ?? 0), 0, '.', ',') ?></td>
                                <td class="text-end fw-semibold"><?= rupiah_dashboard_gudang($row->total_nilai_selisih ?? 0) ?></td>
                                <td class="text-center">
                                    <?php if ((string) ($row->status_posting ?? '') === 'posted'): ?>
                                        <span class="badge bg-success">Posted</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Draft</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>