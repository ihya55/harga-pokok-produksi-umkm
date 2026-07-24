<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status_piutang = trim((string) ($_GET['status_piutang'] ?? 'belum_lunas'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_faktur'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedSort = [
    'no_faktur_penjualan',
    'tanggal_faktur',
    'nama_pelanggan',
    'no_penyerahan_penjualan',
    'total',
    'sisa_piutang',
];

$allowedStatusPiutang = ['semua', 'belum_lunas', 'lunas'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'tanggal_faktur';
}

if (!in_array($order, ['asc', 'desc'], true)) {
    $order = 'desc';
}

if (!in_array($status_piutang, $allowedStatusPiutang, true)) {
    $status_piutang = 'belum_lunas';
}

if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

if ($page < 1) {
    $page = 1;
}

if (!function_exists('piutang_pelanggan_uang')) {
    function piutang_pelanggan_uang($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('piutang_pelanggan_badge')) {
    function piutang_pelanggan_badge($sisa_piutang): string
    {
        return ((float) $sisa_piutang <= 0)
            ? '<span class="badge text-bg-success">Lunas</span>'
            : '<span class="badge text-bg-warning">Belum Lunas</span>';
    }
}

$query = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_penyerahan_penjualan as pp', 'pp.id_penyerahan_penjualan', '=', 'f.id_penyerahan_penjualan')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'f.id_pelanggan')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.status_faktur', 'posted')
    ->where('f.jenis_pembayaran', 'kredit');

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('f.no_faktur_penjualan', 'like', '%' . $q . '%')
            ->orWhere('pp.no_penyerahan_penjualan', 'like', '%' . $q . '%')
            ->orWhere('ps.no_pesanan_penjualan', 'like', '%' . $q . '%')
            ->orWhere('pl.kode_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('pl.nama_pelanggan', 'like', '%' . $q . '%');
    });
}

