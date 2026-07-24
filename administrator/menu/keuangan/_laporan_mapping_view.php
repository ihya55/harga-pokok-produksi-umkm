<?php
declare(strict_types=1);

require_once __DIR__ . '/_keuangan_helper.php';

$kode_laporan = $kode_laporan ?? 'LABA_RUGI';
$judul_laporan = $judul_laporan ?? 'Laporan';
$menu_laporan = $menu_laporan ?? 'keuangan/laba-rugi';
$gunakan_periode_awal = $gunakan_periode_awal ?? true;

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));

$rows = keu_laporan_mapping_rows($kode_laporan, $tanggal_awal, $tanggal_akhir);

$params_cetak = $_GET;
$params_cetak['menu'] = $menu_laporan . '/cetak';
$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));

$totalAset = $kode_laporan === 'NERACA' ? keu_laporan_summary($rows, 'total-aset') : 0.0;
$totalLiabilitasEkuitas = $kode_laporan === 'NERACA' ? keu_laporan_summary($rows, 'total-liabilitas-ekuitas') : 0.0;
$selisihNeraca = $totalAset - $totalLiabilitasEkuitas;
$labaBersih = $kode_laporan === 'LABA_RUGI' ? keu_laporan_summary($rows, 'laba-rugi-bersih') : 0.0;
$kasAkhir = $kode_laporan === 'ARUS_KAS' ? keu_laporan_summary($rows, 'kas-akhir') : 0.0;

