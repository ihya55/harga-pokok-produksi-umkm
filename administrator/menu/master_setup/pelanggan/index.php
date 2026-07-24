<?php
declare(strict_types=1);

$q        = trim((string) ($_GET['q'] ?? ''));
$sort     = trim((string) ($_GET['sort'] ?? 'nama_pelanggan'));
$order    = strtolower(trim((string) ($_GET['order'] ?? 'asc')));
$perPage  = (int) ($_GET['per_page'] ?? 10);
$page     = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['id_pelanggan', 'kode_pelanggan', 'nama_pelanggan', 'jenis_pelanggan', 'status_aktif'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'nama_pelanggan';
}

if (!in_array($order, ['asc', 'desc'], true)) {
    $order = 'asc';
}

if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

if ($page < 1) {
    $page = 1;
}

$query = PelangganORM::query()
    ->where('id_entitas', (int) ($user['id_entitas'] ?? 0));

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('kode_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('nama_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('no_hp', 'like', '%' . $q . '%')
            ->orWhere('email', 'like', '%' . $q . '%')
            ->orWhere('jenis_pelanggan', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_pelanggan = $query
    ->orderBy($sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_pelanggan(string $column): string
{
    $params = [
        'menu'     => 'master_setup/pelanggan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'nama_pelanggan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_pelanggan(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'nama_pelanggan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_pelanggan(int $targetPage): string
{
    $params = [
        'menu'     => 'master_setup/pelanggan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'nama_pelanggan')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'asc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Pelanggan</h1>
    <p class="page-subtitle">Kelola data pelanggan untuk entitas aktif</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Pelanggan</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="master_setup/pelanggan">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <div class="filter-search-box">
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Cari kode/nama/no hp/email..."
                            value="<?= esc($q) ?>"
                        >
                    </div>

                    <div style="min-width: 140px;">
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($allowedPerPage as $limit): ?>
                                <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>>
                                    <?= $limit ?> baris
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>

                    <?php if ($q !== '' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('master_setup/pelanggan')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('master_setup/pelanggan/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Pelanggan
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/master_setup/pelanggan/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div class="small text-muted">
                    Menampilkan
                    <strong><?= $totalRows > 0 ? ($offset + 1) : 0 ?></strong>
                    -
                    <strong><?= min($offset + $perPage, $totalRows) ?></strong>
                    dari
                    <strong><?= (int) $totalRows ?></strong> data
                </div>

                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>
            </div>

            <div class="table-responsive border rounded">
                <div style="max-height: 420px; overflow-y: auto;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="50" class="text-center">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th width="70" class="text-center">No</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pelanggan('kode_pelanggan')) ?>" class="text-decoration-none text-dark">
                                        Kode<?= sort_icon_pelanggan('kode_pelanggan') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pelanggan('nama_pelanggan')) ?>" class="text-decoration-none text-dark">
                                        Nama Pelanggan<?= sort_icon_pelanggan('nama_pelanggan') ?>
                                    </a>
                                </th>
                                <th>No. HP</th>
                                <th>Jenis</th>
                                <th>Status</th>
                                <th width="210" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $offset + 1; ?>
                            <?php if ($data_pelanggan->count() > 0): ?>
                                <?php foreach ($data_pelanggan as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="id_pelanggan[]" value="<?= (int) $row->id_pelanggan ?>" class="row-check">
                                        </td>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->kode_pelanggan) ?></td>
                                        <td><?= esc($row->nama_pelanggan) ?></td>
                                        <td><?= esc($row->no_hp ?? '-') ?></td>
                                        <td>
                                            <?php
                                            $jenisPelangganLabel = match ((string) ($row->jenis_pelanggan ?? '')) {
                                                'umum'       => 'Umum',
                                                'reseller'   => 'Reseller',
                                                'grosir'     => 'Grosir',
                                                'langganan'  => 'Langganan',
                                                default      => '-',
                                            };
                                            ?>
                                            <?= esc($jenisPelangganLabel) ?>
                                        </td>
                                        <td>
                                            <?php if ((int) $row->status_aktif === 1): ?>
                                                <span class="badge text-bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('master_setup/pelanggan/detail') . '&id=' . (int) $row->id_pelanggan) ?>"
                                               class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <a href="<?= esc(admin_page_url('master_setup/pelanggan/edit') . '&id=' . (int) $row->id_pelanggan) ?>"
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <a href="<?= esc(admin_url('menu/master_setup/pelanggan/hapus.php?id=' . (int) $row->id_pelanggan)) ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Yakin ingin menghapus data pelanggan ini?')"
                                               title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        Belum ada data pelanggan.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <?php if ($totalPages > 1): ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
                <div class="small text-muted">
                    Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong>
                </div>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url_pelanggan(1)) ?>">Awal</a>
                        </li>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url_pelanggan($page - 1)) ?>">«</a>
                        </li>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_pelanggan($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url_pelanggan($page + 1)) ?>">»</a>
                        </li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url_pelanggan($totalPages)) ?>">Akhir</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const rowChecks = document.querySelectorAll('.row-check');

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rowChecks.forEach(function (item) {
                item.checked = checkAll.checked;
            });
        });
    }
});
</script>