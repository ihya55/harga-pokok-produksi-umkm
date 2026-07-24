<?php
declare(strict_types=1);

$q        = trim((string) ($_GET['q'] ?? ''));
$sort     = trim((string) ($_GET['sort'] ?? 'nama_satuan'));
$order    = strtolower(trim((string) ($_GET['order'] ?? 'asc')));
$perPage  = (int) ($_GET['per_page'] ?? 10);
$page     = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['id_satuan', 'nama_satuan', 'keterangan'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'nama_satuan';
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

$query = SatuanORM::query();

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('nama_satuan', 'like', '%' . $q . '%')
            ->orWhere('keterangan', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_satuan = $query
    ->orderBy($sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url(string $column): string
{
    $params = [
        'menu'     => 'master_setup/satuan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'nama_satuan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'nama_satuan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url(int $targetPage): string
{
    $params = [
        'menu'     => 'master_setup/satuan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'nama_satuan')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'asc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Satuan</h1>
    <p class="page-subtitle">Kelola data satuan yang digunakan pada sistem</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Satuan</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="master_setup/satuan">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <div class="filter-search-box flex-grow-1">
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Cari nama/keterangan..."
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
                        <a href="<?= esc(admin_page_url('master_setup/satuan')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('master_setup/satuan/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Satuan
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/master_setup/satuan/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
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
                                    <a href="<?= esc(build_sort_url('nama_satuan')) ?>" class="text-decoration-none text-dark">
                                        Nama Satuan<?= sort_icon('nama_satuan') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url('keterangan')) ?>" class="text-decoration-none text-dark">
                                        Keterangan<?= sort_icon('keterangan') ?>
                                    </a>
                                </th>
                                <th width="180" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $offset + 1; ?>
                            <?php if ($data_satuan->count() > 0): ?>
                                <?php foreach ($data_satuan as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="id_satuan[]" value="<?= (int) $row->id_satuan ?>" class="row-check">
                                        </td>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->nama_satuan) ?></td>
                                        <td><?= esc($row->keterangan ?? '-') ?></td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('master_setup/satuan/edit') . '&id=' . (int) $row->id_satuan) ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <a href="<?= esc(admin_url('menu/master_setup/satuan/hapus.php?id=' . (int) $row->id_satuan)) ?>"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Yakin ingin menghapus data satuan ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada data satuan.
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
                            <a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url(1)) ?>">Awal</a>
                        </li>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url($page - 1)) ?>">«</a>
                        </li>

                        <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url($page + 1)) ?>">»</a>
                        </li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url($totalPages)) ?>">Akhir</a>
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