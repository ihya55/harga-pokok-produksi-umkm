<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/helpers_mapping_laporan.php';
require_once __DIR__ . '/../../keuangan/_keuangan_helper.php';

$id_entitas = mapping_laporan_id_entitas();
$q = trim((string) ($_GET['q'] ?? ''));
$tab = strtoupper(trim((string) ($_GET['tab'] ?? 'NERACA')));

$sourceTypes = mapping_laporan_source_types();
$arahOptions = mapping_laporan_arah_saldo_options();
$operatorOptions = mapping_laporan_operator_options();
$kategoriCoaOptions = mapping_laporan_kategori_coa_options();

$coa_options = Capsule::table('tb_coa')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('kode_coa')
    ->get();

$coa_picker_options = $coa_options->map(function ($coa): array {
    return [
        'id_coa' => (int) ($coa->id_coa ?? 0),
        'kode_coa' => (string) ($coa->kode_coa ?? ''),
        'nama_coa' => (string) ($coa->nama_coa ?? ''),
        'kategori_coa' => (string) ($coa->kategori_coa ?? ''),
        'posisi_saldo_normal' => (string) ($coa->posisi_saldo_normal ?? ''),
        'boleh_transaksi' => (int) ($coa->boleh_transaksi ?? 0),
    ];
})->values()->all();

$report_codes = Capsule::table('tb_mapping_laporan')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->select('kode_laporan')
    ->distinct()
    ->orderBy('kode_laporan')
    ->pluck('kode_laporan')
    ->all();

if (empty($report_codes)) {
    $report_codes = ['NERACA'];
}

if ($tab !== 'SEMUA' && !in_array($tab, $report_codes, true)) {
    $tab = in_array('NERACA', $report_codes, true) ? 'NERACA' : (string) $report_codes[0];
}

$rowsQuery = Capsule::table('tb_mapping_laporan')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1);

if ($tab !== 'SEMUA') {
    $rowsQuery->where('kode_laporan', $tab);
}

$rows_all = $rowsQuery
    ->orderBy('kode_laporan')
    ->orderBy('urutan')
    ->orderBy('sub_urutan')
    ->orderBy('id_mapping_laporan')
    ->get();

$mappingIds = $rows_all->pluck('id_mapping_laporan')->map(fn($id) => (int) $id)->all();

$details = collect();
if (!empty($mappingIds)) {
    $details = Capsule::table('tb_mapping_laporan_detail as d')
        ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'd.id_coa')
        ->where('d.id_entitas', $id_entitas)
        ->whereIn('d.id_mapping_laporan', $mappingIds)
        ->select([
            'd.*',
            'c.kode_coa',
            'c.nama_coa',
            'c.kategori_coa',
        ])
        ->orderBy('d.id_mapping_laporan')
        ->orderBy('d.id_mapping_laporan_detail')
        ->get()
        ->groupBy('id_mapping_laporan');
}

if ($q !== '') {
    $rows_all = $rows_all->filter(function ($row) use ($q, $details): bool {
        $text = strtolower(implode(' ', [
            (string) ($row->kode_laporan ?? ''),
            (string) ($row->nama_laporan ?? ''),
            (string) ($row->kategori_kode ?? ''),
            (string) ($row->kategori_label ?? ''),
            (string) ($row->sub_kode ?? ''),
            (string) ($row->sub_label ?? ''),
            (string) ($row->tipe_baris ?? ''),
            (string) ($row->rumus_total ?? ''),
        ]));

        foreach (($details[(int) $row->id_mapping_laporan] ?? []) as $detail) {
            $text .= ' ' . strtolower(implode(' ', [
                (string) ($detail->tipe_sumber ?? ''),
                (string) ($detail->nilai_sumber ?? ''),
                (string) ($detail->kode_coa ?? ''),
                (string) ($detail->nama_coa ?? ''),
                (string) ($detail->keterangan ?? ''),
            ]));
        }

        return str_contains($text, strtolower($q));
    });
}

$reportCounts = ['SEMUA' => Capsule::table('tb_mapping_laporan')->where('id_entitas', $id_entitas)->where('status_aktif', 1)->count()];
foreach ($report_codes as $code) {
    $reportCounts[$code] = Capsule::table('tb_mapping_laporan')
        ->where('id_entitas', $id_entitas)
        ->where('kode_laporan', $code)
        ->where('status_aktif', 1)
        ->count();
}

