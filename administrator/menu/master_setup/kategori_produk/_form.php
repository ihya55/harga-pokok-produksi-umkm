<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Kategori Produk') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_kategori_produk'])): ?>
                <input type="hidden" name="id_kategori_produk" value="<?= (int) $data_form['id_kategori_produk'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Kategori</label>
                    <input
                        type="text"
                        class="form-control"
                        value="<?= esc($data_form['kode_kategori_produk'] ?? 'Otomatis saat disimpan') ?>"
                        readonly
                    >
                    <div class="form-text">Kode dibuat otomatis oleh sistem.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama Kategori Produk <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="nama_kategori_produk"
                        class="form-control"
                        maxlength="100"
                        required
                        value="<?= esc($data_form['nama_kategori_produk'] ?? '') ?>"
                        placeholder="Contoh: Minuman"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold">Keterangan</label>
                    <textarea
                        name="keterangan"
                        class="form-control"
                        rows="4"
                        placeholder="Keterangan kategori produk"
                    ><?= esc($data_form['keterangan'] ?? '') ?></textarea>
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

                <a href="<?= esc(admin_page_url('master_setup/kategori_produk')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>