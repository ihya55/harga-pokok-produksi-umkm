<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Pemasok') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_pemasok'])): ?>
                <input type="hidden" name="id_pemasok" value="<?= (int) $data_form['id_pemasok'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Pemasok</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc($data_form['kode_pemasok'] ?? 'Otomatis saat disimpan') ?>"
                        readonly
                    >
                    <div class="form-text">Kode dibuat otomatis oleh sistem.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama Pemasok <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nama_pemasok"
                        class="form-control"
                        maxlength="150"
                        required
                        value="<?= esc($data_form['nama_pemasok'] ?? '') ?>"
                        placeholder="Contoh: CV Sumber Makmur"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">No. HP</label>
                    <input
                        type="text"
                        name="no_hp"
                        class="form-control"
                        maxlength="30"
                        value="<?= esc($data_form['no_hp'] ?? '') ?>"
                        placeholder="Contoh: 08123456789"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Email</label>
                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        maxlength="120"
                        value="<?= esc($data_form['email'] ?? '') ?>"
                        placeholder="Contoh: pemasok@email.com"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Tempo (Hari)</label>
                    <input
                        type="number"
                        name="tempo_hari"
                        class="form-control"
                        min="0"
                        step="1"
                        value="<?= esc((string) ($data_form['tempo_hari'] ?? '0')) ?>"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Aktif</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= ((string) ($data_form['status_aktif'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= ((string) ($data_form['status_aktif'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Alamat</label>
                    <textarea
                        name="alamat"
                        class="form-control"
                        rows="4"
                        placeholder="Alamat pemasok"
                    ><?= esc($data_form['alamat'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>

                <a href="<?= esc(admin_page_url('master_setup/pemasok')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>