<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$q = trim((string) ($_GET['q'] ?? ''));
$tab = trim((string) ($_GET['tab'] ?? 'semua'));
$id_entitas = (int) (user_login()['id_entitas'] ?? 0);

if (!function_exists('setting_akun_template_group')) {
    function setting_akun_template_group(?string $kode): string
    {
        $kode = strtoupper((string) $kode);
        if (str_contains($kode, 'PENJUALAN') || str_contains($kode, 'PENYERAHAN')) {
            return 'Penjualan';
        }
        if (str_contains($kode, 'PEMBELIAN') || str_contains($kode, 'UTANG')) {
            return 'Pembelian';
        }
        if (str_contains($kode, 'PRODUKSI') || str_contains($kode, 'PEMAKAIAN') || str_contains($kode, 'HASIL_PRODUKSI')) {
            return 'Produksi';
        }
        if (str_contains($kode, 'STOK') || str_contains($kode, 'SALDO_AWAL')) {
            return 'Persediaan';
        }
        if (str_contains($kode, 'KAS') || str_contains($kode, 'BANK') || str_contains($kode, 'SETOR') || str_contains($kode, 'TARIK')) {
            return 'Kas dan Bank';
        }
        if (str_contains($kode, 'BEBAN') || str_contains($kode, 'PENYUSUTAN')) {
            return 'Beban dan Penyusutan';
        }
        return 'Lainnya';
    }
}

if (!function_exists('setting_akun_nav_url')) {
    function setting_akun_nav_url(string $menu, string $tab, string $q = ''): string
    {
        $params = ['menu' => $menu, 'tab' => $tab];
        if ($q !== '') {
            $params['q'] = $q;
        }
        return admin_url('index.php?' . http_build_query($params));
    }
}

$coa_options = Capsule::table('tb_coa')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->where('boleh_transaksi', 1)
    ->orderBy('kode_coa')
    ->get();

$rows_all = Capsule::table('tb_template_jurnal_detail as d')
    ->join('tb_template_jurnal as t', 't.id_template_jurnal', '=', 'd.id_template_jurnal')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'd.id_coa_default')
    ->where('t.id_entitas', $id_entitas)
    ->where('t.status_aktif', 1)
    ->select([
        'd.*',
        't.kode_template_jurnal',
        't.nama_template_jurnal',
        't.kode_jenis_transaksi',
        't.keterangan as keterangan_template',
        'c.kode_coa',
        'c.nama_coa',
        'c.kategori_coa',
    ])
    ->orderBy('t.kode_jenis_transaksi')
    ->orderBy('t.kode_template_jurnal')
    ->orderBy('d.urutan')
    ->get();

$groups = ['semua' => ['label' => 'Semua Account', 'count' => $rows_all->count()]];
foreach ($rows_all as $row) {
    $group = setting_akun_template_group($row->kode_jenis_transaksi ?? '');
    if (!isset($groups[$group])) {
        $groups[$group] = ['label' => $group, 'count' => 0];
    }
    $groups[$group]['count']++;
}

if ($tab !== 'semua' && !isset($groups[$tab])) {
    $tab = 'semua';
}

$rows = $rows_all->filter(function ($row) use ($tab, $q) {
    if ($tab !== 'semua' && setting_akun_template_group($row->kode_jenis_transaksi ?? '') !== $tab) {
        return false;
    }
    if ($q === '') {
        return true;
    }
    $haystack = strtolower(implode(' ', [
        (string) ($row->kode_template_jurnal ?? ''),
        (string) ($row->nama_template_jurnal ?? ''),
        (string) ($row->kode_jenis_transaksi ?? ''),
        (string) ($row->posisi_dc ?? ''),
        (string) ($row->sumber_nominal ?? ''),
        (string) ($row->keterangan_baris ?? ''),
        (string) ($row->kode_coa ?? ''),
        (string) ($row->nama_coa ?? ''),
    ]));
    return str_contains($haystack, strtolower($q));
});
?>

<div class="page-header mb-4">
    <h1 class="page-title">Template Jurnal</h1>
    <p class="page-subtitle">Pengaturan akun default per baris jurnal. Template dan baris tidak ditambah atau dihapus; hanya akun default yang diperbarui.</p>
</div>

