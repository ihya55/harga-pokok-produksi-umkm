<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers_mapping_akun.php';

/*
|--------------------------------------------------------------------------
| Fallback variabel form
|--------------------------------------------------------------------------
| _form.php idealnya dipanggil dari tambah.php / edit.php.
| Tapi fallback ini dibuat supaya tidak error Undefined variable
| jika variabel belum dikirim dari file pemanggil.
|--------------------------------------------------------------------------
*/
$data_form = $data_form ?? [
    'id_mapping_akun' => '',
    'kode_jenis_transaksi' => '',
    'jenis_objek' => 'global',
    'id_objek' => 0,
    'peran_akun' => '',
    'id_coa' => '',
    'status_aktif' => 1,
];

$is_edit_form = !empty($data_form['id_mapping_akun'] ?? null);

$form_action = $form_action ?? (
    $is_edit_form
        ? admin_url('menu/master_setup/mapping_akun/update.php')
        : admin_url('menu/master_setup/mapping_akun/simpan.php')
);

$page_title = $page_title ?? ($is_edit_form ? 'Edit Mapping Akun' : 'Tambah Mapping Akun');
$page_subtitle = $page_subtitle ?? 'Kelola mapping akun transaksi dan laporan';
$button_label = $button_label ?? ($is_edit_form ? 'Update' : 'Simpan');

$jenisObjekOptions = opsi_jenis_objek_mapping_akun();
$peranAkunOptions = opsi_peran_akun_mapping();
$jenisTransaksiOptions = opsi_jenis_transaksi_mapping_akun();

