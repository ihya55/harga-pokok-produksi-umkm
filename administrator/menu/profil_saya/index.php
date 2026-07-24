<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_pengguna = (int) ($user['id_pengguna'] ?? 0);

if ($id_pengguna <= 0) {
    set_flash('error', 'Sesi pengguna tidak valid. Silakan login ulang.');
    redirect_admin('dashboard');
}

$row = Capsule::table('tb_pengguna as p')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'p.id_entitas')
    ->leftJoin('tb_role as r', 'r.id_role', '=', 'p.id_role')
    ->where('p.id_pengguna', $id_pengguna)
    ->select([
        'p.*',
        'e.kode_entitas',
        'e.nama_entitas',
        'r.nama_role',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data profil tidak ditemukan.');
    redirect_admin('dashboard');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_pengguna = trim((string) ($_POST['nama_pengguna'] ?? ''));
    $nama_lengkap = trim((string) ($_POST['nama_lengkap'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $no_hp = trim((string) ($_POST['no_hp'] ?? ''));

    if ($nama_pengguna === '' || $nama_lengkap === '') {
        set_flash('error', 'Nama pengguna dan nama lengkap wajib diisi.');
        redirect_admin('profil');
    }

    $emailValue = $email !== '' ? $email : null;

    if ($emailValue !== null && !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
        set_flash('error', 'Format email tidak valid.');
        redirect_admin('profil');
    }

    $cekEmail = false;

    if ($emailValue !== null) {
        $cekEmail = Capsule::table('tb_pengguna')
            ->where('email', $emailValue)
            ->where('id_pengguna', '!=', $id_pengguna)
            ->exists();
    }

    if ($cekEmail) {
        set_flash('error', 'Email sudah digunakan pengguna lain.');
        redirect_admin('profil');
    }

    Capsule::table('tb_pengguna')
        ->where('id_pengguna', $id_pengguna)
        ->update([
            'nama_pengguna' => $nama_pengguna,
            'nama_lengkap' => $nama_lengkap,
            'email' => $emailValue,
            'no_hp' => $no_hp !== '' ? $no_hp : null,
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh' => $id_pengguna,
        ]);

    $_SESSION['user_login']['nama_lengkap'] = $nama_lengkap;

    set_flash('success', 'Profil berhasil diperbarui.');
    redirect_admin('profil');
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Profil Saya</h1>
    <p class="page-subtitle">Kelola informasi dasar akun pengguna yang sedang login</p>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center mb-3" style="width:84px;height:84px;font-size:34px;">
                    <i class="bi bi-person"></i>
                </div>

                <h2 class="h5 mb-1"><?= esc((string) $row->nama_lengkap) ?></h2>
                <div class="text-muted mb-3">@<?= esc((string) $row->username) ?></div>

                <div class="d-grid gap-2 text-start">
                    <div class="border rounded p-3">
                        <div class="text-muted small">Entitas Aktif</div>
                        <div class="fw-semibold"><?= esc(($row->kode_entitas ?? '-') . ' - ' . ($row->nama_entitas ?? '-')) ?></div>
                    </div>

                    <div class="border rounded p-3">
                        <div class="text-muted small">Role</div>
                        <div class="fw-semibold"><?= esc((string) ($row->nama_role ?? '-')) ?></div>
                    </div>

                    <div class="border rounded p-3">
                        <div class="text-muted small">Terakhir Login</div>
                        <div class="fw-semibold"><?= esc($row->terakhir_login ? date('d/m/Y H:i', strtotime((string) $row->terakhir_login)) : '-') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Edit Profil</h2>

                <form method="post" action="<?= esc(admin_page_url('profil')) ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Username</label>
                            <input type="text" class="form-control" value="<?= esc((string) $row->username) ?>" readonly>
                            <div class="form-text">Username tidak diubah dari halaman profil.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Pengguna <span class="text-danger">*</span></label>
                            <input type="text" name="nama_pengguna" class="form-control" maxlength="100" required value="<?= esc((string) $row->nama_pengguna) ?>">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control" maxlength="150" required value="<?= esc((string) $row->nama_lengkap) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" maxlength="120" value="<?= esc((string) ($row->email ?? '')) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">No HP</label>
                            <input type="text" name="no_hp" class="form-control" maxlength="30" value="<?= esc((string) ($row->no_hp ?? '')) ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center gap-2 mt-4">
                        <a href="<?= esc(admin_page_url('dashboard')) ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Kembali
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>Simpan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>