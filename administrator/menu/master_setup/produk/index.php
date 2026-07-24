<?php
declare(strict_types=1);

$q        = trim((string) ($_GET['q'] ?? ''));
$sort     = trim((string) ($_GET['sort'] ?? 'kode_produk'));
$order    = strtolower(trim((string) ($_GET['order'] ?? 'asc')));
$perPage  = (int) ($_GET['per_page'] ?? 10);
$page     = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['kode_produk', 'barcode_produk', 'nama_produk', 'jenis_produk', 'harga_jual', 'status_produk'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'kode_produk';
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

$query = ProdukORM::query()
    ->from('tb_produk as p')
    ->leftJoin('tb_kategori_produk as k', 'k.id_kategori_produk', '=', 'p.id_kategori_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('p.id_entitas', (int) ($user['id_entitas'] ?? 0));

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('p.kode_produk', 'like', '%' . $q . '%')
            ->orWhere('p.barcode_produk', 'like', '%' . $q . '%')
            ->orWhere('p.nama_produk', 'like', '%' . $q . '%')
            ->orWhere('p.jenis_produk', 'like', '%' . $q . '%')
            ->orWhere('k.nama_kategori_produk', 'like', '%' . $q . '%')
            ->orWhere('s.nama_satuan', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;

$data_produk = $query
    ->select([
        'p.*',
        'k.kode_kategori_produk',
        'k.nama_kategori_produk',
        's.nama_satuan',
    ])
    ->orderBy('p.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_produk(string $column): string
{
    $params = [
        'menu'     => 'master_setup/produk',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'kode_produk'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_produk(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'kode_produk'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Produk</h1>
    <p class="page-subtitle">Kelola data produk per entitas</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Produk</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="master_setup/produk">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <div class="filter-search-box">
                        <input type="text" name="q" class="form-control" placeholder="Cari kode, barcode, nama, kategori, satuan..." value="<?= esc($q) ?>">
                    </div>

                    <div style="min-width: 140px;">
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($allowedPerPage as $limit): ?>
                                <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>><?= $limit ?> baris</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>

                    <?php if ($q !== '' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('master_setup/produk')) ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('master_setup/produk/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Produk
                </a>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <div style="max-height: 420px; overflow-y: auto;">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="70" class="text-center">No</th>
                            <th><a href="<?= esc(build_sort_url_produk('kode_produk')) ?>" class="text-decoration-none text-dark">Kode<?= sort_icon_produk('kode_produk') ?></a></th>
                            <th><a href="<?= esc(build_sort_url_produk('barcode_produk')) ?>" class="text-decoration-none text-dark">Barcode<?= sort_icon_produk('barcode_produk') ?></a></th>
                            <th><a href="<?= esc(build_sort_url_produk('nama_produk')) ?>" class="text-decoration-none text-dark">Nama Produk<?= sort_icon_produk('nama_produk') ?></a></th>
                            <th>Kategori</th>
                            <th>Satuan</th>
                            <th><a href="<?= esc(build_sort_url_produk('jenis_produk')) ?>" class="text-decoration-none text-dark">Jenis<?= sort_icon_produk('jenis_produk') ?></a></th>
                            <th><a href="<?= esc(build_sort_url_produk('harga_jual')) ?>" class="text-decoration-none text-dark">Harga Jual<?= sort_icon_produk('harga_jual') ?></a></th>
                            <th>Status</th>
                            <th width="210" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($data_produk->count() > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($data_produk as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="fw-semibold"><?= esc($row->kode_produk) ?></td>
                                    <td><?= esc($row->barcode_produk ?? '-') ?></td>
                                    <td><?= esc($row->nama_produk) ?></td>
                                    <td><?= esc(($row->kode_kategori_produk ?? '-') . ((isset($row->nama_kategori_produk) && $row->nama_kategori_produk !== null) ? ' - ' . $row->nama_kategori_produk : '')) ?></td>
                                    <td><?= esc($row->nama_satuan ?? '-') ?></td>
                                    <td><?= esc($row->jenis_produk ?? '-') ?></td>
                                    <td><?= 'Rp ' . number_format((float) $row->harga_jual, 2, ',', '.') ?></td>
                                    <td>
                                        <?php if ((int) $row->status_produk === 1): ?>
                                            <span class="badge text-bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= esc(admin_page_url('master_setup/produk/detail') . '&id=' . (int) $row->id_produk) ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                        <a href="<?= esc(admin_page_url('master_setup/produk/edit') . '&id=' . (int) $row->id_produk) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square"></i></a>
                                        <a href="<?= esc(admin_url('menu/master_setup/produk/hapus.php?id=' . (int) $row->id_produk)) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus data ini?')"><i class="bi bi-trash"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">Belum ada data produk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>