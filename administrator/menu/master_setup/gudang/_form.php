<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Gudang') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_gudang'])): ?>
                <input type="hidden" name="id_gudang" value="<?= (int) $data_form['id_gudang'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Gudang</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc($data_form['kode_gudang'] ?? 'Otomatis saat disimpan') ?>"
                        readonly
                    >
                    <div class="form-text">Kode dibuat otomatis oleh sistem.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Nama Gudang <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nama_gudang"
                        class="form-control"
                        maxlength="100"
                        required
                        value="<?= esc($data_form['nama_gudang'] ?? '') ?>"
                        placeholder="Contoh: Gudang Bahan Baku"
                    >
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Jenis Gudang <span class="text-danger">*</span></label>
                    <select name="jenis_gudang" class="form-select" required>
                        <option value="">-- Pilih Jenis Gudang --</option>
                        <?php
                        $jenisOptions = [
                            'bahan_baku' => 'Bahan Baku',
                            'barang_jadi' => 'Barang Jadi',
                            'toko' => 'Toko',
                            'lainnya' => 'Lainnya',
                        ];
                        foreach ($jenisOptions as $value => $label):
                        ?>
                            <option value="<?= esc($value) ?>" <?= (($data_form['jenis_gudang'] ?? '') === $value) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Alamat</label>
                    <textarea
                        name="alamat"
                        class="form-control"
                        rows="4"
                        placeholder="Alamat gudang"
                    ><?= esc($data_form['alamat'] ?? '') ?></textarea>
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

                <a href="<?= esc(admin_page_url('master_setup/gudang')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>