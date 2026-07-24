<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('role_detail_is_super_admin')) {
    function role_detail_is_super_admin(array $user_login): bool
    {
        $username = strtolower(trim((string) ($user_login['username'] ?? '')));
        $nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

        if ($username === 'super_admin' || $nama_role_session === 'super_admin') {
            return true;
        }

        $id_role = (int) ($user_login['id_role'] ?? 0);

        if ($id_role > 0) {
            $role = Capsule::table('tb_role')
                ->where('id_role', $id_role)
                ->first();

            if ($role) {
                return strtolower(trim((string) $role->nama_role)) === 'super_admin';
            }
        }

        return false;
    }
}

if (!role_detail_is_super_admin(user_login())) {
    set_flash('error', 'Menu Role hanya boleh diakses oleh super admin.');
    redirect_admin('dashboard');
}

$id_role = (int) ($_GET['id'] ?? 0);

$row = Capsule::table('tb_role')
    ->where('id_role', $id_role)
    ->first();

if (!$row) {
    ?>
    <div class="alert alert-danger">Data role tidak ditemukan.</div>
    <a href="<?= esc(admin_page_url('master/role')) ?>" class="btn btn-outline-secondary">Kembali</a>
    <?php
    return;
}

$hak_akses = Capsule::table('tb_role_menu as rm')
    ->join('tb_menu as m', 'm.id_menu', '=', 'rm.id_menu')
    ->where('rm.id_role', $id_role)
    ->where('rm.status_aktif', 1)
    ->select([
        'rm.*',
        'm.kode_menu',
        'm.nama_menu',
        'm.url',
        'm.jenis_menu',
        'm.id_menu_induk',
        'm.urutan',
        'm.tingkat_menu',
    ])
    ->orderBy('m.urutan', 'asc')
    ->orderBy('m.id_menu', 'asc')
    ->get();

$jumlah_pengguna = (int) Capsule::table('tb_pengguna')
    ->where('id_role', $id_role)
    ->count();

$jumlah_akses_lihat = (int) Capsule::table('tb_role_menu')
    ->where('id_role', $id_role)
    ->where('boleh_lihat', 1)
    ->where('status_aktif', 1)
    ->count();

$is_super_role = strtolower((string) $row->nama_role) === 'super_admin';
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Role</h1>
            <p class="page-subtitle">Informasi role dan daftar hak akses menu.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc(admin_page_url('master/role')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if (!$is_super_role): ?>
                <a href="<?= esc(admin_page_url('master/role/edit') . '&id=' . (int) $row->id_role) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Nama Role</div>
                <div class="h4 mb-0"><?= esc((string) $row->nama_role) ?></div>
                <?php if ($is_super_role): ?>
                    <span class="badge bg-danger mt-2">System Role</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pengguna</div>
                <div class="h4 mb-0"><?= number_format($jumlah_pengguna, 0, '.', ',') ?></div>
                <div class="text-muted small">Memakai role ini</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Menu Bisa Dilihat</div>
                <div class="h4 mb-0"><?= number_format($jumlah_akses_lihat, 0, '.', ',') ?></div>
                <div class="text-muted small">Hak akses lihat aktif</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Informasi Role</h2>

        <table class="table table-sm table-borderless align-middle mb-0">
            <tr>
                <th width="180" class="text-muted">Nama Role</th>
                <td class="fw-semibold"><?= esc((string) $row->nama_role) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Keterangan</th>
                <td><?= esc((string) ($row->keterangan ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Tanggal Dibuat</th>
                <td><?= esc($row->tanggal_dibuat ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?></td>
            </tr>
            <tr>
                <th class="text-muted">Tanggal Diubah</th>
                <td><?= esc($row->tanggal_diubah ? date('d/m/Y H:i', strtotime((string) $row->tanggal_diubah)) : '-') ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Hak Akses Menu</h2>

        <div class="table-responsive border rounded">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Menu</th>
                        <th class="text-center">Lihat</th>
                        <th class="text-center">Tambah</th>
                        <th class="text-center">Ubah</th>
                        <th class="text-center">Hapus</th>
                        <th class="text-center">Posting</th>
                        <th class="text-center">Approve</th>
                        <th class="text-center">Cetak</th>
                        <th class="text-center">Export</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($hak_akses->count() === 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">Hak akses belum tersedia.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hak_akses as $a): ?>
                            <tr>
                                <td>
                                    <div class="<?= (int) $a->tingkat_menu === 1 ? 'fw-bold' : 'ps-3' ?>">
                                        <?= (int) $a->tingkat_menu > 1 ? '<i class="bi bi-arrow-return-right me-1 text-muted"></i>' : '' ?>
                                        <?= esc((string) $a->nama_menu) ?>
                                    </div>
                                    <div class="text-muted small">
                                        <?= esc((string) $a->kode_menu) ?>
                                        <?php if (!empty($a->url)): ?>
                                            · <?= esc((string) $a->url) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <?php foreach (['boleh_lihat', 'boleh_tambah', 'boleh_ubah', 'boleh_hapus', 'boleh_posting', 'boleh_approve', 'boleh_cetak', 'boleh_export'] as $field): ?>
                                    <td class="text-center">
                                        <?= ((int) $a->{$field} === 1)
                                            ? '<span class="badge bg-success">Ya</span>'
                                            : '<span class="badge bg-light text-muted">-</span>' ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>