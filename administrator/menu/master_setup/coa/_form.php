<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form COA') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_coa'])): ?>
                <input type="hidden" name="id_coa" value="<?= (int) $data_form['id_coa'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode COA <span class="text-danger">*</span></label>
                    <input type="text" name="kode_coa" class="form-control" maxlength="30" required value="<?= esc($data_form['kode_coa'] ?? '') ?>" placeholder="Contoh: 1101">
                    <div class="form-text">Kode COA diisi manual sesuai keputusan sistem.</div>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama COA <span class="text-danger">*</span></label>
                    <input type="text" name="nama_coa" class="form-control" maxlength="150" required value="<?= esc($data_form['nama_coa'] ?? '') ?>" placeholder="Contoh: Kas">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">COA Induk</label>
                    <select name="id_coa_induk" class="form-select">
                        <option value="">- Tidak Ada -</option>
                        <?php foreach (($coa_induk_options ?? []) as $item): ?>
                            <option value="<?= (int) $item->id_coa ?>" <?= ((string) ($data_form['id_coa_induk'] ?? '') === (string) $item->id_coa) ? 'selected' : '' ?>>
                                <?= esc($item->kode_coa . ' - ' . $item->nama_coa) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Level COA <span class="text-danger">*</span></label>
                    <input type="number" name="level_coa" class="form-control" min="1" step="1" required value="<?= esc((string) ($data_form['level_coa'] ?? '1')) ?>">
                </div>

                <div class="col-md-4">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Kategori COA <span class="text-danger">*</span></label>
                        <select name="kategori_coa" class="form-select" required>
                            <option value="">- Pilih Kategori -</option>
                            <option value="aset" <?= (($data_form['kategori_coa'] ?? '') === 'aset') ? 'selected' : '' ?>>aset</option>
                            <option value="liabilitas" <?= (($data_form['kategori_coa'] ?? '') === 'liabilitas') ? 'selected' : '' ?>>liabilitas</option>
                            <option value="ekuitas" <?= (($data_form['kategori_coa'] ?? '') === 'ekuitas') ? 'selected' : '' ?>>ekuitas</option>
                            <option value="pendapatan" <?= (($data_form['kategori_coa'] ?? '') === 'pendapatan') ? 'selected' : '' ?>>pendapatan</option>
                            <option value="hpp" <?= (($data_form['kategori_coa'] ?? '') === 'hpp') ? 'selected' : '' ?>>hpp</option>
                            <option value="beban" <?= (($data_form['kategori_coa'] ?? '') === 'beban') ? 'selected' : '' ?>>beban</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Posisi Saldo Normal <span class="text-danger">*</span></label>
                    <select name="posisi_saldo_normal" class="form-select" required>
                        <option value="debit" <?= (($data_form['posisi_saldo_normal'] ?? '') === 'debit') ? 'selected' : '' ?>>debit</option>
                        <option value="kredit" <?= (($data_form['posisi_saldo_normal'] ?? '') === 'kredit') ? 'selected' : '' ?>>kredit</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Boleh Transaksi</label>
                    <select name="boleh_transaksi" class="form-select">
                        <option value="1" <?= ((string) ($data_form['boleh_transaksi'] ?? '1') === '1') ? 'selected' : '' ?>>Ya</option>
                        <option value="0" <?= ((string) ($data_form['boleh_transaksi'] ?? '1') === '0') ? 'selected' : '' ?>>Tidak</option>
                    </select>
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

                <a href="<?= esc(admin_page_url('master_setup/coa')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>