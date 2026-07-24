<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_biaya'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedStatus = ['semua', 'draft', 'posted'];
$allowedSort = ['no_biaya_produksi', 'tanggal_biaya', 'jumlah_biaya', 'status_posting'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($status, $allowedStatus, true)) $status = 'semua';
if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_biaya';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = BiayaProduksiORM::query()
    ->from('tb_biaya_produksi as bp')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'bp.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->where('bp.id_entitas', $id_entitas);

if ($status !== 'semua') {
    $query->where('bp.status_posting', $status);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('bp.no_biaya_produksi', 'like', '%' . $q . '%')
            ->orWhere('bp.keterangan', 'like', '%' . $q . '%')
            ->orWhere('pp.no_perintah_produksi', 'like', '%' . $q . '%')
            ->orWhere('pr.kode_produk', 'like', '%' . $q . '%')
            ->orWhere('pr.nama_produk', 'like', '%' . $q . '%')
            ->orWhereExists(function ($exists) use ($q) {
                $exists->from('tb_biaya_produksi_detail as bpd')
                    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'bpd.id_coa_lawan')
                    ->whereColumn('bpd.id_biaya_produksi', 'bp.id_biaya_produksi')
                    ->where(function ($w) use ($q) {
                        $w->where('bpd.jenis_biaya_produksi', 'like', '%' . $q . '%')
                            ->orWhere('bpd.keterangan', 'like', '%' . $q . '%')
                            ->orWhere('c.kode_coa', 'like', '%' . $q . '%')
                            ->orWhere('c.nama_coa', 'like', '%' . $q . '%');
                    });
            });
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_biaya = $query
    ->select([
        'bp.*',
        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.qty_hasil',
        'pp.status_produksi',
        'pr.kode_produk',
        'pr.nama_produk',
    ])
    ->orderBy('bp.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

$ids_biaya = $data_biaya->pluck('id_biaya_produksi')->map(function ($id) {
    return (int) $id;
})->toArray();

$jumlah_detail_map = [];
$jenis_detail_map = [];

if (count($ids_biaya) > 0) {
    $detail_summary_rows = BiayaProduksiDetailORM::query()
        ->from('tb_biaya_produksi_detail as bpd')
        ->whereIn('bpd.id_biaya_produksi', $ids_biaya)
        ->selectRaw('bpd.id_biaya_produksi, COUNT(*) as jumlah_jenis')
        ->groupBy('bpd.id_biaya_produksi')
        ->get();

    foreach ($detail_summary_rows as $jr) {
        $jumlah_detail_map[(int) $jr->id_biaya_produksi] = (int) $jr->jumlah_jenis;
    }

    $jenis_rows = BiayaProduksiDetailORM::query()
        ->whereIn('id_biaya_produksi', $ids_biaya)
        ->select(['id_biaya_produksi', 'jenis_biaya_produksi'])
        ->orderBy('id_biaya_produksi_detail', 'asc')
        ->get();

    foreach ($jenis_rows as $jr) {
        $key = (int) $jr->id_biaya_produksi;

        if (!isset($jenis_detail_map[$key])) {
            $jenis_detail_map[$key] = [];
        }

        if (count($jenis_detail_map[$key]) < 3) {
            $jenis_detail_map[$key][] = ucfirst(str_replace('_', ' ', (string) $jr->jenis_biaya_produksi));
        }
    }
}

function build_sort_url_biaya_produksi(string $column): string
{
    $params = [
        'menu'     => 'produksi/biaya',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_biaya'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_biaya_produksi(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_biaya'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_biaya_produksi(int $targetPage): string
{
    $params = [
        'menu'     => 'produksi/biaya',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_biaya')),
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
    <h1 class="page-title">Biaya Produksi</h1>
    <p class="page-subtitle">Kelola banyak jenis biaya untuk satu perintah produksi</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Biaya Produksi</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="produksi/biaya">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari biaya, perintah, produk, jenis biaya..."
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
                        <a href="<?= esc(admin_page_url('produksi/biaya')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('produksi/biaya/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Biaya
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/produksi/biaya/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
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
                                    <a href="<?= esc(build_sort_url_biaya_produksi('no_biaya_produksi')) ?>" class="text-decoration-none text-dark">
                                        No Biaya<?= sort_icon_biaya_produksi('no_biaya_produksi') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_biaya_produksi('tanggal_biaya')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_biaya_produksi('tanggal_biaya') ?>
                                    </a>
                                </th>
                                <th>Perintah Produksi</th>
                                <th>Produk</th>
                                <th>Jenis Biaya</th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_biaya_produksi('jumlah_biaya')) ?>" class="text-decoration-none text-dark">
                                        Total<?= sort_icon_biaya_produksi('jumlah_biaya') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_biaya_produksi('status_posting')) ?>" class="text-decoration-none text-dark">
                                        Status<?= sort_icon_biaya_produksi('status_posting') ?>
                                    </a>
                                </th>
                                <th width="230" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_biaya->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_biaya as $row): ?>
                                    <?php
                                    $id_biaya = (int) $row->id_biaya_produksi;
                                    $jumlah_jenis = (int) ($jumlah_detail_map[$id_biaya] ?? 0);
                                    $jenis_ringkas = $jenis_detail_map[$id_biaya] ?? [];
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ((string) $row->status_posting === 'draft'): ?>
                                                <input type="checkbox" name="id_biaya_produksi[]" value="<?= (int) $row->id_biaya_produksi ?>" class="row-check">
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->no_biaya_produksi) ?></td>
                                        <td><?= esc($row->tanggal_biaya) ?></td>
                                        <td><?= esc($row->no_perintah_produksi ?? '-') ?></td>
                                        <td><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= (int) $jumlah_jenis ?> Jenis</div>
                                            <?php if (count($jenis_ringkas) > 0): ?>
                                                <div class="text-muted small"><?= esc(implode(', ', $jenis_ringkas)) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">Rp <?= esc(number_format((float) $row->jumlah_biaya, 2, '.', ',')) ?></td>
                                        <td>
                                            <?php $badge = ((string) $row->status_posting === 'posted') ? 'success' : 'secondary'; ?>
                                            <span class="badge text-bg-<?= esc($badge) ?>">
                                                <?= esc(ucfirst((string) $row->status_posting)) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/biaya/detail') . '&id=' . (int) $row->id_biaya_produksi . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ((string) $row->status_posting === 'draft'): ?>
                                                <a href="<?= esc(admin_page_url('produksi/biaya/edit') . '&id=' . (int) $row->id_biaya_produksi . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/produksi/biaya/posting.php?id=' . (int) $row->id_biaya_produksi . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Posting biaya produksi ini? Jurnal biaya produksi akan dibuat otomatis dari template.');"
                                                   title="Posting">
                                                    <i class="bi bi-journal-check"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/produksi/biaya/hapus.php?id=' . (int) $row->id_biaya_produksi . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Yakin ingin menghapus data ini?');"
                                                   title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('menu/produksi/biaya/cetak.php?id=' . (int) $row->id_biaya_produksi)) ?>"
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
                                        Data biaya produksi belum ada.
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
                            <a class="page-link" href="<?= esc(build_page_url_biaya_produksi(max(1, $page - 1))) ?>">Prev</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_biaya_produksi($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_biaya_produksi(min($totalPages, $page + 1))) ?>">Next</a>
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