<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$jenis_barang = trim((string) ($_GET['jenis_barang'] ?? 'semua'));
$id_referensi_barang = (int) ($_GET['id_referensi_barang'] ?? 0);
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);
$status_stok = trim((string) ($_GET['status_stok'] ?? 'semua'));
$sort = trim((string) ($_GET['sort'] ?? 'nama_barang'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'asc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedJenisBarang = ['semua', 'bahan_baku', 'produk'];
$allowedStatusStok = ['semua', 'kosong', 'minimum', 'aman'];
$allowedSort = [
    'jenis_barang',
    'kode_barang',
    'nama_barang',
    'nama_gudang',
    'qty_saldo',
    'stok_minimum',
    'nilai_saldo',
    'tanggal_update',
];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($jenis_barang, $allowedJenisBarang, true)) $jenis_barang = 'semua';
if ($jenis_barang === 'semua') $id_referensi_barang = 0;
if (!in_array($status_stok, $allowedStatusStok, true)) $status_stok = 'semua';
if (!in_array($sort, $allowedSort, true)) $sort = 'nama_barang';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'asc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

function format_qty_stok_minimum($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

function badge_jenis_barang_stok_minimum(string $jenis): string
{
    if ($jenis === 'bahan_baku') {
        return '<span class="badge bg-info-subtle text-info">Bahan Baku</span>';
    }

    if ($jenis === 'produk') {
        return '<span class="badge bg-success-subtle text-success">Produk</span>';
    }

    return '<span class="badge bg-secondary">-</span>';
}

function status_stok_minimum_row($qty, $stok_minimum): array
{
    $qty = (float) $qty;
    $stok_minimum = (float) $stok_minimum;

    if ($stok_minimum <= 0) {
        return ['secondary', 'Tidak Diatur'];
    }

    if ($qty <= 0) {
        return ['danger', 'Kosong'];
    }

    if ($qty <= $stok_minimum) {
        return ['warning text-dark', 'Minimum'];
    }

    return ['success', 'Aman'];
}

$gudang_options = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$barang_options = [];

if ($jenis_barang === 'bahan_baku') {
    $barang_options = BahanBakuORM::query()
        ->from('tb_bahan_baku as b')
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
        ->where('b.id_entitas', $id_entitas)
        ->where('b.status_aktif', 1)
        ->select([
            'b.id_bahan_baku as id_barang',
            'b.kode_bahan_baku as kode_barang',
            'b.nama_bahan_baku as nama_barang',
            's.nama_satuan',
        ])
        ->orderBy('b.nama_bahan_baku', 'asc')
        ->get();
}

if ($jenis_barang === 'produk') {
    $barang_options = ProdukORM::query()
        ->from('tb_produk as p')
        ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
        ->where('p.id_entitas', $id_entitas)
        ->where('p.status_produk', 1)
        ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
        ->select([
            'p.id_produk as id_barang',
            'p.kode_produk as kode_barang',
            'p.nama_produk as nama_barang',
            's.nama_satuan',
        ])
        ->orderBy('p.nama_produk', 'asc')
        ->get();
}

$queryBahan = SaldoStokORM::query()
    ->from('tb_saldo_stok as ss')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'ss.id_referensi_barang')
            ->where('ss.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'b.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
    ->where('ss.id_entitas', $id_entitas)
    ->where('ss.jenis_barang', 'bahan_baku')
    ->where('b.status_aktif', 1)
    ->selectRaw("
        ss.id_saldo_stok,
        ss.id_entitas,
        ss.jenis_barang,
        ss.id_referensi_barang,
        ss.id_gudang,
        ss.qty_saldo,
        ss.nilai_saldo,
        ss.hpp_rata_rata,
        ss.tanggal_update,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
        b.stok_minimum,
        st.nama_satuan,
        g.kode_gudang,
        g.nama_gudang
    ");

$queryProduk = SaldoStokORM::query()
    ->from('tb_saldo_stok as ss')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'ss.id_referensi_barang')
            ->where('ss.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ss.id_gudang')
    ->where('ss.id_entitas', $id_entitas)
    ->where('ss.jenis_barang', 'produk')
    ->where('p.status_produk', 1)
    ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
    ->selectRaw("
        ss.id_saldo_stok,
        ss.id_entitas,
        ss.jenis_barang,
        ss.id_referensi_barang,
        ss.id_gudang,
        ss.qty_saldo,
        ss.nilai_saldo,
        ss.hpp_rata_rata,
        ss.tanggal_update,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
        p.stok_minimum,
        st.nama_satuan,
        g.kode_gudang,
        g.nama_gudang
    ");

if ($jenis_barang === 'bahan_baku') {
    $query = $queryBahan;
} elseif ($jenis_barang === 'produk') {
    $query = $queryProduk;
} else {
    $query = $queryBahan->unionAll($queryProduk);
}

$baseQuery = SaldoStokORM::query()->fromSub($query, 'x');

if ($id_referensi_barang > 0 && $jenis_barang !== 'semua') {
    $baseQuery->where('id_referensi_barang', $id_referensi_barang);
}

if ($id_gudang > 0) {
    $baseQuery->where('id_gudang', $id_gudang);
}

if ($q !== '') {
    $baseQuery->where(function ($sub) use ($q) {
        $sub->where('kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('nama_gudang', 'like', '%' . $q . '%')
            ->orWhere('kode_barang', 'like', '%' . $q . '%')
            ->orWhere('nama_barang', 'like', '%' . $q . '%');
    });
}

if ($status_stok === 'kosong') {
    $baseQuery->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= 0')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0');
} elseif ($status_stok === 'minimum') {
    $baseQuery->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) > 0')
        ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= CAST(stok_minimum AS DECIMAL(18,3))')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0');
} elseif ($status_stok === 'aman') {
    $baseQuery->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) > CAST(stok_minimum AS DECIMAL(18,3))')
        ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0');
} else {
    $baseQuery->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0');
}

