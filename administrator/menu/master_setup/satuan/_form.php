<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Satuan') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_satuan'])): ?>
                <input type="hidden" name="id_satuan" value="<?= (int) $data_form['id_satuan'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama Satuan <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nama_satuan"
                        class="form-control"
                        maxlength="50"
                        required
                        value="<?= esc($data_form['nama_satuan'] ?? '') ?>"
                        placeholder="Contoh: pcs, kg, gram"
                    >
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="4"
                        placeholder="Keterangan tambahan jika ada"
                    ><?= esc($data_form['keterangan'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>

                <a href="<?= esc(admin_page_url('master_setup/satuan')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>