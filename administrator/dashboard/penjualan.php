<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

function rupiah_dashboard_penjualan($value): string
{
    return 'Rp ' . number_format((float) $value, 2, '.', ',');
}

function qty_dashboard_penjualan($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

function safe_count_dashboard_penjualan(callable $callback): int
{
    try {
        return (int) $callback();
    } catch (Throwable $e) {
        return 0;
    }
}

function safe_sum_dashboard_penjualan(callable $callback): float
{
    try {
        return (float) $callback();
    } catch (Throwable $e) {
        return 0.0;
    }
}

function safe_get_dashboard_penjualan(callable $callback)
{
    try {
        return $callback();
    } catch (Throwable $e) {
        return collect();
    }
}

function badge_status_dashboard_penjualan(string $status): string
{
    $status = strtolower($status);
    $label = ucwords(str_replace('_', ' ', $status));
    $class = 'bg-secondary';

    if (in_array($status, ['posted', 'lunas', 'selesai', 'terkonfirmasi', 'dikonfirmasi'], true)) {
        $class = 'bg-success';
    }

    if (in_array($status, ['draft', 'menunggu', 'diproses'], true)) {
        $class = 'bg-secondary';
    }

    if (in_array($status, ['batal', 'dibatalkan'], true)) {
        $class = 'bg-danger';
    }

    if (in_array($status, ['sebagian', 'belum_lunas'], true)) {
        $class = 'bg-warning text-dark';
    }

    return '<span class="badge ' . $class . '">' . esc($label) . '</span>';
}

$tanggal_hari_ini = date('Y-m-d');
$awal_bulan = date('Y-m-01');
$akhir_bulan = date('Y-m-t');

/*
|--------------------------------------------------------------------------
| Ringkasan
|--------------------------------------------------------------------------
*/

$pesanan_hari_ini = safe_count_dashboard_penjualan(function () use ($id_entitas, $tanggal_hari_ini) {
    return Capsule::table('tb_pesanan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->whereDate('tanggal_pesanan', $tanggal_hari_ini)
        ->count();
});

$pesanan_bulan_ini = safe_count_dashboard_penjualan(function () use ($id_entitas, $awal_bulan, $akhir_bulan) {
    return Capsule::table('tb_pesanan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->whereBetween('tanggal_pesanan', [$awal_bulan, $akhir_bulan])
        ->count();
});

$penjualan_bulan_ini = safe_sum_dashboard_penjualan(function () use ($id_entitas, $awal_bulan, $akhir_bulan) {
    return Capsule::table('tb_faktur_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->whereBetween('tanggal_faktur', [$awal_bulan, $akhir_bulan])
        ->sum('total');
});

$piutang_berjalan = safe_sum_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->sum('sisa_piutang');
});

$faktur_belum_lunas = safe_count_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->where('sisa_piutang', '>', 0)
        ->count();
});

$penyerahan_draft = safe_count_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_penyerahan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('status_penyerahan', 'draft')
        ->count();
});

$pesanan_online = safe_count_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_pesanan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('sumber_pesanan', 'website')
        ->count();
});

$pelanggan_aktif = safe_count_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_pelanggan')
        ->where('id_entitas', $id_entitas)
        ->where('status_aktif', 1)
        ->count();
});

/*
|--------------------------------------------------------------------------
| Tabel ringkas
|--------------------------------------------------------------------------
*/

$pesanan_terbaru = safe_get_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_pesanan_penjualan as pp')
        ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
        ->where('pp.id_entitas', $id_entitas)
        ->select([
            'pp.id_pesanan_penjualan',
            'pp.no_pesanan_penjualan',
            'pp.tanggal_pesanan',
            'pp.sumber_pesanan',
            'pp.status_pesanan',
            'pp.total',
            'pl.kode_pelanggan',
            'pl.nama_pelanggan',
        ])
        ->orderByDesc('pp.tanggal_pesanan')
        ->orderByDesc('pp.id_pesanan_penjualan')
        ->limit(10)
        ->get();
});

