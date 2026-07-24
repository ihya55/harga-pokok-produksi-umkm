<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$q = trim((string) ($_GET['q'] ?? ''));
$tab = trim((string) ($_GET['tab'] ?? 'semua'));

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

$rows_all = Capsule::table('tb_konfigurasi_akun as k')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'k.id_coa')
    ->where('k.id_entitas', $id_entitas)
    ->select([
        'k.*',
        'c.kode_coa',
        'c.nama_coa',
        'c.kategori_coa',
    ])
    ->orderBy('k.digunakan_di_menu')
    ->orderBy('k.kode_konfigurasi')
    ->get();

$groups = ['semua' => ['label' => 'Semua Account', 'count' => $rows_all->count()]];
foreach ($rows_all as $row) {
    $group = trim((string) ($row->digunakan_di_menu ?: 'Lainnya'));
    if (!isset($groups[$group])) {
        $groups[$group] = ['label' => $group, 'count' => 0];
    }
    $groups[$group]['count']++;
}

if ($tab !== 'semua' && !isset($groups[$tab])) {
    $tab = 'semua';
}

$rows = $rows_all->filter(function ($row) use ($tab, $q) {
    $group = trim((string) ($row->digunakan_di_menu ?: 'Lainnya'));
    if ($tab !== 'semua' && $group !== $tab) {
        return false;
    }
    if ($q === '') {
        return true;
    }
    $haystack = strtolower(implode(' ', [
        (string) ($row->kode_konfigurasi ?? ''),
        (string) ($row->nama_konfigurasi ?? ''),
        (string) ($row->digunakan_di_menu ?? ''),
        (string) ($row->keterangan ?? ''),
        (string) ($row->kode_coa ?? ''),
        (string) ($row->nama_coa ?? ''),
    ]));
    return str_contains($haystack, strtolower($q));
});
?>

<div class="page-header mb-4">
    <h1 class="page-title">Konfigurasi Akun</h1>
    <p class="page-subtitle">Pengaturan akun default sistem. Tidak ada tambah/hapus konfigurasi; hanya update kode akun.</p>
</div>

<div class="accurate-setting-wrap">
    <aside class="accurate-setting-nav">
        <div class="nav-title"><i class="bi bi-sliders me-1"></i>Preference</div>
        <?php foreach ($groups as $groupKey => $group): ?>
            <a href="<?= esc(setting_akun_nav_url('master_setup/konfigurasi_akun', (string) $groupKey, $q)) ?>" class="<?= $tab === (string) $groupKey ? 'active' : '' ?>">
                <i class="bi bi-gear"></i>
                <span><?= esc($group['label']) ?></span>
                <span class="count-badge"><?= (int) $group['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </aside>

    <section class="accurate-setting-panel">
        <form method="post" action="<?= esc(admin_url('menu/master_setup/konfigurasi_akun/update.php')) ?>">
            <div class="accurate-panel-header">
                <div>
                    <h2 class="accurate-panel-title">Default Account For</h2>
                    <p class="accurate-panel-subtitle">Akun default untuk modul master, produk, pembelian, dan transaksi terkait.</p>
                </div>
                <div class="accurate-toolbar">
                    <input type="hidden" name="tab" value="<?= esc($tab) ?>">
                    <input type="hidden" name="q" value="<?= esc($q) ?>">
                    <input type="text" id="settingLiveSearch" class="form-control" placeholder="Cari konfigurasi / akun..." value="<?= esc($q) ?>">
                    <a href="<?= esc(admin_page_url('master_setup/konfigurasi_akun')) ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>

            <div class="accurate-setting-body">
                <div class="currency-row">
                    <label>Currency Name:</label>
                    <input type="text" class="form-control form-control-sm" value="IDR" readonly>
                </div>

                <div class="default-title">Default Account For:</div>

                <datalist id="coaListKonfigurasi">
                    <?php foreach ($coa_options as $coa): ?>
                        <option value="<?= esc($coa->kode_coa) ?>"><?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?></option>
                        <option value="<?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <?php if ($rows->count() > 0): ?>
                    <div class="setting-list-box">
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $selectId = 'konfigurasi_akun_' . (int) $row->id_konfigurasi_akun;
                            $searchText = trim(($row->kode_konfigurasi ?? '') . ' ' . ($row->nama_konfigurasi ?? '') . ' ' . ($row->digunakan_di_menu ?? '') . ' ' . ($row->kode_coa ?? '') . ' ' . ($row->nama_coa ?? ''));
                            ?>
                            <div class="setting-row" data-search="<?= esc($searchText) ?>">
                                <div class="setting-label">
                                    <strong><?= esc($row->nama_konfigurasi ?? '-') ?></strong>
                                    <span><?= esc(($row->digunakan_di_menu ?? '-') . ' | ' . ($row->kode_konfigurasi ?? '-')) ?></span>
                                </div>
                                <input
                                    type="text"
                                    name="konfigurasi_kode[<?= (int) $row->id_konfigurasi_akun ?>]"
                                    class="form-control form-control-sm setting-account-code"
                                    list="coaListKonfigurasi"
                                    value="<?= esc($row->kode_coa ?? '') ?>"
                                    data-select-target="<?= esc($selectId) ?>"
                                    placeholder="Kode">
                                <select
                                    name="konfigurasi[<?= (int) $row->id_konfigurasi_akun ?>]"
                                    id="<?= esc($selectId) ?>"
                                    class="form-select form-select-sm setting-account-select"
                                    required>
                                    <option value="">- Pilih Akun -</option>
                                    <?php foreach ($coa_options as $coa): ?>
                                        <option
                                            value="<?= (int) $coa->id_coa ?>"
                                            data-code="<?= esc($coa->kode_coa) ?>"
                                            data-name="<?= esc($coa->nama_coa) ?>"
                                            <?= (int) $row->id_coa === (int) $coa->id_coa ? 'selected' : '' ?>>
                                            <?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="setting-empty">Tidak ada konfigurasi akun yang sesuai filter.</div>
                <?php endif; ?>
            </div>

            <div class="setting-footer">
                <span class="me-auto small text-muted">Total ditampilkan: <?= (int) $rows->count() ?> dari <?= (int) $rows_all->count() ?> konfigurasi</span>
                <a href="<?= esc(admin_page_url('master_setup/konfigurasi_akun')) ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancel</a>
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
