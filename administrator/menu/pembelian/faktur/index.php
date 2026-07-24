<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_faktur'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedSort = ['no_faktur_pembelian', 'tanggal_faktur', 'status_faktur', 'total', 'sisa_utang'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_faktur';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$query = FakturPembelianORM::query()
    ->from('tb_faktur_pembelian as fp')
    ->leftJoin('tb_penerimaan_pembelian as pn', 'pn.id_penerimaan_pembelian', '=', 'fp.id_penerimaan_pembelian')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
    ->where('fp.id_entitas', $id_entitas);

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('fp.no_faktur_pembelian', 'like', '%' . $q . '%')
            ->orWhere('pn.no_penerimaan_pembelian', 'like', '%' . $q . '%')
            ->orWhere('p.nama_pemasok', 'like', '%' . $q . '%')
            ->orWhere('p.kode_pemasok', 'like', '%' . $q . '%')
            ->orWhere('fp.status_faktur', 'like', '%' . $q . '%');
    });
}

$totalRows = (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_faktur = $query
    ->select([
        'fp.*',
        'pn.no_penerimaan_pembelian',
        'p.kode_pemasok',
        'p.nama_pemasok',
    ])
    ->orderBy('fp.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_faktur_pembelian(string $column): string
{
    $params = [
        'menu'     => 'pembelian/faktur',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => $column,
        'order'    => 'asc',
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_faktur'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_faktur_pembelian(string $column): string
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

function build_page_url_faktur_pembelian(int $targetPage): string
{
    $params = [
        'menu'     => 'pembelian/faktur',
        'q'        => trim((string) ($_GET['q'] ?? '')),
        'sort'     => trim((string) ($_GET['sort'] ?? 'tanggal_faktur')),
        'order'    => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page' => (int) ($_GET['per_page'] ?? 10),
        'hal'      => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Faktur Pembelian</h1>
    <p class="page-subtitle">Kelola faktur pembelian dan utang pemasok</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1">Daftar Faktur Pembelian</h2>
                <div class="text-muted small">Total data: <?= (int) $totalRows ?></div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                    <input type="hidden" name="menu" value="pembelian/faktur">
                    <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                    <input type="hidden" name="order" value="<?= esc($order) ?>">

                    <div class="filter-search-box">
                        <input
                            type="text"
                            name="q"
                            class="form-control"
                            placeholder="Cari no faktur, penerimaan, pemasok, status..."
                            value="<?= esc($q) ?>">
                    </div>

                    <div style="min-width:140px;">
                        <select name="per_page" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($allowedPerPage as $limit): ?>
                                <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>>
                                    <?= $limit ?> baris
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search"></i>
                    </button>

                    <?php if ($q !== '' || $perPage !== 10): ?>
                        <a href="<?= esc(admin_page_url('pembelian/faktur')) ?>" class="btn btn-outline-secondary">
                            Reset
                        </a>
                    <?php endif; ?>
                </form>

                <a href="<?= esc(admin_page_url('pembelian/faktur/tambah')) ?>" class="btn btn-gradient">
                    <i class="bi bi-plus-circle me-1"></i>Tambah Faktur
                </a>
            </div>
        </div>

        <form method="post" action="<?= esc(admin_url('menu/pembelian/faktur/hapus_massal.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data yang dipilih?')">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash me-1"></i>Hapus Terpilih
                </button>

                <div class="text-muted small">
                    Pilih data draft yang ingin dihapus
                </div>
            </div>

            <div class="table-responsive border rounded">
                <div style="max-height:420px; overflow-y:auto;">
                    <table class="table align-middle table-hover mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th width="50" class="text-center"><input type="checkbox" id="checkAll"></th>
                                <th width="70" class="text-center">No</th>
                                <th>
                                    <a href="<?= esc(build_sort_url_faktur_pembelian('no_faktur_pembelian')) ?>" class="text-decoration-none text-dark">
                                        No Faktur<?= sort_icon_faktur_pembelian('no_faktur_pembelian') ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="<?= esc(build_sort_url_faktur_pembelian('tanggal_faktur')) ?>" class="text-decoration-none text-dark">
                                        Tanggal<?= sort_icon_faktur_pembelian('tanggal_faktur') ?>
                                    </a>
                                </th>
                                <th>No Penerimaan</th>
                                <th>Pemasok</th>
                                <th>Jenis Bayar</th>
                                <th>Status</th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_faktur_pembelian('total')) ?>" class="text-decoration-none text-dark">
                                        Total<?= sort_icon_faktur_pembelian('total') ?>
                                    </a>
                                </th>
                                <th class="text-end">
                                    <a href="<?= esc(build_sort_url_faktur_pembelian('sisa_utang')) ?>" class="text-decoration-none text-dark">
                                        Sisa Utang<?= sort_icon_faktur_pembelian('sisa_utang') ?>
                                    </a>
                                </th>
                                <th width="240" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data_faktur->count() > 0): ?>
                                <?php $no = $offset + 1; ?>
                                <?php foreach ($data_faktur as $row): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?php if ((string) $row->status_faktur === 'draft'): ?>
                                                <input type="checkbox" name="id_faktur_pembelian[]" value="<?= (int) $row->id_faktur_pembelian ?>" class="row-check">
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= esc($row->no_faktur_pembelian) ?></td>
                                        <td><?= esc($row->tanggal_faktur) ?></td>
                                        <td><?= esc($row->no_penerimaan_pembelian ?? '-') ?></td>
                                        <td><?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></td>
                                        <td><?= esc(ucfirst((string) $row->jenis_pembayaran)) ?></td>
                                        <td>
                                            <?php $badge = ((string) $row->status_faktur === 'posted') ? 'success' : 'secondary'; ?>
                                            <span class="badge text-bg-<?= $badge ?>">
                                                <?= esc(ucfirst((string) $row->status_faktur)) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">Rp <?= esc(number_format((float) $row->total, 2, '.', ',')) ?></td>
                                        <td class="text-end">Rp <?= esc(number_format((float) $row->sisa_utang, 2, '.', ',')) ?></td>
                                        <td class="text-center">
                                            <a href="<?= esc(admin_page_url('pembelian/faktur/detail') . '&id=' . (int) $row->id_faktur_pembelian) ?>" class="btn btn-sm btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>

                                            <?php if ((string) $row->status_faktur === 'draft'): ?>
                                                <a href="<?= esc(admin_page_url('pembelian/faktur/edit') . '&id=' . (int) $row->id_faktur_pembelian) ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>

                                                <?php
                                                    $jenis_bayar_posting = strtolower((string) ($row->jenis_pembayaran ?? 'kredit'));

                                                    if ($jenis_bayar_posting === 'tunai') {
                                                        $pesan_posting = 'Posting faktur ini? Jurnal pembelian tunai akan dibuat.';
                                                    } elseif ($jenis_bayar_posting === 'transfer') {
                                                        $pesan_posting = 'Posting faktur ini? Jurnal pembelian transfer akan dibuat.';
                                                    } else {
                                                        $pesan_posting = 'Posting faktur ini? Jurnal utang pembelian akan dibuat.';
                                                    }
                                                    ?>

                                                    <a href="<?= esc(admin_url('menu/pembelian/faktur/posting.php?id=' . (int) $row->id_faktur_pembelian)) ?>"
                                                    class="btn btn-sm btn-outline-success"
                                                    onclick="return confirm('<?= esc($pesan_posting) ?>');">
                                                        <i class="bi bi-journal-check"></i>
                                                    </a>

                                                <a href="<?= esc(admin_url('menu/pembelian/faktur/hapus.php?id=' . (int) $row->id_faktur_pembelian)) ?>"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Yakin ingin menghapus data ini?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= esc(admin_url('menu/pembelian/faktur/cetak.php?id=' . (int) $row->id_faktur_pembelian)) ?>"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        Data faktur pembelian belum ada.
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
                            <a class="page-link" href="<?= esc(build_page_url_faktur_pembelian(max(1, $page - 1))) ?>">Prev</a>
                        </li>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc(build_page_url_faktur_pembelian($i)) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_faktur_pembelian(min($totalPages, $page + 1))) ?>">Next</a>
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