$faktur_belum_lunas_rows = safe_get_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_penjualan as f')
        ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'f.id_pelanggan')
        ->where('f.id_entitas', $id_entitas)
        ->where('f.status_faktur', 'posted')
        ->where('f.sisa_piutang', '>', 0)
        ->select([
            'f.id_faktur_penjualan',
            'f.no_faktur_penjualan',
            'f.tanggal_faktur',
            'f.jatuh_tempo',
            'f.jenis_pembayaran',
            'f.total',
            'f.sisa_piutang',
            'pl.kode_pelanggan',
            'pl.nama_pelanggan',
        ])
        ->orderBy('f.jatuh_tempo', 'asc')
        ->orderBy('f.id_faktur_penjualan', 'asc')
        ->limit(10)
        ->get();
});

$penyerahan_terbaru = safe_get_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_penyerahan_penjualan as pn')
        ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pn.id_pelanggan')
        ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'pn.id_gudang')
        ->where('pn.id_entitas', $id_entitas)
        ->select([
            'pn.id_penyerahan_penjualan',
            'pn.no_penyerahan_penjualan',
            'pn.tanggal_penyerahan',
            'pn.status_penyerahan',
            'pl.kode_pelanggan',
            'pl.nama_pelanggan',
            'g.kode_gudang',
            'g.nama_gudang',
        ])
        ->orderByDesc('pn.tanggal_penyerahan')
        ->orderByDesc('pn.id_penyerahan_penjualan')
        ->limit(10)
        ->get();
});

$top_produk = safe_get_dashboard_penjualan(function () use ($id_entitas, $awal_bulan, $akhir_bulan) {
    return Capsule::table('tb_faktur_penjualan_detail as d')
        ->join('tb_faktur_penjualan as f', 'f.id_faktur_penjualan', '=', 'd.id_faktur_penjualan')
        ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
        ->where('f.id_entitas', $id_entitas)
        ->where('f.status_faktur', 'posted')
        ->whereBetween('f.tanggal_faktur', [$awal_bulan, $akhir_bulan])
        ->selectRaw("
            d.id_produk,
            p.kode_produk,
            p.nama_produk,
            s.nama_satuan,
            COUNT(*) as jumlah_baris,
            COALESCE(SUM(d.qty), 0) as total_qty,
            COALESCE(SUM(d.subtotal), 0) as total_penjualan
        ")
        ->groupBy('d.id_produk', 'p.kode_produk', 'p.nama_produk', 's.nama_satuan')
        ->orderByDesc('total_penjualan')
        ->limit(10)
        ->get();
});

$pembayaran_terbaru = safe_get_dashboard_penjualan(function () use ($id_entitas) {
    return Capsule::table('tb_pembayaran_penjualan as p')
        ->leftJoin('tb_faktur_penjualan as f', 'f.id_faktur_penjualan', '=', 'p.id_faktur_penjualan')
        ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'p.id_pelanggan')
        ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'p.id_coa_kas_bank')
        ->where('p.id_entitas', $id_entitas)
        ->select([
            'p.id_pembayaran_penjualan',
            'p.no_pembayaran_penjualan',
            'p.tanggal_pembayaran',
            'p.metode_pembayaran',
            'p.jumlah_bayar',
            'p.status_posting',
            'f.no_faktur_penjualan',
            'pl.kode_pelanggan',
            'pl.nama_pelanggan',
            'c.kode_coa',
            'c.nama_coa',
        ])
        ->orderByDesc('p.tanggal_pembayaran')
        ->orderByDesc('p.id_pembayaran_penjualan')
        ->limit(10)
        ->get();
});
?>