$previewTanggalAwal = date('Y-m-01');
$previewTanggalAkhir = date('Y-m-t');
$neracaPreview = [];
if ($tab === 'NERACA' || $tab === 'SEMUA') {
    try {
        $neracaRows = keu_laporan_mapping_rows('NERACA', $previewTanggalAwal, $previewTanggalAkhir);
        $neracaPreview = [
            'total_aset' => keu_laporan_summary($neracaRows, 'total-aset'),
            'total_liabilitas_ekuitas' => keu_laporan_summary($neracaRows, 'total-liabilitas-ekuitas'),
        ];
        $neracaPreview['selisih'] = $neracaPreview['total_aset'] - $neracaPreview['total_liabilitas_ekuitas'];
    } catch (Throwable $e) {
        $neracaPreview = [];
    }
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Mapping Laporan</h1>
    <p class="page-subtitle">Setup sumber akun untuk laporan keuangan. Contoh: baris <strong>Kas dan Bank</strong> pada Neraca dapat diisi prefix <code>111</code> untuk kas dan <code>112</code> untuk semua akun bank seperti Bank BNI/BCA.</p>
</div>

<div class="accurate-setting-wrap mapping-laporan-wrap">
    <aside class="accurate-setting-nav">
        <div class="nav-title"><i class="bi bi-sliders me-1"></i>Preference</div>
        <a href="<?= esc(mapping_laporan_nav_url('SEMUA', $q)) ?>" class="<?= $tab === 'SEMUA' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i><span>Semua Laporan</span><span class="count-badge"><?= (int) ($reportCounts['SEMUA'] ?? 0) ?></span>
        </a>
        <?php foreach ($report_codes as $code): ?>
            <a href="<?= esc(mapping_laporan_nav_url((string) $code, $q)) ?>" class="<?= $tab === (string) $code ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span><?= esc(mapping_laporan_report_label((string) $code)) ?></span>
                <span class="count-badge"><?= (int) ($reportCounts[$code] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
    </aside>

    <section class="accurate-setting-panel">
        <form method="post" action="<?= esc(admin_url('menu/master_setup/mapping_laporan/update.php')) ?>">
            <input type="hidden" name="tab" value="<?= esc($tab) ?>">
            <input type="hidden" name="q" value="<?= esc($q) ?>">

            <div class="accurate-panel-header">
                <div>
                    <h2 class="accurate-panel-title">Report Account Mapping For</h2>
                    <p class="accurate-panel-subtitle">Atur akun/prefix/kategori yang masuk ke setiap baris laporan.</p>
                </div>
                <div class="accurate-toolbar">
                    <input type="text" id="settingLiveSearch" class="form-control" placeholder="Cari laporan / akun / prefix..." value="<?= esc($q) ?>">
                    <a href="<?= esc(admin_page_url('master_setup/mapping_laporan')) ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>

            <?php if (!empty($neracaPreview)): ?>
                <div class="mapping-preview-grid">
                    <div class="mapping-preview-card">
                        <span>Total Aset</span>
                        <strong><?= keu_uang($neracaPreview['total_aset']) ?></strong>
                    </div>
                    <div class="mapping-preview-card">
                        <span>Total Liabilitas + Ekuitas</span>
                        <strong><?= keu_uang($neracaPreview['total_liabilitas_ekuitas']) ?></strong>
                    </div>
                    <div class="mapping-preview-card <?= abs((float) $neracaPreview['selisih']) > 0.01 ? 'is-warning' : 'is-ok' ?>">
                        <span>Selisih Neraca</span>
                        <strong><?= keu_uang($neracaPreview['selisih']) ?></strong>
                    </div>
                </div>
            <?php endif; ?>

            <div class="accurate-setting-body">
                <div class="currency-row">
                    <label>Currency Name:</label>
                    <input type="text" class="form-control form-control-sm" value="IDR" readonly>
                </div>

                <div class="default-title">Default Report Mapping For:</div>

                <datalist id="coaListMappingLaporan">
                    <?php foreach ($coa_options as $coa): ?>
                        <option value="<?= esc($coa->kode_coa) ?>"><?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?></option>
                        <option value="<?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <datalist id="kategoriCoaList">
                    <?php foreach ($kategoriCoaOptions as $value => $label): ?>
                        <option value="<?= esc($value) ?>"><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </datalist>

                <?php if ($rows_all->count() > 0): ?>
                    <div class="setting-list-box mapping-report-list">
                        <?php foreach ($rows_all as $row): ?>
                            <?php
                            $rowDetails = $details[(int) $row->id_mapping_laporan] ?? collect();
                            $isDetail = (string) $row->tipe_baris === 'DETAIL';
                            $searchText = trim(implode(' ', [
                                (string) $row->kode_laporan,
                                (string) $row->kategori_label,
                                (string) $row->sub_label,
                                (string) $row->tipe_baris,
                                (string) $row->rumus_total,
                            ]));
                            ?>
                            <div class="setting-row mapping-report-row" data-search="<?= esc($searchText) ?>">
                                <div class="mapping-line-head">
                                    <div class="setting-label">
                                        <strong><?= esc(mapping_laporan_report_label($row->kode_laporan) . ' - ' . $row->sub_label) ?></strong>
                                        <span><?= esc($row->kategori_label . ' | ' . $row->tipe_baris . ' | ' . $row->sub_kode) ?></span>
                                    </div>
                                    <div class="mapping-line-meta">
                                        <span class="badge text-bg-light border">Urutan <?= (int) $row->urutan ?>.<?= (int) $row->sub_urutan ?></span>
                                        <?php if (!$isDetail): ?>
                                            <span class="badge text-bg-secondary"><?= esc($row->rumus_total ?: '-') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <input type="hidden" name="mapping[<?= (int) $row->id_mapping_laporan ?>][id]" value="<?= (int) $row->id_mapping_laporan ?>">
                                <div class="mapping-line-edit">
                                    <div>
                                        <label>Label Kategori</label>
                                        <input type="text" name="mapping[<?= (int) $row->id_mapping_laporan ?>][kategori_label]" class="form-control form-control-sm" value="<?= esc($row->kategori_label) ?>">
                                    </div>
                                    <div>
                                        <label>Label Baris</label>
                                        <input type="text" name="mapping[<?= (int) $row->id_mapping_laporan ?>][sub_label]" class="form-control form-control-sm" value="<?= esc($row->sub_label) ?>">
                                    </div>
                                    <div>
                                        <label>Rumus / Catatan</label>
                                        <input type="text" name="mapping[<?= (int) $row->id_mapping_laporan ?>][rumus_total]" class="form-control form-control-sm" value="<?= esc($row->rumus_total ?? '') ?>" <?= $isDetail ? 'readonly' : '' ?>>
                                    </div>
                                </div>

                                <?php if ($isDetail): ?>
                                    <div class="mapping-detail-table">
                                        <div class="mapping-detail-header">
                                            <span>Tipe Sumber</span>
                                            <span>Nilai / Kode</span>
                                            <span>Arah</span>
                                            <span>Operator</span>
                                            <span>Keterangan</span>
                                            <span>Status</span>
                                        </div>

                                        <?php if ($rowDetails->count() === 0): ?>
                                            <div class="mapping-empty-line">Belum ada sumber akun untuk baris ini.</div>
                                        <?php endif; ?>

                                        <?php foreach ($rowDetails as $detail): ?>
                                            <div class="mapping-detail-row">
                                                <select name="detail[<?= (int) $detail->id_mapping_laporan_detail ?>][tipe_sumber]" class="form-select form-select-sm mapping-source-type">
                                                    <?php foreach ($sourceTypes as $value => $label): ?>
                                                        <option value="<?= esc($value) ?>" <?= (string) $detail->tipe_sumber === (string) $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="mapping-source-input-wrap">
                                                    <input type="text" name="detail[<?= (int) $detail->id_mapping_laporan_detail ?>][nilai_sumber]" class="form-control form-control-sm mapping-source-value" list="coaListMappingLaporan" value="<?= esc(mapping_laporan_source_display($detail)) ?>" placeholder="Kode COA / prefix / kategori">
                                                    <button type="button" class="btn btn-outline-primary btn-sm mapping-coa-picker-btn" title="Pilih dari daftar COA">
                                                        <i class="bi bi-search"></i>
                                                    </button>
                                                </div>
                                                <select name="detail[<?= (int) $detail->id_mapping_laporan_detail ?>][arah_saldo]" class="form-select form-select-sm">
                                                    <?php foreach ($arahOptions as $value => $label): ?>
                                                        <option value="<?= esc($value) ?>" <?= (string) $detail->arah_saldo === (string) $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select name="detail[<?= (int) $detail->id_mapping_laporan_detail ?>][operator_nilai]" class="form-select form-select-sm">
                                                    <?php foreach ($operatorOptions as $value => $label): ?>
                                                        <option value="<?= esc($value) ?>" <?= (string) $detail->operator_nilai === (string) $value ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="detail[<?= (int) $detail->id_mapping_laporan_detail ?>][keterangan]" class="form-control form-control-sm" value="<?= esc($detail->keterangan ?? '') ?>" placeholder="Keterangan">
                                                <div class="mapping-status-cell">
                                                    <label class="form-check small mb-0">
                                                        <input class="form-check-input" type="checkbox" name="detail[<?= (int) $detail->id_mapping_laporan_detail ?>][status_aktif]" value="1" <?= (int) $detail->status_aktif === 1 ? 'checked' : '' ?>> Aktif
                                                    </label>
                                                    <label class="form-check small text-danger mb-0">
                                                        <input class="form-check-input" type="checkbox" name="detail[<?= (int) $detail->id_mapping_laporan_detail ?>][hapus]" value="1"> Hapus
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                        <div class="mapping-new-rows" data-mapping-id="<?= (int) $row->id_mapping_laporan ?>"></div>

                                        <div class="mapping-add-row-bar">
                                            <button type="button" class="btn btn-outline-primary btn-sm mapping-add-detail-btn" data-mapping-id="<?= (int) $row->id_mapping_laporan ?>">
                                                <i class="bi bi-plus-lg me-1"></i>Baris Baru
                                            </button>
                                            <span class="small text-muted">Gunakan tombol ini untuk menambah lebih dari satu COA/prefix pada baris laporan.</span>
                                        </div>

                                        <template class="mapping-new-row-template" data-mapping-id="<?= (int) $row->id_mapping_laporan ?>">
                                            <div class="mapping-detail-row mapping-new-row">
                                                <select name="new_detail[<?= (int) $row->id_mapping_laporan ?>][__ROW_KEY__][tipe_sumber]" class="form-select form-select-sm mapping-source-type">
                                                    <?php foreach ($sourceTypes as $value => $label): ?>
                                                        <option value="<?= esc($value) ?>" <?= $value === 'COA' ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <div class="mapping-source-input-wrap">
                                                    <input type="text" name="new_detail[<?= (int) $row->id_mapping_laporan ?>][__ROW_KEY__][nilai_sumber]" class="form-control form-control-sm mapping-source-value" list="coaListMappingLaporan" value="" placeholder="Tambah kode COA/prefix baru">
                                                    <button type="button" class="btn btn-outline-primary btn-sm mapping-coa-picker-btn" title="Pilih dari daftar COA">
                                                        <i class="bi bi-search"></i>
                                                    </button>
                                                </div>
                                                <select name="new_detail[<?= (int) $row->id_mapping_laporan ?>][__ROW_KEY__][arah_saldo]" class="form-select form-select-sm">
                                                    <?php foreach ($arahOptions as $value => $label): ?>
                                                        <option value="<?= esc($value) ?>" <?= $value === 'DEBIT_MINUS_KREDIT' ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <select name="new_detail[<?= (int) $row->id_mapping_laporan ?>][__ROW_KEY__][operator_nilai]" class="form-select form-select-sm">
                                                    <?php foreach ($operatorOptions as $value => $label): ?>
                                                        <option value="<?= esc($value) ?>" <?= $value === 'TAMBAH' ? 'selected' : '' ?>><?= esc($label) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="new_detail[<?= (int) $row->id_mapping_laporan ?>][__ROW_KEY__][keterangan]" class="form-control form-control-sm" value="" placeholder="Keterangan baru">
                                                <div class="mapping-status-cell">
                                                    <button type="button" class="btn btn-outline-danger btn-sm mapping-remove-new-row">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <span class="small text-muted">Baris baru</span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                <?php else: ?>
                                    <div class="mapping-formula-note">Baris ini dihitung dari rumus/subtotal. Sumber akun hanya diatur pada baris DETAIL.</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="setting-empty">Tidak ada mapping laporan yang sesuai filter.</div>
                <?php endif; ?>
            </div>

            <div class="setting-footer">
                <span class="me-auto small text-muted">Total ditampilkan: <?= (int) $rows_all->count() ?> baris mapping</span>
                <a href="<?= esc(admin_page_url('master_setup/mapping_laporan')) ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancel</a>
                <button type="submit" class="btn btn-gradient"><i class="bi bi-check-lg me-1"></i>OK</button>
            </div>
        </form>
    </section>
</div>

<div class="mapping-coa-modal" id="mappingCoaModal" aria-hidden="true">
    <div class="mapping-coa-modal-backdrop" data-coa-close></div>
    <div class="mapping-coa-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="mappingCoaModalTitle">
        <div class="mapping-coa-modal-header">
            <div>
                <h3 id="mappingCoaModalTitle">Pilih Kode COA</h3>
                <p>Klik akun untuk mengisi kode akun pada baris mapping.</p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-coa-close>
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="mapping-coa-modal-search">
            <input type="text" id="mappingCoaSearch" class="form-control" placeholder="Cari kode COA, nama akun, kategori, atau saldo normal...">
            <button type="button" class="btn btn-outline-secondary" id="mappingCoaClearSearch">Reset</button>
        </div>
        <div class="mapping-coa-modal-table">
            <table>
                <thead>
                    <tr>
                        <th style="width: 120px;">Kode</th>
                        <th>Nama Akun</th>
                        <th style="width: 130px;">Kategori</th>
                        <th style="width: 110px;">Normal</th>
                        <th style="width: 120px;">Transaksi</th>
                    </tr>
                </thead>
                <tbody id="mappingCoaTableBody"></tbody>
            </table>
        </div>
        <div class="mapping-coa-modal-footer">
            <span id="mappingCoaResultInfo" class="small text-muted"></span>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-coa-close>Tutup</button>
        </div>
    </div>
</div>

<style>
    .mapping-laporan-wrap,
    .accurate-setting-wrap {
        display: grid;
        grid-template-columns: 245px minmax(0, 1fr);
        gap: 16px;
        align-items: start;
    }
    .accurate-setting-nav {
        border: 1px solid #d6d8e7;
        background: #f7f7fb;
        min-height: 560px;
        border-radius: 10px;
        overflow: hidden;
    }
    .accurate-setting-nav .nav-title {
        padding: 13px 15px;
        font-weight: 700;
        color: #23235f;
        border-bottom: 1px solid #dfe1ee;
        background: #ffffff;
    }
    .accurate-setting-nav a {
        display: flex;
        gap: 9px;
        align-items: center;
        padding: 10px 13px;
        color: #303044;
        text-decoration: none;
        border-bottom: 1px solid rgba(0,0,0,.04);
        font-size: 13px;
    }
    .accurate-setting-nav a.active,
    .accurate-setting-nav a:hover {
        background: #3154d8;
        color: #ffffff;
    }
    .accurate-setting-nav .count-badge {
        margin-left: auto;
        border-radius: 999px;
        padding: 1px 7px;
        background: rgba(0,0,0,.08);
        font-size: 11px;
    }
    .accurate-setting-nav a.active .count-badge,
    .accurate-setting-nav a:hover .count-badge { background: rgba(255,255,255,.24); }
    .accurate-setting-panel {
        border: 1px solid #d6d8e7;
        border-radius: 10px;
        background: #ffffff;
        overflow: hidden;
        box-shadow: 0 10px 24px rgba(17,24,39,.06);
    }
    .accurate-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        padding: 16px 18px;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(180deg, #ffffff, #f8f9ff);
    }
    .accurate-panel-title { margin: 0; font-size: 18px; font-weight: 800; color: #23235f; }
    .accurate-panel-subtitle { margin: 3px 0 0; color: #6b7280; font-size: 12px; }
    .accurate-toolbar { display: flex; gap: 8px; align-items: center; min-width: 430px; }
    .accurate-toolbar .form-control { min-width: 280px; }
    .accurate-setting-body { padding: 16px 18px; background: #fbfbfe; }
    .currency-row {
        display: grid;
        grid-template-columns: 150px 220px;
        gap: 10px;
        align-items: center;
        margin-bottom: 12px;
        font-size: 13px;
    }
    .currency-row label { text-align: right; font-weight: 600; color: #374151; }
    .default-title { font-weight: 800; margin: 6px 0 10px 150px; color: #111827; }
    .setting-list-box { border: 1px solid #e5e7eb; border-radius: 10px; background: #ffffff; overflow: hidden; }
    .setting-row { padding: 13px 14px; border-bottom: 1px solid #eef0f6; }
    .setting-row:last-child { border-bottom: 0; }
    .mapping-line-head { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; margin-bottom:10px; }
    .setting-label strong { display:block; font-size:14px; color:#111827; }
    .setting-label span { display:block; font-size:12px; color:#6b7280; margin-top:2px; }
    .mapping-line-meta { display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
    .mapping-line-edit { display:grid; grid-template-columns: 1fr 1fr 1fr; gap:10px; margin-bottom:10px; }
    .mapping-line-edit label { font-size:11px; color:#6b7280; font-weight:700; margin-bottom:3px; }
    .mapping-detail-table { border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; background:#fff; }
    .mapping-detail-header,
    .mapping-detail-row { display:grid; grid-template-columns: 160px 1.25fr 150px 105px 1fr 120px; gap:8px; align-items:center; }
    .mapping-detail-header { background:#f3f4f6; padding:8px 10px; font-size:11px; font-weight:800; color:#4b5563; text-transform:uppercase; letter-spacing:.02em; }
    .mapping-detail-row { padding:8px 10px; border-top:1px solid #eef0f6; }
    .mapping-new-row { background:#fbfdff; }
    .mapping-status-cell { display:flex; flex-direction:column; gap:2px; }
    .mapping-source-input-wrap { display:flex; gap:6px; align-items:center; min-width:0; }
    .mapping-source-input-wrap .form-control { min-width:0; }
    .mapping-source-input-wrap .mapping-coa-picker-btn { flex:0 0 auto; min-width:34px; }
    .mapping-source-input-wrap.is-non-coa .mapping-coa-picker-btn { opacity:.45; pointer-events:none; }
    .mapping-add-row-bar { display:flex; gap:10px; align-items:center; padding:9px 10px; border-top:1px dashed #dbe2ef; background:#f8fbff; }
    .mapping-new-rows:empty { display:none; }
    .mapping-coa-modal { display:none; position:fixed; inset:0; z-index:2050; }
    .mapping-coa-modal.is-open { display:block; }
    .mapping-coa-modal-backdrop { position:absolute; inset:0; background:rgba(15,23,42,.45); }
    .mapping-coa-modal-dialog { position:relative; width:min(920px, calc(100vw - 32px)); max-height:calc(100vh - 54px); margin:27px auto; background:#fff; border-radius:12px; box-shadow:0 24px 70px rgba(15,23,42,.34); overflow:hidden; display:flex; flex-direction:column; }
    .mapping-coa-modal-header { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; padding:15px 18px; border-bottom:1px solid #e5e7eb; background:linear-gradient(180deg,#fff,#f8f9ff); }
    .mapping-coa-modal-header h3 { margin:0; font-size:17px; font-weight:800; color:#23235f; }
    .mapping-coa-modal-header p { margin:3px 0 0; color:#6b7280; font-size:12px; }
    .mapping-coa-modal-search { display:flex; gap:8px; padding:12px 18px; border-bottom:1px solid #eef0f6; background:#fbfbfe; }
    .mapping-coa-modal-table { overflow:auto; min-height:260px; max-height:55vh; }
    .mapping-coa-modal-table table { width:100%; border-collapse:collapse; font-size:13px; }
    .mapping-coa-modal-table th { position:sticky; top:0; z-index:1; background:#f3f4f6; color:#4b5563; font-size:11px; text-transform:uppercase; letter-spacing:.02em; padding:9px 10px; border-bottom:1px solid #e5e7eb; }
    .mapping-coa-modal-table td { padding:9px 10px; border-bottom:1px solid #eef0f6; vertical-align:top; }
    .mapping-coa-modal-table tr { cursor:pointer; }
    .mapping-coa-modal-table tr:hover { background:#eef4ff; }
    .mapping-coa-code { font-weight:800; color:#1f3eb3; }
    .mapping-coa-name { font-weight:700; color:#111827; }
    .mapping-coa-muted { color:#6b7280; font-size:12px; }
    .mapping-coa-modal-footer { display:flex; justify-content:space-between; gap:12px; align-items:center; padding:10px 18px; border-top:1px solid #e5e7eb; background:#fff; }
    .mapping-empty-line { padding:14px 10px; color:#6b7280; font-size:12px; border-top:1px solid #eef0f6; }
    .mapping-formula-note { border:1px dashed #cbd5e1; color:#64748b; border-radius:8px; padding:10px 12px; background:#f8fafc; font-size:12px; }
    .mapping-preview-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:10px; padding:14px 18px; border-bottom:1px solid #e5e7eb; background:#fafbff; }
    .mapping-preview-card { border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px; background:#fff; }
    .mapping-preview-card span { display:block; font-size:12px; color:#6b7280; }
    .mapping-preview-card strong { display:block; margin-top:4px; font-size:16px; color:#111827; }
    .mapping-preview-card.is-warning { border-color:#f59e0b; background:#fffbeb; }
    .mapping-preview-card.is-ok { border-color:#22c55e; background:#f0fdf4; }
    .setting-footer { display:flex; align-items:center; gap:10px; padding:13px 18px; border-top:1px solid #e5e7eb; background:#fff; }
    .btn-gradient { background:linear-gradient(135deg,#3154d8,#6236d9); color:#fff; border:0; }
    .btn-gradient:hover { color:#fff; filter:brightness(.95); }
    .setting-empty { padding:30px; text-align:center; color:#6b7280; background:#fff; border:1px dashed #cbd5e1; border-radius:10px; }
    @media (max-width: 1100px) {
        .mapping-laporan-wrap,.accurate-setting-wrap { grid-template-columns:1fr; }
        .accurate-setting-nav { min-height:0; }
        .accurate-toolbar { min-width:0; width:100%; }
        .accurate-panel-header { flex-direction:column; }
        .mapping-preview-grid { grid-template-columns:1fr; }
        .mapping-line-edit { grid-template-columns:1fr; }
        .mapping-detail-header { display:none; }
        .mapping-detail-row { grid-template-columns:1fr; }
    }
</style>

<script>
(function(){
    const coaOptions = <?= json_encode($coa_picker_options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const search = document.getElementById('settingLiveSearch');
    const rows = document.querySelectorAll('.mapping-report-row');
    let timer = null;

    if (search) {
        search.addEventListener('input', function(){
            const term = (this.value || '').toLowerCase();
            rows.forEach(function(row){
                const text = (row.dataset.search || row.textContent || '').toLowerCase();
                row.style.display = text.indexOf(term) >= 0 ? '' : 'none';
            });
            clearTimeout(timer);
            timer = setTimeout(function(){
                const url = new URL(window.location.href);
                if (search.value.trim() === '') { url.searchParams.delete('q'); }
                else { url.searchParams.set('q', search.value.trim()); }
                history.replaceState(null, '', url.toString());
            }, 250);
        });
    }

    function refreshSourceInput(select) {
        const row = select.closest('.mapping-detail-row');
        if (!row) return;
        const input = row.querySelector('.mapping-source-value');
        const wrap = row.querySelector('.mapping-source-input-wrap');
        if (!input) return;

        if (select.value === 'KATEGORI_COA') {
            input.setAttribute('list', 'kategoriCoaList');
            input.placeholder = 'aset / liabilitas / ekuitas / pendapatan / hpp / beban';
            if (wrap) wrap.classList.add('is-non-coa');
        } else if (select.value === 'COA') {
            input.setAttribute('list', 'coaListMappingLaporan');
            input.placeholder = 'Kode COA, contoh 1121';
            if (wrap) wrap.classList.remove('is-non-coa');
        } else if (select.value === 'KODE_COA_PREFIX') {
            input.removeAttribute('list');
            input.placeholder = 'Prefix, contoh 112 untuk semua bank';
            if (wrap) wrap.classList.add('is-non-coa');
        } else {
            input.removeAttribute('list');
            input.placeholder = 'Nilai sumber';
            if (wrap) wrap.classList.add('is-non-coa');
        }
    }

    function bindSourceType(select) {
        if (!select || select.dataset.boundSourceType === '1') return;
        select.dataset.boundSourceType = '1';
        select.addEventListener('change', function(){ refreshSourceInput(this); });
        refreshSourceInput(select);
    }

    document.querySelectorAll('.mapping-source-type').forEach(bindSourceType);

    document.querySelectorAll('.mapping-add-detail-btn').forEach(function(button){
        button.addEventListener('click', function(){
            const mappingId = this.dataset.mappingId;
            const table = this.closest('.mapping-detail-table');
            if (!table) return;
            const container = table.querySelector('.mapping-new-rows[data-mapping-id="' + mappingId + '"]');
            const template = table.querySelector('.mapping-new-row-template[data-mapping-id="' + mappingId + '"]');
            if (!container || !template) return;

            const key = 'n' + Date.now() + '_' + Math.floor(Math.random() * 100000);
            const html = template.innerHTML.replaceAll('__ROW_KEY__', key);
            const holder = document.createElement('div');
            holder.innerHTML = html.trim();
            const newRow = holder.firstElementChild;
            container.appendChild(newRow);

            newRow.querySelectorAll('.mapping-source-type').forEach(bindSourceType);
            const input = newRow.querySelector('.mapping-source-value');
            if (input) input.focus();
        });
    });

    document.addEventListener('click', function(event){
        const removeBtn = event.target.closest('.mapping-remove-new-row');
        if (removeBtn) {
            const row = removeBtn.closest('.mapping-new-row');
            if (row) row.remove();
            return;
        }

        const pickBtn = event.target.closest('.mapping-coa-picker-btn');
        if (pickBtn) {
            const row = pickBtn.closest('.mapping-detail-row');
            if (!row) return;
            const sourceType = row.querySelector('.mapping-source-type');
            const input = row.querySelector('.mapping-source-value');
            if (!input) return;
            if (sourceType && sourceType.value !== 'COA') {
                sourceType.value = 'COA';
                refreshSourceInput(sourceType);
            }
            openCoaModal(input);
        }
    });

    const modal = document.getElementById('mappingCoaModal');
    const modalSearch = document.getElementById('mappingCoaSearch');
    const modalClear = document.getElementById('mappingCoaClearSearch');
    const modalBody = document.getElementById('mappingCoaTableBody');
    const modalInfo = document.getElementById('mappingCoaResultInfo');
    let activeCoaInput = null;

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function coaText(coa) {
        return [coa.kode_coa, coa.nama_coa, coa.kategori_coa, coa.posisi_saldo_normal, coa.boleh_transaksi ? 'transaksi' : 'header'].join(' ').toLowerCase();
    }

    function renderCoaRows(term) {
        if (!modalBody) return;
        const keyword = (term || '').trim().toLowerCase();
        const filtered = coaOptions.filter(function(coa){
            return keyword === '' || coaText(coa).indexOf(keyword) >= 0;
        });
        const limited = filtered.slice(0, 250);

        if (limited.length === 0) {
            modalBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">COA tidak ditemukan.</td></tr>';
        } else {
            modalBody.innerHTML = limited.map(function(coa){
                const transaksiLabel = Number(coa.boleh_transaksi) === 1 ? 'Ya' : 'Header';
                return '<tr data-kode="' + escapeHtml(coa.kode_coa) + '">' +
                    '<td><span class="mapping-coa-code">' + escapeHtml(coa.kode_coa) + '</span></td>' +
                    '<td><div class="mapping-coa-name">' + escapeHtml(coa.nama_coa) + '</div><div class="mapping-coa-muted">ID COA: ' + escapeHtml(coa.id_coa) + '</div></td>' +
                    '<td>' + escapeHtml(coa.kategori_coa) + '</td>' +
                    '<td>' + escapeHtml(coa.posisi_saldo_normal) + '</td>' +
                    '<td>' + escapeHtml(transaksiLabel) + '</td>' +
                    '</tr>';
            }).join('');
        }

        if (modalInfo) {
            modalInfo.textContent = filtered.length + ' COA ditemukan' + (filtered.length > limited.length ? ' (menampilkan 250 pertama)' : '');
        }
    }

    function openCoaModal(input) {
        activeCoaInput = input;
        if (!modal) return;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        if (modalSearch) {
            modalSearch.value = input.value || '';
            renderCoaRows(modalSearch.value);
            setTimeout(function(){ modalSearch.focus(); modalSearch.select(); }, 50);
        } else {
            renderCoaRows('');
        }
    }

    function closeCoaModal() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        activeCoaInput = null;
    }

    if (modalSearch) {
        modalSearch.addEventListener('input', function(){ renderCoaRows(this.value); });
        modalSearch.addEventListener('keydown', function(event){
            if (event.key !== 'Enter') return;
            event.preventDefault();
            const first = modalBody ? modalBody.querySelector('tr[data-kode]') : null;
            if (first) first.click();
        });
    }

    if (modalClear) {
        modalClear.addEventListener('click', function(){
            if (!modalSearch) return;
            modalSearch.value = '';
            renderCoaRows('');
            modalSearch.focus();
        });
    }

    if (modalBody) {
        modalBody.addEventListener('click', function(event){
            const row = event.target.closest('tr[data-kode]');
            if (!row || !activeCoaInput) return;
            activeCoaInput.value = row.dataset.kode || '';
            activeCoaInput.dispatchEvent(new Event('input', { bubbles: true }));
            activeCoaInput.focus();
            closeCoaModal();
        });
    }

    document.querySelectorAll('[data-coa-close]').forEach(function(button){
        button.addEventListener('click', closeCoaModal);
    });

    document.addEventListener('keydown', function(event){
        if (event.key === 'Escape' && modal && modal.classList.contains('is-open')) {
            closeCoaModal();
        }
    });
})();
</script>
