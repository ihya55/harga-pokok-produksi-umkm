<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

function rupiah_dashboard_pembelian($value): string
{
    return 'Rp ' . number_format((float) $value, 2, '.', ',');
}

function count_dashboard_pembelian(callable $callback): int
{
    try {
        return (int) $callback();
    } catch (Throwable $e) {
        return 0;
    }
}

function sum_dashboard_pembelian(callable $callback): float
{
    try {
        return (float) $callback();
    } catch (Throwable $e) {
        return 0.0;
    }
}

/*
|--------------------------------------------------------------------------
| Ringkasan Pesanan Pembelian
|--------------------------------------------------------------------------
*/
$total_pesanan = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_pesanan_pembelian')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$pesanan_draft = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_pesanan_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_pesanan', 'draft')
        ->count();
});

$pesanan_posted = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_pesanan_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_pesanan', 'posted')
        ->count();
});

/*
|--------------------------------------------------------------------------
| Ringkasan Penerimaan Pembelian
|--------------------------------------------------------------------------
*/
$total_penerimaan = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_penerimaan_pembelian')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$penerimaan_draft = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_penerimaan_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_penerimaan', 'draft')
        ->count();
});

$penerimaan_posted = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_penerimaan_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_penerimaan', 'posted')
        ->count();
});

/*
|--------------------------------------------------------------------------
| Ringkasan Faktur Pembelian
|--------------------------------------------------------------------------
*/
$total_faktur = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_pembelian')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$faktur_draft = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'draft')
        ->count();
});

$faktur_posted = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->count();
});

$faktur_belum_lunas = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->where('jenis_pembayaran', 'kredit')
        ->where('sisa_utang', '>', 0)
        ->count();
});

$faktur_sudah_lunas = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->where('jenis_pembayaran', 'kredit')
        ->where('sisa_utang', '<=', 0)
        ->count();
});

$total_utang = sum_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_faktur_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_faktur', 'posted')
        ->where('jenis_pembayaran', 'kredit')
        ->sum('sisa_utang');
});

/*
|--------------------------------------------------------------------------
| Ringkasan Pembayaran Pembelian
|--------------------------------------------------------------------------
*/
$total_pembayaran = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_pembayaran_pembelian')
        ->where('id_entitas', $id_entitas)
        ->count();
});

$pembayaran_draft = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_pembayaran_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'draft')
        ->count();
});

$pembayaran_posted = count_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_pembayaran_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->count();
});

$total_pembayaran_posted = sum_dashboard_pembelian(function () use ($id_entitas) {
    return Capsule::table('tb_pembayaran_pembelian')
        ->where('id_entitas', $id_entitas)
        ->where('status_posting', 'posted')
        ->sum('jumlah_bayar');
});

/*
|--------------------------------------------------------------------------
| Data tabel bawah
|--------------------------------------------------------------------------
*/
$faktur_jatuh_tempo = collect();

try {
    $faktur_jatuh_tempo = Capsule::table('tb_faktur_pembelian as fp')
        ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
        ->where('fp.id_entitas', $id_entitas)
        ->where('fp.status_faktur', 'posted')
        ->where('fp.jenis_pembayaran', 'kredit')
        ->where('fp.sisa_utang', '>', 0)
        ->select([
            'fp.id_faktur_pembelian',
            'fp.no_faktur_pembelian',
            'fp.tanggal_faktur',
            'fp.jatuh_tempo',
            'fp.total',
            'fp.sisa_utang',
            'p.kode_pemasok',
            'p.nama_pemasok',
        ])
        ->orderBy('fp.jatuh_tempo', 'asc')
        ->limit(8)
        ->get();
} catch (Throwable $e) {
    $faktur_jatuh_tempo = collect();
}

$pembayaran_draft_rows = collect();

