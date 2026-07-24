<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_pengambilan'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedStatus = ['semua', 'draft', 'diperiksa', 'posted'];
$allowedSort = [
    'no_pengambilan_bahan',
    'tanggal_pengambilan',
    'status_posting',
];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($status, $allowedStatus, true)) $status = 'semua';
if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_pengambilan';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = PengambilanBahanORM::query()
    ->from('tb_pengambilan_bahan as pb')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'pb.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'pb.id_gudang')
    ->where('pb.id_entitas', $id_entitas);

if ($status !== 'semua') {
    $query->where('pb.status_posting', $status);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('pb.no_pengambilan_bahan', 'like', '%' . $q . '%')
            ->orWhere('pb.catatan', 'like', '%' . $q . '%')
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

$data_pengambilan = $query
    ->select([
        'pb.*',
        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.qty_hasil',
        'pp.status_produksi',
        'pr.kode_produk',
        'pr.nama_produk',
        'g.kode_gudang',
        'g.nama_gudang',
    ])
    ->orderBy('pb.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

$ids_pengambilan = $data_pengambilan->pluck('id_pengambilan_bahan')->map(function ($id) {
    return (int) $id;
})->toArray();

$jumlah_detail_map = [];
$total_bahan_map = [];

if (count($ids_pengambilan) > 0) {
    $summary_rows = PengambilanBahanDetailORM::query()
        ->from('tb_pengambilan_bahan_detail as pbd')
        ->whereIn('pbd.id_pengambilan_bahan', $ids_pengambilan)
        ->selectRaw('pbd.id_pengambilan_bahan, COUNT(*) as jumlah_baris, COALESCE(SUM(pbd.subtotal),0) as total_bahan')
        ->groupBy('pbd.id_pengambilan_bahan')
        ->get();

    foreach ($summary_rows as $sr) {
        $jumlah_detail_map[(int) $sr->id_pengambilan_bahan] = (int) $sr->jumlah_baris;
        $total_bahan_map[(int) $sr->id_pengambilan_bahan] = (float) $sr->total_bahan;
    }
}

function build_sort_url_pengambilan_bahan(string $column): string
{
    $params = [
        'menu'     => 'produksi/pengambilan-bahan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_pengambilan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_pengambilan_bahan(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_pengambilan'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_pengambilan_bahan(int $targetPage): string
{
    $params = [
        'menu'     => 'produksi/pengambilan-bahan',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'status'   => trim((string) ($_GET['status'] ?? 'semua')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_pengambilan')),
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
    <h1 class="page-title">Pengambilan Bahan</h1>
    <p class="page-subtitle">Bon pengambilan bahan untuk produksi dengan kontrol stok gudang</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Pengambilan Bahan</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="produksi/pengambilan-bahan">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari no bon, perintah, produk, gudang..."
                        value="<?= esc($q) ?>">

                    <select name="status" class="form-select" style="min-width:150px;" onchange="this.form.submit()">
                        <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua Status</option>
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                        <option value="diperiksa" <?= $status === 'diperiksa' ? 'selected' : '' ?>>Diperiksa</option>
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
                        <a href="<?= esc(admin_page_url('produksi/pengambilan-bahan')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('produksi/pengambilan-bahan/tambah') . '&back_url=' . $back_url) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Bon
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/produksi/pengambilan_bahan/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">
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
                                <th width="50" class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th width="70" class="text-center">No</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pengambilan_bahan('no_pengambilan_bahan')) ?>" class="text-decoration-none text-dark">
                                        No Bon<?= sort_icon_pengambilan_bahan('no_pengambilan_bahan') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pengambilan_bahan('tanggal_pengambilan')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_pengambilan_bahan('tanggal_pengambilan') ?>
                                    </a>
                                </th>
                                <th>Perintah Produksi</th>
                                <th>Produk</th>
                                <th>Gudang</th>
                                <th class="text-end">Baris</th>
                                <th class="text-end">Nilai Bahan</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_pengambilan_bahan('status_posting')) ?>" class="text-decoration-none text-dark">
                                        Status<?= sort_icon_pengambilan_bahan('status_posting') ?>
                                    </a>
                                </th>
                                <th width="260" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_pengambilan->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_pengambilan as $row): ?>
                                    <?php
                                    $id_pengambilan = (int) $row->id_pengambilan_bahan;
                                    $jumlah_baris = (int) ($jumlah_detail_map[$id_pengambilan] ?? 0);
                                    $total_bahan = (float) ($total_bahan_map[$id_pengambilan] ?? 0);
                                    $status_row = (string) ($row->status_posting ?? 'draft');

                                    $badge = 'secondary';
                                    if ($status_row === 'diperiksa') $badge = 'warning';
                                    if ($status_row === 'posted') $badge = 'success';
                                    ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ($status_row === 'draft'): ?>
                                                <input type="checkbox" name="id_pengambilan_bahan[]" value="<?= (int) $row->id_pengambilan_bahan ?>" class="row-check">
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->no_pengambilan_bahan) ?></td>
                                        <td><?= esc($row->tanggal_pengambilan) ?></td>
                                        <td><?= esc($row->no_perintah_produksi ?? '-') ?></td>
                                        <td><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></td>
                                        <td><?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></td>
                                        <td class="text-end"><?= (int) $jumlah_baris ?></td>
                                        <td class="text-end">Rp <?= esc(number_format($total_bahan, 2, '.', ',')) ?></td>
                                        <td>
                                            <span class="badge text-bg-<?= esc($badge) ?>">
                                                <?= esc(ucfirst($status_row)) ?>
                                            </span>
                                        </td>

                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('produksi/pengambilan-bahan/detail') . '&id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ($status_row === 'draft' || $status_row === 'diperiksa'): ?>
                                                <a href="<?= esc(admin_page_url('produksi/pengambilan-bahan/edit') . '&id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_url) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($status_row === 'draft'): ?>
                                                <a href="<?= esc(admin_url('index.php?menu=produksi/pengambilan-bahan/periksa-stok&id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-warning"
                                                   onclick="return confirm('Periksa stok bahan untuk bon ini? Jika cukup, status menjadi diperiksa.');"
                                                   title="Periksa Stok">
                                                    <i class="bi bi-clipboard-check"></i>
                                                </a>

                                                <a href="<?= esc(admin_url('menu/produksi/pengambilan_bahan/hapus.php?id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Yakin ingin menghapus bon draft ini?');"
                                                   title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($status_row === 'diperiksa'): ?>
                                                <a href="<?= esc(admin_url('index.php?menu=produksi/pengambilan-bahan/posting&id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_url)) ?>"
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Posting pengambilan bahan ini? Stok akan keluar dan jurnal akan dibuat.');"
                                                   title="Posting">
                                                    <i class="bi bi-journal-check"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('menu/produksi/pengambilan_bahan/cetak.php?id=' . (int) $row->id_pengambilan_bahan)) ?>"
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
                                    <td colspan="11" class="text-center text-muted py-4">
                                        Data pengambilan bahan belum ada.
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
                            <a class="page-link" href="<?= esc(build_page_url_pengambilan_bahan(max(1, $page - 1))) ?>">Prev</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_pengambilan_bahan($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_pengambilan_bahan(min($totalPages, $page + 1))) ?>">Next</a>
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