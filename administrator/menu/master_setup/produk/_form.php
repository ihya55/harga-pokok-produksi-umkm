<?php
require_once __DIR__ . '/helpers_produk.php';
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Produk') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" enctype="multipart/form-data">
            <?php if (!empty($data_form['id_produk'])): ?>
                <input type="hidden" name="id_produk" value="<?= (int) $data_form['id_produk'] ?>">
            <?php endif; ?>

            <?php if (!empty($data_form['gambar_produk'])): ?>
                <input type="hidden" name="gambar_produk_lama" value="<?= esc($data_form['gambar_produk']) ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Produk</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['kode_produk'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Barcode</label>
                    <input
                        type="text"
                        name="barcode_produk"
                        class="form-control"
                        maxlength="100"
                        placeholder="Opsional, otomatis jika dikosongkan"
                        value="<?= esc($data_form['barcode_produk'] ?? '') ?>"
                    >
                    <div class="form-text">Boleh dikosongkan. Sistem akan membuat kode barcode otomatis.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="nama_produk" class="form-control" maxlength="150" required value="<?= esc($data_form['nama_produk'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kategori Produk</label>
                    <select name="id_kategori_produk" class="form-select">
                        <option value="">- Pilih Kategori -</option>
                        <?php foreach (($kategori_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_kategori_produk ?>" <?= ((string) ($data_form['id_kategori_produk'] ?? '') === (string) $item->id_kategori_produk) ? 'selected' : '' ?>>
                                <?= esc($item->kode_kategori_produk . ' - ' . $item->nama_kategori_produk) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Satuan <span class="text-danger">*</span></label>
                    <select name="id_satuan" class="form-select" required>
                        <option value="">- Pilih Satuan -</option>
                        <?php foreach (($satuan_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_satuan ?>" <?= ((string) ($data_form['id_satuan'] ?? '') === (string) $item->id_satuan) ? 'selected' : '' ?>>
                                <?= esc($item->nama_satuan) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jenis Produk <span class="text-danger">*</span></label>
                    <select name="jenis_produk" id="jenis_produk" class="form-select" required>
                        <option value="">- Pilih Jenis Produk -</option>
                        <?php foreach (($jenis_produk_options ?? []) as $key => $label): ?>
                            <option value="<?= esc($key) ?>" <?= (($data_form['jenis_produk'] ?? '') === $key) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Harga Jual <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="harga_jual_display"
                        id="harga_jual_display"
                        class="form-control text-end"
                        value="<?= esc(number_format((float) ($data_form['harga_jual'] ?? 0), 2, '.', ',')) ?>"
                        required
                    >
                    <input
                        type="hidden"
                        name="harga_jual"
                        id="harga_jual"
                        value="<?= esc(number_format((float) ($data_form['harga_jual'] ?? 0), 2, '.', '')) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">HPP Standar <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="hpp_standar_display"
                        id="hpp_standar_display"
                        class="form-control text-end"
                        value="<?= esc(number_format((float) ($data_form['hpp_standar'] ?? 0), 2, '.', ',')) ?>"
                        required
                    >
                    <input
                        type="hidden"
                        name="hpp_standar"
                        id="hpp_standar"
                        value="<?= esc(number_format((float) ($data_form['hpp_standar'] ?? 0), 2, '.', '')) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Stok Minimum <span class="text-danger">*</span></label>
                    <input
                        type="number"
                        name="stok_minimum"
                        class="form-control"
                        min="0"
                        step="1"
                        required
                        value="<?= esc((string) ($data_form['stok_minimum'] ?? '0')) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Berat Bersih</label>
                    <input type="number" name="berat_bersih" class="form-control" min="0" step="0.001" value="<?= esc((string) ($data_form['berat_bersih'] ?? '')) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Satuan Berat Bersih</label>
                    <select name="satuan_berat_bersih" class="form-select">
                        <option value="gram" <?= (($data_form['satuan_berat_bersih'] ?? 'gram') === 'gram') ? 'selected' : '' ?>>gram</option>
                        <option value="kg" <?= (($data_form['satuan_berat_bersih'] ?? 'gram') === 'kg') ? 'selected' : '' ?>>kg</option>
                    </select>
                </div>

                <div class="col-md-9">
                    <label class="form-label fw-semibold">Gambar Produk (jpg/jpeg)</label>
                    <input type="file" name="gambar_produk" class="form-control" accept=".jpg,.jpeg,image/jpeg">
                    <?php if (!empty($data_form['gambar_produk'])): ?>
                        <div class="form-text">File saat ini: <?= esc($data_form['gambar_produk']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">COA Penjualan</label>
                    <select name="id_coa_penjualan" id="id_coa_penjualan" class="form-select">
                        <option value="">- Pilih COA Penjualan -</option>
                        <?php foreach (($coa_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_coa ?>" <?= ((string) ($data_form['id_coa_penjualan'] ?? '') === (string) $item->id_coa) ? 'selected' : '' ?>>
                                <?= esc($item->kode_coa . ' - ' . $item->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">COA HPP</label>
                    <select name="id_coa_hpp" id="id_coa_hpp" class="form-select">
                        <option value="">- Pilih COA HPP -</option>
                        <?php foreach (($coa_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_coa ?>" <?= ((string) ($data_form['id_coa_hpp'] ?? '') === (string) $item->id_coa) ? 'selected' : '' ?>>
                                <?= esc($item->kode_coa . ' - ' . $item->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">COA Persediaan</label>
                    <select name="id_coa_persediaan" id="id_coa_persediaan" class="form-select">
                        <option value="">- Pilih COA Persediaan -</option>
                        <?php foreach (($coa_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_coa ?>" <?= ((string) ($data_form['id_coa_persediaan'] ?? '') === (string) $item->id_coa) ? 'selected' : '' ?>>
                                <?= esc($item->kode_coa . ' - ' . $item->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Produk</label>
                    <select name="status_produk" class="form-select">
                        <option value="1" <?= ((string) ($data_form['status_produk'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= ((string) ($data_form['status_produk'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>
                <a href="<?= esc(admin_page_url('master_setup/produk')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const jenisProduk = document.getElementById('jenis_produk');
    const coaPenjualan = document.getElementById('id_coa_penjualan');
    const coaHpp = document.getElementById('id_coa_hpp');
    const coaPersediaan = document.getElementById('id_coa_persediaan');
    const defaultCoa = <?= json_encode($default_coa_produk ?? [], JSON_UNESCAPED_UNICODE) ?>;

    function applyDefaultCoa() {
        const jenis = jenisProduk.value;
        if (!defaultCoa[jenis]) return;

        coaPenjualan.value = defaultCoa[jenis].id_coa_penjualan ?? '';
        coaHpp.value = defaultCoa[jenis].id_coa_hpp ?? '';
        coaPersediaan.value = defaultCoa[jenis].id_coa_persediaan ?? '';
    }

    if (jenisProduk) {
        jenisProduk.addEventListener('change', applyDefaultCoa);
    }

    function bindMoneyFormatter(displayId, hiddenId) {
        const displayInput = document.getElementById(displayId);
        const hiddenInput = document.getElementById(hiddenId);

        if (!displayInput || !hiddenInput) return;

        function normalizeNumber(value) {
            value = String(value || '').replace(/,/g, '');
            if (value === '' || isNaN(value)) {
                return '0.00';
            }
            return parseFloat(value).toFixed(2);
        }

        function formatNumber(value) {
            const num = normalizeNumber(value);
            const parts = num.split('.');
            parts[0] = Number(parts[0]).toLocaleString('en-US');
            return parts.join('.');
        }

        function syncToHidden() {
            hiddenInput.value = normalizeNumber(displayInput.value);
        }

        function formatDisplay() {
            displayInput.value = formatNumber(displayInput.value);
            syncToHidden();
        }

        displayInput.addEventListener('input', function () {
            const raw = this.value.replace(/[^0-9.]/g, '');
            const dotCount = (raw.match(/\./g) || []).length;
            if (dotCount > 1) return;
            this.value = raw;
            syncToHidden();
        });

        displayInput.addEventListener('blur', formatDisplay);

        if (displayInput.form) {
            displayInput.form.addEventListener('submit', function () {
                syncToHidden();
            });
        }
    }

    bindMoneyFormatter('harga_jual_display', 'harga_jual');
    bindMoneyFormatter('hpp_standar_display', 'hpp_standar');
});
</script>