$coa_options = $coa_options ?? [];
$produk_options = $produk_options ?? [];
$bahan_options = $bahan_options ?? [];
$pelanggan_options = $pelanggan_options ?? [];
$pemasok_options = $pemasok_options ?? [];
$gudang_options = $gudang_options ?? [];
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title) ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle) ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" id="formMappingAkun">
            <?php if (!empty($data_form['id_mapping_akun'])): ?>
                <input type="hidden" name="id_mapping_akun" value="<?= (int) $data_form['id_mapping_akun'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Jenis Transaksi <span class="text-danger">*</span>
                    </label>

                    <select name="kode_jenis_transaksi" class="form-select" required>
                        <option value="">- Pilih Jenis Transaksi -</option>

                        <?php foreach ($jenisTransaksiOptions as $kode => $label): ?>
                            <option value="<?= esc($kode) ?>" <?= (($data_form['kode_jenis_transaksi'] ?? '') === $kode) ? 'selected' : '' ?>>
                                <?= esc($label) ?> (<?= esc($kode) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="form-text">
                        Contoh: ARUS_KAS, FAKTUR_PENJUALAN_TUNAI, PEMBAYARAN_PEMBELIAN.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Jenis Objek <span class="text-danger">*</span>
                    </label>

                    <select name="jenis_objek" id="jenis_objek" class="form-select" required>
                        <?php foreach ($jenisObjekOptions as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= (($data_form['jenis_objek'] ?? 'global') === $key) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="form-text">
                        Pilih Global untuk mapping umum tanpa objek spesifik.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Peran Akun <span class="text-danger">*</span>
                    </label>

                    <select name="peran_akun" class="form-select" required>
                        <option value="">- Pilih Peran Akun -</option>

                        <?php foreach ($peranAkunOptions as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= (($data_form['peran_akun'] ?? '') === $key) ? 'selected' : '' ?>>
                                <?= esc($label) ?> (<?= esc($key) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Objek</label>

                    <div class="objek-global border rounded p-3 bg-light">
                        <div class="fw-semibold">Global / Semua Objek</div>
                        <div class="text-muted small">
                            Mapping berlaku umum untuk entitas ini. ID objek otomatis disimpan 0.
                        </div>
                    </div>

                    <div class="objek-select-wrap mt-2" data-jenis="produk" style="display:none;">
                        <select name="id_objek_produk" class="form-select objek-select">
                            <option value="">- Pilih Produk -</option>

                            <?php foreach ($produk_options as $item): ?>
                                <option value="<?= (int) $item->id_produk ?>" <?= (($data_form['jenis_objek'] ?? '') === 'produk' && (int) ($data_form['id_objek'] ?? 0) === (int) $item->id_produk) ? 'selected' : '' ?>>
                                    <?= esc(($item->kode_produk ?? '-') . ' - ' . ($item->nama_produk ?? '-')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="objek-select-wrap mt-2" data-jenis="bahan_baku" style="display:none;">
                        <select name="id_objek_bahan_baku" class="form-select objek-select">
                            <option value="">- Pilih Bahan Baku -</option>

                            <?php foreach ($bahan_options as $item): ?>
                                <option value="<?= (int) $item->id_bahan_baku ?>" <?= (($data_form['jenis_objek'] ?? '') === 'bahan_baku' && (int) ($data_form['id_objek'] ?? 0) === (int) $item->id_bahan_baku) ? 'selected' : '' ?>>
                                    <?= esc(($item->kode_bahan_baku ?? '-') . ' - ' . ($item->nama_bahan_baku ?? '-')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="objek-select-wrap mt-2" data-jenis="pelanggan" style="display:none;">
                        <select name="id_objek_pelanggan" class="form-select objek-select">
                            <option value="">- Pilih Pelanggan -</option>

                            <?php foreach ($pelanggan_options as $item): ?>
                                <option value="<?= (int) $item->id_pelanggan ?>" <?= (($data_form['jenis_objek'] ?? '') === 'pelanggan' && (int) ($data_form['id_objek'] ?? 0) === (int) $item->id_pelanggan) ? 'selected' : '' ?>>
                                    <?= esc(($item->kode_pelanggan ?? '-') . ' - ' . ($item->nama_pelanggan ?? '-')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="objek-select-wrap mt-2" data-jenis="pemasok" style="display:none;">
                        <select name="id_objek_pemasok" class="form-select objek-select">
                            <option value="">- Pilih Pemasok -</option>

                            <?php foreach ($pemasok_options as $item): ?>
                                <option value="<?= (int) $item->id_pemasok ?>" <?= (($data_form['jenis_objek'] ?? '') === 'pemasok' && (int) ($data_form['id_objek'] ?? 0) === (int) $item->id_pemasok) ? 'selected' : '' ?>>
                                    <?= esc(($item->kode_pemasok ?? '-') . ' - ' . ($item->nama_pemasok ?? '-')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="objek-select-wrap mt-2" data-jenis="gudang" style="display:none;">
                        <select name="id_objek_gudang" class="form-select objek-select">
                            <option value="">- Pilih Gudang -</option>

                            <?php foreach ($gudang_options as $item): ?>
                                <option value="<?= (int) $item->id_gudang ?>" <?= (($data_form['jenis_objek'] ?? '') === 'gudang' && (int) ($data_form['id_objek'] ?? 0) === (int) $item->id_gudang) ? 'selected' : '' ?>>
                                    <?= esc(($item->kode_gudang ?? '-') . ' - ' . ($item->nama_gudang ?? '-')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">
                        COA <span class="text-danger">*</span>
                    </label>

                    <select name="id_coa" class="form-select" required>
                        <option value="">- Pilih COA -</option>

                        <?php foreach ($coa_options as $coa): ?>
                            <option value="<?= (int) $coa->id_coa ?>" <?= ((int) ($data_form['id_coa'] ?? 0) === (int) $coa->id_coa) ? 'selected' : '' ?>>
                                <?= esc(($coa->kode_coa ?? '-') . ' - ' . ($coa->nama_coa ?? '-') . ' [' . ($coa->kategori_coa ?? '-') . ']') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="form-text">
                        Hanya COA aktif dan boleh transaksi yang ditampilkan.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">
                        Status <span class="text-danger">*</span>
                    </label>

                    <select name="status_aktif" class="form-select" required>
                        <option value="1" <?= ((int) ($data_form['status_aktif'] ?? 1) === 1) ? 'selected' : '' ?>>
                            Aktif
                        </option>
                        <option value="0" <?= ((int) ($data_form['status_aktif'] ?? 1) === 0) ? 'selected' : '' ?>>
                            Nonaktif
                        </option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url('master_setup/mapping_akun')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check2-circle me-1"></i><?= esc($button_label) ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const jenisObjek = document.getElementById('jenis_objek');
    const globalBox = document.querySelector('.objek-global');
    const wraps = document.querySelectorAll('.objek-select-wrap');
    const form = document.getElementById('formMappingAkun');

    function refreshObjek() {
        const value = jenisObjek.value;

        wraps.forEach(function (wrap) {
            const isActive = wrap.dataset.jenis === value;
            wrap.style.display = isActive ? '' : 'none';

            wrap.querySelectorAll('select').forEach(function (select) {
                select.required = isActive && value !== 'global';

                if (!isActive) {
                    select.value = '';
                }
            });
        });

        if (globalBox) {
            globalBox.style.display = value === 'global' ? '' : 'none';
        }
    }

    if (jenisObjek) {
        jenisObjek.addEventListener('change', refreshObjek);
        refreshObjek();
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            const jenis = jenisObjek ? jenisObjek.value : 'global';

            if (jenis !== 'global') {
                const activeWrap = document.querySelector('.objek-select-wrap[data-jenis="' + jenis + '"]');
                const select = activeWrap ? activeWrap.querySelector('select') : null;

                if (!select || !select.value) {
                    e.preventDefault();
                    alert('Objek wajib dipilih untuk jenis objek selain Global.');
                    return false;
                }
            }
        });
    }
})();
</script>