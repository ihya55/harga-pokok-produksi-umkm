<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_perintah'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedStatus = ['semua', 'draft', 'posted'];
$allowedSort = [
    'no_perintah_produksi',
    'tanggal_perintah',
    'qty_rencana',
    'qty_hasil',
    'status_produksi',
    'tanggal_mulai',
    'tanggal_selesai',
];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($status, $allowedStatus, true)) $status = 'semua';
if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_perintah';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->where('pp.id_entitas', $id_entitas);

if ($status !== 'semua') {
    $query->where('pp.status_produksi', $status);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('pp.no_perintah_produksi', 'like', '%' . $q . '%')
            ->orWhere('pp.status_produksi', 'like', '%' . $q . '%')
            ->orWhere('pp.catatan', 'like', '%' . $q . '%')
            ->orWhere('pr.kode_produk', 'like', '%' . $q . '%')
            ->orWhere('pr.nama_produk', 'like', '%' . $q . '%')
            ->orWhere('r.kode_resep', 'like', '%' . $q . '%')
            ->orWhere('r.nama_resep', 'like', '%' . $q . '%')
            ->orWhere('ps.no_pesanan_penjualan', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_perintah = $query
    ->select([
        'pp.*',
        'pr.kode_produk',
        'pr.nama_produk',
        'r.kode_resep',
        'r.nama_resep',
        'ps.no_pesanan_penjualan',
    ])
    ->orderBy('pp.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_perintah_produksi(string $column): string
{
    $params = [
        'menu'     => 'produksi/perintah',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_perintah'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_perintah_produksi(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_perintah'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_perintah_produksi(int $targetPage): string
{
    $params = [
        'menu'     => 'produksi/perintah',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_perintah')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Perintah Produksi</h1>
    <p class="page-subtitle">Kelola rencana produksi berdasarkan produk dan resep/BOM</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Perintah Produksi</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="produksi/perintah">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari no perintah, produk, resep..."
                        value="<?= esc($q) ?>">

                    <select name="status" class="form-select" style="min-width:140px;" onchange="this.form.submit()">
                        <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="posted" <?= $status === 'posted' ? 'selected' : '' ?>>Posted</option>
                    </select>

                    <select name="per_page" class="form-select" style="min-width:120px;" onchange="this.form.submit()">
                        <?php foreach ($allowedPerPage as $limit): ?>
                            <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>>
                                <?= $limit ?> baris
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>

                    <?php if ($q !== '' || $status !== 'semua' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('produksi/perintah')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('produksi/perintah/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Perintah
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/produksi/perintah/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
            <input type="hidden" name="back_url" value="<?= esc($current_url) ?>">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>

                <div class="text-muted small">Hanya data draft yang bisa dihapus</div>
            </div>

            <div class="table-responsive border rounded">
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="50" class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th width="70" class="text-center">No</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_perintah_produksi('no_perintah_produksi')) ?>" class="text-decoration-none text-dark">
                                        No Perintah<?= sort_icon_perintah_produksi('no_perintah_produksi') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_perintah_produksi('tanggal_perintah')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_perintah_produksi('tanggal_perintah') ?>
                                    </a>
                                </th>
                                <th>Produk</th>
                                <th>Resep/BOM</th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_perintah_produksi('qty_rencana')) ?>" class="text-decoration-none text-dark">
                                        Qty Rencana<?= sort_icon_perintah_produksi('qty_rencana') ?>
                                    </a>
                                </th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_perintah_produksi('qty_hasil')) ?>" class="text-decoration-none text-dark">
                                        Qty Hasil<?= sort_icon_perintah_produksi('qty_hasil') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_perintah_produksi('status_produksi')) ?>" class="text-decoration-none text-dark">
                                        Status<?= sort_icon_perintah_produksi('status_produksi') ?>
                                    </a>
                                </th>
                                <th width="230" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_perintah->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_perintah as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ((string) $row->status_produksi === 'draft'): ?>
                                                <input type="checkbox" name="id_perintah_produksi[]" value="<?= (int) $row->id_perintah_produksi ?>" class="row-check">
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center"><?= $no++ ?></td>

                                        <td class="fw-semibold">
                                            <?= esc($row->no_perintah_produksi) ?>
                                            <?php if (!empty($row->no_pesanan_penjualan)): ?>
                                                <div class="text-muted small">Pesanan: <?= esc($row->no_pesanan_penjualan) ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= esc($row->tanggal_perintah) ?>
                                            <div class="text-muted small">
                                                <?= esc($row->tanggal_mulai ?? '-') ?> s/d <?= esc($row->tanggal_selesai ?? '-') ?>
                                            </div>
                                        </td>

                                        <td><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></td>

                                        <td><?= esc(($row->kode_resep ?? '-') . ' - ' . ($row->nama_resep ?? '-')) ?></td>

                                        <td class="text-end"><?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?></td>

                                        <td class="text-end"><?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></td>

                                        <td>
                                            <?php $badge = ((string) $row->status_produksi === 'posted') ? 'success' : 'secondary'; ?>
                                            <span class="badge text-bg-<?= esc($badge) ?>">
                                                <?= esc(ucfirst((string) $row->status_produksi)) ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/perintah/detail') . '&id=' . (int) $row->id_perintah_produksi . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ((string) $row->status_produksi === 'draft'): ?>
                                                <a href="<?= esc(admin_page_url('produksi/perintah/edit') . '&id=' . (int) $row->id_perintah_produksi . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/produksi/perintah/posting.php?id=' . (int) $row->id_perintah_produksi . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Posting perintah produksi ini? Setelah posted, data tidak bisa diedit.');"
                                                   title="Posting">
                                                    <i class="bi bi-journal-check"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/produksi/perintah/hapus.php?id=' . (int) $row->id_perintah_produksi . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Yakin ingin menghapus data ini?');"
                                                   title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('menu/produksi/perintah/cetak.php?id=' . (int) $row->id_perintah_produksi)) ?>"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-secondary"
                                               title="Cetak">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        Data perintah produksi belum ada.
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
                            <a class="page-link" href="<?= esc(build_page_url_perintah_produksi(max(1, $page - 1))) ?>">Prev</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_perintah_produksi($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_perintah_produksi(min($totalPages, $page + 1))) ?>">Next</a>
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