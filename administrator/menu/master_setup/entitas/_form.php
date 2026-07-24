<?php
$data_form = $data_form ?? [];
$is_super_admin = (bool) ($data_form['is_super_admin'] ?? false);
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Entitas') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" enctype="multipart/form-data">
            <?php if (!empty($data_form['id_entitas'])): ?>
                <input type="hidden" name="id_entitas" value="<?= (int) $data_form['id_entitas'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Kode Entitas <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="kode_entitas"
                        class="form-control"
                        maxlength="30"
                        required
                        value="<?= esc((string) ($data_form['kode_entitas'] ?? '')) ?>"
                        placeholder="Contoh: ENT-001"
                        <?= $is_super_admin ? '' : 'readonly' ?>>
                    <?php if (!$is_super_admin): ?>
                        <div class="form-text">Kode entitas hanya bisa diubah oleh super admin.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <label class="form-label fw-semibold">Nama Entitas <span class="text-danger">*</span></label>
                    <input type="text" name="nama_entitas" class="form-control" maxlength="150" required value="<?= esc((string) ($data_form['nama_entitas'] ?? '')) ?>" placeholder="Nama usaha/perusahaan">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama Pemilik</label>
                    <input type="text" name="nama_pemilik" class="form-control" maxlength="150" value="<?= esc((string) ($data_form['nama_pemilik'] ?? '')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Jenis Usaha</label>
                    <input type="text" name="jenis_usaha" class="form-control" maxlength="100" value="<?= esc((string) ($data_form['jenis_usaha'] ?? '')) ?>" placeholder="Contoh: Makanan, Manufaktur, Retail">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">No HP</label>
                    <input type="text" name="no_hp" class="form-control" maxlength="30" value="<?= esc((string) ($data_form['no_hp'] ?? '')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" maxlength="120" value="<?= esc((string) ($data_form['email'] ?? '')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="4" placeholder="Alamat lengkap entitas"><?= esc((string) ($data_form['alamat'] ?? '')) ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Logo UMKM</label>
                    <input type="file" name="logo_umkm" class="form-control" accept="image/png,image/jpeg,image/jpg,image/webp">
                    <div class="form-text">Opsional. Logo tampil di struk thermal dan laporan cetak. Format: JPG, PNG, WEBP.</div>
                    <?php if (!empty($data_form['logo_umkm'])): ?>
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img src="<?= esc(base_url((string) $data_form['logo_umkm'])) ?>" alt="Logo UMKM" style="width:64px;height:64px;object-fit:contain;border:1px solid #e5e7eb;border-radius:12px;background:#fff;padding:6px;">
                            <div class="small text-muted"><?= esc((string) $data_form['logo_umkm']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($is_super_admin): ?>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status_aktif" class="form-select">
                            <option value="1" <?= (int) ($data_form['status_aktif'] ?? 1) === 1 ? 'selected' : '' ?>>Aktif</option>
                            <option value="0" <?= (int) ($data_form['status_aktif'] ?? 1) === 0 ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>
                <?php else: ?>
                    <input type="hidden" name="status_aktif" value="<?= (int) ($data_form['status_aktif'] ?? 1) ?>">
                <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url('master/entitas')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>
            </div>
        </form>
    </div>
</div>