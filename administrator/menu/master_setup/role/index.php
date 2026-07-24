<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('role_index_is_super_admin')) {
    function role_index_is_super_admin(array $user_login): bool
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

if (!role_index_is_super_admin(user_login())) {
    set_flash('error', 'Menu Role hanya boleh diakses oleh super admin.');
    redirect_admin('dashboard');
}

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['hal'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 10);

if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 10;
}

$query = Capsule::table('tb_role');

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('nama_role', 'like', '%' . $q . '%')
            ->orWhere('keterangan', 'like', '%' . $q . '%');
    });
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_role = $query
    ->select('*')
    ->orderBy('id_role', 'asc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$total_role = (int) Capsule::table('tb_role')->count();
$total_role_menu = (int) Capsule::table('tb_role_menu')->where('status_aktif', 1)->count();
$total_pengguna = (int) Capsule::table('tb_pengguna')->count();

function role_page_url(int $targetPage): string
{
    return admin_url('index.php?' . http_build_query([
        'menu' => 'master/role',
        'q' => trim((string) ($_GET['q'] ?? '')),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal' => $targetPage,
    ]));
}
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Role</h1>
            <p class="page-subtitle">Kelola role dan hak akses menu. Khusus super admin.</p>
        </div>

        <a href="<?= esc(admin_page_url('master/role/tambah')) ?>" class="btn btn-gradient">
            <i class="bi bi-plus-circle me-1"></i>Tambah Role
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Role</div>
                <div class="h4 mb-0"><?= number_format($total_role, 0, '.', ',') ?></div>
                <div class="text-muted small">Role sistem</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Hak Akses Aktif</div>
                <div class="h4 mb-0"><?= number_format($total_role_menu, 0, '.', ',') ?></div>
                <div class="text-muted small">Baris tb_role_menu aktif</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Pengguna</div>
                <div class="h4 mb-0"><?= number_format($total_pengguna, 0, '.', ',') ?></div>
                <div class="text-muted small">Pengguna semua entitas</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="menu" value="master/role">

            <div class="col-md-7">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Cari nama role atau keterangan...">
            </div>

            <div class="col-md-3">
                <label class="form-label">Baris</label>
                <select name="per_page" class="form-select">
                    <?php foreach ([10, 25, 50, 100] as $limit): ?>
                        <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-search me-1"></i>Cari
                </button>
            </div>
        </form>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60" class="text-center">No</th>
                        <th>Nama Role</th>
                        <th>Keterangan</th>
                        <th class="text-end">Pengguna</th>
                        <th class="text-end">Menu Diizinkan</th>
                        <th width="180" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($data_role->count() === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Data role belum tersedia.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($data_role as $row): ?>
                            <?php
                            $jumlah_pengguna = (int) Capsule::table('tb_pengguna')
                                ->where('id_role', (int) $row->id_role)
                                ->count();

                            $jumlah_menu = (int) Capsule::table('tb_role_menu')
                                ->where('id_role', (int) $row->id_role)
                                ->where('status_aktif', 1)
                                ->where('boleh_lihat', 1)
                                ->count();

                            $is_super_role = strtolower((string) $row->nama_role) === 'super_admin';
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) $row->nama_role) ?></div>
                                    <?php if ($is_super_role): ?>
                                        <span class="badge text-bg-danger">System Role</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc((string) ($row->keterangan ?? '-')) ?></td>
                                <td class="text-end"><?= number_format($jumlah_pengguna, 0, '.', ',') ?></td>
                                <td class="text-end"><?= number_format($jumlah_menu, 0, '.', ',') ?></td>
                                <td class="text-center">
                                    <a href="<?= esc(admin_page_url('master/role/detail') . '&id=' . (int) $row->id_role) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if (!$is_super_role): ?>
                                        <a href="<?= esc(admin_page_url('master/role/edit') . '&id=' . (int) $row->id_role) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <a href="<?= esc(admin_url('menu/master_setup/role/hapus.php?id=' . (int) $row->id_role)) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus role ini?')" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Role super_admin tidak boleh diubah">
                                            <i class="bi bi-lock"></i>
                                        </button>
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
                        <a class="page-link" href="<?= esc(role_page_url(max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(role_page_url($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(role_page_url(min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>