try {
    $pembayaran_draft_rows = Capsule::table('tb_pembayaran_pembelian as pb')
        ->leftJoin('tb_faktur_pembelian as fp', 'fp.id_faktur_pembelian', '=', 'pb.id_faktur_pembelian')
        ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'pb.id_pemasok')
        ->where('pb.id_entitas', $id_entitas)
        ->where('pb.status_posting', 'draft')
        ->select([
            'pb.id_pembayaran_pembelian',
            'pb.no_pembayaran_pembelian',
            'pb.tanggal_pembayaran',
            'pb.metode_pembayaran',
            'pb.jumlah_bayar',
            'fp.no_faktur_pembelian',
            'p.kode_pemasok',
            'p.nama_pemasok',
        ])
        ->orderBy('pb.tanggal_pembayaran', 'desc')
        ->limit(8)
        ->get();
} catch (Throwable $e) {
    $pembayaran_draft_rows = collect();
}
?>


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
    <h1 class="page-title">Dashboard Pembelian</h1>
    <p class="page-subtitle">Ringkasan alur pembelian, tagihan, dan pembayaran pemasok</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Total Pesanan</div>
                        <div class="h3 mb-0"><?= (int) $total_pesanan ?></div>
                    </div>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-cart-check"></i>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <span class="badge text-bg-secondary">Draft <?= (int) $pesanan_draft ?></span>
                    <span class="badge text-bg-success">Posted <?= (int) $pesanan_posted ?></span>
                </div>

                <a href="<?= esc(admin_page_url('pembelian/pesanan')) ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Total Penerimaan</div>
                        <div class="h3 mb-0"><?= (int) $total_penerimaan ?></div>
                    </div>
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-box-arrow-in-down"></i>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <span class="badge text-bg-secondary">Draft <?= (int) $penerimaan_draft ?></span>
                    <span class="badge text-bg-success">Posted <?= (int) $penerimaan_posted ?></span>
                </div>

                <a href="<?= esc(admin_page_url('pembelian/penerimaan')) ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Total Faktur</div>
                        <div class="h3 mb-0"><?= (int) $total_faktur ?></div>
                    </div>
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-receipt"></i>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <span class="badge text-bg-secondary">Draft <?= (int) $faktur_draft ?></span>
                    <span class="badge text-bg-success">Posted <?= (int) $faktur_posted ?></span>
                </div>

                <a href="<?= esc(admin_page_url('pembelian/faktur')) ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small mb-1">Total Utang</div>
                        <div class="h5 mb-0 text-danger"><?= esc(rupiah_dashboard_pembelian($total_utang)) ?></div>
                    </div>
                    <div class="rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width:42px;height:42px;">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <span class="badge text-bg-danger">Belum Lunas <?= (int) $faktur_belum_lunas ?></span>
                    <span class="badge text-bg-success">Lunas <?= (int) $faktur_sudah_lunas ?></span>
                </div>

                <a href="<?= esc(admin_url('index.php?menu=pembelian/pembayaran&status_lunas=belum_lunas')) ?>" class="stretched-link"></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Pembayaran Draft</div>
                <div class="h3 mb-0"><?= (int) $pembayaran_draft ?></div>
                <div class="text-muted small mt-2">Menunggu posting jurnal pembayaran</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Pembayaran Posted</div>
                <div class="h3 mb-0"><?= (int) $pembayaran_posted ?></div>
                <div class="text-muted small mt-2">Pembayaran sudah mengurangi utang</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Dibayar</div>
                <div class="h5 mb-0 text-success"><?= esc(rupiah_dashboard_pembelian($total_pembayaran_posted)) ?></div>
                <div class="text-muted small mt-2">Total pembayaran posted</div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Pembayaran</div>
                <div class="h3 mb-0"><?= (int) $total_pembayaran ?></div>
                <div class="text-muted small mt-2">Draft + posted</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Shortcut Modul Pembelian</h2>
                <div class="text-muted small">Akses cepat ke alur pembelian</div>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
            <a href="<?= esc(admin_page_url('pembelian/pesanan')) ?>" class="btn btn-outline-primary">
                <i class="bi bi-cart-check me-1"></i>Pesanan Pembelian
            </a>

            <a href="<?= esc(admin_page_url('pembelian/penerimaan')) ?>" class="btn btn-outline-info">
                <i class="bi bi-box-arrow-in-down me-1"></i>Penerimaan Pembelian
            </a>

            <a href="<?= esc(admin_page_url('pembelian/faktur')) ?>" class="btn btn-outline-warning">
                <i class="bi bi-receipt me-1"></i>Faktur Pembelian
            </a>

            <a href="<?= esc(admin_page_url('pembelian/pembayaran')) ?>" class="btn btn-outline-success">
                <i class="bi bi-cash-coin me-1"></i>Pembayaran Pembelian
            </a>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Faktur Kredit Jatuh Tempo</h2>
                        <div class="text-muted small">Faktur belum lunas diurutkan dari jatuh tempo terdekat</div>
                    </div>

                    <a href="<?= esc(admin_url('index.php?menu=pembelian/pembayaran&status_lunas=belum_lunas')) ?>" class="btn btn-sm btn-outline-primary">
                        Lihat Semua
                    </a>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Faktur</th>
                                <th>Pemasok</th>
                                <th>Jatuh Tempo</th>
                                <th class="text-end">Sisa Utang</th>
                                <th width="120" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($faktur_jatuh_tempo->count() > 0): ?>
                                <?php foreach ($faktur_jatuh_tempo as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc($row->no_faktur_pembelian ?? '-') ?></td>
                                        <td><?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></td>
                                        <td><?= esc($row->jatuh_tempo ?? '-') ?></td>
                                        <td class="text-end text-danger fw-semibold">
                                            <?= esc(rupiah_dashboard_pembelian($row->sisa_utang ?? 0)) ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('pembelian/pembayaran/tambah') . '&id_faktur_pembelian=' . (int) $row->id_faktur_pembelian) ?>" class="btn btn-sm btn-success" title="Bayar">
                                                <i class="bi bi-cash-coin"></i>
                                            </a>

                                            <a href="<?= esc(admin_page_url('pembelian/pembayaran/riwayat') . '&id_faktur_pembelian=' . (int) $row->id_faktur_pembelian) ?>" class="btn btn-sm btn-outline-primary" title="Riwayat">
                                                <i class="bi bi-clock-history"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Tidak ada faktur kredit yang belum lunas.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Pembayaran Draft</h2>
                        <div class="text-muted small">Pembayaran yang belum diposting</div>
                    </div>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No Bayar</th>
                                <th>No Faktur</th>
                                <th class="text-end">Jumlah</th>
                                <th width="90" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($pembayaran_draft_rows->count() > 0): ?>
                                <?php foreach ($pembayaran_draft_rows as $row): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc($row->no_pembayaran_pembelian ?? '-') ?></td>
                                        <td><?= esc($row->no_faktur_pembelian ?? '-') ?></td>
                                        <td class="text-end"><?= esc(rupiah_dashboard_pembelian($row->jumlah_bayar ?? 0)) ?></td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('pembelian/pembayaran/detail') . '&id=' . (int) $row->id_pembayaran_pembelian) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="<?= esc(admin_url('menu/pembelian/pembayaran/posting.php?id=' . (int) $row->id_pembayaran_pembelian)) ?>"
                                               class="btn btn-sm btn-outline-success"
                                               onclick="return confirm('Posting pembayaran ini?');"
                                               title="Posting">
                                                <i class="bi bi-journal-check"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Tidak ada pembayaran draft.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>