if ($status_piutang === 'belum_lunas') {
    $query->where('f.sisa_piutang', '>', 0);
} elseif ($status_piutang === 'lunas') {
    $query->where('f.sisa_piutang', '<=', 0);
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$orderColumn = match ($sort) {
    'nama_pelanggan' => 'pl.nama_pelanggan',
    'no_penyerahan_penjualan' => 'pp.no_penyerahan_penjualan',
    default => 'f.' . $sort,
};

$data_piutang = $query
    ->select([
        'f.*',
        'pp.no_penyerahan_penjualan',
        'pp.status_penyerahan',
        'ps.no_pesanan_penjualan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.no_hp',
    ])
    ->selectRaw("(
        SELECT COUNT(*)
        FROM tb_faktur_penjualan_detail d
        WHERE d.id_faktur_penjualan = f.id_faktur_penjualan
    ) AS jumlah_item")
    ->selectRaw("(
        SELECT COUNT(*)
        FROM tb_pembayaran_penjualan p
        WHERE p.id_faktur_penjualan = f.id_faktur_penjualan
          AND p.status_posting <> 'batal'
    ) AS jumlah_pembayaran")
    ->orderBy($orderColumn, $order)
    ->orderBy('f.id_faktur_penjualan', 'desc')
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_piutang_pelanggan(string $column): string
{
    $params = [
        'menu'           => 'penjualan/faktur',
        'q'              => trim((string) ($_GET['q'] ?? '')),
        'status_piutang' => trim((string) ($_GET['status_piutang'] ?? 'belum_lunas')),
        'sort'           => $column,
        'order'          => 'asc',
        'per_page'       => (int) ($_GET['per_page'] ?? 10),
        'hal'            => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_faktur'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_piutang_pelanggan(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_faktur'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_piutang_pelanggan(int $targetPage): string
{
    $params = [
        'menu'           => 'penjualan/faktur',
        'q'              => trim((string) ($_GET['q'] ?? '')),
        'status_piutang' => trim((string) ($_GET['status_piutang'] ?? 'belum_lunas')),
        'sort'           => trim((string) ($_GET['sort'] ?? 'tanggal_faktur')),
        'order'          => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page'       => (int) ($_GET['per_page'] ?? 10),
        'hal'            => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);

$total_belum_lunas = (int) Capsule::table('tb_faktur_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('jenis_pembayaran', 'kredit')
    ->where('sisa_piutang', '>', 0)
    ->count();

$total_lunas = (int) Capsule::table('tb_faktur_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('jenis_pembayaran', 'kredit')
    ->where('sisa_piutang', '<=', 0)
    ->count();

$total_piutang = (float) Capsule::table('tb_faktur_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('jenis_pembayaran', 'kredit')
    ->sum('sisa_piutang');

$total_pelanggan_piutang = (int) Capsule::table('tb_faktur_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('jenis_pembayaran', 'kredit')
    ->where('sisa_piutang', '>', 0)
    ->distinct('id_pelanggan')
    ->count('id_pelanggan');
?>

<div class="page-header mb-4">
    <h1 class="page-title">Daftar Piutang Pelanggan</h1>
    <p class="page-subtitle">Catatan pelanggan yang membeli kredit. UMKM cukup klik tombol <strong>Bayar</strong> saat pelanggan melunasi.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Belum Lunas</div>
                <div class="h4 mb-0"><?= number_format($total_belum_lunas, 0, '.', ',') ?></div>
                <div class="text-muted small">Transaksi kredit</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pelanggan Berpiutang</div>
                <div class="h4 mb-0"><?= number_format($total_pelanggan_piutang, 0, '.', ',') ?></div>
                <div class="text-muted small">Masih punya sisa</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Piutang</div>
                <div class="h4 mb-0">Rp <?= piutang_pelanggan_uang($total_piutang) ?></div>
                <div class="text-muted small">Nominal belum diterima</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Lunas</div>
                <div class="h4 mb-0"><?= number_format($total_lunas, 0, '.', ',') ?></div>
                <div class="text-muted small">Riwayat piutang selesai</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Piutang Pelanggan</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                <input type="hidden" name="menu" value="penjualan/faktur">
                <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                <input type="hidden" name="order" value="<?= esc($order) ?>">

                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Cari pelanggan, no piutang, penyerahan..."
                    value="<?= esc($q) ?>">

                <select name="status_piutang" class="form-select" style="min-width:165px;" onchange="this.form.submit()">
                    <option value="semua" <?= $status_piutang === 'semua' ? 'selected' : '' ?>>Semua Piutang</option>
                    <option value="belum_lunas" <?= $status_piutang === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                    <option value="lunas" <?= $status_piutang === 'lunas' ? 'selected' : '' ?>>Lunas</option>
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

                <?php if ($q !== '' || $status_piutang !== 'belum_lunas' || $perPage !== 10): ?>
                    <a href="<?= esc(admin_page_url('penjualan/faktur')) ?>" class="btn btn-outline-secondary">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive border rounded">
            <div style="max-height:420px; overflow-y:auto;">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="70" class="text-center">No</th>
                            <th>
                                <a href="<?= esc(build_sort_url_piutang_pelanggan('no_faktur_penjualan')) ?>" class="text-decoration-none text-dark">
                                    No Piutang<?= sort_icon_piutang_pelanggan('no_faktur_penjualan') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= esc(build_sort_url_piutang_pelanggan('tanggal_faktur')) ?>" class="text-decoration-none text-dark">
                                    Tanggal<?= sort_icon_piutang_pelanggan('tanggal_faktur') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= esc(build_sort_url_piutang_pelanggan('nama_pelanggan')) ?>" class="text-decoration-none text-dark">
                                    Pelanggan<?= sort_icon_piutang_pelanggan('nama_pelanggan') ?>
                                </a>
                            </th>
                            <th>Penyerahan / Pesanan</th>
                            <th class="text-end">Item</th>
                            <th class="text-end">
                                <a href="<?= esc(build_sort_url_piutang_pelanggan('total')) ?>" class="text-decoration-none text-dark">
                                    Total Belanja<?= sort_icon_piutang_pelanggan('total') ?>
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="<?= esc(build_sort_url_piutang_pelanggan('sisa_piutang')) ?>" class="text-decoration-none text-dark">
                                    Sisa Piutang<?= sort_icon_piutang_pelanggan('sisa_piutang') ?>
                                </a>
                            </th>
                            <th>Status</th>
                            <th width="170" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($data_piutang->count() > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($data_piutang as $row): ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>
                                    <td class="fw-semibold">
                                        <?= esc((string) $row->no_faktur_penjualan) ?>
                                        <?php if (!empty($row->catatan)): ?>
                                            <div class="text-muted small"><?= esc(mb_strimwidth((string) $row->catatan, 0, 38, '...')) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc((string) $row->tanggal_faktur) ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= esc((string) ($row->nama_pelanggan ?? '-')) ?></div>
                                        <div class="text-muted small"><?= esc((string) (($row->kode_pelanggan ?? '-') . (!empty($row->no_hp) ? ' · ' . $row->no_hp : ''))) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= esc((string) ($row->no_penyerahan_penjualan ?? '-')) ?></div>
                                        <div class="text-muted small">Pesanan: <?= esc((string) ($row->no_pesanan_penjualan ?? '-')) ?></div>
                                    </td>
                                    <td class="text-end"><?= esc(number_format((int) ($row->jumlah_item ?? 0), 0, '.', ',')) ?></td>
                                    <td class="text-end fw-semibold"><?= esc(piutang_pelanggan_uang($row->total ?? 0)) ?></td>
                                    <td class="text-end fw-semibold text-danger"><?= esc(piutang_pelanggan_uang($row->sisa_piutang ?? 0)) ?></td>
                                    <td><?= piutang_pelanggan_badge($row->sisa_piutang ?? 0) ?></td>
                                    <td class="text-center">
                                        <a href="<?= esc(admin_page_url('penjualan/faktur/detail') . '&id=' . (int) $row->id_faktur_penjualan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail Piutang">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if ((float) ($row->sisa_piutang ?? 0) > 0): ?>
                                            <a href="<?= esc(admin_page_url('penjualan/pembayaran/tambah') . '&id_faktur_penjualan=' . (int) $row->id_faktur_penjualan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-warning" title="Bayar Piutang">
                                                <i class="bi bi-cash-coin"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= esc(admin_url('index.php?menu=penjualan/faktur/cetak&id=' . (int) $row->id_faktur_penjualan)) ?>" class="btn btn-sm btn-outline-secondary" title="Cetak" target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Data piutang pelanggan belum tersedia.
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
                        <a class="page-link" href="<?= esc(build_page_url_piutang_pelanggan(max(1, $page - 1))) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_piutang_pelanggan($i)) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_piutang_pelanggan(min($totalPages, $page + 1))) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