<style>
    .dashboard-penjualan-table-wrap {
        max-height: 340px;
        overflow: auto;
        border-radius: 0.75rem;
    }

    .dashboard-penjualan-table-wrap table {
        min-width: 920px;
    }

    .dashboard-penjualan-table-wrap-sm {
        max-height: 300px;
        overflow: auto;
        border-radius: 0.75rem;
    }

    .dashboard-penjualan-table-wrap-sm table {
        min-width: 760px;
    }

    .dashboard-penjualan-table-wrap thead th,
    .dashboard-penjualan-table-wrap-sm thead th {
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
    <h1 class="page-title">Dashboard Penjualan</h1>
    <p class="page-subtitle">Ringkasan pesanan, kasir/POS, penyerahan, faktur, pembayaran, dan pesanan online.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pesanan Hari Ini</div>
                <div class="h4 mb-0"><?= number_format($pesanan_hari_ini, 0, '.', ',') ?></div>
                <div class="text-muted small">Bulan ini: <?= number_format($pesanan_bulan_ini, 0, '.', ',') ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Penjualan Bulan Ini</div>
                <div class="h4 mb-0"><?= rupiah_dashboard_penjualan($penjualan_bulan_ini) ?></div>
                <div class="text-muted small">Berdasarkan faktur posted</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Piutang Berjalan</div>
                <div class="h4 mb-0"><?= rupiah_dashboard_penjualan($piutang_berjalan) ?></div>
                <div class="text-muted small">Faktur belum lunas: <?= number_format($faktur_belum_lunas, 0, '.', ',') ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pesanan Online</div>
                <div class="h4 mb-0"><?= number_format($pesanan_online, 0, '.', ',') ?></div>
                <div class="text-muted small">Pelanggan aktif: <?= number_format($pelanggan_aktif, 0, '.', ',') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <a href="<?= esc(admin_page_url('penjualan/kasir')) ?>" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body">
                <div class="text-muted small">Kasir / POS</div>
                <div class="h5 mb-1">Transaksi Cepat</div>
                <div class="text-muted small">Penjualan tunai, transfer, atau kredit dari layar kasir.</div>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="<?= esc(admin_page_url('penjualan/penyerahan')) ?>" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body">
                <div class="text-muted small">Penyerahan Draft</div>
                <div class="h5 mb-1"><?= number_format($penyerahan_draft, 0, '.', ',') ?> Dokumen</div>
                <div class="text-muted small">Perlu diposting agar stok keluar.</div>
            </div>
        </a>
    </div>

    <div class="col-md-4">
        <a href="<?= esc(admin_page_url('penjualan/pembayaran')) ?>" class="card border-0 shadow-sm text-decoration-none text-dark h-100">
            <div class="card-body">
                <div class="text-muted small">Pembayaran Penjualan</div>
                <div class="h5 mb-1">Terima Pelunasan</div>
                <div class="text-muted small">Pembayaran faktur kredit dan piutang pelanggan.</div>
            </div>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1">Pesanan Terbaru</h2>
                        <div class="text-muted small">Maksimal 10 pesanan terbaru.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="btn btn-outline-primary btn-sm">Lihat Pesanan</a>
                </div>

                <div class="table-responsive dashboard-penjualan-table-wrap-sm border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th width="140">No Pesanan</th>
                                <th width="110">Tanggal</th>
                                <th>Pelanggan</th>
                                <th width="110">Sumber</th>
                                <th width="120" class="text-end">Total</th>
                                <th width="110" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pesanan_terbaru->count() === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada pesanan penjualan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pesanan_terbaru as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td class="fw-semibold"><?= esc((string) ($row->no_pesanan_penjualan ?? '-')) ?></td>
                                        <td><?= esc(!empty($row->tanggal_pesanan) ? date('d/m/Y', strtotime((string) $row->tanggal_pesanan)) : '-') ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_pelanggan ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_pelanggan ?? '-')) ?></div>
                                        </td>
                                        <td><?= esc(ucwords((string) ($row->sumber_pesanan ?? '-'))) ?></td>
                                        <td class="text-end fw-semibold"><?= rupiah_dashboard_penjualan($row->total ?? 0) ?></td>
                                        <td class="text-center"><?= badge_status_dashboard_penjualan((string) ($row->status_pesanan ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1">Faktur Belum Lunas</h2>
                        <div class="text-muted small">Maksimal 10 faktur piutang.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('penjualan/faktur')) ?>" class="btn btn-outline-danger btn-sm">Lihat Faktur</a>
                </div>

                <div class="table-responsive dashboard-penjualan-table-wrap-sm border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th width="140">No Faktur</th>
                                <th>Pelanggan</th>
                                <th width="110">Jatuh Tempo</th>
                                <th width="130" class="text-end">Total</th>
                                <th width="130" class="text-end">Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($faktur_belum_lunas_rows->count() === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Tidak ada faktur belum lunas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faktur_belum_lunas_rows as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td class="fw-semibold"><?= esc((string) ($row->no_faktur_penjualan ?? '-')) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_pelanggan ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_pelanggan ?? '-')) ?></div>
                                        </td>
                                        <td><?= esc(!empty($row->jatuh_tempo) ? date('d/m/Y', strtotime((string) $row->jatuh_tempo)) : '-') ?></td>
                                        <td class="text-end"><?= rupiah_dashboard_penjualan($row->total ?? 0) ?></td>
                                        <td class="text-end fw-semibold text-danger"><?= rupiah_dashboard_penjualan($row->sisa_piutang ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Top Produk Terjual Bulan Ini</h2>
                <div class="text-muted small">Qty tampil per produk dan satuan. Tidak dijumlahkan lintas satuan.</div>
            </div>
            <a href="<?= esc(admin_page_url('penjualan/faktur')) ?>" class="btn btn-outline-primary btn-sm">Lihat Penjualan</a>
        </div>

        <div class="table-responsive dashboard-penjualan-table-wrap border">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="140">Kode</th>
                        <th>Produk</th>
                        <th width="110">Satuan</th>
                        <th width="130" class="text-end">Qty</th>
                        <th width="140" class="text-end">Baris</th>
                        <th width="170" class="text-end">Nilai Penjualan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($top_produk->count() === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada produk terjual bulan ini.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($top_produk as $i => $row): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= esc((string) ($row->kode_produk ?? '-')) ?></td>
                                <td><?= esc((string) ($row->nama_produk ?? '-')) ?></td>
                                <td><?= esc((string) ($row->nama_satuan ?? '-')) ?></td>
                                <td class="text-end fw-semibold"><?= esc(qty_dashboard_penjualan($row->total_qty ?? 0)) ?></td>
                                <td class="text-end"><?= number_format((int) ($row->jumlah_baris ?? 0), 0, '.', ',') ?></td>
                                <td class="text-end fw-semibold"><?= rupiah_dashboard_penjualan($row->total_penjualan ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1">Penyerahan Terbaru</h2>
                        <div class="text-muted small">Maksimal 10 dokumen penyerahan.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('penjualan/penyerahan')) ?>" class="btn btn-outline-primary btn-sm">Lihat Penyerahan</a>
                </div>

                <div class="table-responsive dashboard-penjualan-table-wrap-sm border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th width="150">No Penyerahan</th>
                                <th width="110">Tanggal</th>
                                <th>Pelanggan</th>
                                <th width="130">Gudang</th>
                                <th width="110" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($penyerahan_terbaru->count() === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Belum ada penyerahan penjualan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($penyerahan_terbaru as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td class="fw-semibold"><?= esc((string) ($row->no_penyerahan_penjualan ?? '-')) ?></td>
                                        <td><?= esc(!empty($row->tanggal_penyerahan) ? date('d/m/Y', strtotime((string) $row->tanggal_penyerahan)) : '-') ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_pelanggan ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_pelanggan ?? '-')) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_gudang ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_gudang ?? '-')) ?></div>
                                        </td>
                                        <td class="text-center"><?= badge_status_dashboard_penjualan((string) ($row->status_penyerahan ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="h5 mb-1">Pembayaran Terbaru</h2>
                        <div class="text-muted small">Maksimal 10 pembayaran terakhir.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('penjualan/pembayaran')) ?>" class="btn btn-outline-primary btn-sm">Lihat Pembayaran</a>
                </div>

                <div class="table-responsive dashboard-penjualan-table-wrap-sm border">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th width="150">No Bayar</th>
                                <th width="110">Tanggal</th>
                                <th>Pelanggan</th>
                                <th width="130">Metode</th>
                                <th width="150" class="text-end">Jumlah</th>
                                <th width="110" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pembayaran_terbaru->count() === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Belum ada pembayaran penjualan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pembayaran_terbaru as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->no_pembayaran_penjualan ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->no_faktur_penjualan ?? '-')) ?></div>
                                        </td>
                                        <td><?= esc(!empty($row->tanggal_pembayaran) ? date('d/m/Y', strtotime((string) $row->tanggal_pembayaran)) : '-') ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_pelanggan ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_pelanggan ?? '-')) ?></div>
                                        </td>
                                        <td>
                                            <div><?= esc(ucwords((string) ($row->metode_pembayaran ?? '-'))) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_coa ?? '-')) ?></div>
                                        </td>
                                        <td class="text-end fw-semibold"><?= rupiah_dashboard_penjualan($row->jumlah_bayar ?? 0) ?></td>
                                        <td class="text-center"><?= badge_status_dashboard_penjualan((string) ($row->status_posting ?? '-')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>