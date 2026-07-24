<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_hasil'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedStatus = ['semua', 'draft', 'posted'];
$allowedSort = [
    'no_hasil_produksi',
    'tanggal_hasil',
    'qty_hasil',
    'total_hpp',
    'hpp_per_unit',
    'status_posting',
];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($status, $allowedStatus, true)) $status = 'semua';
if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_hasil';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = HasilProduksiORM::query()
    ->from('tb_hasil_produksi as hp')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'hp.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'hp.id_produk')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'hp.id_gudang')
    ->where('hp.id_entitas', $id_entitas);

if ($status !== 'semua') {
    $query->where('hp.status_posting', $status);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('hp.no_hasil_produksi', 'like', '%' . $q . '%')
            ->orWhere('hp.catatan', 'like', '%' . $q . '%')
            ->orWhere('pp.no_perintah_produksi', 'like', '%' . $q . '%')
            ->orWhere('pr.kode_produk', 'like', '%' . $q . '%')
            ->orWhere('pr.nama_produk', 'like', '%' . $q . '%')
            ->orWhere('g.kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('g.nama_gudang', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_hasil = $query
    ->select([
        'hp.*',
        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.status_produksi',
        'pr.kode_produk',
        'pr.nama_produk',
        'g.kode_gudang',
        'g.nama_gudang',
    ])
    ->orderBy('hp.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_hasil_produksi(string $column): string
{
    $params = [
        'menu'     => 'produksi/hasil',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_hasil'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_hasil_produksi(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_hasil'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_hasil_produksi(int $targetPage): string
{
    $params = [
        'menu'     => 'produksi/hasil',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_hasil')),
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
    <h1 class="page-title">Hasil Produksi</h1>
    <p class="page-subtitle">Input hasil produksi, hitung HPP, dan posting stok produk jadi</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Hasil Produksi</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="produksi/hasil">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari hasil, perintah, produk, gudang..."
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
                        <a href="<?= esc(admin_page_url('produksi/hasil')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('produksi/hasil/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Hasil
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/produksi/hasil/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
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
                                    <a href="<?= esc(build_sort_url_hasil_produksi('no_hasil_produksi')) ?>" class="text-decoration-none text-dark">
                                        No Hasil<?= sort_icon_hasil_produksi('no_hasil_produksi') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_hasil_produksi('tanggal_hasil')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_hasil_produksi('tanggal_hasil') ?>
                                    </a>
                                </th>
                                <th>Perintah Produksi</th>
                                <th>Produk</th>
                                <th>Gudang</th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_hasil_produksi('qty_hasil')) ?>" class="text-decoration-none text-dark">
                                        Qty Hasil<?= sort_icon_hasil_produksi('qty_hasil') ?>
                                    </a>
                                </th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_hasil_produksi('total_hpp')) ?>" class="text-decoration-none text-dark">
                                        Total HPP<?= sort_icon_hasil_produksi('total_hpp') ?>
                                    </a>
                                </th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_hasil_produksi('hpp_per_unit')) ?>" class="text-decoration-none text-dark">
                                        HPP/Unit<?= sort_icon_hasil_produksi('hpp_per_unit') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_hasil_produksi('status_posting')) ?>" class="text-decoration-none text-dark">
                                        Status<?= sort_icon_hasil_produksi('status_posting') ?>
                                    </a>
                                </th>
                                <th width="250" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_hasil->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_hasil as $row): ?>
                                    <?php
                                    $status_row = (string) ($row->status_posting ?? 'draft');
                                    $badge = $status_row === 'posted' ? 'success' : 'secondary';
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ($status_row === 'draft'): ?>
                                                <input type="checkbox" name="id_hasil_produksi[]" value="<?= (int) $row->id_hasil_produksi ?>" class="row-check">
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->no_hasil_produksi ?? '-') ?></td>
                                        <td><?= esc($row->tanggal_hasil ?? '-') ?></td>
                                        <td>
                                            <?= esc($row->no_perintah_produksi ?? '-') ?>
                                            <div class="text-muted small">
                                                Qty rencana: <?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?>
                                            </div>
                                        </td>
                                        <td><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></td>
                                        <td><?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></td>
                                        <td class="text-end"><?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></td>
                                        <td class="text-end">Rp <?= esc(number_format((float) ($row->total_hpp ?? 0), 2, '.', ',')) ?></td>
                                        <td class="text-end">Rp <?= esc(number_format((float) ($row->hpp_per_unit ?? 0), 2, '.', ',')) ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= esc($badge) ?>">
                                                <?= esc(ucfirst($status_row)) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/hasil/detail') . '&id=' . (int) $row->id_hasil_produksi . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ($status_row === 'draft'): ?>
                                                <a href="<?= esc(admin_page_url('produksi/hasil/edit') . '&id=' . (int) $row->id_hasil_produksi . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('index.php?menu=produksi/hasil/posting&id=' . (int) $row->id_hasil_produksi . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Posting hasil produksi ini? Stok produk jadi akan bertambah dan jurnal akan dibuat.');"
                                                   title="Posting">
                                                    <i class="bi bi-journal-check"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/produksi/hasil/hapus.php?id=' . (int) $row->id_hasil_produksi . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Yakin ingin menghapus hasil produksi draft ini?');"
                                                   title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('index.php?menu=produksi/hasil/cetak&id=' . (int) $row->id_hasil_produksi)) ?>"
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
                                    <td colspan="12" class="text-center text-muted py-4">
                                        Data hasil produksi belum ada.
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
                            <a class="page-link" href="<?= esc(build_page_url_hasil_produksi(max(1, $page - 1))) ?>">Prev</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_hasil_produksi($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_hasil_produksi(min($totalPages, $page + 1))) ?>">Next</a>
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