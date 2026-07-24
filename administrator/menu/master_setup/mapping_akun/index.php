<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/helpers_mapping_akun.php';

$id_entitas = mapping_akun_id_entitas();
$q = trim((string) ($_GET['q'] ?? ''));
$tab = trim((string) ($_GET['tab'] ?? 'semua'));

if (!function_exists('setting_akun_mapping_group')) {
    function setting_akun_mapping_group(?string $kode): string
    {
        $kode = strtoupper((string) $kode);
        if (str_contains($kode, 'PENJUALAN') || str_contains($kode, 'PELANGGAN')) {
            return 'Penjualan';
        }
        if (str_contains($kode, 'PEMBELIAN') || str_contains($kode, 'PEMASOK')) {
            return 'Pembelian';
        }
        if (str_contains($kode, 'PRODUKSI') || str_contains($kode, 'HASIL_PRODUKSI') || str_contains($kode, 'PENGAMBILAN_BAHAN')) {
            return 'Produksi';
        }
        if (str_contains($kode, 'STOK') || str_contains($kode, 'SALDO_AWAL')) {
            return 'Persediaan';
        }
        if (str_contains($kode, 'KAS') || str_contains($kode, 'BANK') || str_contains($kode, 'ARUS_KAS')) {
            return 'Kas dan Bank';
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

$rows_all = Capsule::table('tb_mapping_akun as m')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'm.id_coa')
    ->where('m.id_entitas', $id_entitas)
    ->select([
        'm.*',
        'c.kode_coa',
        'c.nama_coa',
        'c.kategori_coa',
    ])
    ->orderBy('m.kode_jenis_transaksi')
    ->orderBy('m.peran_akun')
    ->orderBy('m.id_mapping_akun')
    ->get();

$pajak_rows = Capsule::schema()->hasTable('tb_pajak')
    ? Capsule::table('tb_pajak')
        ->where('id_entitas', $id_entitas)
        ->orderByDesc('is_default')
        ->orderBy('kode_pajak')
        ->orderBy('id_pajak')
        ->get()
    : collect();

$pajak_default = $pajak_rows->first(function ($row) {
    return (int) ($row->is_default ?? 0) === 1 && (int) ($row->status_aktif ?? 0) === 1;
}) ?? $pajak_rows->first();

$coa_has_payment_cols = Capsule::schema()->hasColumn('tb_coa', 'nomor_rekening')
    && Capsule::schema()->hasColumn('tb_coa', 'qris_static_payload');

$bank_account_rows = Capsule::table('tb_coa')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->where('boleh_transaksi', 1)
    ->where(function ($query) use ($coa_has_payment_cols) {
        $query->where('nama_coa', 'like', '%bank%')
            ->orWhere('kode_coa', 'like', '112%')
            ->orWhere('kategori_coa', 'aset');
        if ($coa_has_payment_cols) {
            $query->orWhere('nomor_rekening', '<>', '')
                ->orWhere('is_akun_qris', 1)
                ->orWhere('qris_static_payload', '<>', '');
        }
    })
    ->orderBy('kode_coa')
    ->get();

$bank_selected = null;
if ($coa_has_payment_cols && $bank_account_rows->count() > 0) {
    $bank_selected = $bank_account_rows->first(function ($row) {
        return (int) ($row->is_akun_qris ?? 0) === 1;
    }) ?? $bank_account_rows->first(function ($row) {
        return trim((string) ($row->nomor_rekening ?? '')) !== '' || trim((string) ($row->qris_static_payload ?? '')) !== '';
    }) ?? $bank_account_rows->first();
}


$gateway_has_table = Capsule::schema()->hasTable('tb_payment_gateway_setting');
$midtrans_has_table = $gateway_has_table;
$midtrans_setting = null;
$duitku_setting = null;
$ipaymu_setting = null;
if ($gateway_has_table) {
    $midtrans_setting = Capsule::table('tb_payment_gateway_setting')
        ->where('id_entitas', $id_entitas)
        ->where('provider', 'midtrans')
        ->first();
    $duitku_setting = Capsule::table('tb_payment_gateway_setting')
        ->where('id_entitas', $id_entitas)
        ->where('provider', 'duitku')
        ->first();
    $ipaymu_setting = Capsule::table('tb_payment_gateway_setting')
        ->where('id_entitas', $id_entitas)
        ->where('provider', 'ipaymu')
        ->first();
}
if (!$midtrans_setting) {
    $midtrans_setting = (object) [
        'provider' => 'midtrans',
        'mode' => 'sandbox',
        'is_active' => 0,
        'server_key' => '',
        'client_key' => '',
        'merchant_id' => '',
        'enabled_payments' => 'qris',
    ];
}
if (!$duitku_setting) {
    $duitku_setting = (object) [
        'provider' => 'duitku',
        'mode' => 'sandbox',
        'is_active' => 0,
        'server_key' => '',
        'client_key' => '',
        'merchant_id' => '',
        'enabled_payments' => 'SP',
    ];
}
if (!$ipaymu_setting) {
    $ipaymu_setting = (object) [
        'provider' => 'ipaymu',
        'mode' => 'sandbox',
        'is_active' => 0,
        'server_key' => '',
        'client_key' => '',
        'merchant_id' => '',
        'enabled_payments' => 'qris',
    ];
}

$groups = ['semua' => ['label' => 'Semua Account', 'count' => $rows_all->count()]];
foreach ($rows_all as $row) {
    $group = setting_akun_mapping_group($row->kode_jenis_transaksi ?? '');
    if (!isset($groups[$group])) {
        $groups[$group] = ['label' => $group, 'count' => 0];
    }
    $groups[$group]['count']++;
}
$groups['pajak_ppn'] = ['label' => 'Pajak / PPN', 'count' => max(1, $pajak_rows->count())];
$groups['rekening_qris'] = ['label' => 'Rekening / QRIS', 'count' => max(1, $bank_account_rows->count())];

if ($tab !== 'semua' && !isset($groups[$tab])) {
    $tab = 'semua';
}

$rows = $rows_all->filter(function ($row) use ($tab, $q) {
    $groupOk = $tab === 'semua' || setting_akun_mapping_group($row->kode_jenis_transaksi ?? '') === $tab;
    if (!$groupOk) {
        return false;
    }
    if ($q === '') {
        return true;
    }
    $haystack = strtolower(implode(' ', [
        (string) ($row->kode_jenis_transaksi ?? ''),
        (string) ($row->jenis_objek ?? ''),
        (string) ($row->peran_akun ?? ''),
        (string) ($row->kode_coa ?? ''),
        (string) ($row->nama_coa ?? ''),
        label_jenis_transaksi_mapping_akun($row->kode_jenis_transaksi ?? ''),
        label_peran_akun_mapping($row->peran_akun ?? ''),
        label_jenis_objek_mapping_akun($row->jenis_objek ?? ''),
    ]));
    return str_contains($haystack, strtolower($q));
});
?>

<div class="page-header mb-4">
    <h1 class="page-title">Mapping Akun</h1>
    <p class="page-subtitle">Pengaturan akun transaksi. Data mapping tidak ditambah atau dihapus; yang diubah hanya kode akun/COA.</p>
</div>

<div class="accurate-setting-wrap">
    <aside class="accurate-setting-nav">
        <div class="nav-title"><i class="bi bi-sliders me-1"></i>Preference</div>
        <?php foreach ($groups as $groupKey => $group): ?>
            <a href="<?= esc(setting_akun_nav_url('master_setup/mapping_akun', (string) $groupKey, $q)) ?>" class="<?= $tab === (string) $groupKey ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i>
                <span><?= esc($group['label']) ?></span>
                <span class="count-badge"><?= (int) $group['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </aside>

    <section class="accurate-setting-panel">
        <?php if ($tab === 'Produksi'): ?>
            <div class="alert alert-info m-3">
                <strong>Template & Mapping Produksi</strong><br>
                Untuk biaya produksi, jenis biaya hanya <b>Tenaga Kerja</b> dan <b>Overhead Pabrik</b>. Template tenaga kerja hanya dipakai untuk tenaga kerja, sedangkan overhead dapat memakai template listrik, air, kuota, kemasan, dan overhead pabrik.
                <div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="<?= esc(admin_page_url('master_setup/template_jurnal') . '&tab=Produksi') ?>">Atur Template Jurnal Produksi</a> <a class="btn btn-sm btn-outline-secondary" href="<?= esc(admin_page_url('master_setup/konfigurasi_akun')) ?>">Atur Konfigurasi Akun</a></div>
            </div>
        <?php endif; ?>
        <?php if ($tab === 'pajak_ppn'): ?>
            <form method="post" action="<?= esc(admin_url('menu/master_setup/mapping_akun/update_pajak.php')) ?>">
                <div class="accurate-panel-header">
                    <div>
                        <h2 class="accurate-panel-title">Pajak / PPN Default</h2>
                        <p class="accurate-panel-subtitle">Persentase PPN yang digunakan otomatis oleh form pembelian, penjualan, dan transaksi lain yang memakai PPN.</p>
                    </div>
                    <div class="accurate-toolbar">
                        <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary">Kembali ke Mapping Akun</a>
                    </div>
                </div>

                <div class="accurate-setting-body">
                    <input type="hidden" name="id_pajak" value="<?= (int) ($pajak_default->id_pajak ?? 0) ?>">
                    <div class="ppn-setting-card">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Kode Pajak</label>
                                <input type="text" name="kode_pajak" class="form-control text-uppercase" maxlength="30" required value="<?= esc($pajak_default->kode_pajak ?? 'PPN') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Nama Pajak</label>
                                <input type="text" name="nama_pajak" class="form-control" maxlength="100" required value="<?= esc($pajak_default->nama_pajak ?? 'Pajak Pertambahan Nilai') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Persentase</label>
                                <div class="input-group">
                                    <input type="number" name="persentase" class="form-control text-end" min="0" max="100" step="0.01" required value="<?= esc((string) ($pajak_default->persentase ?? '11')) ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-semibold">Status</label>
                                <select name="status_aktif" class="form-select">
                                    <option value="1" <?= ((string) ($pajak_default->status_aktif ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                                    <option value="0" <?= ((string) ($pajak_default->status_aktif ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-gradient w-100"><i class="bi bi-check-lg me-1"></i>Simpan PPN</button>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="2" placeholder="Keterangan pajak"><?= esc($pajak_default->keterangan ?? 'Pajak default yang digunakan otomatis pada faktur.') ?></textarea>
                                <div class="form-text">Nilai ini akan dibaca dari database oleh form transaksi yang memakai PPN.</div>
                            </div>
                        </div>
                    </div>

                    <?php if ($pajak_rows->count() > 0): ?>
                        <div class="default-title mt-4">Daftar Pajak Tersimpan</div>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle setting-tax-table">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Pajak</th>
                                        <th class="text-end">Persentase</th>
                                        <th>Default</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pajak_rows as $pajak): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= esc($pajak->kode_pajak ?? '-') ?></td>
                                            <td><?= esc($pajak->nama_pajak ?? '-') ?></td>
                                            <td class="text-end"><?= esc(number_format((float) ($pajak->persentase ?? 0), 2, ',', '.')) ?>%</td>
                                            <td><?= ((int) ($pajak->is_default ?? 0) === 1) ? 'Ya' : 'Tidak' ?></td>
                                            <td><?= ((int) ($pajak->status_aktif ?? 0) === 1) ? 'Aktif' : 'Nonaktif' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setting-footer">
                    <span class="me-auto small text-muted">Menu PPN sekarang digabung di Preference Mapping Akun.</span>
                    <button type="submit" class="btn btn-gradient"><i class="bi bi-check-lg me-1"></i>OK</button>
                </div>
            </form>
        <?php elseif ($tab === 'rekening_qris'): ?>
            <form method="post" enctype="multipart/form-data" action="<?= esc(admin_url('menu/master_setup/mapping_akun/update_bank_qris.php')) ?>">
                <div class="accurate-panel-header">
                    <div>
                        <h2 class="accurate-panel-title">Rekening Bank / QRIS Online</h2>
                        <p class="accurate-panel-subtitle">Nomor rekening dan QRIS dipasang pada akun bank. Data ini dipakai otomatis di halaman Pesanan Online. QRIS memakai metode manual tanpa gateway.</p>
                    </div>
                    <div class="accurate-toolbar">
                        <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary">Kembali ke Mapping Akun</a>
                    </div>
                </div>

                <div class="accurate-setting-body">
                    <?php if (!$coa_has_payment_cols): ?>
                        <div class="alert alert-warning mb-0">
                            Kolom rekening/QRIS pada tabel <strong>tb_coa</strong> belum ada. Jalankan SQL update yang disertakan terlebih dahulu.
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="id_coa_lama" value="<?= (int) ($bank_selected->id_coa ?? 0) ?>">
                        <div class="ppn-setting-card">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Akun Bank / Kas</label>
                                    <select name="id_coa" class="form-select" required>
                                        <option value="">- Pilih akun bank -</option>
                                        <?php foreach ($bank_account_rows as $coaBank): ?>
                                            <option value="<?= (int) $coaBank->id_coa ?>" <?= (int) ($bank_selected->id_coa ?? 0) === (int) $coaBank->id_coa ? 'selected' : '' ?>>
                                                <?= esc(($coaBank->kode_coa ?? '') . ' - ' . ($coaBank->nama_coa ?? '')) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Disarankan pilih akun COA Bank, misalnya 1120 - Bank.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Nama Bank</label>
                                    <input type="text" name="nama_bank" class="form-control" maxlength="100" value="<?= esc($bank_selected->nama_bank ?? '') ?>" placeholder="Contoh: BCA / BRI / Mandiri">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Nomor Rekening</label>
                                    <input type="text" name="nomor_rekening" class="form-control" maxlength="80" value="<?= esc($bank_selected->nomor_rekening ?? '') ?>" placeholder="Nomor rekening transfer">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Atas Nama Rekening</label>
                                    <input type="text" name="atas_nama_rekening" class="form-control" maxlength="150" value="<?= esc($bank_selected->atas_nama_rekening ?? '') ?>" placeholder="Nama pemilik rekening">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nama Merchant QRIS</label>
                                    <input type="text" name="qris_nama_merchant" class="form-control" maxlength="150" value="<?= esc($bank_selected->qris_nama_merchant ?? '') ?>" placeholder="Nama merchant QRIS">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Payload QRIS Statis</label>
                                    <textarea name="qris_static_payload" class="form-control font-monospace" rows="4" placeholder="Tempel payload QRIS statis dari merchant/bank di sini"><?= esc($bank_selected->qris_static_payload ?? '') ?></textarea>
                                    <div class="form-text">Opsional. Kalau tidak punya payload, kosongkan dan gunakan gambar QRIS manual di bawah.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Gambar QRIS Manual</label>
                                    <?php if (!empty($bank_selected->qris_image_path ?? '')): ?>
                                        <div class="mb-2"><img src="<?= esc(base_url((string) $bank_selected->qris_image_path)) ?>" alt="QRIS" style="max-width:220px;border:1px solid #e5e7eb;border-radius:16px;padding:8px;background:#fff;"></div>
                                    <?php endif; ?>
                                    <input type="file" name="qris_image" class="form-control" accept="image/png,image/jpeg,image/webp">
                                    <div class="form-text">Upload gambar QRIS dari GoPay/DANA/bank. Ini dipakai untuk QRIS manual + upload bukti pembayaran.</div>
                                    <input type="hidden" name="qris_image_path_lama" value="<?= esc($bank_selected->qris_image_path ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="is_akun_qris" value="1" id="isAkunQris" <?= (int) ($bank_selected->is_akun_qris ?? 0) === 1 ? 'checked' : '' ?>>
                                        <label class="form-check-label fw-semibold" for="isAkunQris">Jadikan akun ini sebagai rekening/QRIS utama pesanan online</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($bank_account_rows->count() > 0): ?>
                            <div class="default-title mt-4">Daftar Akun Bank / Kas</div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle setting-tax-table">
                                    <thead>
                                        <tr>
                                            <th>Kode Akun</th>
                                            <th>Nama Akun</th>
                                            <th>Bank</th>
                                            <th>No Rekening</th>
                                            <th>QRIS Utama</th>
                                            <th>Gambar QRIS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bank_account_rows as $coaBank): ?>
                                            <tr>
                                                <td class="fw-semibold"><?= esc($coaBank->kode_coa ?? '-') ?></td>
                                                <td><?= esc($coaBank->nama_coa ?? '-') ?></td>
                                                <td><?= esc($coaBank->nama_bank ?? '-') ?></td>
                                                <td><?= esc($coaBank->nomor_rekening ?? '-') ?></td>
                                                <td><?= (int) ($coaBank->is_akun_qris ?? 0) === 1 ? 'Ya' : 'Tidak' ?></td>
                                                <td><?= !empty($coaBank->qris_image_path ?? '') ? 'Ada' : '-' ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="setting-footer">
                    <span class="me-auto small text-muted">Dipakai otomatis oleh metode Transfer Bank dan QRIS pada Pesanan Online.</span>
                    <button type="submit" class="btn btn-gradient" <?= !$coa_has_payment_cols ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i>Simpan Rekening / QRIS</button>
                </div>
            </form>
        <?php elseif (false && $tab === 'payment_gateway'): ?>
            <form method="post" action="<?= esc(admin_url('menu/master_setup/mapping_akun/update_ipaymu.php')) ?>" class="mb-4">
                <div class="accurate-panel-header">
                    <div>
                        <h2 class="accurate-panel-title">iPaymu QRIS Dinamis</h2>
                        <p class="accurate-panel-subtitle">Pilihan utama selain Duitku. Customer akan diarahkan ke halaman iPaymu untuk QRIS sesuai nominal pesanan.</p>
                    </div>
                    <div class="accurate-toolbar">
                        <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary">Kembali ke Mapping Akun</a>
                    </div>
                </div>

                <div class="accurate-setting-body">
                    <?php if (!$gateway_has_table): ?>
                        <div class="alert alert-warning mb-0">
                            Tabel <strong>tb_payment_gateway_setting</strong> belum ada. Jalankan SQL update iPaymu terlebih dahulu.
                        </div>
                    <?php else: ?>
                        <div class="ppn-setting-card">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Provider</label>
                                    <input type="text" class="form-control" value="iPaymu" readonly>
                                    <input type="hidden" name="provider" value="ipaymu">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Mode</label>
                                    <select name="mode" class="form-select">
                                        <option value="sandbox" <?= ((string) ($ipaymu_setting->mode ?? 'sandbox') === 'sandbox') ? 'selected' : '' ?>>Sandbox / Percobaan</option>
                                        <option value="production" <?= ((string) ($ipaymu_setting->mode ?? '') === 'production') ? 'selected' : '' ?>>Production / Uang Asli</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="is_active" class="form-select">
                                        <option value="0" <?= ((int) ($ipaymu_setting->is_active ?? 0) === 0) ? 'selected' : '' ?>>Nonaktif</option>
                                        <option value="1" <?= ((int) ($ipaymu_setting->is_active ?? 0) === 1) ? 'selected' : '' ?>>Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Channel QRIS</label>
                                    <input type="text" name="enabled_payments" class="form-control text-lowercase" value="<?= esc($ipaymu_setting->enabled_payments ?? 'qris') ?>" placeholder="qris">
                                    <div class="form-text">Isi <strong>qris</strong>.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">VA Number iPaymu</label>
                                    <input type="text" name="merchant_id" class="form-control font-monospace" value="<?= esc($ipaymu_setting->merchant_id ?? '') ?>" placeholder="Nomor VA dari dashboard iPaymu">
                                    <div class="form-text">Diambil dari dashboard iPaymu. Ini bukan nomor rekening bank biasa.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">API Key iPaymu</label>
                                    <input type="password" name="server_key" class="form-control font-monospace" value="<?= esc($ipaymu_setting->server_key ?? '') ?>" placeholder="API Key iPaymu">
                                    <div class="form-text">API Key didapat setelah login dashboard iPaymu. Jangan diberikan ke pelanggan.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Notify URL</label>
                                    <input type="text" class="form-control" readonly value="<?= esc(base_url('pesanan-online/ipaymu_callback.php')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Return URL</label>
                                    <input type="text" class="form-control" readonly value="<?= esc(base_url('pesanan-online/sukses.php')) ?>">
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        Jika iPaymu aktif, sistem akan memakai iPaymu lebih dulu untuk QRIS otomatis. Jika iPaymu gagal atau belum diisi, customer tetap melihat QRIS manual dari tab Rekening / QRIS dan bisa upload bukti pembayaran.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setting-footer">
                    <span class="me-auto small text-muted">QRIS manual dari Rekening / QRIS tetap tampil sebagai cadangan.</span>
                    <button type="submit" class="btn btn-gradient" <?= !$gateway_has_table ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i>Simpan iPaymu QRIS</button>
                </div>
            </form>

            <form method="post" action="<?= esc(admin_url('menu/master_setup/mapping_akun/update_duitku.php')) ?>" class="mb-4">
                <div class="accurate-panel-header">
                    <div>
                        <h2 class="accurate-panel-title">Duitku QRIS Dinamis (Cadangan)</h2>
                        <p class="accurate-panel-subtitle">Cadangan jika iPaymu nonaktif. QRIS akan dibuat otomatis sesuai total pesanan.</p>
                    </div>
                    <div class="accurate-toolbar">
                        <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary">Kembali ke Mapping Akun</a>
                    </div>
                </div>

                <div class="accurate-setting-body">
                    <?php if (!$gateway_has_table): ?>
                        <div class="alert alert-warning mb-0">
                            Tabel <strong>tb_payment_gateway_setting</strong> belum ada. Jalankan SQL update Duitku terlebih dahulu.
                        </div>
                    <?php else: ?>
                        <div class="ppn-setting-card">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Provider</label>
                                    <input type="text" class="form-control" value="Duitku" readonly>
                                    <input type="hidden" name="provider" value="duitku">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Mode</label>
                                    <select name="mode" class="form-select">
                                        <option value="sandbox" <?= ((string) ($duitku_setting->mode ?? 'sandbox') === 'sandbox') ? 'selected' : '' ?>>Sandbox / Percobaan</option>
                                        <option value="production" <?= ((string) ($duitku_setting->mode ?? '') === 'production') ? 'selected' : '' ?>>Production / Uang Asli</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="is_active" class="form-select">
                                        <option value="0" <?= ((int) ($duitku_setting->is_active ?? 0) === 0) ? 'selected' : '' ?>>Nonaktif</option>
                                        <option value="1" <?= ((int) ($duitku_setting->is_active ?? 0) === 1) ? 'selected' : '' ?>>Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Kode Payment QRIS</label>
                                    <input type="text" name="enabled_payments" class="form-control text-uppercase" value="<?= esc($duitku_setting->enabled_payments ?? 'SP') ?>" placeholder="SP">
                                    <div class="form-text">Umumnya isi <strong>SP</strong> untuk QRIS ShopeePay/All-ID di Duitku.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Merchant Code</label>
                                    <input type="text" name="merchant_id" class="form-control font-monospace" value="<?= esc($duitku_setting->merchant_id ?? '') ?>" placeholder="DXXXX">
                                    <div class="form-text">Diambil dari Dashboard Duitku / project merchant.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">API Key</label>
                                    <input type="password" name="server_key" class="form-control font-monospace" value="<?= esc($duitku_setting->server_key ?? '') ?>" placeholder="API Key Duitku">
                                    <div class="form-text">Jangan diberikan ke pelanggan. Dipakai untuk signature HMAC SHA256.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Callback URL</label>
                                    <input type="text" class="form-control" readonly value="<?= esc(base_url('pesanan-online/duitku_callback.php')) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Return URL</label>
                                    <input type="text" class="form-control" readonly value="<?= esc(base_url('pesanan-online/sukses.php')) ?>">
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        Prioritas sistem: iPaymu lebih dulu, lalu Duitku, lalu Midtrans, lalu QRIS manual dari Rekening / QRIS.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setting-footer">
                    <span class="me-auto small text-muted">Aktifkan setelah Merchant Code dan API Key benar.</span>
                    <button type="submit" class="btn btn-gradient" <?= !$gateway_has_table ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i>Simpan Duitku QRIS</button>
                </div>
            </form>

            <form method="post" action="<?= esc(admin_url('menu/master_setup/mapping_akun/update_midtrans.php')) ?>">
                <div class="accurate-panel-header">
                    <div>
                        <h2 class="accurate-panel-title">Midtrans QRIS Otomatis (Cadangan)</h2>
                        <p class="accurate-panel-subtitle">Cadangan terakhir setelah iPaymu dan Duitku.</p>
                    </div>
                    <div class="accurate-toolbar">
                        <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary">Kembali ke Mapping Akun</a>
                    </div>
                </div>

                <div class="accurate-setting-body">
                    <?php if (!$midtrans_has_table): ?>
                        <div class="alert alert-warning mb-0">
                            Tabel <strong>tb_payment_gateway_setting</strong> belum ada. Jalankan SQL update Midtrans yang disertakan terlebih dahulu.
                        </div>
                    <?php else: ?>
                        <div class="ppn-setting-card">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Provider</label>
                                    <input type="text" class="form-control" value="Midtrans" readonly>
                                    <input type="hidden" name="provider" value="midtrans">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Mode</label>
                                    <select name="mode" class="form-select">
                                        <option value="sandbox" <?= ((string) ($midtrans_setting->mode ?? 'sandbox') === 'sandbox') ? 'selected' : '' ?>>Sandbox / Percobaan</option>
                                        <option value="production" <?= ((string) ($midtrans_setting->mode ?? '') === 'production') ? 'selected' : '' ?>>Production / Live</option>
                                    </select>
                                    <div class="form-text">Untuk belajar dan uji coba, pilih Sandbox.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Status</label>
                                    <select name="is_active" class="form-select">
                                        <option value="0" <?= ((int) ($midtrans_setting->is_active ?? 0) === 0) ? 'selected' : '' ?>>Nonaktif</option>
                                        <option value="1" <?= ((int) ($midtrans_setting->is_active ?? 0) === 1) ? 'selected' : '' ?>>Aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold">Payment Aktif</label>
                                    <input type="text" name="enabled_payments" class="form-control" value="<?= esc($midtrans_setting->enabled_payments ?? 'qris') ?>" placeholder="qris">
                                    <div class="form-text">Isi <strong>qris</strong> agar pelanggan langsung diarahkan ke QRIS.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Server Key</label>
                                    <input type="password" name="server_key" class="form-control font-monospace" value="<?= esc($midtrans_setting->server_key ?? '') ?>" placeholder="SB-Mid-server-...">
                                    <div class="form-text">Diambil dari Dashboard Midtrans &gt; Access Keys. Jangan diberikan ke pelanggan.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Client Key</label>
                                    <input type="text" name="client_key" class="form-control font-monospace" value="<?= esc($midtrans_setting->client_key ?? '') ?>" placeholder="SB-Mid-client-...">
                                    <div class="form-text">Client Key dipakai untuk memunculkan Snap QRIS di halaman pesanan.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Merchant ID</label>
                                    <input type="text" name="merchant_id" class="form-control" value="<?= esc($midtrans_setting->merchant_id ?? '') ?>" placeholder="Opsional">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Notification URL</label>
                                    <input type="text" class="form-control" readonly value="<?= esc(base_url('pesanan-online/midtrans_callback.php')) ?>">
                                    <div class="form-text">Masukkan URL ini di dashboard Midtrans agar status pembayaran bisa update otomatis jika website sudah online/public.</div>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        Alur QRIS otomatis: pelanggan checkout &gt; pilih QRIS &gt; sistem membuat Snap Token Midtrans &gt; QRIS muncul sesuai nominal pesanan. Untuk localhost, status lunas mungkin belum otomatis karena webhook Midtrans tidak bisa masuk ke komputer lokal.
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="setting-footer">
                    <span class="me-auto small text-muted">Aktifkan hanya setelah Server Key dan Client Key benar.</span>
                    <button type="submit" class="btn btn-gradient" <?= !$midtrans_has_table ? 'disabled' : '' ?>><i class="bi bi-check-lg me-1"></i>Simpan Gateway QRIS</button>
                </div>
            </form>
        <?php else: ?>
        <form method="post" action="<?= esc(admin_url('menu/master_setup/mapping_akun/update.php')) ?>">
            <div class="accurate-panel-header">
                <div>
                    <h2 class="accurate-panel-title">Default Account For</h2>
                    <p class="accurate-panel-subtitle">Mapping akun transaksi untuk entitas aktif. Kolom selain akun dikunci agar tidak membuat insert baru.</p>
                </div>
                <div class="accurate-toolbar">
                    <input type="hidden" name="tab" value="<?= esc($tab) ?>">
                    <input type="hidden" name="q" value="<?= esc($q) ?>">
                    <input type="text" id="settingLiveSearch" class="form-control" placeholder="Cari transaksi / peran / akun..." value="<?= esc($q) ?>">
                    <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>

            <div class="accurate-setting-body">
                <div class="currency-row">
                    <label>Currency Name:</label>
                    <input type="text" class="form-control form-control-sm" value="IDR" readonly>
                </div>

                <div class="default-title">Default Account For:</div>

                <datalist id="coaListMapping">
                    <?php foreach ($coa_options as $coa): ?>
                        <option value="<?= esc($coa->kode_coa) ?>"><?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?></option>
                        <option value="<?= esc($coa->kode_coa . ' - ' . $coa->nama_coa) ?>"></option>
                    <?php endforeach; ?>
                </datalist>

                <?php if ($rows->count() > 0): ?>
                    <div class="setting-list-box">
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $selectId = 'mapping_akun_' . (int) $row->id_mapping_akun;
                            $labelTransaksi = label_jenis_transaksi_mapping_akun($row->kode_jenis_transaksi ?? '');
                            $labelPeran = label_peran_akun_mapping($row->peran_akun ?? '');
                            $labelObjek = label_jenis_objek_mapping_akun($row->jenis_objek ?? '');
                            $objekNama = mapping_akun_nama_objek((string) ($row->jenis_objek ?? ''), (int) ($row->id_objek ?? 0), $id_entitas);
                            $searchText = trim($labelTransaksi . ' ' . $labelPeran . ' ' . $labelObjek . ' ' . $objekNama . ' ' . ($row->kode_coa ?? '') . ' ' . ($row->nama_coa ?? ''));
                            ?>
                            <div class="setting-row" data-search="<?= esc($searchText) ?>">
                                <div class="setting-label">
                                    <strong><?= esc($labelTransaksi . ' - ' . $labelPeran) ?></strong>
                                    <span><?= esc($labelObjek . ' | ' . $objekNama . ' | ' . ($row->kode_jenis_transaksi ?? '-')) ?></span>
                                </div>
                                <input
                                    type="text"
                                    name="mapping_kode[<?= (int) $row->id_mapping_akun ?>]"
                                    class="form-control form-control-sm setting-account-code"
                                    list="coaListMapping"
                                    value="<?= esc($row->kode_coa ?? '') ?>"
                                    data-select-target="<?= esc($selectId) ?>"
                                    placeholder="Kode">
                                <select
                                    name="mapping[<?= (int) $row->id_mapping_akun ?>]"
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
                    <div class="setting-empty">Tidak ada mapping akun yang sesuai filter.</div>
                <?php endif; ?>
            </div>

            <div class="setting-footer">
                <span class="me-auto small text-muted">Total ditampilkan: <?= (int) $rows->count() ?> dari <?= (int) $rows_all->count() ?> mapping</span>
                <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancel</a>
                <button type="submit" class="btn btn-gradient"><i class="bi bi-check-lg me-1"></i>OK</button>
            </div>
        </form>
        <?php endif; ?>
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