<div class="accurate-setting-wrap">
    <aside class="accurate-setting-nav">
        <div class="nav-title"><i class="bi bi-sliders me-1"></i>Preference</div>
        <?php foreach ($groups as $groupKey => $group): ?>
            <a href="<?= esc(setting_akun_nav_url('master_setup/template_jurnal', (string) $groupKey, $q)) ?>" class="<?= $tab === (string) $groupKey ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i>
                <span><?= esc($group['label']) ?></span>
                <span class="count-badge"><?= (int) $group['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </aside>

    <section class="accurate-setting-panel">
        <form method="post" action="<?= esc(admin_url('menu/master_setup/template_jurnal/update.php')) ?>">
            <div class="accurate-panel-header">
                <div>
                    <h2 class="accurate-panel-title">Default Account For</h2>
                    <p class="accurate-panel-subtitle">Kode akun default yang dipakai saat sistem membentuk jurnal otomatis.</p>
                </div>
                <div class="accurate-toolbar">
                    <input type="hidden" name="tab" value="<?= esc($tab) ?>">
                    <input type="hidden" name="q" value="<?= esc($q) ?>">
                    <input type="text" id="settingLiveSearch" class="form-control" placeholder="Cari template / akun..." value="<?= esc($q) ?>">
                    <a href="<?= esc(admin_page_url('master_setup/template_jurnal')) ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>

            <div class="accurate-setting-body">
                <div class="currency-row">
                    <label>Currency Name:</label>
                    <input type="text" class="form-control form-control-sm" value="IDR" readonly>
                </div>

                <div class="default-title">Default Account For:</div>

                <datalist id="coaListTemplate">
                    <?php foreach ($coa_options as $coa): ?>
                        <option value="<?= esc($coa->kode_coa) ?>"><?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?></option>
                        <option value="<?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <?php if ($rows->count() > 0): ?>
                    <div class="setting-list-box">
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $selectId = 'template_akun_' . (int) $row->id_template_jurnal_detail;
                            $dcLabel = strtoupper((string) ($row->posisi_dc ?? '-'));
                            $searchText = trim(($row->kode_template_jurnal ?? '') . ' ' . ($row->nama_template_jurnal ?? '') . ' ' . ($row->kode_jenis_transaksi ?? '') . ' ' . ($row->posisi_dc ?? '') . ' ' . ($row->kode_coa ?? '') . ' ' . ($row->nama_coa ?? '') . ' ' . ($row->keterangan_baris ?? ''));
                            ?>
                            <div class="setting-row" data-search="<?= esc($searchText) ?>">
                                <div class="setting-label">
                                    <strong><?= esc($row->nama_template_jurnal . ' - ' . $dcLabel) ?></strong>
                                    <span><?= esc('Baris ' . (int) $row->urutan . ' | ' . ($row->kode_jenis_transaksi ?? '-') . ' | ' . ($row->keterangan_baris ?? '-')) ?></span>
                                </div>
                                <input
                                    type="text"
                                    name="template_kode[<?= (int) $row->id_template_jurnal_detail ?>]"
                                    class="form-control form-control-sm setting-account-code"
                                    list="coaListTemplate"
                                    value="<?= esc($row->kode_coa ?? '') ?>"
                                    data-select-target="<?= esc($selectId) ?>"
                                    placeholder="Kode">
                                <select
                                    name="template[<?= (int) $row->id_template_jurnal_detail ?>]"
                                    id="<?= esc($selectId) ?>"
                                    class="form-select form-select-sm setting-account-select">
                                    <option value="">- Pilih Akun -</option>
                                    <?php foreach ($coa_options as $coa): ?>
                                        <option
                                            value="<?= (int) $coa->id_coa ?>"
                                            data-code="<?= esc($coa->kode_coa) ?>"
                                            data-name="<?= esc($coa->nama_coa) ?>"
                                            <?= (int) ($row->id_coa_default ?? 0) === (int) $coa->id_coa ? 'selected' : '' ?>>
                                            <?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="setting-empty">Tidak ada template jurnal yang sesuai filter.</div>
                <?php endif; ?>
            </div>

            <div class="setting-footer">
                <span class="me-auto small text-muted">Total ditampilkan: <?= (int) $rows->count() ?> dari <?= (int) $rows_all->count() ?> baris template</span>
                <a href="<?= esc(admin_page_url('master_setup/template_jurnal')) ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancel</a>
                <button type="submit" class="btn btn-gradient"><i class="bi bi-check-lg me-1"></i>OK</button>
            </div>
        </form>
    </section>
</div>

<style>
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
    .accurate-setting-nav a:hover .count-badge {
        background: rgba(255,255,255,.24);
    }

    .accurate-setting-panel {
        border: 1px solid #d6d8e7;
        background: #f1f0f8;
        border-radius: 10px;
        min-height: 560px;
        box-shadow: 0 2px 8px rgba(19, 31, 77, .04);
    }

    .accurate-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 14px;
        padding: 16px 18px 12px;
        border-bottom: 1px solid #dfe1ee;
        background: #ffffff;
        border-radius: 10px 10px 0 0;
    }

    .accurate-panel-title {
        margin: 0;
        color: #23235f;
        font-size: 18px;
        font-weight: 800;
    }

    .accurate-panel-subtitle {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 12px;
    }

    .accurate-toolbar {
        display: flex;
        gap: 8px;
        min-width: 360px;
        justify-content: flex-end;
    }

    .accurate-setting-body {
        padding: 16px 18px 18px;
    }

    .currency-row {
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 440px;
        margin-bottom: 12px;
    }

    .currency-row label {
        min-width: 130px;
        margin: 0;
        font-weight: 600;
        font-size: 13px;
    }

    .default-title {
        margin: 4px 0 10px;
        font-weight: 800;
        color: #202124;
    }

    .setting-row {
        display: grid;
        grid-template-columns: minmax(240px, 330px) 96px minmax(260px, 1fr);
        gap: 8px;
        align-items: center;
        padding: 7px 0;
        border-bottom: 1px solid rgba(93, 95, 121, .13);
    }

    .setting-row:last-child {
        border-bottom: 0;
    }

    .setting-label {
        font-size: 13px;
        line-height: 1.25;
        color: #25273a;
    }

    .setting-label strong {
        display: block;
        font-weight: 700;
    }

    .setting-label span {
        display: block;
        color: #6b7280;
        font-size: 11px;
        margin-top: 3px;
    }

    .setting-account-code {
        font-size: 13px;
        text-align: center;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
    }

    .setting-account-select {
        font-size: 13px;
    }

    .setting-list-box {
        border: 1px solid #dedfea;
        background: #ffffff;
        border-radius: 8px;
        padding: 10px 14px;
        max-height: 62vh;
        overflow: auto;
    }

    .setting-empty {
        border: 1px dashed #c7c9d9;
        border-radius: 8px;
        background: #fff;
        padding: 32px;
        text-align: center;
        color: #6b7280;
    }

    .setting-footer {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        padding: 12px 18px 16px;
        border-top: 1px solid #dfe1ee;
        background: #ffffff;
        border-radius: 0 0 10px 10px;
    }

    @media (max-width: 1100px) {
        .accurate-setting-wrap {
            grid-template-columns: 1fr;
        }
        .accurate-setting-nav {
            min-height: auto;
        }
        .setting-row {
            grid-template-columns: 1fr;
            align-items: stretch;
            padding: 10px 0;
        }
        .accurate-toolbar {
            min-width: 0;
            width: 100%;
        }
        .accurate-panel-header {
            flex-direction: column;
        }
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rows = document.querySelectorAll('.setting-row');

    function normalize(value) {
        return String(value || '').trim().toLowerCase();
    }

    function applySelectToCode(select) {
        const codeInput = document.querySelector('[data-select-target="' + select.id + '"]');
        if (!codeInput) return;
        const option = select.options[select.selectedIndex];
        codeInput.value = option ? (option.dataset.code || '') : '';
    }

    function applyCodeToSelect(input) {
        const select = document.getElementById(input.dataset.selectTarget || '');
        if (!select) return;

        const raw = normalize(input.value);
        const codeOnly = normalize(String(input.value || '').split(' - ')[0]);
        let matchedValue = '';

        Array.from(select.options).forEach(function (option) {
            if (matchedValue || !option.value) return;
            const code = normalize(option.dataset.code || '');
            const label = normalize(option.textContent || '');
            if (raw === code || raw === label || codeOnly === code) {
                matchedValue = option.value;
            }
        });

        if (matchedValue) {
            select.value = matchedValue;
            input.classList.remove('is-invalid');
        } else if (raw !== '') {
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    }

    document.querySelectorAll('.setting-account-select').forEach(function (select) {
        select.addEventListener('change', function () {
            applySelectToCode(select);
        });
    });

    document.querySelectorAll('.setting-account-code').forEach(function (input) {
        input.addEventListener('change', function () {
            applyCodeToSelect(input);
        });
        input.addEventListener('blur', function () {
            applyCodeToSelect(input);
        });
    });

    const searchInput = document.getElementById('settingLiveSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const keyword = normalize(searchInput.value);
            rows.forEach(function (row) {
                const haystack = normalize(row.dataset.search || row.textContent || '');
                row.style.display = haystack.indexOf(keyword) !== -1 ? '' : 'none';
            });
        });
    }
});
</script>
