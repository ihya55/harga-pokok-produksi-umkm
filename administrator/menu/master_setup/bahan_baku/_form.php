<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Bahan Baku') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_bahan_baku'])): ?>
                <input type="hidden" name="id_bahan_baku" value="<?= (int) $data_form['id_bahan_baku'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Bahan</label>
                    <input type="text" class="form-control" value="<?= esc($data_form['kode_bahan_baku'] ?? 'Otomatis saat disimpan') ?>" readonly>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama Bahan <span class="text-danger">*</span></label>
                    <input type="text" name="nama_bahan_baku" class="form-control" maxlength="150" required value="<?= esc($data_form['nama_bahan_baku'] ?? '') ?>">
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
                    <label class="form-label fw-semibold">Harga Standar <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="harga_standar_display"
                        id="harga_standar_display"
                        class="form-control text-end"
                        value="<?= esc(number_format((float) ($data_form['harga_standar'] ?? 0), 2, '.', ',')) ?>"
                        required
                    >
                    <input
                        type="hidden"
                        name="harga_standar"
                        id="harga_standar"
                        value="<?= esc(number_format((float) ($data_form['harga_standar'] ?? 0), 2, '.', '')) ?>"
                    >
                </div>

                <div class="col-md-4">
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

                <div class="col-md-8">
                    <label class="form-label fw-semibold">COA Persediaan</label>
                    <select name="id_coa_persediaan" class="form-select">
                        <option value="">- Pilih COA Persediaan -</option>
                        <?php foreach (($coa_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_coa ?>" <?= ((string) ($data_form['id_coa_persediaan'] ?? '') === (string) $item->id_coa) ? 'selected' : '' ?>>
                                <?= esc($item->kode_coa . ' - ' . $item->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Default sistem: 1140 - Persediaan Bahan Baku</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Aktif</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= ((string) ($data_form['status_aktif'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= ((string) ($data_form['status_aktif'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>
                <a href="<?= esc(admin_page_url('master_setup/bahan_baku')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const displayInput = document.getElementById('harga_standar_display');
    const hiddenInput = document.getElementById('harga_standar');

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

    if (displayInput && hiddenInput) {
        displayInput.addEventListener('input', function () {
            const cursorPos = this.selectionStart;
            const raw = this.value.replace(/[^0-9.]/g, '');
            const dotCount = (raw.match(/\./g) || []).length;

            if (dotCount > 1) return;

            this.value = raw;
            syncToHidden();
        });

        displayInput.addEventListener('blur', formatDisplay);

        displayInput.form.addEventListener('submit', function () {
            syncToHidden();
        });
    }
});
</script>