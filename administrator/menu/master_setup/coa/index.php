<?php
declare(strict_types=1);

$q        = trim((string) ($_GET['q'] ?? ''));
$sort     = trim((string) ($_GET['sort'] ?? 'kode_coa'));
$order    = strtolower(trim((string) ($_GET['order'] ?? 'asc')));
$perPage  = (int) ($_GET['per_page'] ?? 10);
$page     = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['kode_coa', 'nama_coa', 'level_coa', 'kategori_coa', 'posisi_saldo_normal', 'boleh_transaksi', 'status_aktif'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'kode_coa';
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

$query = CoaORM::query()
    ->from('tb_coa as c')
    ->leftJoin('tb_coa as p', 'p.id_coa', '=', 'c.id_coa_induk')
    ->where('c.id_entitas', (int) ($user['id_entitas'] ?? 0));

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('c.kode_coa', 'like', '%' . $q . '%')
            ->orWhere('c.nama_coa', 'like', '%' . $q . '%')
            ->orWhere('c.kategori_coa', 'like', '%' . $q . '%')
            ->orWhere('c.posisi_saldo_normal', 'like', '%' . $q . '%')
            ->orWhere('p.nama_coa', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_coa = $query
    ->select([
        'c.id_coa',
        'c.kode_coa',
        'c.nama_coa',
        'c.level_coa',
        'c.kategori_coa',
        'c.posisi_saldo_normal',
        'c.boleh_transaksi',
        'c.status_aktif',
        'p.kode_coa as kode_coa_induk',
        'p.nama_coa as nama_coa_induk',
    ])
    ->orderBy('c.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_coa(string $column): string
{
    $params = [
        'menu'     => 'master_setup/coa',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'kode_coa'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_coa(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'kode_coa'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_coa(int $targetPage): string
{
    $params = [
        'menu'     => 'master_setup/coa',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'kode_coa')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'asc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">COA</h1>
    <p class="page-subtitle">Kelola chart of accounts untuk entitas aktif</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar COA</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="master_setup/coa">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <div class="filter-search-box">
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Cari kode/nama/kategori/akun induk..."
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
                        <a href="<?= esc(admin_page_url('master_setup/coa')) ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('master_setup/coa/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah COA
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/master_setup/coa/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div class="small text-muted">
                    Menampilkan <strong><?= $totalRows > 0 ? ($offset + 1) : 0 ?></strong> - <strong><?= min($offset + $perPage, $totalRows) ?></strong> dari <strong><?= (int) $totalRows ?></strong> data
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
                                    <a href="<?= esc(build_sort_url_coa('kode_coa')) ?>" class="text-decoration-none text-dark">
                                        Kode COA<?= sort_icon_coa('kode_coa') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_coa('nama_coa')) ?>" class="text-decoration-none text-dark">
                                        Nama COA<?= sort_icon_coa('nama_coa') ?>
                                    </a>
                                </th>
                                <th>COA Induk</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_coa('level_coa')) ?>" class="text-decoration-none text-dark">
                                        Level<?= sort_icon_coa('level_coa') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_coa('kategori_coa')) ?>" class="text-decoration-none text-dark">
                                        Kategori<?= sort_icon_coa('kategori_coa') ?>
                                    </a>
                                </th>
                                <th>Normal</th>
                                <th>Boleh Transaksi</th>
                                <th>Status</th>
                                <th width="210" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($data_coa->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_coa as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="id_coa[]" value="<?= (int) $row->id_coa ?>" class="row-check">
                                        </td>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->kode_coa) ?></td>
                                        <td><?= esc($row->nama_coa) ?></td>
                                        <td>
                                            <?= esc(
                                                ($row->kode_coa_induk ?? '-') .
                                                ((isset($row->nama_coa_induk) && $row->nama_coa_induk !== null) ? ' - ' . $row->nama_coa_induk : '')
                                            ) ?>
                                        </td>
                                        <td><?= (int) $row->level_coa ?></td>
                                        <td><?= esc($row->kategori_coa) ?></td>
                                        <td><?= esc($row->posisi_saldo_normal) ?></td>
                                        <td>
                                            <?php if ((int) $row->boleh_transaksi === 1): ?>
                                                <span class="badge text-bg-primary">Ya</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-warning">Tidak</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ((int) $row->status_aktif === 1): ?>
                                                <span class="badge text-bg-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('master_setup/coa/detail') . '&id=' . (int) $row->id_coa) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= esc(admin_page_url('master_setup/coa/edit') . '&id=' . (int) $row->id_coa) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="<?= esc(admin_url('menu/master_setup/coa/hapus.php?id=' . (int) $row->id_coa)) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus data COA ini?')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">Belum ada data COA.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <?php if ($totalPages > 1): ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
                <div class="small text-muted">Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong></div>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url_coa(1)) ?>">Awal</a>
                        </li>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url_coa($page - 1)) ?>">«</a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_coa($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url_coa($page + 1)) ?>">»</a>
                        </li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url_coa($totalPages)) ?>">Akhir</a>
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