<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Pajak') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_pajak'])): ?>
                <input type="hidden" name="id_pajak" value="<?= (int) $data_form['id_pajak'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Kode Pajak <span class="text-danger">*</span></label>
                    <input type="text" name="kode_pajak" class="form-control text-uppercase" maxlength="30" required value="<?= esc($data_form['kode_pajak'] ?? 'PPN') ?>" placeholder="PPN">
                    <div class="form-text">Contoh: PPN.</div>
                </div>

                <div class="col-md-5">
                    <label class="form-label fw-semibold">Nama Pajak <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pajak" class="form-control" maxlength="100" required value="<?= esc($data_form['nama_pajak'] ?? 'Pajak Pertambahan Nilai') ?>" placeholder="Pajak Pertambahan Nilai">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Persentase <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" name="persentase" class="form-control text-end" min="0" max="100" step="0.01" required value="<?= esc((string) ($data_form['persentase'] ?? '11')) ?>">
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= ((string) ($data_form['status_aktif'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= ((string) ($data_form['status_aktif'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jadikan Default</label>
                    <select name="is_default" class="form-select">
                        <option value="1" <?= ((string) ($data_form['is_default'] ?? '1') === '1') ? 'selected' : '' ?>>Ya</option>
                        <option value="0" <?= ((string) ($data_form['is_default'] ?? '1') === '0') ? 'selected' : '' ?>>Tidak</option>
                    </select>
                    <div class="form-text">Jika Ya, pajak lain dengan kode yang sama otomatis bukan default.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Keterangan pajak"><?= esc($data_form['keterangan'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>
                <a href="<?= esc(admin_page_url('master_setup/pajak')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>
