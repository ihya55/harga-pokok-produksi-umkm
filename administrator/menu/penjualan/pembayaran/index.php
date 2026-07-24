<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$metode = trim((string) ($_GET['metode'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_pembayaran'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedSort = [
    'no_pembayaran_penjualan',
    'tanggal_pembayaran',
    'no_faktur_penjualan',
    'nama_pelanggan',
    'metode_pembayaran',
    'jumlah_bayar',
    'status_posting',
];

$allowedStatus = ['semua', 'draft', 'posted', 'batal'];
$allowedMetode = ['semua', 'tunai', 'transfer'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'tanggal_pembayaran';
}

if (!in_array($order, ['asc', 'desc'], true)) {
    $order = 'desc';
}

if (!in_array($status, $allowedStatus, true)) {
    $status = 'semua';
}

if (!in_array($metode, $allowedMetode, true)) {
    $metode = 'semua';
}

if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

if ($page < 1) {
    $page = 1;
}

if (!function_exists('pembayaran_penjualan_uang')) {
    function pembayaran_penjualan_uang($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pembayaran_penjualan_badge_status')) {
    function pembayaran_penjualan_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        if ($status === 'posted') {
            return '<span class="badge text-bg-success">Posted</span>';
        }

        if ($status === 'batal') {
            return '<span class="badge text-bg-danger">Batal</span>';
        }

        return '<span class="badge text-bg-secondary">Draft</span>';
    }
}

$query = Capsule::table('tb_pembayaran_penjualan as p')
    ->leftJoin('tb_faktur_penjualan as f', 'f.id_faktur_penjualan', '=', 'p.id_faktur_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'p.id_pelanggan')
    ->leftJoin('tb_coa as coa', 'coa.id_coa', '=', 'p.id_coa_kas_bank')
    ->where('p.id_entitas', $id_entitas);

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('p.no_pembayaran_penjualan', 'like', '%' . $q . '%')
            ->orWhere('f.no_faktur_penjualan', 'like', '%' . $q . '%')
            ->orWhere('pl.kode_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('pl.nama_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('coa.kode_coa', 'like', '%' . $q . '%')
            ->orWhere('coa.nama_coa', 'like', '%' . $q . '%');
    });
}

if ($status !== 'semua') {
    $query->where('p.status_posting', $status);
}

if ($metode !== 'semua') {
    $query->where('p.metode_pembayaran', $metode);
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$orderColumn = match ($sort) {
    'no_faktur_penjualan' => 'f.no_faktur_penjualan',
    'nama_pelanggan' => 'pl.nama_pelanggan',
    default => 'p.' . $sort,
};

$data_pembayaran = $query
    ->select([
        'p.*',
        'f.no_faktur_penjualan',
        'f.tanggal_faktur',
        'f.total as total_faktur',
        'f.sisa_piutang',
        'f.status_faktur',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'coa.kode_coa',
        'coa.nama_coa',
    ])
    ->orderBy($orderColumn, $order)
    ->orderBy('p.id_pembayaran_penjualan', 'desc')
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_pembayaran_penjualan(string $column): string
{
    $params = [
        'menu'     => 'penjualan/pembayaran',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'metode'   => trim((string) ($_GET['metode'] ?? 'semua')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_pembayaran'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_pembayaran_penjualan(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_pembayaran'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_pembayaran_penjualan(int $targetPage): string
{
    $params = [
        'menu'     => 'penjualan/pembayaran',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'metode'   => trim((string) ($_GET['metode'] ?? 'semua')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_pembayaran')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);

$total_draft = (int) Capsule::table('tb_pembayaran_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_posting', 'draft')
    ->count();

$total_posted_bulan_ini = (float) Capsule::table('tb_pembayaran_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_posting', 'posted')
    ->whereBetween('tanggal_pembayaran', [date('Y-m-01'), date('Y-m-t')])
    ->sum('jumlah_bayar');

$total_piutang = (float) Capsule::table('tb_faktur_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('jenis_pembayaran', 'kredit')
    ->where('sisa_piutang', '>', 0)
    ->sum('sisa_piutang');

$faktur_belum_lunas = (int) Capsule::table('tb_faktur_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('jenis_pembayaran', 'kredit')
    ->where('sisa_piutang', '>', 0)
    ->count();
?>

<div class="page-header mb-4">
    <h1 class="page-title">Pembayaran Penjualan</h1>
    <p class="page-subtitle">Kelola penerimaan pembayaran dari piutang pelanggan kredit</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Draft Pembayaran</div>
                <div class="h4 mb-0"><?= number_format($total_draft, 0, '.', ',') ?></div>
                <div class="text-muted small">Belum diposting</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Penerimaan Bulan Ini</div>
                <div class="h4 mb-0">Rp <?= pembayaran_penjualan_uang($total_posted_bulan_ini) ?></div>
                <div class="text-muted small">Pembayaran posted</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Sisa Piutang</div>
                <div class="h4 mb-0">Rp <?= pembayaran_penjualan_uang($total_piutang) ?></div>
                <div class="text-muted small">Piutang kredit belum lunas</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Faktur Belum Lunas</div>
                <div class="h4 mb-0"><?= number_format($faktur_belum_lunas, 0, '.', ',') ?></div>
                <div class="text-muted small">Siap dibayar</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Pembayaran Penjualan</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="penjualan/pembayaran">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari pembayaran, faktur, pelanggan..."
                        value="<?= esc($q) ?>">

                    <select name="status" class="form-select" style="min-width:145px;" onchange="this.form.submit()">
                        <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="posted" <?= $status === 'posted' ? 'selected' : '' ?>>Posted</option>
                        <option value="batal" <?= $status === 'batal' ? 'selected' : '' ?>>Batal</option>
                    </select>

                    <select name="metode" class="form-select" style="min-width:145px;" onchange="this.form.submit()">
                        <option value="semua" <?= $metode === 'semua' ? 'selected' : '' ?>>Semua Metode</option>
                        <option value="tunai" <?= $metode === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                        <option value="transfer" <?= $metode === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                    </select>

                    <select name="per_page" class="form-select" style="min-width:120px;" onchange="this.form.submit()">
                        <?php foreach ($allowedPerPage as $limit): ?>
                            <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>>
                                <?= $limit ?> baris
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-outline-primary" title="Cari">
                        <i class="bi bi-search"></i>
                    </button>

                    <?php if ($q !== '' || $status !== 'semua' || $metode !== 'semua' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('penjualan/pembayaran')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('penjualan/pembayaran/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Pembayaran
                </a>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <div style="max-height:420px; overflow-y:auto;">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="70" class="text-center">No</th>

                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_penjualan('no_pembayaran_penjualan')) ?>" class="text-decoration-none text-dark">
                                    No Pembayaran<?= sort_icon_pembayaran_penjualan('no_pembayaran_penjualan') ?>
                                </a>
                            </th>

                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_penjualan('tanggal_pembayaran')) ?>" class="text-decoration-none text-dark">
                                    Tanggal<?= sort_icon_pembayaran_penjualan('tanggal_pembayaran') ?>
                                </a>
                            </th>

                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_penjualan('no_faktur_penjualan')) ?>" class="text-decoration-none text-dark">
                                    Faktur<?= sort_icon_pembayaran_penjualan('no_faktur_penjualan') ?>
                                </a>
                            </th>

                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_penjualan('nama_pelanggan')) ?>" class="text-decoration-none text-dark">
                                    Pelanggan<?= sort_icon_pembayaran_penjualan('nama_pelanggan') ?>
                                </a>
                            </th>

                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_penjualan('metode_pembayaran')) ?>" class="text-decoration-none text-dark">
                                    Metode<?= sort_icon_pembayaran_penjualan('metode_pembayaran') ?>
                                </a>
                            </th>

                            <th>Akun Kas/Bank</th>

                            <th class="text-end">
                                <a href="<?= esc(build_sort_url_pembayaran_penjualan('jumlah_bayar')) ?>" class="text-decoration-none text-dark">
                                    Jumlah<?= sort_icon_pembayaran_penjualan('jumlah_bayar') ?>
                                </a>
                            </th>

                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_penjualan('status_posting')) ?>" class="text-decoration-none text-dark">
                                    Status<?= sort_icon_pembayaran_penjualan('status_posting') ?>
                                </a>
                            </th>

                            <th width="210" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($data_pembayaran->count() > 0): ?>
                            <?php $no = $offset + 1; ?>

                            <?php foreach ($data_pembayaran as $row): ?>
                                <?php $row_status = (string) ($row->status_posting ?? 'draft'); ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>

                                    <td class="fw-semibold">
                                        <?= esc((string) $row->no_pembayaran_penjualan) ?>
                                        <?php if (!empty($row->catatan)): ?>
                                            <div class="text-muted small"><?= esc(mb_strimwidth((string) $row->catatan, 0, 38, '...')) ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= esc((string) $row->tanggal_pembayaran) ?></td>

                                    <td>
                                        <div class="fw-semibold"><?= esc((string) ($row->no_faktur_penjualan ?? '-')) ?></div>
                                        <div class="text-muted small">
                                            Sisa: Rp <?= esc(pembayaran_penjualan_uang($row->sisa_piutang ?? 0)) ?>
                                        </div>
                                    </td>

                                    <td><?= esc(($row->kode_pelanggan ?? '-') . ' - ' . ($row->nama_pelanggan ?? '-')) ?></td>

                                    <td><?= esc(ucfirst((string) $row->metode_pembayaran)) ?></td>

                                    <td>
                                        <div class="fw-semibold"><?= esc((string) ($row->kode_coa ?? '-')) ?></div>
                                        <div class="text-muted small"><?= esc((string) ($row->nama_coa ?? '-')) ?></div>
                                    </td>

                                    <td class="text-end fw-semibold">Rp <?= esc(pembayaran_penjualan_uang($row->jumlah_bayar ?? 0)) ?></td>

                                    <td><?= pembayaran_penjualan_badge_status($row_status) ?></td>

                                    <td class="text-center">
                                        <a href="<?= esc(admin_page_url('penjualan/pembayaran/detail') . '&id=' . (int) $row->id_pembayaran_penjualan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if ($row_status === 'draft'): ?>
                                            <a href="<?= esc(admin_url('index.php?menu=penjualan/pembayaran/posting&id=' . (int) $row->id_pembayaran_penjualan)) ?>" class="btn btn-sm btn-outline-success" title="Posting" onclick="return confirm('Posting pembayaran ini? Jurnal penerimaan kas/bank akan dibuat dan sisa piutang faktur akan berkurang.')">
                                                <i class="bi bi-check2-circle"></i>
                                            </a>

                                            <a href="<?= esc(admin_url('index.php?menu=penjualan/pembayaran/hapus&id=' . (int) $row->id_pembayaran_penjualan)) ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Hapus pembayaran draft ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= esc(admin_url('index.php?menu=penjualan/pembayaran/cetak&id=' . (int) $row->id_pembayaran_penjualan)) ?>" class="btn btn-sm btn-outline-secondary" title="Cetak" target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Data pembayaran penjualan belum tersedia.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_pembayaran_penjualan(max(1, $page - 1))) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_pembayaran_penjualan($i)) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_pembayaran_penjualan(min($totalPages, $page + 1))) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>