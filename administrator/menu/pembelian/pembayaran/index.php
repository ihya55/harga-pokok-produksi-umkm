<?php
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$status_lunas = trim((string) ($_GET['status_lunas'] ?? 'belum_lunas'));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_faktur'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedStatusLunas = ['belum_lunas', 'sudah_lunas'];
$allowedSort = ['no_faktur_pembelian', 'tanggal_faktur', 'total', 'sisa_utang', 'jatuh_tempo', 'jenis_pembayaran'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($status_lunas, $allowedStatusLunas, true)) $status_lunas = 'belum_lunas';
if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_faktur';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

$jumlah_belum_lunas = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('sisa_utang', '>', 0)
    ->count();

$jumlah_sudah_lunas = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_faktur', 'posted')
    ->where('sisa_utang', '<=', 0)
    ->count();

$query = FakturPembelianORM::query()
    ->from('tb_faktur_pembelian as fp')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
    ->leftJoin('tb_penerimaan_pembelian as pn', 'pn.id_penerimaan_pembelian', '=', 'fp.id_penerimaan_pembelian')
    ->where('fp.id_entitas', $id_entitas)
    ->where('fp.status_faktur', 'posted');

if ($status_lunas === 'sudah_lunas') {
    $query->where('fp.sisa_utang', '<=', 0);
} else {
    $query->where('fp.sisa_utang', '>', 0);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('fp.no_faktur_pembelian', 'like', '%' . $q . '%')
            ->orWhere('pn.no_penerimaan_pembelian', 'like', '%' . $q . '%')
            ->orWhere('p.nama_pemasok', 'like', '%' . $q . '%')
            ->orWhere('p.kode_pemasok', 'like', '%' . $q . '%')
            ->orWhere('fp.jenis_pembayaran', 'like', '%' . $q . '%')
            ->orWhereRaw("
                EXISTS (
                    SELECT 1
                    FROM tb_pembayaran_pembelian pp
                    WHERE pp.id_faktur_pembelian = fp.id_faktur_pembelian
                    AND pp.id_entitas = fp.id_entitas
                    AND pp.no_pembayaran_pembelian LIKE ?
                )
            ", ['%' . $q . '%']);
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
    ->selectRaw("
        (
            SELECT COUNT(*)
            FROM tb_pembayaran_pembelian pp
            WHERE pp.id_faktur_pembelian = fp.id_faktur_pembelian
            AND pp.id_entitas = fp.id_entitas
            AND pp.status_posting = 'posted'
        ) as jumlah_pembayaran
    ")
    ->selectRaw("
        (
            SELECT MAX(pp.no_pembayaran_pembelian)
            FROM tb_pembayaran_pembelian pp
            WHERE pp.id_faktur_pembelian = fp.id_faktur_pembelian
            AND pp.id_entitas = fp.id_entitas
            AND pp.status_posting = 'posted'
        ) as no_pembayaran_terakhir
    ")
    ->selectRaw("
        (
            SELECT MAX(pp.tanggal_pembayaran)
            FROM tb_pembayaran_pembelian pp
            WHERE pp.id_faktur_pembelian = fp.id_faktur_pembelian
            AND pp.id_entitas = fp.id_entitas
            AND pp.status_posting = 'posted'
        ) as tanggal_pembayaran_terakhir
    ")
    ->selectRaw("
        (
            SELECT COALESCE(SUM(pp.jumlah_bayar), 0)
            FROM tb_pembayaran_pembelian pp
            WHERE pp.id_faktur_pembelian = fp.id_faktur_pembelian
            AND pp.id_entitas = fp.id_entitas
            AND pp.status_posting = 'posted'
        ) as total_dibayar
    ")
    ->orderBy('fp.' . $sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

function build_sort_url_pembayaran_pembelian(string $column): string
{
    $params = [
        'menu'         => 'pembelian/pembayaran',
        'status_lunas' => trim((string) ($_GET['status_lunas'] ?? 'belum_lunas')),
        'q'            => trim((string) ($_GET['q'] ?? '')),
        'sort'         => $column,
        'order'        => 'asc',
        'per_page'     => (int) ($_GET['per_page'] ?? 10),
        'hal'          => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_faktur'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_pembayaran_pembelian(string $column): string
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

function build_page_url_pembayaran_pembelian(int $targetPage): string
{
    $params = [
        'menu'         => 'pembelian/pembayaran',
        'status_lunas' => trim((string) ($_GET['status_lunas'] ?? 'belum_lunas')),
        'q'            => trim((string) ($_GET['q'] ?? '')),
        'sort'         => trim((string) ($_GET['sort'] ?? 'tanggal_faktur')),
        'order'        => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page'     => (int) ($_GET['per_page'] ?? 10),
        'hal'          => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

function build_tab_url_pembayaran_pembelian(string $status): string
{
    $params = [
        'menu'         => 'pembelian/pembayaran',
        'status_lunas' => $status,
        'q'            => trim((string) ($_GET['q'] ?? '')),
        'sort'         => trim((string) ($_GET['sort'] ?? 'tanggal_faktur')),
        'order'        => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page'     => (int) ($_GET['per_page'] ?? 10),
        'hal'          => 1,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

function label_jenis_bayar_pembelian(?string $jenis): string
{
    $jenis = strtolower(trim((string) $jenis));

    if ($jenis === 'tunai') {
        return 'Tunai';
    }

    if ($jenis === 'transfer') {
        return 'Transfer';
    }

    return 'Kredit';
}

function badge_jenis_bayar_pembelian(?string $jenis): string
{
    $jenis = strtolower(trim((string) $jenis));

    if ($jenis === 'tunai') {
        return 'text-bg-success';
    }

    if ($jenis === 'transfer') {
        return 'text-bg-primary';
    }

    return 'text-bg-warning';
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Pembayaran Pembelian</h1>
    <p class="page-subtitle">Daftar faktur pembelian berdasarkan status pelunasan</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $status_lunas === 'belum_lunas' ? 'active' : '' ?>"
                   href="<?= esc(build_tab_url_pembayaran_pembelian('belum_lunas')) ?>">
                    Belum Lunas
                    <span class="badge text-bg-danger ms-1"><?= (int) $jumlah_belum_lunas ?></span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?= $status_lunas === 'sudah_lunas' ? 'active' : '' ?>"
                   href="<?= esc(build_tab_url_pembayaran_pembelian('sudah_lunas')) ?>">
                    Sudah Lunas
                    <span class="badge text-bg-success ms-1"><?= (int) $jumlah_sudah_lunas ?></span>
                </a>
            </li>
        </ul>

        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <?php if ($status_lunas === 'sudah_lunas'): ?>
                    <h2 class="h5 mb-1">Faktur Sudah Lunas</h2>
                    <div class="text-muted small">Total faktur sudah lunas: <?= (int) $totalRows ?></div>
                <?php else: ?>
                    <h2 class="h5 mb-1">Faktur Belum Lunas</h2>
                    <div class="text-muted small">Total faktur belum lunas: <?= (int) $totalRows ?></div>
                <?php endif; ?>
            </div>

            <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="d-flex flex-column flex-md-row gap-2 align-items-stretch">
                <input type="hidden" name="menu" value="pembelian/pembayaran">
                <input type="hidden" name="status_lunas" value="<?= esc($status_lunas) ?>">
                <input type="hidden" name="sort" value="<?= esc($sort) ?>">
                <input type="hidden" name="order" value="<?= esc($order) ?>">

                <div class="filter-search-box">
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        placeholder="Cari faktur, penerimaan, pemasok, pembayaran..."
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
                    <a href="<?= esc(admin_url('index.php?menu=pembelian/pembayaran&status_lunas=' . $status_lunas)) ?>" class="btn btn-outline-secondary">
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
                            <th width="60" class="text-center">No</th>
                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_pembelian('no_faktur_pembelian')) ?>" class="text-decoration-none text-dark">
                                    No Faktur<?= sort_icon_pembayaran_pembelian('no_faktur_pembelian') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_pembelian('tanggal_faktur')) ?>" class="text-decoration-none text-dark">
                                    Tanggal Faktur<?= sort_icon_pembayaran_pembelian('tanggal_faktur') ?>
                                </a>
                            </th>
                            <th>No Penerimaan</th>
                            <th>Pemasok</th>
                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_pembelian('jenis_pembayaran')) ?>" class="text-decoration-none text-dark">
                                    Jenis Bayar<?= sort_icon_pembayaran_pembelian('jenis_pembayaran') ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= esc(build_sort_url_pembayaran_pembelian('jatuh_tempo')) ?>" class="text-decoration-none text-dark">
                                    Jatuh Tempo<?= sort_icon_pembayaran_pembelian('jatuh_tempo') ?>
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="<?= esc(build_sort_url_pembayaran_pembelian('total')) ?>" class="text-decoration-none text-dark">
                                    Total Faktur<?= sort_icon_pembayaran_pembelian('total') ?>
                                </a>
                            </th>
                            <th class="text-end">
                                <a href="<?= esc(build_sort_url_pembayaran_pembelian('sisa_utang')) ?>" class="text-decoration-none text-dark">
                                    Sisa Utang<?= sort_icon_pembayaran_pembelian('sisa_utang') ?>
                                </a>
                            </th>
                            <th>Pembayaran</th>
                            <th width="210" class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($data_faktur->count() > 0): ?>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($data_faktur as $row): ?>
                                <?php
                                $jenis_bayar = strtolower((string) ($row->jenis_pembayaran ?? 'kredit'));
                                $jumlah_pembayaran = (int) ($row->jumlah_pembayaran ?? 0);
                                $total_dibayar = (float) ($row->total_dibayar ?? 0);
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++ ?></td>

                                    <td class="fw-semibold"><?= esc($row->no_faktur_pembelian) ?></td>

                                    <td><?= esc($row->tanggal_faktur) ?></td>

                                    <td><?= esc($row->no_penerimaan_pembelian ?? '-') ?></td>

                                    <td><?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></td>

                                    <td>
                                        <span class="badge <?= esc(badge_jenis_bayar_pembelian($jenis_bayar)) ?>">
                                            <?= esc(label_jenis_bayar_pembelian($jenis_bayar)) ?>
                                        </span>
                                    </td>

                                    <td><?= esc($row->jatuh_tempo ?? '-') ?></td>

                                    <td class="text-end">
                                        Rp <?= esc(number_format((float) $row->total, 2, '.', ',')) ?>
                                    </td>

                                    <td class="text-end fw-semibold <?= ((float) $row->sisa_utang > 0) ? 'text-danger' : 'text-success' ?>">
                                        Rp <?= esc(number_format((float) $row->sisa_utang, 2, '.', ',')) ?>
                                    </td>

                                    <td>
                                        <?php if ($jumlah_pembayaran > 0): ?>
                                            <div class="fw-semibold text-success">
                                                <?= esc($row->no_pembayaran_terakhir ?? '-') ?>
                                            </div>
                                            <div class="small text-muted">
                                                <?= esc($row->tanggal_pembayaran_terakhir ?? '-') ?>
                                                · Rp <?= esc(number_format($total_dibayar, 2, '.', ',')) ?>
                                            </div>
                                        <?php elseif ((float) $row->sisa_utang <= 0 && in_array($jenis_bayar, ['tunai', 'transfer'], true)): ?>
                                            <span class="badge text-bg-warning">Belum tercatat</span>
                                            <div class="small text-muted">Cek posting faktur</div>
                                        <?php elseif ((float) $row->sisa_utang <= 0): ?>
                                            <span class="badge text-bg-success">Lunas</span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <?php if ((float) $row->sisa_utang > 0): ?>
                                            <a
                                                href="<?= esc(admin_page_url('pembelian/pembayaran/tambah') . '&id_faktur_pembelian=' . (int) $row->id_faktur_pembelian . '&back_url=' . $back_url) ?>"
                                                class="btn btn-sm btn-success"
                                                title="Bayar Faktur">
                                                <i class="bi bi-cash-coin"></i>
                                            </a>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" title="Faktur Sudah Lunas" disabled>
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        <?php endif; ?>

                                        <a
                                            href="<?= esc(admin_page_url('pembelian/pembayaran/detail-faktur') . '&id_faktur_pembelian=' . (int) $row->id_faktur_pembelian . '&back_url=' . $back_url) ?>"
                                            class="btn btn-sm btn-outline-info"
                                            title="Detail Tagihan">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a
                                            href="<?= esc(admin_page_url('pembelian/pembayaran/riwayat') . '&id_faktur_pembelian=' . (int) $row->id_faktur_pembelian . '&back_url=' . $back_url) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Riwayat Pembayaran">
                                            <i class="bi bi-clock-history"></i>
                                        </a>

                                        <a
                                            href="<?= esc(admin_url('menu/pembelian/pembayaran/cetak_faktur.php?id_faktur_pembelian=' . (int) $row->id_faktur_pembelian)) ?>"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-secondary"
                                            title="Cetak Tagihan">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    <?= $status_lunas === 'sudah_lunas'
                                        ? 'Tidak ada faktur pembelian yang sudah lunas.'
                                        : 'Tidak ada faktur pembelian yang masih belum lunas.' ?>
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
                        <a class="page-link" href="<?= esc(build_page_url_pembayaran_pembelian(max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_pembayaran_pembelian($i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_pembayaran_pembelian(min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>