<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_penyerahan'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedSort = [
    'no_penyerahan_penjualan',
    'tanggal_penyerahan',
    'status_penyerahan',
    'nama_pelanggan',
    'nama_gudang',
    'no_pesanan_penjualan',
    'total_hpp',
];

$allowedStatus = [
    'semua',
    'draft',
    'posted',
    'batal',
];

$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'tanggal_penyerahan';
}

if (!in_array($order, ['asc', 'desc'], true)) {
    $order = 'desc';
}

if (!in_array($status, $allowedStatus, true)) {
    $status = 'semua';
}

if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

if ($page < 1) {
    $page = 1;
}

if (!function_exists('penyerahan_penjualan_uang')) {
    function penyerahan_penjualan_uang($value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('penyerahan_penjualan_qty')) {
    function penyerahan_penjualan_qty($value): string
    {
        $number = (float) $value;

        if (floor($number) == $number) {
            return number_format($number, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($number, 3, '.', ','), '0'), '.');
    }
}

if (!function_exists('penyerahan_penjualan_badge_status')) {
    function penyerahan_penjualan_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        if ($status === 'draft') {
            return '<span class="badge text-bg-secondary">Draft</span>';
        }

        if ($status === 'posted') {
            return '<span class="badge text-bg-success">Posted</span>';
        }

        if ($status === 'batal') {
            return '<span class="badge text-bg-danger">Batal</span>';
        }

        return '<span class="badge text-bg-light">' . esc(ucfirst($status ?: '-')) . '</span>';
    }
}

if (!function_exists('penyerahan_penjualan_badge_faktur')) {
    function penyerahan_penjualan_badge_faktur(int $jumlah_faktur): string
    {
        if ($jumlah_faktur > 0) {
            return '<span class="badge text-bg-primary">Sudah Difaktur</span>';
        }

        return '<span class="badge text-bg-light">Belum Difaktur</span>';
    }
}

$query = Capsule::table('tb_penyerahan_penjualan as pp')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
    ->leftJoin('tb_gudang as gd', 'gd.id_gudang', '=', 'pp.id_gudang')
    ->where('pp.id_entitas', $id_entitas);

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('pp.no_penyerahan_penjualan', 'like', '%' . $q . '%')
            ->orWhere('ps.no_pesanan_penjualan', 'like', '%' . $q . '%')
            ->orWhere('pl.kode_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('pl.nama_pelanggan', 'like', '%' . $q . '%')
            ->orWhere('gd.kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('gd.nama_gudang', 'like', '%' . $q . '%')
            ->orWhere('pp.status_penyerahan', 'like', '%' . $q . '%');
    });
}

if ($status !== 'semua') {
    $query->where('pp.status_penyerahan', $status);
}

$totalRows = (int) (clone $query)->count();

$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$orderColumn = match ($sort) {
    'nama_pelanggan' => 'pl.nama_pelanggan',
    'nama_gudang' => 'gd.nama_gudang',
    'no_pesanan_penjualan' => 'ps.no_pesanan_penjualan',
    'total_hpp' => 'total_hpp',
    default => 'pp.' . $sort,
};

