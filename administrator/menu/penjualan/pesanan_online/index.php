<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_pesanan'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedStatus = ['semua', 'draft', 'terkonfirmasi', 'diproses', 'selesai', 'batal'];
$allowedSort = [
    'no_pesanan_penjualan',
    'tanggal_pesanan',
    'tanggal_kirim_rencana',
    'status_pesanan',
    'nama_pelanggan',
    'total',
];

$allowedPerPage = [10, 25, 50, 100];

if (!in_array($status, $allowedStatus, true)) {
    $status = 'semua';
}

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'tanggal_pesanan';
}

if (!in_array($order, ['asc', 'desc'], true)) {
    $order = 'desc';
}

if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

if ($page < 1) {
    $page = 1;
}

if (!function_exists('pesanan_online_uang')) {
    function pesanan_online_uang($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pesanan_online_qty')) {
    function pesanan_online_qty($value): string
    {
        $number = (float) $value;

        if (floor($number) == $number) {
            return number_format($number, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($number, 3, '.', ','), '0'), '.');
    }
}

if (!function_exists('pesanan_online_badge_status')) {
    function pesanan_online_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'draft' => '<span class="badge text-bg-secondary">Draft</span>',
            'terkonfirmasi' => '<span class="badge text-bg-success">Terkonfirmasi</span>',
            'diproses' => '<span class="badge text-bg-warning">Diproses</span>',
            'selesai' => '<span class="badge text-bg-primary">Selesai</span>',
            'batal' => '<span class="badge text-bg-danger">Batal</span>',
            default => '<span class="badge text-bg-light">' . esc(ucfirst($status ?: '-')) . '</span>',
        };
    }
}

$query = Capsule::table('tb_pesanan_penjualan as ps')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
    ->where('ps.id_entitas', $id_entitas)
    ->where('ps.sumber_pesanan', 'website');

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('ps.no_pesanan_penjualan', 'like', '%' . $q . '%')
            ->orWhere('pl.kode_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('pl.nama_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('pl.no_hp', 'like', '%' . $q . '%')
            ->orWhere('ps.status_pesanan', 'like', '%' . $q . '%');
    });
}

