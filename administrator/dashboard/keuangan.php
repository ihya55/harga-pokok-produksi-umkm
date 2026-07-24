<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../menu/keuangan/_keuangan_helper.php';

$id_entitas = keu_id_entitas();
$tanggal_awal = date('Y-m-01');
$tanggal_akhir = date('Y-m-t');
$tahun_ini = date('Y');

$laba_rugi_rows = keu_laporan_mapping_rows('LABA_RUGI', $tanggal_awal, $tanggal_akhir);
$neraca_rows = keu_laporan_mapping_rows('NERACA', $tanggal_awal, $tanggal_akhir);
$arus_rows = keu_laporan_mapping_rows('ARUS_KAS', $tanggal_awal, $tanggal_akhir);

$total_pendapatan = keu_laporan_summary($laba_rugi_rows, 'total-pendapatan');
$total_beban = abs(keu_laporan_summary($laba_rugi_rows, 'total-beban-operasional'));
$laba_bersih = keu_laporan_summary($laba_rugi_rows, 'laba-rugi-bersih');

$total_aset = keu_laporan_summary($neraca_rows, 'total-aset');
$total_liabilitas = keu_laporan_summary($neraca_rows, 'total-liabilitas');
$total_ekuitas = keu_laporan_summary($neraca_rows, 'total-ekuitas');

$kas_masuk = 0.0;
$kas_keluar = 0.0;

foreach ($arus_rows as $r) {
    if (($r['tipe_baris'] ?? '') === 'DETAIL') {
        $nilai = (float) $r['nilai'];
        if ($nilai >= 0) {
            $kas_masuk += $nilai;
        } else {
            $kas_keluar += abs($nilai);
        }
    }
}

$jurnal_draft = (int) Capsule::table('tb_jurnal')
    ->where('id_entitas', $id_entitas)
    ->where('status_jurnal', 'draft')
    ->count();

$jurnal_terbaru = Capsule::table('tb_jurnal')
    ->where('id_entitas', $id_entitas)
    ->orderBy('tanggal_jurnal', 'desc')
    ->orderBy('id_jurnal', 'desc')
    ->limit(5)
    ->get();

$bulan_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$chart_masuk = array_fill(1, 12, 0.0);
$chart_keluar = array_fill(1, 12, 0.0);

try {
    $queryChart = Capsule::table('tb_jurnal as j')
        ->join('tb_jurnal_detail as jd', 'jd.id_jurnal', '=', 'j.id_jurnal')
        ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
        ->where('j.id_entitas', $id_entitas)
        ->where('j.status_jurnal', 'posted')
        ->whereYear('j.tanggal_jurnal', $tahun_ini)
        ->selectRaw('MONTH(j.tanggal_jurnal) as bulan')
        ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit),0) as mutasi')
        ->groupByRaw('MONTH(j.tanggal_jurnal)');
    
    keu_exclude_saldo_awal_query($queryChart, 'j');

    keu_is_kas_bank_query($queryChart, 'c', $id_entitas);

    foreach ($queryChart->get() as $r) {
        $bulan = (int) $r->bulan;
        $mutasi = (float) $r->mutasi;

        if ($bulan >= 1 && $bulan <= 12) {
            if ($mutasi >= 0) {
                $chart_masuk[$bulan] += $mutasi;
            } else {
                $chart_keluar[$bulan] += abs($mutasi);
            }
        }
    }
} catch (Throwable $e) {
    $chart_masuk = array_fill(1, 12, 0.0);
    $chart_keluar = array_fill(1, 12, 0.0);
}

$max_chart = max(1, max($chart_masuk), max($chart_keluar));
?>

