<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_pesanan'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['no_pesanan_pembelian', 'tanggal_pesanan', 'status_pesanan', 'total'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_pesanan';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = PesananPembelianORM::query()
    ->from('tb_pesanan_pembelian as pp')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'pp.id_pemasok')
    ->where('pp.id_entitas', $id_entitas);

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('pp.no_pesanan_pembelian', 'like', '%' . $q . '%')
            ->orWhere('p.nama_pemasok', 'like', '%' . $q . '%')
            ->orWhere('p.kode_pemasok', 'like', '%' . $q . '%')
            ->orWhere('pp.status_pesanan', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_pesanan = $query
    ->select([
        'pp.*',
        'p.kode_pemasok',
        'p.nama_pemasok',
    ])
    ->orderBy('pp.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_pesanan(string $column): string
{
    $params = [
        'menu'     => 'pembelian/pesanan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_pesanan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_pesanan(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_pesanan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_pesanan(int $targetPage): string
{
    $params = [
        'menu'     => 'pembelian/pesanan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_pesanan')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Pesanan Pembelian</h1>
    <p class="page-subtitle">Kelola dokumen pesanan pembelian bahan baku</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Pesanan Pembelian</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="pembelian/pesanan">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <div class="filter-search-box">
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Cari no pesanan, pemasok, status..."
                            value="<?= esc($q) ?>">
                    </div>

                    <div style="min-width:140px;">
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
                        <a href="<?= esc(admin_page_url('pembelian/pesanan')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('pembelian/pesanan/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Pesanan
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/pembelian/pesanan/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
            <div class="table-responsive border rounded">
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="50" class="text-center">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th width="70" class="text-center">No</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pesanan('no_pesanan_pembelian')) ?>" class="text-decoration-none text-dark">
                                        No Pesanan<?= sort_icon_pesanan('no_pesanan_pembelian') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pesanan('tanggal_pesanan')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_pesanan('tanggal_pesanan') ?>
                                    </a>
                                </th>
                                <th>Pemasok</th>
                                <th>Tgl Datang Rencana</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pesanan('status_pesanan')) ?>" class="text-decoration-none text-dark">
                                        Status<?= sort_icon_pesanan('status_pesanan') ?>
                                    </a>
                                </th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_pesanan('total')) ?>" class="text-decoration-none text-dark">
                                        Total<?= sort_icon_pesanan('total') ?>
                                    </a>
                                </th>
                                <th width="230" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_pesanan->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_pesanan as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ((string) $row->status_pesanan === 'draft'): ?>
                                                <input type="checkbox" name="id_pesanan_pembelian[]" value="<?= (int) $row->id_pesanan_pembelian ?>" class="row-check">
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->no_pesanan_pembelian) ?></td>
                                        <td><?= esc($row->tanggal_pesanan) ?></td>
                                        <td><?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></td>
                                        <td><?= esc($row->tanggal_datang_rencana ?? '-') ?></td>
                                        <td>
                                            <?php $badge = ((string) $row->status_pesanan === 'terkonfirmasi') ? 'success' : 'secondary'; ?>
                                            <span class="badge text-bg-<?= $badge ?>">
                                                <?= esc(ucfirst((string) $row->status_pesanan)) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">Rp <?= esc(number_format((float) $row->total, 2, '.', ',')) ?></td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('pembelian/pesanan/detail') . '&id=' . (int) $row->id_pesanan_pembelian) ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ((string) $row->status_pesanan === 'draft'): ?>
                                                <a href="<?= esc(admin_page_url('pembelian/pesanan/edit') . '&id=' . (int) $row->id_pesanan_pembelian) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/pembelian/pesanan/konfirmasi.php?id=' . (int) $row->id_pesanan_pembelian)) ?>"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Konfirmasi pesanan pembelian ini?');">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/pembelian/pesanan/hapus.php?id=' . (int) $row->id_pesanan_pembelian)) ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Yakin ingin menghapus data ini?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('menu/pembelian/pesanan/cetak.php?id=' . (int) $row->id_pesanan_pembelian)) ?>"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        Data pesanan pembelian belum ada.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-3">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_pesanan(max(1, $page - 1))) ?>">Prev</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_pesanan($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_pesanan(min($totalPages, $page + 1))) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('checkAll')?.addEventListener('change', function() {
    document.querySelectorAll('.row-check').forEach((el) => {
        el.checked = this.checked;
    });
});
</script>