if ($status !== 'semua') {
    $query->where('ps.status_pesanan', $status);
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$orderColumn = match ($sort) {
    'nama_pelanggan' => 'pl.nama_pelanggan',
    default => 'ps.' . $sort,
};

$data_pesanan = $query
    ->select([
        'ps.id_pesanan_penjualan',
        'ps.no_pesanan_penjualan',
        'ps.tanggal_pesanan',
        'ps.tanggal_kirim_rencana',
        'ps.status_pesanan',
        'ps.subtotal',
        'ps.diskon',
        'ps.total',
        'ps.catatan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.no_hp',
    ])
    ->selectRaw("
        (
            SELECT COUNT(*)
            FROM tb_pesanan_penjualan_detail d
            WHERE d.id_pesanan_penjualan = ps.id_pesanan_penjualan
        ) AS jumlah_item
    ")
    ->selectRaw("
        (
            SELECT COALESCE(SUM(d.qty), 0)
            FROM tb_pesanan_penjualan_detail d
            WHERE d.id_pesanan_penjualan = ps.id_pesanan_penjualan
        ) AS total_qty
    ")
    ->selectRaw("
        (
            SELECT COUNT(*)
            FROM tb_pesanan_penjualan_detail d
            WHERE d.id_pesanan_penjualan = ps.id_pesanan_penjualan
              AND COALESCE((
                    SELECT SUM(ss.qty_saldo)
                    FROM tb_saldo_stok ss
                    WHERE ss.id_entitas = ps.id_entitas
                      AND ss.jenis_barang = 'produk'
                      AND ss.id_referensi_barang = d.id_produk
              ), 0) < d.qty
        ) AS jumlah_item_stok_kurang
    ")
    ->orderBy($orderColumn, $order)
    ->orderBy('ps.id_pesanan_penjualan', 'desc')
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_pesanan_online(string $column): string
{
    $params = [
        'menu'     => 'penjualan/pesanan-online',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
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

function sort_icon_pesanan_online(string $column): string
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

function build_page_url_pesanan_online(int $targetPage): string
{
    $params = [
        'menu'     => 'penjualan/pesanan-online',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_pesanan')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);

$total_draft = (int) Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('sumber_pesanan', 'website')
    ->where('status_pesanan', 'draft')
    ->count();

$total_terkonfirmasi = (int) Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('sumber_pesanan', 'website')
    ->where('status_pesanan', 'terkonfirmasi')
    ->count();

$total_diproses = (int) Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('sumber_pesanan', 'website')
    ->where('status_pesanan', 'diproses')
    ->count();

$total_bulan_ini = (float) Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('sumber_pesanan', 'website')
    ->where('status_pesanan', '<>', 'batal')
    ->whereBetween('tanggal_pesanan', [date('Y-m-01'), date('Y-m-t')])
    ->sum('total');
?>

<div class="page-header mb-4">
    <h1 class="page-title">Pesanan Online</h1>
    <p class="page-subtitle">Kelola order website dari tabel pesanan penjualan dengan sumber pesanan website</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Order Baru</div>
                <div class="h4 mb-0"><?= number_format($total_draft, 0, '.', ',') ?></div>
                <div class="text-muted small">Status draft</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Terkonfirmasi</div>
                <div class="h4 mb-0"><?= number_format($total_terkonfirmasi, 0, '.', ',') ?></div>
                <div class="text-muted small">Siap diproses</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Diproses</div>
                <div class="h4 mb-0"><?= number_format($total_diproses, 0, '.', ',') ?></div>
                <div class="text-muted small">Sudah masuk alur penyerahan/produksi</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Nilai Bulan Ini</div>
                <div class="h4 mb-0">Rp <?= pesanan_online_uang($total_bulan_ini) ?></div>
                <div class="text-muted small">Tidak termasuk batal</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Pesanan Online</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                <input type="hidden" name="menu" value="penjualan/pesanan-online">
                <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                <input type="hidden" name="order" value="<?= esc($order) ?>">

                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Cari no pesanan, pelanggan, no HP..."
                    value="<?= esc($q) ?>">

                <select name="status" class="form-select" style="min-width:150px;" onchange="this.form.submit()">
                    <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="terkonfirmasi" <?= $status === 'terkonfirmasi' ? 'selected' : '' ?>>Terkonfirmasi</option>
                    <option value="diproses" <?= $status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                    <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    <option value="batal" <?= $status === 'batal' ? 'selected' : '' ?>>Batal</option>
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
                    <a href="<?= esc(admin_page_url('penjualan/pesanan-online')) ?>" class="btn btn-outline-secondary">
                        Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive border rounded">
            <div style="max-height:420px; overflow-y:auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="65" class="text-center">No</th>
                            <th>
                                <a href="<?= esc(build_sort_url_pesanan_online('no_pesanan_penjualan')) ?>" class="text-decoration-none text-dark">
                                    No Pesanan<?= sort_icon_pesanan_online('no_pesanan_penjualan') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= esc(build_sort_url_pesanan_online('tanggal_pesanan')) ?>" class="text-decoration-none text-dark">
                                    Tanggal<?= sort_icon_pesanan_online('tanggal_pesanan') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= esc(build_sort_url_pesanan_online('nama_pelanggan')) ?>" class="text-decoration-none text-dark">
                                    Pelanggan<?= sort_icon_pesanan_online('nama_pelanggan') ?>
                                </a>
                            </th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Item</th>
                            <th class="text-end">
                                <a href="<?= esc(build_sort_url_pesanan_online('total')) ?>" class="text-decoration-none text-dark">
                                    Total<?= sort_icon_pesanan_online('total') ?>
                                </a>
                            </th>
                            <th>Stok</th>
                            <th>
                                <a href="<?= esc(build_sort_url_pesanan_online('status_pesanan')) ?>" class="text-decoration-none text-dark">
                                    Status<?= sort_icon_pesanan_online('status_pesanan') ?>
                                </a>
                            </th>
                            <th width="220" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($data_pesanan->count() === 0): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Belum ada pesanan online.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($data_pesanan as $row): ?>
                                <?php
                                $row_status = (string) ($row->status_pesanan ?? 'draft');
                                $stok_kurang = (int) ($row->jumlah_item_stok_kurang ?? 0);
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>

                                    <td>
                                        <div class="fw-semibold"><?= esc((string) $row->no_pesanan_penjualan) ?></div>
                                        <?php if (!empty($row->catatan)): ?>
                                            <div class="text-muted small"><?= esc(mb_strimwidth((string) $row->catatan, 0, 42, '...')) ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= esc((string) $row->tanggal_pesanan) ?>
                                        <?php if (!empty($row->tanggal_kirim_rencana)): ?>
                                            <div class="text-muted small">Kirim: <?= esc((string) $row->tanggal_kirim_rencana) ?></div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="fw-semibold"><?= esc((string) ($row->nama_pelanggan ?? '-')) ?></div>
                                        <div class="text-muted small">
                                            <?= esc((string) ($row->kode_pelanggan ?? '-')) ?>
                                            <?php if (!empty($row->no_hp)): ?>
                                                · <?= esc((string) $row->no_hp) ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="text-end"><?= esc(pesanan_online_qty($row->total_qty ?? 0)) ?></td>
                                    <td class="text-end"><?= esc(number_format((int) ($row->jumlah_item ?? 0), 0, '.', ',')) ?></td>
                                    <td class="text-end fw-semibold">Rp <?= esc(pesanan_online_uang($row->total ?? 0)) ?></td>

                                    <td>
                                        <?php if ($stok_kurang > 0): ?>
                                            <span class="badge text-bg-warning">Kurang</span>
                                            <div class="text-muted small"><?= $stok_kurang ?> item</div>
                                        <?php else: ?>
                                            <span class="badge text-bg-success">Cukup</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= pesanan_online_badge_status($row_status) ?></td>

                                    <td class="text-center">
                                        <a href="<?= esc(admin_page_url('penjualan/pesanan-online/detail') . '&id=' . (int) $row->id_pesanan_penjualan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if ($row_status === 'draft'): ?>
                                            <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/konfirmasi&id=' . (int) $row->id_pesanan_penjualan)) ?>" class="btn btn-sm btn-outline-success" title="Konfirmasi" onclick="return confirm('Konfirmasi pesanan online ini?')">
                                                <i class="bi bi-check2-circle"></i>
                                            </a>

                                            <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/batal&id=' . (int) $row->id_pesanan_penjualan)) ?>" class="btn btn-sm btn-outline-danger" title="Batal" onclick="return confirm('Batalkan pesanan online ini?')">
                                                <i class="bi bi-x-circle"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($row_status === 'terkonfirmasi'): ?>
                                            <a href="<?= esc(admin_page_url('penjualan/penyerahan/tambah') . '&id_pesanan_penjualan=' . (int) $row->id_pesanan_penjualan) ?>" class="btn btn-sm btn-outline-primary" title="Buat Penyerahan">
                                                <i class="bi bi-truck"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/cetak&id=' . (int) $row->id_pesanan_penjualan)) ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Cetak">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/struk&id=' . (int) $row->id_pesanan_penjualan)) ?>" class="btn btn-sm btn-outline-warning" target="_blank" title="Cetak Struk Packing UMKM">
                                            <i class="bi bi-receipt-cutoff"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_pesanan_online(max(1, $page - 1))) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_pesanan_online($i)) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_pesanan_online(min($totalPages, $page + 1))) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>