<style>
    .keu-hero {
        border: 0;
        border-radius: 24px;
        color: #fff;
        background:
            radial-gradient(circle at top left, rgba(255,255,255,.28), transparent 28%),
            linear-gradient(135deg, #2563eb 0%, #7c3aed 58%, #f97316 128%);
        box-shadow: 0 16px 38px rgba(37, 99, 235, .20);
    }

    .keu-card {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
    }

    .keu-icon {
        width: 44px;
        height: 44px;
        border-radius: 15px;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }

    .bg-keu-blue { background: linear-gradient(135deg, #2563eb, #38bdf8); }
    .bg-keu-purple { background: linear-gradient(135deg, #7c3aed, #c084fc); }
    .bg-keu-orange { background: linear-gradient(135deg, #f97316, #fbbf24); }
    .bg-keu-dark { background: linear-gradient(135deg, #111827, #64748b); }

    .keu-chart {
        height: 260px;
        border-radius: 18px;
        padding: 16px 12px 32px;
        background:
            linear-gradient(to bottom, rgba(37,99,235,.06), rgba(249,115,22,.03)),
            repeating-linear-gradient(to bottom, transparent 0, transparent 50px, rgba(15,23,42,.06) 51px);
    }

    .keu-chart-grid {
        height: 100%;
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        align-items: end;
        gap: 9px;
    }

    .keu-chart-col {
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: end;
        gap: 6px;
        text-align: center;
    }

    .keu-bars {
        height: calc(100% - 24px);
        display: flex;
        align-items: end;
        justify-content: center;
        gap: 4px;
    }

    .keu-bar {
        width: 9px;
        min-height: 4px;
        border-radius: 999px 999px 4px 4px;
    }

    .keu-bar.masuk { background: linear-gradient(180deg, #38bdf8, #2563eb); }
    .keu-bar.keluar { background: linear-gradient(180deg, #fbbf24, #f97316); }

    .keu-label { font-size: 11px; color: #64748b; }
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

<div class="card keu-hero mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="badge bg-white bg-opacity-25 mb-3">Keuangan / Akuntansi</div>
                <h1 class="fw-bold mb-1">Dashboard Keuangan</h1>
                <div class="opacity-75">Ringkasan kas, laba rugi, neraca, dan jurnal berdasarkan mapping laporan.</div>
            </div>
            <div class="text-end">
                <div class="opacity-75">Periode</div>
                <div class="fs-5 fw-bold"><?= esc(date('F Y')) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card keu-card h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="keu-icon bg-keu-blue"><i class="bi bi-arrow-down-circle"></i></div>
                <div>
                    <div class="text-muted small">Kas Masuk Bulan Ini</div>
                    <div class="h5 mb-0"><?= keu_uang($kas_masuk) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card keu-card h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="keu-icon bg-keu-orange"><i class="bi bi-arrow-up-circle"></i></div>
                <div>
                    <div class="text-muted small">Kas Keluar Bulan Ini</div>
                    <div class="h5 mb-0"><?= keu_uang($kas_keluar) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card keu-card h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="keu-icon bg-keu-purple"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <div class="text-muted small">Laba/Rugi Bulan Ini</div>
                    <div class="h5 mb-0 <?= $laba_bersih >= 0 ? 'text-success' : 'text-danger' ?>"><?= keu_uang($laba_bersih) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card keu-card h-100">
            <div class="card-body d-flex gap-3 align-items-center">
                <div class="keu-icon bg-keu-dark"><i class="bi bi-journal-check"></i></div>
                <div>
                    <div class="text-muted small">Jurnal Draft</div>
                    <div class="h5 mb-0"><?= keu_angka($jurnal_draft) ?> Jurnal</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card keu-card h-100">
            <div class="card-body">
                <div class="text-muted small">Total Pendapatan</div>
                <div class="h4 mb-1"><?= keu_uang($total_pendapatan) ?></div>
                <a href="<?= esc(admin_page_url('keuangan/laba-rugi')) ?>" class="small text-decoration-none">Selengkapnya</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card keu-card h-100">
            <div class="card-body">
                <div class="text-muted small">Total Aset</div>
                <div class="h4 mb-1"><?= keu_uang($total_aset) ?></div>
                <a href="<?= esc(admin_page_url('keuangan/neraca')) ?>" class="small text-decoration-none">Selengkapnya</a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card keu-card h-100">
            <div class="card-body">
                <div class="text-muted small">Liabilitas + Ekuitas</div>
                <div class="h4 mb-1"><?= keu_uang($total_liabilitas + $total_ekuitas) ?></div>
                <a href="<?= esc(admin_page_url('keuangan/neraca')) ?>" class="small text-decoration-none">Selengkapnya</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <div class="card keu-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                    <div>
                        <h2 class="h5 mb-1">Grafik Arus Kas Tahun <?= esc((string) $tahun_ini) ?></h2>
                        <div class="text-muted small">Akun kas/bank dibaca dari tb_mapping_akun.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('keuangan/arus-kas')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <div class="keu-chart">
                    <div class="keu-chart-grid">
                        <?php foreach ($bulan_labels as $i => $label): ?>
                            <?php
                            $bulan = $i + 1;
                            $masuk = (float) ($chart_masuk[$bulan] ?? 0);
                            $keluar = (float) ($chart_keluar[$bulan] ?? 0);
                            $hMasuk = max(3, ($masuk / $max_chart) * 100);
                            $hKeluar = max(3, ($keluar / $max_chart) * 100);
                            ?>
                            <div class="keu-chart-col">
                                <div class="keu-bars">
                                    <div class="keu-bar masuk" style="height:<?= esc((string) $hMasuk) ?>%" title="Masuk: <?= esc(keu_uang($masuk)) ?>"></div>
                                    <div class="keu-bar keluar" style="height:<?= esc((string) $hKeluar) ?>%" title="Keluar: <?= esc(keu_uang($keluar)) ?>"></div>
                                </div>
                                <div class="keu-label"><?= esc($label) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex gap-3 mt-3 small text-muted flex-wrap">
                    <span><i class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#2563eb;"></i>Kas Masuk</span>
                    <span><i class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#f97316;"></i>Kas Keluar</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card keu-card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h2 class="h5 mb-1">Jurnal Terbaru</h2>
                        <div class="text-muted small">Maksimal 5 jurnal terakhir.</div>
                    </div>
                    <a href="<?= esc(admin_page_url('keuangan/jurnal')) ?>" class="btn btn-sm btn-outline-primary">Selengkapnya</a>
                </div>

                <div class="list-group list-group-flush">
                    <?php if ($jurnal_terbaru->count() === 0): ?>
                        <div class="text-muted py-4 text-center">Belum ada jurnal.</div>
                    <?php else: ?>
                        <?php foreach ($jurnal_terbaru as $j): ?>
                            <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $j->id_jurnal) ?>" class="list-group-item list-group-item-action px-0">
                                <div class="d-flex justify-content-between gap-2">
                                    <div>
                                        <div class="fw-semibold"><?= esc((string) $j->no_jurnal) ?></div>
                                        <div class="text-muted small"><?= esc((string) $j->kode_jenis_transaksi) ?> · <?= esc(keu_tanggal($j->tanggal_jurnal)) ?></div>
                                    </div>
                                    <div class="text-end">
                                        <?= keu_badge_status($j->status_jurnal) ?>
                                        <div class="small text-muted mt-1"><?= keu_uang($j->total_debit) ?></div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>