<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = user_login();
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

if (!function_exists('pengguna_is_super_admin')) {
    function pengguna_is_super_admin(array $user_login): bool
    {
        $username = strtolower(trim((string) ($user_login['username'] ?? '')));
        $nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

        if ($username === 'super_admin' || $nama_role_session === 'super_admin') {
            return true;
        }

        $id_role = (int) ($user_login['id_role'] ?? 0);

        if ($id_role > 0) {
            $role = Capsule::table('tb_role')->where('id_role', $id_role)->first();

            if ($role) {
                $nama_role = strtolower(trim((string) ($role->nama_role ?? '')));
                return $nama_role === 'super_admin';
            }
        }

        return false;
    }
}

$is_super_admin = pengguna_is_super_admin($user_login);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$role_filter = (int) ($_GET['role'] ?? 0);
$entitas_filter = (int) ($_GET['entitas'] ?? 0);
$page = max(1, (int) ($_GET['hal'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 10);

if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 10;
}

if (!in_array($status, ['semua', 'aktif', 'nonaktif'], true)) {
    $status = 'semua';
}

$query = Capsule::table('tb_pengguna as p')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'p.id_entitas')
    ->leftJoin('tb_role as r', 'r.id_role', '=', 'p.id_role');

if (!$is_super_admin) {
    $query->where('p.id_entitas', $id_entitas_login)
        ->where('r.nama_role', '<>', 'super_admin');
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('p.nama_pengguna', 'like', '%' . $q . '%')
            ->orWhere('p.username', 'like', '%' . $q . '%')
            ->orWhere('p.nama_lengkap', 'like', '%' . $q . '%')
            ->orWhere('p.email', 'like', '%' . $q . '%')
            ->orWhere('p.no_hp', 'like', '%' . $q . '%')
            ->orWhere('e.nama_entitas', 'like', '%' . $q . '%')
            ->orWhere('r.nama_role', 'like', '%' . $q . '%');
    });
}

if ($status === 'aktif') {
    $query->where('p.status_aktif', 1);
} elseif ($status === 'nonaktif') {
    $query->where('p.status_aktif', 0);
}

if ($role_filter > 0) {
    $query->where('p.id_role', $role_filter);
}

