<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);
$tanggal_awal = trim((string) ($_GET['tanggal_awal'] ?? ''));
$tanggal_akhir = trim((string) ($_GET['tanggal_akhir'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_stok_opname'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedStatus = ['semua', 'draft', 'posted'];
$allowedSort = [
    'no_stok_opname',
    'tanggal_stok_opname',
    'status_posting',
    'nama_gudang',
];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($status, $allowedStatus, true)) $status = 'semua';
if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_stok_opname';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

function format_rupiah_stok_opname_index($value): string
{
    return 'Rp ' . number_format((float) $value, 2, '.', ',');
}

$query = StokOpnameORM::query()
    ->from('tb_stok_opname as so')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'so.id_gudang')
    ->where('so.id_entitas', $id_entitas);

if ($status !== 'semua') {
    $query->where('so.status_posting', $status);
}

if ($id_gudang > 0) {
    $query->where('so.id_gudang', $id_gudang);
}

if ($tanggal_awal !== '') {
    $query->whereDate('so.tanggal_stok_opname', '>=', $tanggal_awal);
}

if ($tanggal_akhir !== '') {
    $query->whereDate('so.tanggal_stok_opname', '<=', $tanggal_akhir);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('so.no_stok_opname', 'like', '%' . $q . '%')
            ->orWhere('so.catatan', 'like', '%' . $q . '%')
            ->orWhere('g.kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('g.nama_gudang', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$sortColumn = $sort === 'nama_gudang' ? 'g.nama_gudang' : 'so.' . $sort;

$data_opname = (clone $query)
    ->select([
        'so.*',
        'g.kode_gudang',
        'g.nama_gudang',
    ])
    ->orderBy($sortColumn, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

$ids_opname = $data_opname->pluck('id_stok_opname')->map(function ($id) {
    return (int) $id;
})->toArray();

$summary_map = [];

if (count($ids_opname) > 0) {
    $summary_rows = StokOpnameDetailORM::query()
        ->whereIn('id_stok_opname', $ids_opname)
        ->selectRaw("
            id_stok_opname,
            COUNT(*) as jumlah_baris,
            SUM(CASE WHEN selisih_qty > 0 THEN 1 ELSE 0 END) as jumlah_plus,
            SUM(CASE WHEN selisih_qty < 0 THEN 1 ELSE 0 END) as jumlah_minus,
            COALESCE(SUM(nilai_selisih), 0) as total_nilai_selisih
        ")
        ->groupBy('id_stok_opname')
        ->get();

    foreach ($summary_rows as $sr) {
        $summary_map[(int) $sr->id_stok_opname] = [
            'jumlah_baris' => (int) ($sr->jumlah_baris ?? 0),
            'jumlah_plus' => (int) ($sr->jumlah_plus ?? 0),
            'jumlah_minus' => (int) ($sr->jumlah_minus ?? 0),
            'total_nilai_selisih' => (float) ($sr->total_nilai_selisih ?? 0),
        ];
    }
}

$total_draft = StokOpnameORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_posting', 'draft')
    ->count();

$total_posted = StokOpnameORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_posting', 'posted')
    ->count();

$total_nilai_selisih_global = 0;

if ($totalRows > 0) {
    $allIdsQuery = (clone $query)->pluck('so.id_stok_opname')->map(function ($id) {
        return (int) $id;
    })->toArray();

    if (count($allIdsQuery) > 0) {
        $total_nilai_selisih_global = (float) StokOpnameDetailORM::query()
            ->whereIn('id_stok_opname', $allIdsQuery)
            ->sum('nilai_selisih');
    }
}

$gudang_options = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

function build_sort_url_stok_opname(string $column): string
{
    $params = [
        'menu'          => 'persediaan/stok-opname',
        'q'             => trim((string) ($_GET['q'] ?? '')),
        'status'        => trim((string) ($_GET['status'] ?? 'semua')),
        'id_gudang'     => (int) ($_GET['id_gudang'] ?? 0),
        'tanggal_awal'  => trim((string) ($_GET['tanggal_awal'] ?? '')),
        'tanggal_akhir' => trim((string) ($_GET['tanggal_akhir'] ?? '')),
        'sort'          => $column,
        'order'         => 'asc',
        'per_page'      => (int) ($_GET['per_page'] ?? 10),
        'hal'           => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_stok_opname'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_stok_opname(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_stok_opname'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_stok_opname(int $targetPage): string
{
    $params = [
        'menu'          => 'persediaan/stok-opname',
        'q'             => trim((string) ($_GET['q'] ?? '')),
        'status'        => trim((string) ($_GET['status'] ?? 'semua')),
        'id_gudang'     => (int) ($_GET['id_gudang'] ?? 0),
        'tanggal_awal'  => trim((string) ($_GET['tanggal_awal'] ?? '')),
        'tanggal_akhir' => trim((string) ($_GET['tanggal_akhir'] ?? '')),
        'sort'          => trim((string) ($_GET['sort'] ?? 'tanggal_stok_opname')),
        'order'         => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page'      => (int) ($_GET['per_page'] ?? 10),
        'hal'           => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);

$cetakQuery = $_GET;
unset($cetakQuery['menu']);

$urlCetak = admin_url(
    'index.php?menu=persediaan/stok-opname/cetak' .
    (count($cetakQuery) ? '&' . http_build_query($cetakQuery) : '')
);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Stok Opname</h1>
    <p class="page-subtitle">Pencocokan stok sistem dengan stok fisik di gudang.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Total Dokumen</div>
                        <div class="h4 mb-0"><?= number_format((int) $totalRows, 0, '.', ',') ?></div>
                    </div>
                    <div class="text-primary fs-4">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Draft</div>
                        <div class="h4 mb-0"><?= number_format((int) $total_draft, 0, '.', ',') ?></div>
                    </div>
                    <div class="text-secondary fs-4">
                        <i class="bi bi-file-earmark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Posted</div>
                        <div class="h4 mb-0"><?= number_format((int) $total_posted, 0, '.', ',') ?></div>
                    </div>
                    <div class="text-success fs-4">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Nilai Selisih</div>
                        <div class="h4 mb-0"><?= format_rupiah_stok_opname_index($total_nilai_selisih_global) ?></div>
                    </div>
                    <div class="text-warning fs-4">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Stok Opname</h2>
                <div class="text-muted small">
                    Qty tidak ditotal karena satuan barang dapat berbeda. Nilai selisih tetap boleh ditotal.
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <a href="<?= esc($urlCetak) ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i>Cetak
                </a>

                <a href="<?= esc(admin_page_url('persediaan/stok-opname/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Stok Opname
                </a>
            </div>
        </div>

        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="menu" value="persediaan/stok-opname">
            <input type="hidden" name="sort" value="<?= esc($sort) ?>">
            <input type="hidden" name="order" value="<?= esc($order) ?>">

            <div class="col-md-3">
                <label class="form-label fw-semibold">Cari</label>
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="No opname, gudang, catatan..."
                    value="<?= esc($q) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Status</label>
                <select name="status" class="form-select">
                    <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="posted" <?= $status === 'posted' ? 'selected' : '' ?>>Posted</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Gudang</label>
                <select name="id_gudang" class="form-select">
                    <option value="0">Semua Gudang</option>
                    <?php foreach ($gudang_options as $g): ?>
                        <option value="<?= (int) $g->id_gudang ?>" <?= $id_gudang === (int) $g->id_gudang ? 'selected' : '' ?>>
                            <?= esc(($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?= esc($tanggal_awal) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?= esc($tanggal_akhir) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Limit</label>
                <select name="per_page" class="form-select">
                    <?php foreach ($allowedPerPage as $limit): ?>
                        <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>>
                            <?= $limit ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-10 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i>Filter
                </button>

                <?php if (
                    $q !== '' ||
                    $status !== 'semua' ||
                    $id_gudang > 0 ||
                    $tanggal_awal !== '' ||
                    $tanggal_akhir !== '' ||
                    $perPage !== 10
                ): ?>
                    <a href="<?= esc(admin_page_url('persediaan/stok-opname')) ?>" class="btn btn-outline-secondary">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <form method="post" action="<?= esc(admin_url('index.php?menu=persediaan/stok-opname/hapus-massal')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
            <input type="hidden" name="back_url" value="<?= esc($current_url) ?>">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>

                <div class="text-muted small">Hanya dokumen draft yang bisa dihapus.</div>
            </div>

            <div class="table-responsive border rounded">
                <div style="max-height:460px; overflow-y:auto;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="45" class="text-center">
                                    <input type="checkbox" onclick="document.querySelectorAll('.check-row').forEach(cb => cb.checked = this.checked)">
                                </th>
                                <th width="55" class="text-center">No</th>
                                <th>
                                    <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_opname('no_stok_opname')) ?>">
                                        No Opname<?= sort_icon_stok_opname('no_stok_opname') ?>
                                    </a>
                                </th>
                                <th width="140">
                                    <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_opname('tanggal_stok_opname')) ?>">
                                        Tanggal<?= sort_icon_stok_opname('tanggal_stok_opname') ?>
                                    </a>
                                </th>
                                <th>
                                    <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_opname('nama_gudang')) ?>">
                                        Gudang<?= sort_icon_stok_opname('nama_gudang') ?>
                                    </a>
                                </th>
                                <th width="110" class="text-center">Detail</th>
                                <th width="110" class="text-center">Selisih +</th>
                                <th width="110" class="text-center">Selisih -</th>
                                <th width="160" class="text-end">Nilai Selisih</th>
                                <th width="110" class="text-center">
                                    <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_opname('status_posting')) ?>">
                                        Status<?= sort_icon_stok_opname('status_posting') ?>
                                    </a>
                                </th>
                                <th width="220" class="text-end">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_opname->count() === 0): ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        Belum ada data stok opname.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($data_opname as $i => $row): ?>
                                    <?php
                                    $id_row = (int) $row->id_stok_opname;
                                    $summary = $summary_map[$id_row] ?? [
                                        'jumlah_baris' => 0,
                                        'jumlah_plus' => 0,
                                        'jumlah_minus' => 0,
                                        'total_nilai_selisih' => 0,
                                    ];
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ((string) $row->status_posting === 'draft'): ?>
                                                <input type="checkbox" name="ids[]" value="<?= $id_row ?>" class="check-row">
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center"><?= $offset + $i + 1 ?></td>

                                        <td>
                                            <div class="fw-semibold"><?= esc($row->no_stok_opname ?? '-') ?></div>
                                            <div class="text-muted small">
                                                <?= esc(!empty($row->tanggal_dibuat) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?>
                                            </div>
                                        </td>

                                        <td><?= esc(date('d/m/Y', strtotime((string) $row->tanggal_stok_opname))) ?></td>

                                        <td>
                                            <div class="fw-semibold"><?= esc($row->nama_gudang ?? '-') ?></div>
                                            <div class="text-muted small"><?= esc($row->kode_gudang ?? '-') ?></div>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border">
                                                <?= number_format((int) $summary['jumlah_baris'], 0, '.', ',') ?> item
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-success">
                                                <?= number_format((int) $summary['jumlah_plus'], 0, '.', ',') ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <span class="badge bg-danger">
                                                <?= number_format((int) $summary['jumlah_minus'], 0, '.', ',') ?>
                                            </span>
                                        </td>

                                        <td class="text-end fw-semibold">
                                            <?= format_rupiah_stok_opname_index($summary['total_nilai_selisih']) ?>
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
                                                    href="<?= esc(admin_page_url('persediaan/stok-opname/detail') . '&id=' . $id_row . '&back_url=' . $back_url) ?>"
                                                    class="btn btn-outline-info"
                                                    title="Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>

                                                <?php if ((string) $row->status_posting === 'draft'): ?>
                                                    <a
                                                        href="<?= esc(admin_page_url('persediaan/stok-opname/edit') . '&id=' . $id_row . '&back_url=' . $back_url) ?>"
                                                        class="btn btn-outline-warning"
                                                        title="Edit">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>

                                                    <a
                                                        href="<?= esc(admin_url('index.php?menu=persediaan/stok-opname/posting&id=' . $id_row . '&back_url=' . $back_url)) ?>"
                                                        class="btn btn-outline-success"
                                                        onclick="return confirm('Posting stok opname ini? Selisih stok akan membentuk mutasi stok, update saldo stok, dan jurnal koreksi.')"
                                                        title="Posting">
                                                        <i class="bi bi-check-circle"></i>
                                                    </a>

                                                    <a
                                                        href="<?= esc(admin_url('index.php?menu=persediaan/stok-opname/hapus&id=' . $id_row . '&back_url=' . $back_url)) ?>"
                                                        class="btn btn-outline-danger"
                                                        onclick="return confirm('Hapus stok opname ini?')"
                                                        title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <a
                                                    href="<?= esc(admin_url('index.php?menu=persediaan/stok-opname/cetak&id=' . $id_row)) ?>"
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
                                <th colspan="8" class="text-end">Total Nilai Selisih Halaman Ini</th>
                                <th class="text-end">
                                    <?php
                                    $total_halaman = 0;
                                    foreach ($data_opname as $row) {
                                        $id_row = (int) $row->id_stok_opname;
                                        $total_halaman += (float) ($summary_map[$id_row]['total_nilai_selisih'] ?? 0);
                                    }
                                    echo format_rupiah_stok_opname_index($total_halaman);
                                    ?>
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
                <div class="text-muted small">
                    Halaman <?= (int) $page ?> dari <?= (int) $totalPages ?>.
                    Menampilkan <?= number_format($data_opname->count(), 0, '.', ',') ?>
                    dari <?= number_format($totalRows, 0, '.', ',') ?> data.
                </div>

                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_stok_opname(max(1, $page - 1))) ?>">
                                Sebelumnya
                            </a>
                        </li>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        ?>

                        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_stok_opname($p)) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_stok_opname(min($totalPages, $page + 1))) ?>">
                                Berikutnya
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </form>
    </div>
</div>