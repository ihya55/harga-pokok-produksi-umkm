<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_pengguna = (int) ($user['id_pengguna'] ?? 0);

if ($id_pengguna <= 0) {
    set_flash('error', 'Sesi pengguna tidak valid. Silakan login ulang.');
    redirect_admin('dashboard');
}

$row = Capsule::table('tb_pengguna')
    ->where('id_pengguna', $id_pengguna)
    ->first();

if (!$row) {
    set_flash('error', 'Data pengguna tidak ditemukan.');
    redirect_admin('dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama = (string) ($_POST['password_lama'] ?? '');
    $password_baru = (string) ($_POST['password_baru'] ?? '');
    $password_konfirmasi = (string) ($_POST['password_konfirmasi'] ?? '');

    if ($password_lama === '' || $password_baru === '' || $password_konfirmasi === '') {
        set_flash('error', 'Semua field password wajib diisi.');
        redirect_admin('ganti-password');
    }

    if (!password_verify($password_lama, (string) $row->password_hash)) {
        set_flash('error', 'Password lama salah.');
        redirect_admin('ganti-password');
    }

    if (strlen($password_baru) < 6) {
        set_flash('error', 'Password baru minimal 6 karakter.');
        redirect_admin('ganti-password');
    }

    if ($password_baru !== $password_konfirmasi) {
        set_flash('error', 'Konfirmasi password baru tidak sama.');
        redirect_admin('ganti-password');
    }

    if (password_verify($password_baru, (string) $row->password_hash)) {
        set_flash('error', 'Password baru tidak boleh sama dengan password lama.');
        redirect_admin('ganti-password');
    }

    Capsule::table('tb_pengguna')
        ->where('id_pengguna', $id_pengguna)
        ->update([
            'password_hash' => password_hash($password_baru, PASSWORD_DEFAULT),
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh' => $id_pengguna,
        ]);

    set_flash('success', 'Password berhasil diganti.');
    redirect_admin('ganti-password');
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Ganti Password</h1>
    <p class="page-subtitle">Perbarui password akun login kamu secara aman</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-1"></i>
                    Gunakan password minimal 6 karakter dan jangan gunakan password lama.
                </div>

                <form method="post" action="<?= esc(admin_page_url('ganti-password')) ?>" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Lama <span class="text-danger">*</span></label>
                        <input type="password" name="password_lama" class="form-control" required autocomplete="current-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password_baru" class="form-control" required minlength="6" autocomplete="new-password">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
                        <input type="password" name="password_konfirmasi" class="form-control" required minlength="6" autocomplete="new-password">
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                        <a href="<?= esc(admin_page_url('dashboard')) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>

                        <button type="submit" class="btn btn-primary" onclick="return confirm('Ganti password sekarang?')">
                            <i class="bi bi-shield-lock me-1"></i>Ganti Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>