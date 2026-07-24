<?php
declare(strict_types=1);

$q = trim((string) ($_GET['q'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'kode_pajak'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'asc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['id_pajak', 'kode_pajak', 'nama_pajak', 'persentase', 'is_default', 'status_aktif'];
$allowedPerPage = [10, 25, 50, 100];
if (!in_array($sort, $allowedSort, true)) $sort = 'kode_pajak';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'asc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = PajakORM::query()->where('id_entitas', (int) ($user['id_entitas'] ?? 0));
if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('kode_pajak', 'like', '%' . $q . '%')
            ->orWhere('nama_pajak', 'like', '%' . $q . '%')
            ->orWhere('keterangan', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$data_pajak = $query->orderBy($sort, $order)->skip($offset)->take($perPage)->get();

function build_sort_url_pajak(string $column): string
{
    $params = [
        'menu' => 'master_setup/pajak',
        'q' => trim((string) ($_GET['q'] ?? '')),
        'sort' => $column,
        'order' => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal' => 1,
    ];
    $currentSort = trim((string) ($_GET['sort'] ?? 'kode_pajak'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));
    if ($currentSort === $column && $currentOrder === 'asc') $params['order'] = 'desc';
    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_pajak(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'kode_pajak'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));
    if ($currentSort !== $column) return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    return $currentOrder === 'asc' ? '<i class="bi bi-sort-down ms-1"></i>' : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_pajak(int $targetPage): string
{
    $params = [
        'menu' => 'master_setup/pajak',
        'q' => trim((string) ($_GET['q'] ?? '')),
        'sort' => trim((string) ($_GET['sort'] ?? 'kode_pajak')),
        'order' => strtolower(trim((string) ($_GET['order'] ?? 'asc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal' => $targetPage,
    ];
    return admin_url('index.php?' . http_build_query($params));
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Pajak / PPN</h1>
    <p class="page-subtitle">Kelola persentase pajak default untuk faktur pembelian dan penjualan</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Pajak</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>
            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="master_setup/pajak">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">
                    <input type="text" name="q" class="form-control" placeholder="Cari kode/nama pajak..." value="<?= esc($q) ?>">
                    <select name="per_page" class="form-select" style="min-width:140px" onchange="this.form.submit()">
                        <?php foreach ($allowedPerPage as $limit): ?>
                            <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>><?= $limit ?> baris</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                    <?php if ($q !== '' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('master_setup/pajak')) ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>
                <a href="<?= esc(admin_page_url('master_setup/pajak/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Pajak
                </a>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="small text-muted">
                Menampilkan <strong><?= $totalRows > 0 ? ($offset + 1) : 0 ?></strong> - <strong><?= min($offset + $perPage, $totalRows) ?></strong> dari <strong><?= (int) $totalRows ?></strong> data
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th><a href="<?= esc(build_sort_url_pajak('kode_pajak')) ?>" class="text-decoration-none text-dark">Kode<?= sort_icon_pajak('kode_pajak') ?></a></th>
                        <th><a href="<?= esc(build_sort_url_pajak('nama_pajak')) ?>" class="text-decoration-none text-dark">Nama Pajak<?= sort_icon_pajak('nama_pajak') ?></a></th>
                        <th class="text-end"><a href="<?= esc(build_sort_url_pajak('persentase')) ?>" class="text-decoration-none text-dark">Persentase<?= sort_icon_pajak('persentase') ?></a></th>
                        <th class="text-center">Default</th>
                        <th>Status</th>
                        <th width="160" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = $offset + 1; ?>
                    <?php if ($data_pajak->count() > 0): ?>
                        <?php foreach ($data_pajak as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="fw-semibold"><?= esc($row->kode_pajak) ?></td>
                                <td><?= esc($row->nama_pajak) ?></td>
                                <td class="text-end"><?= esc(number_format((float) $row->persentase, 2, '.', ',')) ?>%</td>
                                <td class="text-center">
                                    <?= ((int) $row->is_default === 1) ? '<span class="badge text-bg-primary">Default</span>' : '<span class="badge text-bg-light text-dark">Tidak</span>' ?>
                                </td>
                                <td>
                                    <?= ((int) $row->status_aktif === 1) ? '<span class="badge text-bg-success">Aktif</span>' : '<span class="badge text-bg-secondary">Nonaktif</span>' ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= esc(admin_page_url('master_setup/pajak/edit') . '&id=' . (int) $row->id_pajak) ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="<?= esc(admin_url('menu/master_setup/pajak/hapus.php?id=' . (int) $row->id_pajak)) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus data pajak ini?')" title="Hapus"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Belum ada data pajak.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
                <div class="small text-muted">Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong></div>
                <nav><ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url_pajak(1)) ?>">Awal</a></li>
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url_pajak($page - 1)) ?>">«</a></li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= esc(build_page_url_pajak($i)) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url_pajak($page + 1)) ?>">»</a></li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url_pajak($totalPages)) ?>">Akhir</a></li>
                </ul></nav>
            </div>
        <?php endif; ?>
    </div>
</div>
