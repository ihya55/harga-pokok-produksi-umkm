<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = user_login();
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

if (!function_exists('pengguna_detail_is_super_admin')) {
    function pengguna_detail_is_super_admin(array $user_login): bool
    {
        $username = strtolower(trim((string) ($user_login['username'] ?? '')));
        $role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

        if ($username === 'super_admin' || $role_session === 'super_admin') {
            return true;
        }

        $id_role = (int) ($user_login['id_role'] ?? 0);
        $role = $id_role > 0 ? Capsule::table('tb_role')->where('id_role', $id_role)->first() : null;

        return $role && strtolower((string) $role->nama_role) === 'super_admin';
    }
}

$is_super_admin = pengguna_detail_is_super_admin($user_login);
$id_pengguna = (int) ($_GET['id'] ?? 0);

$query = Capsule::table('tb_pengguna as p')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'p.id_entitas')
    ->leftJoin('tb_role as r', 'r.id_role', '=', 'p.id_role')
    ->where('p.id_pengguna', $id_pengguna);

if (!$is_super_admin) {
    $query->where('p.id_entitas', $id_entitas_login)
        ->where('r.nama_role', '<>', 'super_admin');
}

$row = $query->select([
    'p.*',
    'e.kode_entitas',
    'e.nama_entitas',
    'r.nama_role',
    'r.keterangan as keterangan_role',
])->first();

if (!$row) {
    ?>
    <div class="alert alert-danger">Data pengguna tidak ditemukan atau kamu tidak punya akses.</div>
    <a href="<?= esc(admin_page_url('master/pengguna')) ?>" class="btn btn-outline-secondary">Kembali</a>
    <?php
    return;
}

$akses_entitas = Capsule::table('tb_pengguna_entitas as pe')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pe.id_entitas')
    ->leftJoin('tb_role as r', 'r.id_role', '=', 'pe.id_role')
    ->where('pe.id_pengguna', (int) $row->id_pengguna)
    ->select([
        'pe.*',
        'e.kode_entitas',
        'e.nama_entitas',
        'r.nama_role',
    ])
    ->orderBy('pe.entitas_default', 'desc')
    ->orderBy('e.nama_entitas', 'asc')
    ->get();
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Pengguna</h1>
            <p class="page-subtitle">Informasi akun, role, entitas, dan status login.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc(admin_page_url('master/pengguna')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('master/pengguna/edit') . '&id=' . (int) $row->id_pengguna) ?>" class="btn btn-primary">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Nama Lengkap</div>
                <div class="h5 mb-1"><?= esc((string) $row->nama_lengkap) ?></div>
                <div class="text-muted">@<?= esc((string) $row->username) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Role</div>
                <div class="h5 mb-1"><?= esc((string) ($row->nama_role ?? '-')) ?></div>
                <div class="text-muted"><?= esc((string) ($row->keterangan_role ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Status</div>
                <div class="h5 mb-1">
                    <?= ((int) $row->status_aktif === 1)
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Nonaktif</span>' ?>
                </div>
                <div class="text-muted">Login terakhir: <?= esc($row->terakhir_login ? date('d/m/Y H:i', strtotime((string) $row->terakhir_login)) : '-') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Informasi Pengguna</h2>

        <table class="table table-sm table-borderless align-middle mb-0">
            <tr>
                <th width="180" class="text-muted">Nama Pengguna</th>
                <td><?= esc((string) $row->nama_pengguna) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Username</th>
                <td class="fw-semibold">@<?= esc((string) $row->username) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Nama Lengkap</th>
                <td><?= esc((string) $row->nama_lengkap) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Email</th>
                <td><?= esc((string) ($row->email ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">No HP</th>
                <td><?= esc((string) ($row->no_hp ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Entitas Utama</th>
                <td><?= esc(($row->kode_entitas ?? '-') . ' - ' . ($row->nama_entitas ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Role Utama</th>
                <td><?= esc((string) ($row->nama_role ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Dibuat</th>
                <td><?= esc($row->tanggal_dibuat ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?></td>
            </tr>
            <tr>
                <th class="text-muted">Diubah</th>
                <td><?= esc($row->tanggal_diubah ? date('d/m/Y H:i', strtotime((string) $row->tanggal_diubah)) : '-') ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Akses Entitas</h2>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Entitas</th>
                        <th>Role</th>
                        <th>Default</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($akses_entitas->count() === 0): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Akses entitas belum tersedia.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($akses_entitas as $a): ?>
                            <tr>
                                <td><?= esc(($a->kode_entitas ?? '-') . ' - ' . ($a->nama_entitas ?? '-')) ?></td>
                                <td><?= esc((string) ($a->nama_role ?? '-')) ?></td>
                                <td><?= (int) $a->entitas_default === 1 ? '<span class="badge bg-primary">Default</span>' : '-' ?></td>
                                <td><?= (int) $a->status_aktif === 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>