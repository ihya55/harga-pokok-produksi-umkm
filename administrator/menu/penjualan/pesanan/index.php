<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_fungsi_penjualan.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$sumber = trim((string) ($_GET['sumber'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_pesanan'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedSort = [
    'no_pesanan_penjualan',
    'tanggal_pesanan',
    'tanggal_kirim_rencana',
    'sumber_pesanan',
    'status_pesanan',
    'total',
];

$allowedPerPage = [10, 25, 50, 100];

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

if (!function_exists('pesanan_penjualan_uang')) {
    function pesanan_penjualan_uang($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pesanan_penjualan_qty_int')) {
    function pesanan_penjualan_qty_int($value): string
    {
        return number_format((int) round((float) $value), 0, '.', ',');
    }
}

if (!function_exists('pesanan_penjualan_diskon_persen_int')) {
    function pesanan_penjualan_diskon_persen_int($subtotal, $diskon): string
    {
        $subtotal = (float) $subtotal;
        $diskon = (float) $diskon;

        if ($subtotal <= 0 || $diskon <= 0) {
            return '0%';
        }

        return number_format((int) round(($diskon / $subtotal) * 100), 0, '.', ',') . '%';
    }
}

if (!function_exists('pesanan_penjualan_badge_status')) {
    function pesanan_penjualan_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        if ($status === 'draft') {
            return '<span class="badge text-bg-secondary">Draft</span>';
        }

        if ($status === 'terkonfirmasi') {
            return '<span class="badge text-bg-success">Terkonfirmasi</span>';
        }

        if ($status === 'diproses') {
            return '<span class="badge text-bg-warning">Diproses</span>';
        }

        if ($status === 'selesai') {
            return '<span class="badge text-bg-primary">Selesai</span>';
        }

        if ($status === 'batal') {
            return '<span class="badge text-bg-danger">Batal</span>';
        }

        return '<span class="badge text-bg-light">' . esc(ucfirst($status ?: '-')) . '</span>';
    }
}

if (!function_exists('pesanan_penjualan_badge_sumber')) {
    function pesanan_penjualan_badge_sumber(string $sumber): string
    {
        $sumber = strtolower(trim($sumber));

        if ($sumber === 'toko') {
            return '<span class="badge text-bg-info">Toko</span>';
        }

        if ($sumber === 'whatsapp') {
            return '<span class="badge text-bg-success">WhatsApp</span>';
        }

        if ($sumber === 'website') {
            return '<span class="badge text-bg-primary">Website</span>';
        }

        if ($sumber === 'reseller') {
            return '<span class="badge text-bg-warning">Reseller</span>';
        }

        return '<span class="badge text-bg-light">' . esc(ucfirst($sumber ?: '-')) . '</span>';
    }
}

$query = Capsule::table('tb_pesanan_penjualan as pp')
    ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 'pp.id_pelanggan')
    ->leftJoin('tb_pesanan_penjualan_detail as d', 'd.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->where('pp.id_entitas', $id_entitas);

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('pp.no_pesanan_penjualan', 'like', '%' . $q . '%')
            ->orWhere('p.kode_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('p.nama_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('pp.sumber_pesanan', 'like', '%' . $q . '%')
            ->orWhere('pp.status_pesanan', 'like', '%' . $q . '%');
    });
}

if ($status !== 'semua') {
    $query->where('pp.status_pesanan', $status);
}

if ($sumber !== 'semua') {
    $query->where('pp.sumber_pesanan', $sumber);
}

$totalRows = (int) (clone $query)
    ->distinct()
    ->count('pp.id_pesanan_penjualan');

