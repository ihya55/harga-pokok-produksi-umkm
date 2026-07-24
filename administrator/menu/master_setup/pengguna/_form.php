<?php
$data_form = $data_form ?? [];
$entitas_options = $entitas_options ?? collect();
$role_options = $role_options ?? collect();
$is_edit = (bool) ($is_edit ?? false);
$user_login = user_login();

if (!function_exists('pengguna_form_is_super_admin')) {
    function pengguna_form_is_super_admin(array $user_login): bool
    {
        $username = strtolower(trim((string) ($user_login['username'] ?? '')));
        $role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

        return $username === 'super_admin' || $role_session === 'super_admin';
    }
}

$is_super_admin = pengguna_form_is_super_admin($user_login);
?>

<div class="page-header mb-4">
    <h1 class="page-title"><?= esc($page_title ?? 'Form Pengguna') ?></h1>
    <p class="page-subtitle"><?= esc($page_subtitle ?? '') ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="post" action="<?= esc($form_action) ?>" autocomplete="off">
            <?php if (!empty($data_form['id_pengguna'])): ?>
                <input type="hidden" name="id_pengguna" value="<?= (int) $data_form['id_pengguna'] ?>">
            <?php endif; ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Entitas <span class="text-danger">*</span></label>
                    <select name="id_entitas" class="form-select" required <?= $is_super_admin ? '' : 'readonly' ?>>
                        <?php foreach ($entitas_options as $e): ?>
                            <option value="<?= (int) $e->id_entitas ?>" <?= (int) ($data_form['id_entitas'] ?? 0) === (int) $e->id_entitas ? 'selected' : '' ?>>
                                <?= esc(($e->kode_entitas ?? '-') . ' - ' . ($e->nama_entitas ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!$is_super_admin): ?>
                        <div class="form-text">Entitas dikunci mengikuti entitas login.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                    <select name="id_role" class="form-select" required>
                        <option value="">- Pilih Role -</option>
                        <?php foreach ($role_options as $r): ?>
                            <option value="<?= (int) $r->id_role ?>" <?= (int) ($data_form['id_role'] ?? 0) === (int) $r->id_role ? 'selected' : '' ?>>
                                <?= esc((string) $r->nama_role) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Nama Pengguna <span class="text-danger">*</span></label>
                    <input type="text" name="nama_pengguna" class="form-control" maxlength="100" required value="<?= esc((string) ($data_form['nama_pengguna'] ?? '')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" maxlength="60" required value="<?= esc((string) ($data_form['username'] ?? '')) ?>">
                    <div class="form-text">Username harus unik.</div>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" maxlength="150" required value="<?= esc((string) ($data_form['nama_lengkap'] ?? '')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" maxlength="120" value="<?= esc((string) ($data_form['email'] ?? '')) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">No HP</label>
                    <input type="text" name="no_hp" class="form-control" maxlength="30" value="<?= esc((string) ($data_form['no_hp'] ?? '')) ?>">
                </div>

                <?php if (!$is_edit): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required minlength="6" autocomplete="new-password">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_konfirmasi" class="form-control" required minlength="6" autocomplete="new-password">
                    </div>
                <?php else: ?>
                    <div class="col-md-12">
                        <div class="alert alert-info mb-0">
                            Untuk mengganti password, gunakan tombol <strong>Reset Password</strong> pada daftar pengguna.
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status_aktif" class="form-select">
                        <option value="1" <?= (int) ($data_form['status_aktif'] ?? 1) === 1 ? 'selected' : '' ?>>Aktif</option>
                        <option value="0" <?= (int) ($data_form['status_aktif'] ?? 1) === 0 ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                <a href="<?= esc(admin_page_url('master/pengguna')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i><?= esc($button_label ?? 'Simpan') ?>
                </button>
            </div>
        </form>
    </div>
</div>