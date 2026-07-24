<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_penerimaan'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['no_penerimaan_pembelian', 'tanggal_penerimaan', 'status_penerimaan'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_penerimaan';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = PenerimaanPembelianORM::query()
    ->from('tb_penerimaan_pembelian as ppn')
    ->leftJoin('tb_pesanan_pembelian as po', 'po.id_pesanan_pembelian', '=', 'ppn.id_pesanan_pembelian')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'ppn.id_pemasok')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ppn.id_gudang')
    ->where('ppn.id_entitas', $id_entitas);

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('ppn.no_penerimaan_pembelian', 'like', '%' . $q . '%')
            ->orWhere('po.no_pesanan_pembelian', 'like', '%' . $q . '%')
            ->orWhere('p.nama_pemasok', 'like', '%' . $q . '%')
            ->orWhere('g.nama_gudang', 'like', '%' . $q . '%')
            ->orWhere('ppn.status_penerimaan', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_penerimaan = $query
    ->select([
        'ppn.*',
        'po.no_pesanan_pembelian',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'g.kode_gudang',
        'g.nama_gudang',
    ])
    ->orderBy('ppn.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_penerimaan(string $column): string
{
    $params = [
        'menu'     => 'pembelian/penerimaan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_penerimaan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_penerimaan(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_penerimaan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_penerimaan(int $targetPage): string
{
    $params = [
        'menu'     => 'pembelian/penerimaan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_penerimaan')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Penerimaan Pembelian</h1>
    <p class="page-subtitle">Kelola penerimaan bahan baku dari pesanan pembelian</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Penerimaan Pembelian</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="pembelian/penerimaan">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <div class="filter-search-box">
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Cari no penerimaan, PO, pemasok, gudang, status..."
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
                        <a href="<?= esc(admin_page_url('pembelian/penerimaan')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('pembelian/penerimaan/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Penerimaan
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/pembelian/penerimaan/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>

                <div class="text-muted small">
                    Pilih data draft yang ingin dihapus
                </div>
            </div>

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
                                    <a href="<?= esc(build_sort_url_penerimaan('no_penerimaan_pembelian')) ?>" class="text-decoration-none text-dark">
                                        No Penerimaan<?= sort_icon_penerimaan('no_penerimaan_pembelian') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_penerimaan('tanggal_penerimaan')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_penerimaan('tanggal_penerimaan') ?>
                                    </a>
                                </th>
                                <th>No PO</th>
                                <th>Pemasok</th>
                                <th>Gudang</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_penerimaan('status_penerimaan')) ?>" class="text-decoration-none text-dark">
                                        Status<?= sort_icon_penerimaan('status_penerimaan') ?>
                                    </a>
                                </th>
                                <th width="230" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_penerimaan->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_penerimaan as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ((string) $row->status_penerimaan === 'draft'): ?>
                                                <input type="checkbox" name="id_penerimaan_pembelian[]" value="<?= (int) $row->id_penerimaan_pembelian ?>" class="row-check">
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->no_penerimaan_pembelian) ?></td>
                                        <td><?= esc($row->tanggal_penerimaan) ?></td>
                                        <td><?= esc($row->no_pesanan_pembelian ?? '-') ?></td>
                                        <td><?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></td>
                                        <td><?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></td>
                                        <td>
                                            <?php $badge = ((string) $row->status_penerimaan === 'posted') ? 'success' : 'secondary'; ?>
                                            <span class="badge text-bg-<?= $badge ?>">
                                                <?= esc(ucfirst((string) $row->status_penerimaan)) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('pembelian/penerimaan/detail') . '&id=' . (int) $row->id_penerimaan_pembelian) ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ((string) $row->status_penerimaan === 'draft'): ?>
                                                <a href="<?= esc(admin_page_url('pembelian/penerimaan/edit') . '&id=' . (int) $row->id_penerimaan_pembelian) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/pembelian/penerimaan/posting.php?id=' . (int) $row->id_penerimaan_pembelian)) ?>"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Posting penerimaan ini? Stok bahan baku akan bertambah.');">
                                                    <i class="bi bi-box-arrow-in-down"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/pembelian/penerimaan/hapus.php?id=' . (int) $row->id_penerimaan_pembelian)) ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Yakin ingin menghapus data ini?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('menu/pembelian/penerimaan/cetak.php?id=' . (int) $row->id_penerimaan_pembelian)) ?>"
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
                                        Data penerimaan pembelian belum ada.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-end align-items-center mt-3">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_penerimaan(max(1, $page - 1))) ?>">Prev</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_penerimaan($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_penerimaan(min($totalPages, $page + 1))) ?>">Next</a>
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