if ($is_super_admin && $entitas_filter > 0) {
    $query->where('p.id_entitas', $entitas_filter);
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_pengguna = $query
    ->select([
        'p.*',
        'e.kode_entitas',
        'e.nama_entitas',
        'r.nama_role',
    ])
    ->orderBy('p.status_aktif', 'desc')
    ->orderBy('p.nama_lengkap', 'asc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$role_options_query = Capsule::table('tb_role')->orderBy('nama_role', 'asc');

if (!$is_super_admin) {
    $role_options_query->where('nama_role', '<>', 'super_admin');
}

$role_options = $role_options_query->get();

$entitas_options = $is_super_admin
    ? Capsule::table('tb_entitas')->orderBy('nama_entitas', 'asc')->get()
    : collect();

$total_aktif_query = Capsule::table('tb_pengguna as p')->leftJoin('tb_role as r', 'r.id_role', '=', 'p.id_role')->where('p.status_aktif', 1);
$total_nonaktif_query = Capsule::table('tb_pengguna as p')->leftJoin('tb_role as r', 'r.id_role', '=', 'p.id_role')->where('p.status_aktif', 0);

if (!$is_super_admin) {
    $total_aktif_query->where('p.id_entitas', $id_entitas_login)->where('r.nama_role', '<>', 'super_admin');
    $total_nonaktif_query->where('p.id_entitas', $id_entitas_login)->where('r.nama_role', '<>', 'super_admin');
}

$total_aktif = (int) $total_aktif_query->count();
$total_nonaktif = (int) $total_nonaktif_query->count();

function pengguna_page_url(int $targetPage): string
{
    return admin_url('index.php?' . http_build_query([
        'menu' => 'master/pengguna',
        'q' => trim((string) ($_GET['q'] ?? '')),
        'status' => trim((string) ($_GET['status'] ?? 'semua')),
        'role' => (int) ($_GET['role'] ?? 0),
        'entitas' => (int) ($_GET['entitas'] ?? 0),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal' => $targetPage,
    ]));
}
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Pengguna</h1>
            <p class="page-subtitle">
                <?= $is_super_admin ? 'Kelola semua pengguna sistem' : 'Kelola pengguna pada entitas kamu sendiri' ?>
            </p>
        </div>

        <a href="<?= esc(admin_page_url('master/pengguna/tambah')) ?>" class="btn btn-gradient">
            <i class="bi bi-plus-circle me-1"></i>Tambah Pengguna
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pengguna Aktif</div>
                <div class="h4 mb-0"><?= number_format($total_aktif, 0, '.', ',') ?></div>
                <div class="text-muted small"><?= $is_super_admin ? 'Semua entitas' : 'Entitas login' ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pengguna Nonaktif</div>
                <div class="h4 mb-0"><?= number_format($total_nonaktif, 0, '.', ',') ?></div>
                <div class="text-muted small">Tidak bisa login</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="menu" value="master/pengguna">

            <div class="col-md-<?= $is_super_admin ? '3' : '4' ?>">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Cari nama, username, email...">
            </div>

            <?php if ($is_super_admin): ?>
                <div class="col-md-3">
                    <label class="form-label">Entitas</label>
                    <select name="entitas" class="form-select">
                        <option value="0">Semua Entitas</option>
                        <?php foreach ($entitas_options as $e): ?>
                            <option value="<?= (int) $e->id_entitas ?>" <?= $entitas_filter === (int) $e->id_entitas ? 'selected' : '' ?>>
                                <?= esc(($e->kode_entitas ?? '-') . ' - ' . ($e->nama_entitas ?? '-')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-2">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                    <option value="0">Semua Role</option>
                    <?php foreach ($role_options as $r): ?>
                        <option value="<?= (int) $r->id_role ?>" <?= $role_filter === (int) $r->id_role ? 'selected' : '' ?>>
                            <?= esc((string) $r->nama_role) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>

            <div class="col-md-1">
                <label class="form-label">Baris</label>
                <select name="per_page" class="form-select">
                    <?php foreach ([10, 25, 50, 100] as $limit): ?>
                        <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1 d-grid">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60" class="text-center">No</th>
                        <th>Pengguna</th>
                        <th>Username</th>
                        <th>Entitas</th>
                        <th>Role</th>
                        <th>Kontak</th>
                        <th>Status</th>
                        <th width="240" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($data_pengguna->count() === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Data pengguna belum tersedia.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($data_pengguna as $row): ?>
                            <?php
                            $is_current_user = (int) $row->id_pengguna === (int) ($user_login['id_pengguna'] ?? 0);
                            $is_super_user = strtolower((string) ($row->nama_role ?? '')) === 'super_admin';
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) $row->nama_lengkap) ?></div>
                                    <div class="text-muted small"><?= esc((string) $row->nama_pengguna) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold">@<?= esc((string) $row->username) ?></div>
                                    <?php if ($is_current_user): ?>
                                        <span class="badge text-bg-info">Akun Saya</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->nama_entitas ?? '-')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($row->kode_entitas ?? '-')) ?></div>
                                </td>
                                <td>
                                    <?= esc((string) ($row->nama_role ?? '-')) ?>
                                    <?php if ($is_super_user): ?>
                                        <span class="badge text-bg-danger">System</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div><?= esc((string) ($row->no_hp ?? '-')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($row->email ?? '-')) ?></div>
                                </td>
                                <td>
                                    <?= ((int) $row->status_aktif === 1)
                                        ? '<span class="badge text-bg-success">Aktif</span>'
                                        : '<span class="badge text-bg-secondary">Nonaktif</span>' ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= esc(admin_page_url('master/pengguna/detail') . '&id=' . (int) $row->id_pengguna) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a href="<?= esc(admin_page_url('master/pengguna/edit') . '&id=' . (int) $row->id_pengguna) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <a href="<?= esc(admin_url('menu/master_setup/pengguna/reset_password.php?id=' . (int) $row->id_pengguna)) ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Reset password pengguna ini ke admin123?')" title="Reset Password">
                                        <i class="bi bi-key"></i>
                                    </a>

                                    <?php if (!$is_current_user): ?>
                                        <a href="<?= esc(admin_url('menu/master_setup/pengguna/status.php?id=' . (int) $row->id_pengguna)) ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Ubah status pengguna ini?')" title="Aktif/Nonaktif">
                                            <i class="bi bi-power"></i>
                                        </a>

                                        <a href="<?= esc(admin_url('menu/master_setup/pengguna/hapus.php?id=' . (int) $row->id_pengguna)) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus pengguna ini?')" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(pengguna_page_url(max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(pengguna_page_url($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(pengguna_page_url(min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>