$data_penyerahan = $query
    ->select([
        'pp.id_penyerahan_penjualan',
        'pp.no_penyerahan_penjualan',
        'pp.tanggal_penyerahan',
        'pp.id_pesanan_penjualan',
        'pp.id_pelanggan',
        'pp.id_gudang',
        'pp.status_penyerahan',
        'pp.catatan',
        'ps.no_pesanan_penjualan',
        'ps.status_pesanan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'gd.kode_gudang',
        'gd.nama_gudang',
    ])
    ->selectRaw("
        (
            SELECT COUNT(*)
            FROM tb_penyerahan_penjualan_detail d
            WHERE d.id_penyerahan_penjualan = pp.id_penyerahan_penjualan
        ) AS jumlah_item
    ")
    ->selectRaw("
        (
            SELECT COALESCE(SUM(d.qty), 0)
            FROM tb_penyerahan_penjualan_detail d
            WHERE d.id_penyerahan_penjualan = pp.id_penyerahan_penjualan
        ) AS total_qty
    ")
    ->selectRaw("
        (
            SELECT COALESCE(SUM(d.hpp_total), 0)
            FROM tb_penyerahan_penjualan_detail d
            WHERE d.id_penyerahan_penjualan = pp.id_penyerahan_penjualan
        ) AS total_hpp
    ")
    ->selectRaw("
        (
            SELECT COUNT(*)
            FROM tb_faktur_penjualan fp
            WHERE fp.id_penyerahan_penjualan = pp.id_penyerahan_penjualan
              AND fp.status_faktur <> 'batal'
        ) AS jumlah_faktur
    ")
    ->orderBy($orderColumn, $order)
    ->orderBy('pp.id_penyerahan_penjualan', 'desc')
    ->skip($offset)
    ->take($perPage)
    ->get();

if (!function_exists('build_sort_url_penyerahan_penjualan')) {
    function build_sort_url_penyerahan_penjualan(string $column): string
    {
        $params = [
            'menu'     => 'penjualan/penyerahan',
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'status'   => trim((string) ($_GET['status'] ?? 'semua')),
            'sort'     => $column,
            'order'    => 'asc',
            'per_page' => (int) ($_GET['per_page'] ?? 10),
            'hal'      => 1,
        ];

        $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_penyerahan'));
        $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

        if ($currentSort === $column && $currentOrder === 'asc') {
            $params['order'] = 'desc';
        }

        return admin_url('index.php?' . http_build_query($params));
    }
}

if (!function_exists('sort_icon_penyerahan_penjualan')) {
    function sort_icon_penyerahan_penjualan(string $column): string
    {
        $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_penyerahan'));
        $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

        if ($currentSort !== $column) {
            return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
        }

        return $currentOrder === 'asc'
            ? '<i class="bi bi-sort-down ms-1"></i>'
            : '<i class="bi bi-sort-up ms-1"></i>';
    }
}

if (!function_exists('build_page_url_penyerahan_penjualan')) {
    function build_page_url_penyerahan_penjualan(int $targetPage): string
    {
        $params = [
            'menu'     => 'penjualan/penyerahan',
            'q'        => trim((string) ($_GET['q'] ?? '')),
            'status'   => trim((string) ($_GET['status'] ?? 'semua')),
            'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_penyerahan')),
            'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
            'per_page' => (int) ($_GET['per_page'] ?? 10),
            'hal'      => $targetPage,
        ];

        return admin_url('index.php?' . http_build_query($params));
    }
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);

$total_draft = (int) Capsule::table('tb_penyerahan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_penyerahan', 'draft')
    ->count();

$total_posted = (int) Capsule::table('tb_penyerahan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('status_penyerahan', 'posted')
    ->count();

$gudang_scan_options = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$total_pesanan_siap = (int) Capsule::table('tb_pesanan_penjualan as ps')
    ->where('ps.id_entitas', $id_entitas)
    ->where('ps.status_pesanan', 'terkonfirmasi')
    ->whereNotExists(function ($sub) use ($id_entitas) {
        $sub->selectRaw('1')
            ->from('tb_penyerahan_penjualan as pp')
            ->whereColumn('pp.id_pesanan_penjualan', 'ps.id_pesanan_penjualan')
            ->where('pp.id_entitas', $id_entitas)
            ->whereIn('pp.status_penyerahan', ['draft', 'posted']);
    })
    ->count();
?>

<div class="page-header mb-4">
    <h1 class="page-title">Penyerahan Penjualan</h1>
    <p class="page-subtitle">Kelola surat jalan dan penyerahan barang berdasarkan pesanan penjualan</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Draft</div>
                <div class="h4 mb-0"><?= number_format($total_draft, 0, '.', ',') ?></div>
                <div class="text-muted small">Belum diposting</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Posted</div>
                <div class="h4 mb-0"><?= number_format($total_posted, 0, '.', ',') ?></div>
                <div class="text-muted small">Stok sudah keluar</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pesanan Siap Diserahkan</div>
                <div class="h4 mb-0"><?= number_format($total_pesanan_siap, 0, '.', ',') ?></div>
                <div class="text-muted small">Pesanan terkonfirmasi belum diserahkan</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Penyerahan Penjualan</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="penjualan/penyerahan">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari no penyerahan, pesanan, pelanggan..."
                        value="<?= esc($q) ?>">

                    <select name="status" class="form-select" style="min-width:150px;" onchange="this.form.submit()">
                        <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="posted" <?= $status === 'posted' ? 'selected' : '' ?>>Posted</option>
                        <option value="batal" <?= $status === 'batal' ? 'selected' : '' ?>>Batal</option>
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

                    <?php if ($q !== '' || $status !== 'semua' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('penjualan/penyerahan')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalScanPenyerahanIndex">
                    <i class="bi bi-qr-code-scan me-1"></i>Scan QR/Barcode
                </button>

                <a href="<?= esc(admin_page_url('penjualan/penyerahan/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Penyerahan
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('index.php?menu=penjualan/penyerahan/hapus-massal')) ?>" onsubmit="return confirm('Yakin ingin menghapus penyerahan draft yang dipilih?')">
            <input type="hidden" name="back_url" value="<?= esc($current_url) ?>">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>

                <div class="text-muted small">Hanya penyerahan draft yang bisa dihapus</div>
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
                                    <a href="<?= esc(build_sort_url_penyerahan_penjualan('no_penyerahan_penjualan')) ?>" class="text-decoration-none text-dark">
                                        No Penyerahan<?= sort_icon_penyerahan_penjualan('no_penyerahan_penjualan') ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= esc(build_sort_url_penyerahan_penjualan('tanggal_penyerahan')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_penyerahan_penjualan('tanggal_penyerahan') ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= esc(build_sort_url_penyerahan_penjualan('no_pesanan_penjualan')) ?>" class="text-decoration-none text-dark">
                                        Pesanan<?= sort_icon_penyerahan_penjualan('no_pesanan_penjualan') ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= esc(build_sort_url_penyerahan_penjualan('nama_pelanggan')) ?>" class="text-decoration-none text-dark">
                                        Pelanggan<?= sort_icon_penyerahan_penjualan('nama_pelanggan') ?>
                                    </a>
                                </th>

                                <th>
                                    <a href="<?= esc(build_sort_url_penyerahan_penjualan('nama_gudang')) ?>" class="text-decoration-none text-dark">
                                        Gudang<?= sort_icon_penyerahan_penjualan('nama_gudang') ?>
                                    </a>
                                </th>

                                <th class="text-end">Qty</th>
                                <th class="text-end">Item</th>

                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_penyerahan_penjualan('total_hpp')) ?>" class="text-decoration-none text-dark">
                                        Total HPP<?= sort_icon_penyerahan_penjualan('total_hpp') ?>
                                    </a>
                                </th>

                                <th>Faktur</th>

                                <th>
                                    <a href="<?= esc(build_sort_url_penyerahan_penjualan('status_penyerahan')) ?>" class="text-decoration-none text-dark">
                                        Status<?= sort_icon_penyerahan_penjualan('status_penyerahan') ?>
                                    </a>
                                </th>

                                <th width="230" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_penyerahan->count() > 0): ?>
                                <?php $no = $offset + 1; ?>

                                <?php foreach ($data_penyerahan as $row): ?>
                                    <?php
                                    $row_status = (string) ($row->status_penyerahan ?? 'draft');
                                    $jumlah_faktur = (int) ($row->jumlah_faktur ?? 0);
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ($row_status === 'draft'): ?>
                                                <input
                                                    type="checkbox"
                                                    name="id_penyerahan_penjualan[]"
                                                    value="<?= (int) $row->id_penyerahan_penjualan ?>"
                                                    class="row-check">
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center"><?= $no++ ?></td>

                                        <td class="fw-semibold">
                                            <?= esc((string) $row->no_penyerahan_penjualan) ?>
                                            <?php if (!empty($row->catatan)): ?>
                                                <div class="text-muted small"><?= esc(mb_strimwidth((string) $row->catatan, 0, 38, '...')) ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= esc((string) $row->tanggal_penyerahan) ?></td>

                                        <td>
                                            <span class="fw-semibold"><?= esc((string) ($row->no_pesanan_penjualan ?? '-')) ?></span>
                                            <?php if (!empty($row->status_pesanan)): ?>
                                                <div class="text-muted small">Status pesanan: <?= esc((string) $row->status_pesanan) ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= esc(($row->kode_pelanggan ?? '-') . ' - ' . ($row->nama_pelanggan ?? '-')) ?>
                                        </td>

                                        <td>
                                            <?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?>
                                        </td>

                                        <td class="text-end"><?= esc(penyerahan_penjualan_qty($row->total_qty ?? 0)) ?></td>

                                        <td class="text-end"><?= esc(number_format((int) ($row->jumlah_item ?? 0), 0, '.', ',')) ?></td>

                                        <td class="text-end fw-semibold">
                                            <?= esc(penyerahan_penjualan_uang($row->total_hpp ?? 0)) ?>
                                        </td>

                                        <td><?= penyerahan_penjualan_badge_faktur($jumlah_faktur) ?></td>

                                        <td><?= penyerahan_penjualan_badge_status($row_status) ?></td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('penjualan/penyerahan/detail') . '&id=' . (int) $row->id_penyerahan_penjualan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ($row_status === 'draft'): ?>
                                                <a href="<?= esc(admin_page_url('penjualan/penyerahan/edit') . '&id=' . (int) $row->id_penyerahan_penjualan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('index.php?menu=penjualan/penyerahan/posting&id=' . (int) $row->id_penyerahan_penjualan)) ?>" class="btn btn-sm btn-outline-success" title="Posting" onclick="return confirm('Posting penyerahan ini? Stok produk akan berkurang dan jurnal HPP akan dibuat.')">
                                                    <i class="bi bi-check-circle"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('index.php?menu=penjualan/penyerahan/hapus&id=' . (int) $row->id_penyerahan_penjualan)) ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Hapus penyerahan draft ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('index.php?menu=penjualan/penyerahan/cetak&id=' . (int) $row->id_penyerahan_penjualan)) ?>" class="btn btn-sm btn-outline-secondary" title="Cetak" target="_blank">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="13" class="text-center text-muted py-4">
                                        Data penyerahan penjualan belum tersedia.
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
                        <a class="page-link" href="<?= esc(build_page_url_penyerahan_penjualan(max(1, $page - 1))) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_penyerahan_penjualan($i)) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_penyerahan_penjualan(min($totalPages, $page + 1))) ?>">
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