$totalRows = (clone $baseQuery)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_stok = (clone $baseQuery)
    ->orderBy($sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

$total_nilai = (clone $baseQuery)->sum('nilai_saldo');

$jumlah_kosong = (clone $baseQuery)
    ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= 0')
    ->count();

$jumlah_minimum = (clone $baseQuery)
    ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) > 0')
    ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= CAST(stok_minimum AS DECIMAL(18,3))')
    ->count();

function build_sort_url_stok_minimum(string $column): string
{
    $params = [
        'menu'                => 'persediaan/stok-minimum',
        'q'                   => trim((string) ($_GET['q'] ?? '')),
        'jenis_barang'        => trim((string) ($_GET['jenis_barang'] ?? 'semua')),
        'id_referensi_barang' => (int) ($_GET['id_referensi_barang'] ?? 0),
        'id_gudang'           => (int) ($_GET['id_gudang'] ?? 0),
        'status_stok'         => trim((string) ($_GET['status_stok'] ?? 'semua')),
        'sort'                => $column,
        'order'               => 'asc',
        'per_page'            => (int) ($_GET['per_page'] ?? 10),
        'hal'                 => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'nama_barang'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_stok_minimum(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'nama_barang'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'asc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_stok_minimum(int $targetPage): string
{
    $params = [
        'menu'                => 'persediaan/stok-minimum',
        'q'                   => trim((string) ($_GET['q'] ?? '')),
        'jenis_barang'        => trim((string) ($_GET['jenis_barang'] ?? 'semua')),
        'id_referensi_barang' => (int) ($_GET['id_referensi_barang'] ?? 0),
        'id_gudang'           => (int) ($_GET['id_gudang'] ?? 0),
        'status_stok'         => trim((string) ($_GET['status_stok'] ?? 'semua')),
        'sort'                => trim((string) ($_GET['sort'] ?? 'nama_barang')),
        'order'               => strtolower(trim((string) ($_GET['order'] ?? 'asc'))),
        'per_page'            => (int) ($_GET['per_page'] ?? 10),
        'hal'                 => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

$cetakQuery = $_GET;
unset($cetakQuery['menu']);

$urlCetak = admin_url(
    'index.php?menu=persediaan/stok-minimum/cetak' .
    (count($cetakQuery) ? '&' . http_build_query($cetakQuery) : '')
);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Stok Minimum</h1>
    <p class="page-subtitle">Pantau item persediaan yang kosong, mencapai minimum, atau aman.</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Total Item</div>
                        <div class="h4 mb-0"><?= number_format((int) $totalRows, 0, '.', ',') ?></div>
                    </div>
                    <div class="text-primary fs-4">
                        <i class="bi bi-box-seam"></i>
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
                        <div class="text-muted small">Stok Kosong</div>
                        <div class="h4 mb-0"><?= number_format((int) $jumlah_kosong, 0, '.', ',') ?></div>
                    </div>
                    <div class="text-danger fs-4">
                        <i class="bi bi-x-circle"></i>
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
                        <div class="text-muted small">Di Minimum</div>
                        <div class="h4 mb-0"><?= number_format((int) $jumlah_minimum, 0, '.', ',') ?></div>
                    </div>
                    <div class="text-warning fs-4">
                        <i class="bi bi-exclamation-triangle"></i>
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
                        <div class="text-muted small">Total Nilai</div>
                        <div class="h4 mb-0">Rp <?= number_format((float) $total_nilai, 2, '.', ',') ?></div>
                    </div>
                    <div class="text-success fs-4">
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
                <h2 class="h5 mb-1">Daftar Stok Minimum</h2>
                <div class="text-muted small">Qty tampil per baris sesuai satuan barang dan tidak dijumlahkan lintas satuan.</div>
            </div>

            <a href="<?= esc($urlCetak) ?>" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>

        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="menu" value="persediaan/stok-minimum">
            <input type="hidden" name="sort" value="<?= esc($sort) ?>">
            <input type="hidden" name="order" value="<?= esc($order) ?>">

            <div class="col-md-2">
                <label class="form-label fw-semibold">Jenis Barang</label>
                <select name="jenis_barang" class="form-select" onchange="this.form.id_referensi_barang.value='0'; this.form.submit()">
                    <option value="semua" <?= $jenis_barang === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="bahan_baku" <?= $jenis_barang === 'bahan_baku' ? 'selected' : '' ?>>Bahan Baku</option>
                    <option value="produk" <?= $jenis_barang === 'produk' ? 'selected' : '' ?>>Produk</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Barang</label>
                <select name="id_referensi_barang" class="form-select" <?= $jenis_barang === 'semua' ? 'disabled' : '' ?>>
                    <option value="0">
                        <?= $jenis_barang === 'semua' ? 'Pilih jenis barang dulu' : 'Semua Barang' ?>
                    </option>

                    <?php foreach ($barang_options as $barang): ?>
                        <option value="<?= (int) $barang->id_barang ?>" <?= $id_referensi_barang === (int) $barang->id_barang ? 'selected' : '' ?>>
                            <?= esc(($barang->kode_barang ?? '-') . ' - ' . ($barang->nama_barang ?? '-') . ' - ' . ($barang->nama_satuan ?? '-')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($jenis_barang === 'semua'): ?>
                    <input type="hidden" name="id_referensi_barang" value="0">
                <?php endif; ?>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Gudang</label>
                <select name="id_gudang" class="form-select">
                    <option value="0">Semua Gudang</option>
                    <?php foreach ($gudang_options as $g): ?>
                        <option value="<?= (int) $g->id_gudang ?>" <?= $id_gudang === (int) $g->id_gudang ? 'selected' : '' ?>>
                            <?= esc($g->nama_gudang ?? '-') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Status Stok</label>
                <select name="status_stok" class="form-select">
                    <option value="semua" <?= $status_stok === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="kosong" <?= $status_stok === 'kosong' ? 'selected' : '' ?>>Kosong</option>
                    <option value="minimum" <?= $status_stok === 'minimum' ? 'selected' : '' ?>>Minimum</option>
                    <option value="aman" <?= $status_stok === 'aman' ? 'selected' : '' ?>>Aman</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Cari</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Kode/nama/gudang">
            </div>

            <div class="col-md-1">
                <label class="form-label fw-semibold">Limit</label>
                <select name="per_page" class="form-select">
                    <?php foreach ($allowedPerPage as $limit): ?>
                        <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>>
                            <?= $limit ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i>Filter
                </button>

                <?php if (
                    $q !== '' ||
                    $jenis_barang !== 'semua' ||
                    $id_referensi_barang > 0 ||
                    $id_gudang > 0 ||
                    $status_stok !== 'semua' ||
                    $perPage !== 10
                ): ?>
                    <a href="<?= esc(admin_page_url('persediaan/stok-minimum')) ?>" class="btn btn-outline-secondary">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive border rounded">
            <div style="max-height:460px; overflow-y:auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="55" class="text-center">No</th>
                            <th width="125">
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_minimum('jenis_barang')) ?>">
                                    Jenis<?= sort_icon_stok_minimum('jenis_barang') ?>
                                </a>
                            </th>
                            <th width="130">
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_minimum('kode_barang')) ?>">
                                    Kode<?= sort_icon_stok_minimum('kode_barang') ?>
                                </a>
                            </th>
                            <th>
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_minimum('nama_barang')) ?>">
                                    Barang<?= sort_icon_stok_minimum('nama_barang') ?>
                                </a>
                            </th>
                            <th width="170">
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_minimum('nama_gudang')) ?>">
                                    Gudang<?= sort_icon_stok_minimum('nama_gudang') ?>
                                </a>
                            </th>
                            <th width="90">Satuan</th>
                            <th width="110" class="text-end">
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_minimum('qty_saldo')) ?>">
                                    Qty<?= sort_icon_stok_minimum('qty_saldo') ?>
                                </a>
                            </th>
                            <th width="110" class="text-end">
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_stok_minimum('stok_minimum')) ?>">
                                    Min<?= sort_icon_stok_minimum('stok_minimum') ?>
                                </a>
                            </th>
                            <th width="145" class="text-end">Nilai</th>
                            <th width="150" class="text-center">Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($data_stok->count() === 0): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Tidak ada data stok minimum.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data_stok as $i => $row): ?>
                                <?php [$statusColor, $statusText] = status_stok_minimum_row($row->qty_saldo ?? 0, $row->stok_minimum ?? 0); ?>
                                <tr>
                                    <td class="text-center"><?= $offset + $i + 1 ?></td>
                                    <td><?= badge_jenis_barang_stok_minimum((string) ($row->jenis_barang ?? '')) ?></td>
                                    <td class="fw-semibold"><?= esc($row->kode_barang ?? '-') ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($row->nama_barang ?? '-') ?></div>
                                        <div class="text-muted small">
                                            Update:
                                            <?= esc(!empty($row->tanggal_update) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_update)) : '-') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= esc($row->nama_gudang ?? '-') ?></div>
                                        <div class="text-muted small"><?= esc($row->kode_gudang ?? '-') ?></div>
                                    </td>
                                    <td><?= esc($row->nama_satuan ?? '-') ?></td>
                                    <td class="text-end fw-semibold"><?= esc(format_qty_stok_minimum($row->qty_saldo ?? 0)) ?></td>
                                    <td class="text-end"><?= esc(format_qty_stok_minimum($row->stok_minimum ?? 0)) ?></td>
                                    <td class="text-end">Rp <?= number_format((float) ($row->nilai_saldo ?? 0), 2, '.', ',') ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-<?= esc($statusColor) ?>"><?= esc($statusText) ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="8" class="text-end">Total Nilai Halaman Ini</th>
                            <th class="text-end">Rp <?= number_format((float) $data_stok->sum('nilai_saldo'), 2, '.', ',') ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
            <div class="text-muted small">
                Halaman <?= (int) $page ?> dari <?= (int) $totalPages ?>.
                Menampilkan <?= number_format($data_stok->count(), 0, '.', ',') ?>
                dari <?= number_format($totalRows, 0, '.', ',') ?> data.
            </div>

            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_stok_minimum(max(1, $page - 1))) ?>">
                            Sebelumnya
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_stok_minimum($p)) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_stok_minimum(min($totalPages, $page + 1))) ?>">
                            Berikutnya
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>