<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = user_login();
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

if (!function_exists('entitas_is_super_admin')) {
    function entitas_is_super_admin(array $user_login): bool
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
                $nama_role = strtolower(trim((string) ($role->nama_role ?? '')));
                $kode_role = strtolower(trim((string) ($role->kode_role ?? '')));

                if ($nama_role === 'super_admin' || $kode_role === 'super_admin') {
                    return true;
                }
            }
        }

        return false;
    }
}

$is_super_admin = entitas_is_super_admin($user_login);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$page = max(1, (int) ($_GET['hal'] ?? 1));
$perPage = (int) ($_GET['per_page'] ?? 10);

if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 10;
}

if (!in_array($status, ['semua', 'aktif', 'nonaktif'], true)) {
    $status = 'semua';
}

$query = Capsule::table('tb_entitas');

if (!$is_super_admin) {
    $query->where('id_entitas', $id_entitas_login);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('kode_entitas', 'like', '%' . $q . '%')
            ->orWhere('nama_entitas', 'like', '%' . $q . '%')
            ->orWhere('nama_pemilik', 'like', '%' . $q . '%')
            ->orWhere('email', 'like', '%' . $q . '%')
            ->orWhere('no_hp', 'like', '%' . $q . '%')
            ->orWhere('jenis_usaha', 'like', '%' . $q . '%');
    });
}

if ($status === 'aktif') {
    $query->where('status_aktif', 1);
} elseif ($status === 'nonaktif') {
    $query->where('status_aktif', 0);
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_entitas = $query
    ->select('*')
    ->orderBy('status_aktif', 'desc')
    ->orderBy('nama_entitas', 'asc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$total_aktif_query = Capsule::table('tb_entitas')->where('status_aktif', 1);
$total_nonaktif_query = Capsule::table('tb_entitas')->where('status_aktif', 0);
$total_pengguna_query = Capsule::table('tb_pengguna');

if (!$is_super_admin) {
    $total_aktif_query->where('id_entitas', $id_entitas_login);
    $total_nonaktif_query->where('id_entitas', $id_entitas_login);
    $total_pengguna_query->where('id_entitas', $id_entitas_login);
}

$total_aktif = (int) $total_aktif_query->count();
$total_nonaktif = (int) $total_nonaktif_query->count();
$total_pengguna = (int) $total_pengguna_query->count();

function entitas_page_url(int $targetPage): string
{
    return admin_url('index.php?' . http_build_query([
        'menu' => 'master/entitas',
        'q' => trim((string) ($_GET['q'] ?? '')),
        'status' => trim((string) ($_GET['status'] ?? 'semua')),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal' => $targetPage,
    ]));
}
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Entitas</h1>
            <p class="page-subtitle">
                <?= $is_super_admin
                    ? 'Kelola seluruh data perusahaan/unit usaha yang memakai aplikasi'
                    : 'Kelola data entitas/perusahaan kamu sendiri' ?>
            </p>
        </div>

        <?php if ($is_super_admin): ?>
            <a href="<?= esc(admin_page_url('master/entitas/tambah')) ?>" class="btn btn-gradient">
                <i class="bi bi-plus-circle me-1"></i>Tambah Entitas
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Entitas Aktif</div>
                <div class="h4 mb-0"><?= number_format($total_aktif, 0, '.', ',') ?></div>
                <div class="text-muted small">Siap digunakan login/transaksi</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Entitas Nonaktif</div>
                <div class="h4 mb-0"><?= number_format($total_nonaktif, 0, '.', ',') ?></div>
                <div class="text-muted small">Tidak aktif</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Pengguna</div>
                <div class="h4 mb-0"><?= number_format($total_pengguna, 0, '.', ',') ?></div>
                <div class="text-muted small"><?= $is_super_admin ? 'Pengguna semua entitas' : 'Pengguna entitas ini' ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="menu" value="master/entitas">

            <div class="col-md-5">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Cari kode, nama, pemilik, email...">
            </div>

            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </div>

            <div class="col-md-2">
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
                        <th>Kode</th>
                        <th>Nama Entitas</th>
                        <th>Pemilik</th>
                        <th>Kontak</th>
                        <th>Jenis Usaha</th>
                        <th>Status</th>
                        <th width="<?= $is_super_admin ? '220' : '140' ?>" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($data_entitas->count() === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Data entitas belum tersedia.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($data_entitas as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="fw-semibold"><?= esc((string) $row->kode_entitas) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) $row->nama_entitas) ?></div>
                                    <div class="text-muted small"><?= esc(mb_strimwidth((string) ($row->alamat ?? '-'), 0, 55, '...')) ?></div>
                                </td>
                                <td><?= esc((string) ($row->nama_pemilik ?? '-')) ?></td>
                                <td>
                                    <div><?= esc((string) ($row->no_hp ?? '-')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($row->email ?? '-')) ?></div>
                                </td>
                                <td><?= esc((string) ($row->jenis_usaha ?? '-')) ?></td>
                                <td>
                                    <?= ((int) $row->status_aktif === 1)
                                        ? '<span class="badge text-bg-success">Aktif</span>'
                                        : '<span class="badge text-bg-secondary">Nonaktif</span>' ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= esc(admin_page_url('master/entitas/detail') . '&id=' . (int) $row->id_entitas) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a href="<?= esc(admin_page_url('master/entitas/edit') . '&id=' . (int) $row->id_entitas) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>

                                    <?php if ($is_super_admin): ?>
                                        <a href="<?= esc(admin_url('menu/master_setup/entitas/status.php?id=' . (int) $row->id_entitas)) ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Ubah status entitas ini?')" title="Aktif/Nonaktif">
                                            <i class="bi bi-power"></i>
                                        </a>

                                        <a href="<?= esc(admin_url('menu/master_setup/entitas/hapus.php?id=' . (int) $row->id_entitas)) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus entitas ini?')" title="Hapus">
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
                        <a class="page-link" href="<?= esc(entitas_page_url(max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(entitas_page_url($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(entitas_page_url(min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>