<div class="modal fade" id="modalScanPenyerahanIndex" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4">
      <div class="modal-header text-white" style="background:linear-gradient(135deg,#2563eb,#6d5dfc,#f97316)">
        <h5 class="modal-title">Scan QR / Barcode Pesanan Online</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Kode Barcode / No Pesanan</label>
          <input type="text" class="form-control form-control-lg" id="scan-index-kode" placeholder="Contoh: PSJ-202606-0001" autocomplete="off">
          <div class="form-text">Scan barcode struk pesanan online atau ketik nomor pesanan.</div>
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold">Gudang Pengambilan</label>
          <select class="form-select" id="scan-index-gudang" required>
            <option value="">- Pilih Gudang -</option>
            <?php foreach ($gudang_scan_options as $g): ?>
              <option value="<?= (int) $g->id_gudang ?>"><?= esc(($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-')) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Transfer/QRIS: detail barang otomatis dimuat. COD: langsung muncul popup terima uang.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" onclick="scanPenyerahanIndexGo()">Lanjut ke Form</button>
      </div>
    </div>
  </div>
</div>

<script>
async function scanPenyerahanIndexGo(){
    const kode = (document.getElementById('scan-index-kode')?.value || '').trim();
    const gudang = (document.getElementById('scan-index-gudang')?.value || '').trim();
    if(!kode){ alert('Kode barcode/no pesanan wajib diisi.'); return; }
    if(!gudang){ alert('Gudang wajib dipilih.'); return; }
    try{
        const url = '<?= esc(admin_page_url('penjualan/penyerahan/load-pesanan-online-barcode')) ?>' + '&kode=' + encodeURIComponent(kode);
        const res = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
        const data = await res.json();
        if(!data.success){ alert(data.message || 'Pesanan tidak ditemukan.'); return; }
        const ps = data.pesanan || {};
        let tujuan = '<?= esc(admin_page_url('penjualan/penyerahan/tambah')) ?>'
            + '&id_pesanan_penjualan=' + encodeURIComponent(ps.id_pesanan_penjualan || '')
            + '&id_gudang=' + encodeURIComponent(gudang);
        if (String(ps.metode_pembayaran_online || '').toLowerCase() === 'cod') {
            tujuan += '&auto_cod=1&kode=' + encodeURIComponent(ps.no_pesanan_penjualan || kode);
        } else {
            tujuan += '&auto_load=1';
        }
        window.location.href = tujuan;
    }catch(e){ alert('Gagal membaca barcode/no pesanan.'); }
}
document.addEventListener('keydown', function(e){
    if(e.key === 'Enter' && document.activeElement?.id === 'scan-index-kode'){
        e.preventDefault(); scanPenyerahanIndexGo();
    }
});
</script>