$renderRows = function (array $items): void {
    $lastKategori = null;
    foreach ($items as $row) {
        if ($lastKategori !== $row['kategori_kode'] && $row['tipe_baris'] === 'DETAIL') {
            echo '<tr class="report-section"><td colspan="2">' . esc($row['kategori_label']) . '</td></tr>';
            $lastKategori = $row['kategori_kode'];
        }

        $class = '';
        if ($row['tipe_baris'] === 'SUBTOTAL') {
            $class = 'report-subtotal';
        } elseif ($row['tipe_baris'] === 'TOTAL') {
            $class = 'report-total';
        }

        $nilaiTampil = (float) $row['nilai'];
        if (in_array((string) $row['kategori_kode'], ['HPP', 'BEBAN_OPERASIONAL', 'TOTAL_BEBAN'], true)) {
            $nilaiTampil = abs($nilaiTampil);
        }

        echo '<tr class="' . esc($class) . '">';
        echo '<td>' . ($row['tipe_baris'] === 'DETAIL' ? '<span class="report-indent"></span>' : '') . esc($row['sub_label']) . '</td>';
        echo '<td class="text-end">' . keu_uang($nilaiTampil) . '</td>';
        echo '</tr>';
    }
};
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title"><?= esc($judul_laporan) ?></h1>
            <p class="page-subtitle">Laporan dihitung dari mapping akun pada Master Setup Mapping Laporan.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc(admin_page_url('master_setup/mapping_laporan')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-sliders me-1"></i>Setup Mapping
            </a>
            <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>

<div class="report-filter-card mb-3">
    <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end">
        <input type="hidden" name="menu" value="<?= esc($menu_laporan) ?>">

        <?php if ($gunakan_periode_awal): ?>
            <div class="col-md-3">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?= esc($tanggal_awal) ?>">
            </div>
        <?php endif; ?>

        <div class="col-md-3">
            <label class="form-label"><?= $gunakan_periode_awal ? 'Tanggal Akhir' : 'Per Tanggal' ?></label>
            <input type="date" name="tanggal_akhir" class="form-control" value="<?= esc($tanggal_akhir) ?>">
        </div>

        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-primary" type="submit">
                <i class="bi bi-search me-1"></i>Tampilkan
            </button>
        </div>
    </form>
</div>

<?php if ($kode_laporan === 'NERACA'): ?>
    <div class="report-summary-grid mb-3">
        <div class="report-summary-card"><span>Total Aset</span><strong><?= keu_uang($totalAset) ?></strong></div>
        <div class="report-summary-card"><span>Total Liabilitas + Ekuitas</span><strong><?= keu_uang($totalLiabilitasEkuitas) ?></strong></div>
        <div class="report-summary-card <?= abs($selisihNeraca) > 0.01 ? 'is-warning' : 'is-ok' ?>"><span>Selisih</span><strong><?= keu_uang($selisihNeraca) ?></strong></div>
    </div>
<?php elseif ($kode_laporan === 'LABA_RUGI'): ?>
    <div class="report-summary-grid one mb-3">
        <div class="report-summary-card"><span>Laba / Rugi Bersih</span><strong><?= keu_uang($labaBersih) ?></strong></div>
    </div>
<?php elseif ($kode_laporan === 'ARUS_KAS'): ?>
    <div class="report-summary-grid one mb-3">
        <div class="report-summary-card"><span>Kas Akhir</span><strong><?= keu_uang($kasAkhir) ?></strong></div>
    </div>
<?php endif; ?>

<div class="report-card">
    <div class="report-card-header">
        <div>
            <h2><?= esc($judul_laporan) ?></h2>
            <p><?= $gunakan_periode_awal ? esc(keu_tanggal($tanggal_awal) . ' s/d ' . keu_tanggal($tanggal_akhir)) : 'Per ' . esc(keu_tanggal($tanggal_akhir)) ?></p>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="text-center text-muted py-5">Mapping laporan belum tersedia.</div>
    <?php elseif ($kode_laporan === 'NERACA'): ?>
        <?php
        $asetRows = array_values(array_filter($rows, fn($r) => ((int) ($r['urutan'] ?? 0)) < 40));
        $pasivaRows = array_values(array_filter($rows, fn($r) => ((int) ($r['urutan'] ?? 0)) >= 40));
        ?>
        <div class="neraca-grid">
            <div class="table-responsive border rounded">
                <table class="table table-hover align-middle mb-0 report-table">
                    <thead><tr><th>Aset</th><th width="190" class="text-end">Nilai</th></tr></thead>
                    <tbody><?php $renderRows($asetRows); ?></tbody>
                </table>
            </div>
            <div class="table-responsive border rounded">
                <table class="table table-hover align-middle mb-0 report-table">
                    <thead><tr><th>Liabilitas dan Ekuitas</th><th width="190" class="text-end">Nilai</th></tr></thead>
                    <tbody><?php $renderRows($pasivaRows); ?></tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0 report-table">
                <thead class="table-light">
                    <tr>
                        <th>Uraian</th>
                        <th width="220" class="text-end">Nilai</th>
                    </tr>
                </thead>
                <tbody><?php $renderRows($rows); ?></tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .report-filter-card,.report-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;box-shadow:0 10px 24px rgba(17,24,39,.05);padding:16px}
    .report-card-header{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:14px;border-bottom:1px solid #eef0f6;padding-bottom:12px}
    .report-card-header h2{margin:0;color:#23235f;font-size:20px;font-weight:800}
    .report-card-header p{margin:4px 0 0;color:#6b7280;font-size:13px}
    .report-summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
    .report-summary-grid.one{grid-template-columns:minmax(260px,420px)}
    .report-summary-card{border:1px solid #e5e7eb;border-radius:14px;background:#fff;padding:14px 16px;box-shadow:0 8px 20px rgba(17,24,39,.04)}
    .report-summary-card span{display:block;color:#6b7280;font-size:12px;margin-bottom:5px}
    .report-summary-card strong{display:block;color:#111827;font-size:18px}
    .report-summary-card.is-warning{border-color:#f59e0b;background:#fffbeb}
    .report-summary-card.is-ok{border-color:#22c55e;background:#f0fdf4}
    .neraca-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .report-table thead th{background:#f8fafc;color:#374151;font-weight:800}
    .report-table td,.report-table th{padding:10px 12px}
    .report-section td{background:#eef2ff!important;color:#23235f;font-weight:800}
    .report-subtotal td{background:#f8fafc!important;font-weight:800;border-top:2px solid #d1d5db}
    .report-total td{background:#111827!important;color:#fff!important;font-weight:800;border-color:#111827}
    .report-indent{display:inline-block;width:18px}
    @media (max-width: 1100px){.neraca-grid,.report-summary-grid{grid-template-columns:1fr}.report-summary-grid.one{grid-template-columns:1fr}}
</style>
