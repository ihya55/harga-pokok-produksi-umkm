<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_saldo_awal'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedStatus = ['semua', 'draft', 'posted'];
$allowedSort = [
    'no_saldo_awal_stok',
    'tanggal_saldo_awal',
    'status_posting',
    'total_nilai',
];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($status, $allowedStatus, true)) $status = 'semua';
if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_saldo_awal';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = SaldoAwalStokORM::query()
    ->from('tb_saldo_awal_stok as sa')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'sa.id_gudang')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'sa.id_coa_lawan')
    ->where('sa.id_entitas', $id_entitas);

if ($status !== 'semua') {
    $query->where('sa.status_posting', $status);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('sa.no_saldo_awal_stok', 'like', '%' . $q . '%')
            ->orWhere('sa.catatan', 'like', '%' . $q . '%')
            ->orWhere('g.kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('g.nama_gudang', 'like', '%' . $q . '%')
            ->orWhere('c.kode_coa', 'like', '%' . $q . '%')
            ->orWhere('c.nama_coa', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_saldo_awal = $query
    ->select([
        'sa.*',
        'g.kode_gudang',
        'g.nama_gudang',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderBy('sa.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

$ids_saldo_awal = $data_saldo_awal->pluck('id_saldo_awal_stok')->map(function ($id) {
    return (int) $id;
})->toArray();

$jumlah_detail_map = [];

if (count($ids_saldo_awal) > 0) {
    $summary_rows = SaldoAwalStokDetailORM::query()
        ->whereIn('id_saldo_awal_stok', $ids_saldo_awal)
        ->selectRaw('id_saldo_awal_stok, COUNT(*) as jumlah_baris')
        ->groupBy('id_saldo_awal_stok')
        ->get();

    foreach ($summary_rows as $sr) {
        $jumlah_detail_map[(int) $sr->id_saldo_awal_stok] = (int) $sr->jumlah_baris;
    }
}

function build_sort_url_saldo_awal_stok(string $column): string
{
    $params = [
        'menu'     => 'persediaan/saldo-stok/saldo-awal',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_saldo_awal'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_saldo_awal_stok(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_saldo_awal'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_saldo_awal_stok(int $targetPage): string
{
    $params = [
        'menu'     => 'persediaan/saldo-stok/saldo-awal',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_saldo_awal')),
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
    <h1 class="page-title">Saldo Awal Stok</h1>
    <p class="page-subtitle">Input saldo stok awal yang akan membentuk mutasi stok, saldo stok, dan jurnal akuntansi</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Saldo Awal Stok</h2>
                <div class="text-muted small">
                    Total data: <?= (int) $totalRows ?>. Qty tidak ditotal karena satuan barang dapat berbeda.
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="persediaan/saldo-stok/saldo-awal">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari no saldo awal, gudang, akun..."
                        value="<?= esc($q) ?>">

                    <select name="status" class="form-select" style="min-width:150px;" onchange="this.form.submit()">
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
                        <a href="<?= esc(admin_page_url('persediaan/saldo-stok/saldo-awal')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('persediaan/saldo-stok')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Saldo Stok
                </a>

                <a href="<?= esc(admin_page_url('persediaan/saldo-stok/saldo-awal/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Saldo Awal
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('index.php?menu=persediaan/saldo-stok/saldo-awal/hapus-massal')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
            <input type="hidden" name="back_url" value="<?= esc($current_url) ?>">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>

                <div class="text-muted small">Hanya status draft yang bisa dihapus</div>
            </div>

            <div class="table-responsive border rounded">
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="45" class="text-center">
                                    <input type="checkbox" onclick="document.querySelectorAll('.check-row').forEach(cb => cb.checked = this.checked)">
                                </th>
                                <th width="55" class="text-center">No</th>
                                <th>
                                    <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_saldo_awal_stok('no_saldo_awal_stok')) ?>">
                                        No Saldo Awal<?= sort_icon_saldo_awal_stok('no_saldo_awal_stok') ?>
                                    </a>
                                </th>
                                <th>
                                    <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_saldo_awal_stok('tanggal_saldo_awal')) ?>">
                                        Tanggal<?= sort_icon_saldo_awal_stok('tanggal_saldo_awal') ?>
                                    </a>
                                </th>
                                <th>Gudang</th>
                                <th>Akun Lawan</th>
                                <th class="text-center">Detail</th>
                                <th class="text-end">
                                    <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_saldo_awal_stok('total_nilai')) ?>">
                                        Total Nilai<?= sort_icon_saldo_awal_stok('total_nilai') ?>
                                    </a>
                                </th>
                                <th class="text-center">
                                    <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_saldo_awal_stok('status_posting')) ?>">
                                        Status<?= sort_icon_saldo_awal_stok('status_posting') ?>
                                    </a>
                                </th>
                                <th width="220" class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_saldo_awal->count() === 0): ?>
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        Belum ada data saldo awal stok.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data_saldo_awal as $i => $row): ?>
                                    <?php
                                    $id_row = (int) $row->id_saldo_awal_stok;
                                    $jumlah_detail = $jumlah_detail_map[$id_row] ?? 0;
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ((string) $row->status_posting === 'draft'): ?>
                                                <input type="checkbox" name="ids[]" value="<?= $id_row ?>" class="check-row">
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center"><?= $offset + $i + 1 ?></td>

                                        <td>
                                            <div class="fw-semibold"><?= esc($row->no_saldo_awal_stok ?? '-') ?></div>
                                            <div class="text-muted small">Dokumen saldo awal</div>
                                        </td>

                                        <td><?= esc(date('d/m/Y', strtotime((string) $row->tanggal_saldo_awal))) ?></td>

                                        <td>
                                            <div class="fw-semibold"><?= esc($row->nama_gudang ?? '-') ?></div>
                                            <div class="text-muted small"><?= esc($row->kode_gudang ?? '-') ?></div>
                                        </td>

                                        <td>
                                            <div class="fw-semibold"><?= esc($row->kode_coa ?? '-') ?></div>
                                            <div class="text-muted small"><?= esc($row->nama_coa ?? '-') ?></div>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border"><?= (int) $jumlah_detail ?> item</span>
                                        </td>

                                        <td class="text-end fw-semibold">
                                            Rp <?= number_format((float) ($row->total_nilai ?? 0), 2, '.', ',') ?>
                                        </td>

                                        <td class="text-center">
                                            <?php if ((string) $row->status_posting === 'posted'): ?>
                                                <span class="badge bg-success">Posted</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Draft</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a
                                                    href="<?= esc(admin_page_url('persediaan/saldo-stok/saldo-awal/detail') . '&id=' . $id_row . '&back_url=' . $back_url) ?>"
                                                    class="btn btn-outline-info"
                                                    title="Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>

                                                <?php if ((string) $row->status_posting === 'draft'): ?>
                                                    <a
                                                        href="<?= esc(admin_page_url('persediaan/saldo-stok/saldo-awal/edit') . '&id=' . $id_row . '&back_url=' . $back_url) ?>"
                                                        class="btn btn-outline-warning"
                                                        title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>

                                                    <a
                                                        href="<?= esc(admin_url('index.php?menu=persediaan/saldo-stok/saldo-awal/posting&id=' . $id_row . '&back_url=' . $back_url)) ?>"
                                                        class="btn btn-outline-success"
                                                        onclick="return confirm('Posting saldo awal stok ini?')"
                                                        title="Posting">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>

                                                    <a
                                                        href="<?= esc(admin_url('index.php?menu=persediaan/saldo-stok/saldo-awal/hapus&id=' . $id_row . '&back_url=' . $back_url)) ?>"
                                                        class="btn btn-outline-danger"
                                                        onclick="return confirm('Hapus saldo awal stok ini?')"
                                                        title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <a
                                                    href="<?= esc(admin_url('index.php?menu=persediaan/saldo-stok/saldo-awal/cetak&id=' . $id_row)) ?>"
                                                    target="_blank"
                                                    class="btn btn-outline-secondary"
                                                    title="Cetak">
                                                    <i class="bi bi-printer"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>

                        <tfoot class="table-light">
                            <tr>
                                <th colspan="7" class="text-end">Total Nilai Halaman Ini</th>
                                <th class="text-end">
                                    Rp <?= number_format((float) $data_saldo_awal->sum('total_nilai'), 2, '.', ',') ?>
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
                <div class="text-muted small">
                    Halaman <?= (int) $page ?> dari <?= (int) $totalPages ?>
                </div>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_saldo_awal_stok(max(1, $page - 1))) ?>">
                                Sebelumnya
                            </a>
                        </li>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        ?>

                        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_saldo_awal_stok($p)) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_saldo_awal_stok(min($totalPages, $page + 1))) ?>">
                                Berikutnya
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </form>
    </div>
</div>