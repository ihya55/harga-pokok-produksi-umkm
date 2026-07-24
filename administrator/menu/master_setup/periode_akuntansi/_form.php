<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Periode Akuntansi') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>">
            <?php if (!empty($data_form['id_periode'])): ?>
                <input type="hidden" name="id_periode" value="<?= (int) $data_form['id_periode'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tahun <span class="text-danger">*</span></label>
                    <input type="number" name="tahun" class="form-control" min="2000" max="2100" required value="<?= esc((string) ($data_form['tahun'] ?? date('Y'))) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Bulan <span class="text-danger">*</span></label>
                    <select name="bulan" class="form-select" required>
                        <option value="">- Pilih Bulan -</option>
                        <?php
                        $bulanList = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ];
                        ?>
                        <?php foreach ($bulanList as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ((string) ($data_form['bulan'] ?? '') === (string) $key) ? 'selected' : '' ?>>
                                <?= esc($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_mulai" class="form-control" required value="<?= esc($data_form['tanggal_mulai'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Tanggal Selesai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_selesai" class="form-control" required value="<?= esc($data_form['tanggal_selesai'] ?? '') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status Periode</label>
                    <select name="status_periode" class="form-select">
                        <option value="terbuka" <?= (($data_form['status_periode'] ?? 'terbuka') === 'terbuka') ? 'selected' : '' ?>>terbuka</option>
                        <option value="tertutup" <?= (($data_form['status_periode'] ?? 'terbuka') === 'tertutup') ? 'selected' : '' ?>>tertutup</option>
                    </select>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-gradient">
                    <i class="bi bi-check-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>

                <a href="<?= esc(admin_page_url('master_setup/periode_akuntansi')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </form>
    </div>
</div>