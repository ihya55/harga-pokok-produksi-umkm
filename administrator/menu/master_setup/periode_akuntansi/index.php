<?php
declare(strict_types=1);

$q        = trim((string) ($_GET['q'] ?? ''));
$sort     = trim((string) ($_GET['sort'] ?? 'tahun'));
$order    = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage  = (int) ($_GET['per_page'] ?? 10);
$page     = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['tahun', 'bulan', 'tanggal_mulai', 'tanggal_selesai', 'status_periode'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) {
    $sort = 'tahun';
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

$query = PeriodeAkuntansiORM::query()
    ->from('tb_periode_akuntansi as p')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'p.id_entitas')
    ->leftJoin('tb_pengguna as u', 'u.id_pengguna', '=', 'p.dibuat_oleh')
    ->where('p.id_entitas', (int) ($user['id_entitas'] ?? 0));

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('p.tahun', 'like', '%' . $q . '%')
            ->orWhere('p.bulan', 'like', '%' . $q . '%')
            ->orWhere('p.status_periode', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_periode = $query
    ->select([
        'p.id_periode',
        'p.tahun',
        'p.bulan',
        'p.tanggal_mulai',
        'p.tanggal_selesai',
        'p.status_periode',
        'u.nama_lengkap as nama_pembuat',
    ])
    ->orderBy('p.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function nama_bulan_periode(int $bulan): string
{
    $list = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return $list[$bulan] ?? (string) $bulan;
}

function build_sort_url_periode(string $column): string
{
    $params = [
        'menu'     => 'master_setup/periode_akuntansi',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tahun'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_periode(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tahun'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_periode(int $targetPage): string
{
    $params = [
        'menu'     => 'master_setup/periode_akuntansi',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tahun')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Periode Akuntansi</h1>
    <p class="page-subtitle">Kelola periode akuntansi untuk entitas aktif</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Periode Akuntansi</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="master_setup/periode_akuntansi">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <div class="filter-search-box">
                        <input type="text" name="q" class="form-control" placeholder="Cari tahun, bulan, status..." value="<?= esc($q) ?>">
                    </div>

                    <div style="min-width: 140px;">
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($allowedPerPage as $limit): ?>
                                <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>><?= $limit ?> baris</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>

                    <?php if ($q !== '' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('master_setup/periode_akuntansi')) ?>" class="btn btn-outline-secondary">Reset</a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('master_setup/periode_akuntansi/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Periode
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/master_setup/periode_akuntansi/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div class="small text-muted">
                    Menampilkan <strong><?= $totalRows > 0 ? ($offset + 1) : 0 ?></strong> - <strong><?= min($offset + $perPage, $totalRows) ?></strong> dari <strong><?= (int) $totalRows ?></strong> data
                </div>

                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>
            </div>

            <div class="table-responsive border rounded">
                <div style="max-height: 420px; overflow-y: auto;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="50" class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th width="70" class="text-center">No</th>
                                <th><a href="<?= esc(build_sort_url_periode('tahun')) ?>" class="text-decoration-none text-dark">Tahun<?= sort_icon_periode('tahun') ?></a></th>
                                <th><a href="<?= esc(build_sort_url_periode('bulan')) ?>" class="text-decoration-none text-dark">Bulan<?= sort_icon_periode('bulan') ?></a></th>
                                <th><a href="<?= esc(build_sort_url_periode('tanggal_mulai')) ?>" class="text-decoration-none text-dark">Tanggal Mulai<?= sort_icon_periode('tanggal_mulai') ?></a></th>
                                <th><a href="<?= esc(build_sort_url_periode('tanggal_selesai')) ?>" class="text-decoration-none text-dark">Tanggal Selesai<?= sort_icon_periode('tanggal_selesai') ?></a></th>
                                <th><a href="<?= esc(build_sort_url_periode('status_periode')) ?>" class="text-decoration-none text-dark">Status<?= sort_icon_periode('status_periode') ?></a></th>
                                <th>Dibuat Oleh</th>
                                <th width="210" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($data_periode->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_periode as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <input type="checkbox" name="id_periode[]" value="<?= (int) $row->id_periode ?>" class="row-check">
                                        </td>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td><?= (int) $row->tahun ?></td>
                                        <td><?= esc(nama_bulan_periode((int) $row->bulan)) ?></td>
                                        <td><?= esc((string) $row->tanggal_mulai) ?></td>
                                        <td><?= esc((string) $row->tanggal_selesai) ?></td>
                                        <td>
                                            <?php if (($row->status_periode ?? '') === 'terbuka'): ?>
                                                <span class="badge text-bg-success">Terbuka</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary">Tertutup</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($row->nama_pembuat ?? '-') ?></td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('master_setup/periode_akuntansi/detail') . '&id=' . (int) $row->id_periode) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= esc(admin_page_url('master_setup/periode_akuntansi/edit') . '&id=' . (int) $row->id_periode) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <a href="<?= esc(admin_url('menu/master_setup/periode_akuntansi/hapus.php?id=' . (int) $row->id_periode)) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin ingin menghapus data periode ini?')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">Belum ada data periode akuntansi.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </form>

        <?php if ($totalPages > 1): ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mt-4">
                <div class="small text-muted">Halaman <strong><?= $page ?></strong> dari <strong><?= $totalPages ?></strong></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url_periode(1)) ?>">Awal</a>
                        </li>
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page <= 1 ? '#' : esc(build_page_url_periode($page - 1)) ?>">«</a>
                        </li>
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_periode($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url_periode($page + 1)) ?>">»</a>
                        </li>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $page >= $totalPages ? '#' : esc(build_page_url_periode($totalPages)) ?>">Akhir</a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const rowChecks = document.querySelectorAll('.row-check');

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rowChecks.forEach(function (item) {
                item.checked = checkAll.checked;
            });
        });
    }
});
</script>