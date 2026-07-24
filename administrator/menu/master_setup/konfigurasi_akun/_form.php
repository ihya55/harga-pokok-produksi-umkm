<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Konfigurasi Akun') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_konfigurasi_akun'])): ?>
                <input type="hidden" name="id_konfigurasi_akun" value="<?= (int) $data_form['id_konfigurasi_akun'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Konfigurasi <span class="text-danger">*</span></label>
                    <input type="text" name="kode_konfigurasi" class="form-control" maxlength="100" required value="<?= esc($data_form['kode_konfigurasi'] ?? '') ?>" placeholder="Contoh: persediaan_bahan_baku">
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama Konfigurasi <span class="text-danger">*</span></label>
                    <input type="text" name="nama_konfigurasi" class="form-control" maxlength="150" required value="<?= esc($data_form['nama_konfigurasi'] ?? '') ?>" placeholder="Contoh: Default Persediaan Bahan Baku">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Digunakan di Menu / Modul</label>
                    <input
                        type="text"
                        name="digunakan_di_menu"
                        class="form-control"
                        maxlength="150"
                        value="<?= esc($data_form['digunakan_di_menu'] ?? '') ?>"
                        placeholder="Contoh: Modul Bahan Baku"
                    >
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">COA <span class="text-danger">*</span></label>
                    <select name="id_coa" class="form-select" required>
                        <option value="">- Pilih COA -</option>
                        <?php foreach (($coa_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_coa ?>" <?= ((string) ($data_form['id_coa'] ?? '') === (string) $item->id_coa) ? 'selected' : '' ?>>
                                <?= esc($item->kode_coa . ' - ' . $item->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Aktif</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= ((string) ($data_form['status_aktif'] ?? '1') === '1') ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= ((string) ($data_form['status_aktif'] ?? '1') === '0') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="3" maxlength="255"><?= esc($data_form['keterangan'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>
                <a href="<?= esc(admin_page_url('master_setup/konfigurasi_akun')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>