$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_pesanan = $query
    ->select([
        'pp.id_pesanan_penjualan',
        'pp.no_pesanan_penjualan',
        'pp.tanggal_pesanan',
        'pp.tanggal_kirim_rencana',
        'pp.sumber_pesanan',
        'pp.status_pesanan',
        'pp.subtotal',
        'pp.diskon',
        'pp.total',
        'pp.catatan',
        'p.kode_pelanggan',
        'p.nama_pelanggan',
    ])
    ->selectRaw('COUNT(d.id_pesanan_penjualan_detail) as jumlah_item')
    ->selectRaw('COALESCE(SUM(d.qty), 0) as total_qty')
    ->groupBy([
        'pp.id_pesanan_penjualan',
        'pp.no_pesanan_penjualan',
        'pp.tanggal_pesanan',
        'pp.tanggal_kirim_rencana',
        'pp.sumber_pesanan',
        'pp.status_pesanan',
        'pp.subtotal',
        'pp.diskon',
        'pp.total',
        'pp.catatan',
        'p.kode_pelanggan',
        'p.nama_pelanggan',
    ])
    ->orderBy('pp.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

if (!function_exists('build_sort_url_pesanan_penjualan')) {
    function build_sort_url_pesanan_penjualan(string $column): string
    {
        $params = [
            'menu'     => 'penjualan/pesanan',
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'status'   => trim((string) ($_GET['status'] ?? 'semua')),
            'sumber'   => trim((string) ($_GET['sumber'] ?? 'semua')),
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
}

if (!function_exists('sort_icon_pesanan_penjualan')) {
    function sort_icon_pesanan_penjualan(string $column): string
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
}

if (!function_exists('build_page_url_pesanan_penjualan')) {
    function build_page_url_pesanan_penjualan(int $targetPage): string
    {
        $params = [
            'menu'     => 'penjualan/pesanan',
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'status'   => trim((string) ($_GET['status'] ?? 'semua')),
            'sumber'   => trim((string) ($_GET['sumber'] ?? 'semua')),
            'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_pesanan')),
            'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
            'per_page' => (int) ($_GET['per_page'] ?? 10),
            'hal'      => $targetPage,
        ];

        return admin_url('index.php?' . http_build_query($params));
    }
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);

$total_draft = (int) Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_pesanan', 'draft')
    ->count();

$total_terkonfirmasi = (int) Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_pesanan', 'terkonfirmasi')
    ->count();

$total_batal = (int) Capsule::table('tb_pesanan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_pesanan', 'batal')
    ->count();
?>

<div class="page-header mb-4">
    <h1 class="page-title">Pesanan Penjualan</h1>
    <p class="page-subtitle">Kelola dokumen pesanan penjualan pelanggan</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Draft</div>
                <div class="h4 mb-0"><?= number_format($total_draft, 0, '.', ',') ?></div>
                <div class="text-muted small">Belum dikonfirmasi</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Terkonfirmasi</div>
                <div class="h4 mb-0"><?= number_format($total_terkonfirmasi, 0, '.', ',') ?></div>
                <div class="text-muted small">Siap diproses penyerahan</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Batal</div>
                <div class="h4 mb-0"><?= number_format($total_batal, 0, '.', ',') ?></div>
                <div class="text-muted small">Pesanan dibatalkan</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Pesanan Penjualan</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="penjualan/pesanan">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari no pesanan, pelanggan..."
                        value="<?= esc($q) ?>">

                    <select name="status" class="form-select" style="min-width:150px;" onchange="this.form.submit()">
                        <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="terkonfirmasi" <?= $status === 'terkonfirmasi' ? 'selected' : '' ?>>Terkonfirmasi</option>
                        <option value="diproses" <?= $status === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                        <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="batal" <?= $status === 'batal' ? 'selected' : '' ?>>Batal</option>
                    </select>

                    <select name="sumber" class="form-select" style="min-width:145px;" onchange="this.form.submit()">
                        <option value="semua" <?= $sumber === 'semua' ? 'selected' : '' ?>>Semua Sumber</option>
                        <option value="toko" <?= $sumber === 'toko' ? 'selected' : '' ?>>Toko</option>
                        <option value="whatsapp" <?= $sumber === 'whatsapp' ? 'selected' : '' ?>>WhatsApp</option>
                        <option value="website" <?= $sumber === 'website' ? 'selected' : '' ?>>Website</option>
                        <option value="reseller" <?= $sumber === 'reseller' ? 'selected' : '' ?>>Reseller</option>
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

                    <?php if ($q !== '' || $status !== 'semua' || $sumber !== 'semua' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('penjualan/pesanan/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Pesanan
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('index.php?menu=penjualan/pesanan/hapus-massal')) ?>" onsubmit="return confirm('Yakin ingin menghapus pesanan draft yang dipilih?')">
            <input type="hidden" name="back_url" value="<?= esc($current_url) ?>">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>

                <div class="text-muted small">Hanya pesanan draft yang bisa dihapus</div>
            </div>

            <div class="table-responsive border rounded">
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="50" class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th width="70" class="text-center">No</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pesanan_penjualan('no_pesanan_penjualan')) ?>" class="text-decoration-none text-dark">
                                        No Pesanan<?= sort_icon_pesanan_penjualan('no_pesanan_penjualan') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pesanan_penjualan('tanggal_pesanan')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_pesanan_penjualan('tanggal_pesanan') ?>
                                    </a>
                                </th>
                                <th>Pelanggan</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pesanan_penjualan('sumber_pesanan')) ?>" class="text-decoration-none text-dark">
                                        Sumber<?= sort_icon_pesanan_penjualan('sumber_pesanan') ?>
                                    </a>
                                </th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Item</th>
                                <th class="text-end">Diskon</th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_pesanan_penjualan('total')) ?>" class="text-decoration-none text-dark">
                                        Total<?= sort_icon_pesanan_penjualan('total') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pesanan_penjualan('status_pesanan')) ?>" class="text-decoration-none text-dark">
                                        Status<?= sort_icon_pesanan_penjualan('status_pesanan') ?>
                                    </a>
                                </th>
                                <th width="230" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_pesanan->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_pesanan as $row): ?>
                                    <?php $row_status = (string) ($row->status_pesanan ?? 'draft'); ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ($row_status === 'draft'): ?>
                                                <input type="checkbox" name="id_pesanan_penjualan[]" value="<?= (int) $row->id_pesanan_penjualan ?>" class="row-check">
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center"><?= $no++ ?></td>

                                        <td class="fw-semibold">
                                            <?= esc((string) $row->no_pesanan_penjualan) ?>
                                            <?php if (!empty($row->tanggal_kirim_rencana)): ?>
                                                <div class="text-muted small">Kirim: <?= esc((string) $row->tanggal_kirim_rencana) ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= esc((string) $row->tanggal_pesanan) ?></td>

                                        <td>
                                            <?= esc(($row->kode_pelanggan ?? '-') . ' - ' . ($row->nama_pelanggan ?? '-')) ?>
                                        </td>

                                        <td><?= pesanan_penjualan_badge_sumber((string) ($row->sumber_pesanan ?? '')) ?></td>

                                        <td class="text-end"><?= esc(pesanan_penjualan_qty_int($row->total_qty ?? 0)) ?></td>

                                        <td class="text-end"><?= esc(number_format((int) ($row->jumlah_item ?? 0), 0, '.', ',')) ?></td>

                                        <td class="text-end">
                                            <div class="fw-semibold">
                                                <?= esc(pesanan_penjualan_diskon_persen_int($row->subtotal ?? 0, $row->diskon ?? 0)) ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?= esc(pesanan_penjualan_uang($row->diskon ?? 0)) ?>
                                            </div>
                                        </td>

                                        <td class="text-end fw-semibold">
                                            <?= esc(pesanan_penjualan_uang($row->total ?? 0)) ?>
                                        </td>

                                        <td><?= pesanan_penjualan_badge_status($row_status) ?></td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('penjualan/pesanan/detail') . '&id=' . (int) $row->id_pesanan_penjualan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ($row_status === 'draft'): ?>
                                                <a href="<?= esc(admin_page_url('penjualan/pesanan/edit') . '&id=' . (int) $row->id_pesanan_penjualan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/konfirmasi&id=' . (int) $row->id_pesanan_penjualan)) ?>" class="btn btn-sm btn-outline-success" title="Konfirmasi" onclick="return confirm('Konfirmasi pesanan ini?')">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/hapus&id=' . (int) $row->id_pesanan_penjualan)) ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Hapus pesanan draft ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($row_status === 'terkonfirmasi'): ?>
                                                <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/batal&id=' . (int) $row->id_pesanan_penjualan)) ?>" class="btn btn-sm btn-outline-danger" title="Batalkan" onclick="return confirm('Batalkan pesanan ini?')">
                                                    <i class="bi bi-x-circle"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/cetak&id=' . (int) $row->id_pesanan_penjualan)) ?>" class="btn btn-sm btn-outline-secondary" title="Cetak" target="_blank">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-4">
                                        Data pesanan penjualan belum tersedia.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_pesanan_penjualan(max(1, $page - 1))) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_pesanan_penjualan($i)) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_pesanan_penjualan(min($totalPages, $page + 1))) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');

    if (!checkAll) {
        return;
    }

    checkAll.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(function (checkbox) {
            checkbox.checked = checkAll.checked;
        });
    });
});
</script>