<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$jenis_barang = trim((string) ($_GET['jenis_barang'] ?? 'semua'));
$id_referensi_barang = (int) ($_GET['id_referensi_barang'] ?? 0);
$jenis_mutasi = trim((string) ($_GET['jenis_mutasi'] ?? 'semua'));
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);
$tanggal_awal = trim((string) ($_GET['tanggal_awal'] ?? ''));
$tanggal_akhir = trim((string) ($_GET['tanggal_akhir'] ?? ''));
$sort = trim((string) ($_GET['sort'] ?? 'tanggal_mutasi'));
$order = strtolower(trim((string) ($_GET['order'] ?? 'desc')));
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedJenisBarang = ['semua', 'bahan_baku', 'produk'];
$allowedSort = [
    'tanggal_mutasi',
    'jenis_barang',
    'jenis_mutasi',
    'kode_barang',
    'nama_barang',
    'qty_masuk',
    'qty_keluar',
    'nilai_total',
    'no_sumber',
];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($jenis_barang, $allowedJenisBarang, true)) $jenis_barang = 'semua';
if ($jenis_barang === 'semua') $id_referensi_barang = 0;
if (!in_array($sort, $allowedSort, true)) $sort = 'tanggal_mutasi';
if (!in_array($order, ['asc', 'desc'], true)) $order = 'desc';
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;
if ($page < 1) $page = 1;

function format_qty_mutasi_stok($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

function label_jenis_barang_mutasi_stok(string $jenis): string
{
    if ($jenis === 'bahan_baku') {
        return '<span class="badge bg-info-subtle text-info">Bahan Baku</span>';
    }

    if ($jenis === 'produk') {
        return '<span class="badge bg-success-subtle text-success">Produk</span>';
    }

    return '<span class="badge bg-secondary">-</span>';
}

function label_jenis_mutasi_stok(string $jenis): string
{
    $jenis = strtolower($jenis);
    $label = ucwords(str_replace('_', ' ', $jenis));
    $class = 'bg-secondary';

    if (in_array($jenis, ['masuk', 'penerimaan_pembelian', 'hasil_produksi', 'saldo_awal'], true)) {
        $class = 'bg-success';
    }

    if (in_array($jenis, ['keluar', 'pengambilan_bahan', 'penyerahan_penjualan'], true)) {
        $class = 'bg-danger';
    }

    if (in_array($jenis, ['opname_plus', 'opname_masuk'], true)) {
        $class = 'bg-primary';
    }

    if (in_array($jenis, ['opname_minus', 'opname_keluar'], true)) {
        $class = 'bg-warning text-dark';
    }

    return '<span class="badge ' . $class . '">' . esc($label) . '</span>';
}

$gudang_options = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

$jenis_mutasi_options = MutasiStokORM::query()
    ->where('id_entitas', $id_entitas)
    ->select('jenis_mutasi')
    ->distinct()
    ->orderBy('jenis_mutasi', 'asc')
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
        ->select([
            'p.id_produk as id_barang',
            'p.kode_produk as kode_barang',
            'p.nama_produk as nama_barang',
            's.nama_satuan',
        ])
        ->orderBy('p.nama_produk', 'asc')
        ->get();
}

$queryBahan = MutasiStokORM::query()
    ->from('tb_mutasi_stok as ms')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'ms.id_referensi_barang')
            ->where('ms.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'b.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ms.id_gudang')
    ->where('ms.id_entitas', $id_entitas)
    ->where('ms.jenis_barang', 'bahan_baku')
    ->selectRaw("
        ms.id_mutasi_stok,
        ms.id_entitas,
        ms.tanggal_mutasi,
        ms.jenis_barang,
        ms.id_referensi_barang,
        ms.id_gudang,
        ms.jenis_mutasi,
        ms.qty_masuk,
        ms.qty_keluar,
        ms.harga_satuan,
        ms.nilai_total,
        ms.tabel_sumber,
        ms.id_sumber,
        ms.no_sumber,
        ms.keterangan,
        ms.tanggal_dibuat,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
        st.nama_satuan,
        g.kode_gudang,
        g.nama_gudang
    ");

$queryProduk = MutasiStokORM::query()
    ->from('tb_mutasi_stok as ms')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'ms.id_referensi_barang')
            ->where('ms.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ms.id_gudang')
    ->where('ms.id_entitas', $id_entitas)
    ->where('ms.jenis_barang', 'produk')
    ->selectRaw("
        ms.id_mutasi_stok,
        ms.id_entitas,
        ms.tanggal_mutasi,
        ms.jenis_barang,
        ms.id_referensi_barang,
        ms.id_gudang,
        ms.jenis_mutasi,
        ms.qty_masuk,
        ms.qty_keluar,
        ms.harga_satuan,
        ms.nilai_total,
        ms.tabel_sumber,
        ms.id_sumber,
        ms.no_sumber,
        ms.keterangan,
        ms.tanggal_dibuat,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
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

$baseQuery = MutasiStokORM::query()->fromSub($query, 'x');

if ($id_referensi_barang > 0 && $jenis_barang !== 'semua') {
    $baseQuery->where('id_referensi_barang', $id_referensi_barang);
}

if ($q !== '') {
    $baseQuery->where(function ($sub) use ($q) {
        $sub->where('kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('nama_gudang', 'like', '%' . $q . '%')
            ->orWhere('jenis_mutasi', 'like', '%' . $q . '%')
            ->orWhere('no_sumber', 'like', '%' . $q . '%')
            ->orWhere('keterangan', 'like', '%' . $q . '%')
            ->orWhere('tabel_sumber', 'like', '%' . $q . '%');
    });
}

if ($jenis_mutasi !== 'semua') {
    $baseQuery->where('jenis_mutasi', $jenis_mutasi);
}

if ($id_gudang > 0) {
    $baseQuery->where('id_gudang', $id_gudang);
}

if ($tanggal_awal !== '') {
    $baseQuery->whereDate('tanggal_mutasi', '>=', $tanggal_awal);
}

if ($tanggal_akhir !== '') {
    $baseQuery->whereDate('tanggal_mutasi', '<=', $tanggal_akhir);
}

$totalRows = (clone $baseQuery)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

$data_mutasi = (clone $baseQuery)
    ->orderBy($sort, $order)
    ->skip($offset)
    ->take($perPage)
    ->get();

$total_nilai = (clone $baseQuery)->sum('nilai_total');

function build_sort_url_mutasi_stok(string $column): string
{
    $params = [
        'menu'                => 'persediaan/mutasi-stok',
        'q'                   => trim((string) ($_GET['q'] ?? '')),
        'jenis_barang'        => trim((string) ($_GET['jenis_barang'] ?? 'semua')),
        'id_referensi_barang' => (int) ($_GET['id_referensi_barang'] ?? 0),
        'jenis_mutasi'        => trim((string) ($_GET['jenis_mutasi'] ?? 'semua')),
        'id_gudang'           => (int) ($_GET['id_gudang'] ?? 0),
        'tanggal_awal'        => trim((string) ($_GET['tanggal_awal'] ?? '')),
        'tanggal_akhir'       => trim((string) ($_GET['tanggal_akhir'] ?? '')),
        'sort'                => $column,
        'order'               => 'asc',
        'per_page'            => (int) ($_GET['per_page'] ?? 10),
        'hal'                 => 1,
    ];

    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_mutasi'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort === $column && $currentOrder === 'asc') {
        $params['order'] = 'desc';
    }

    return admin_url('index.php?' . http_build_query($params));
}

function sort_icon_mutasi_stok(string $column): string
{
    $currentSort = trim((string) ($_GET['sort'] ?? 'tanggal_mutasi'));
    $currentOrder = strtolower(trim((string) ($_GET['order'] ?? 'desc')));

    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up ms-1 text-muted"></i>';
    }

    return $currentOrder === 'asc'
        ? '<i class="bi bi-sort-down ms-1"></i>'
        : '<i class="bi bi-sort-up ms-1"></i>';
}

function build_page_url_mutasi_stok(int $targetPage): string
{
    $params = [
        'menu'                => 'persediaan/mutasi-stok',
        'q'                   => trim((string) ($_GET['q'] ?? '')),
        'jenis_barang'        => trim((string) ($_GET['jenis_barang'] ?? 'semua')),
        'id_referensi_barang' => (int) ($_GET['id_referensi_barang'] ?? 0),
        'jenis_mutasi'        => trim((string) ($_GET['jenis_mutasi'] ?? 'semua')),
        'id_gudang'           => (int) ($_GET['id_gudang'] ?? 0),
        'tanggal_awal'        => trim((string) ($_GET['tanggal_awal'] ?? '')),
        'tanggal_akhir'       => trim((string) ($_GET['tanggal_akhir'] ?? '')),
        'sort'                => trim((string) ($_GET['sort'] ?? 'tanggal_mutasi')),
        'order'               => strtolower(trim((string) ($_GET['order'] ?? 'desc'))),
        'per_page'            => (int) ($_GET['per_page'] ?? 10),
        'hal'                 => $targetPage,
    ];

    return admin_url('index.php?' . http_build_query($params));
}

$current_url = admin_url('index.php?' . http_build_query($_GET));
$back_url = urlencode($current_url);

$cetakQuery = $_GET;
unset($cetakQuery['menu']);

$urlCetak = admin_url(
    'index.php?menu=persediaan/mutasi-stok/cetak' .
    (count($cetakQuery) ? '&' . http_build_query($cetakQuery) : '')
);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Mutasi Stok</h1>
    <p class="page-subtitle">Riwayat masuk dan keluar persediaan berdasarkan transaksi sumber</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Total Mutasi</div>
                        <div class="h4 mb-0"><?= number_format((int) $totalRows, 0, '.', ',') ?></div>
                    </div>
                    <div class="text-primary fs-4">
                        <i class="bi bi-arrow-left-right"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Total Nilai</div>
                        <div class="h4 mb-0">Rp <?= number_format((float) $total_nilai, 2, '.', ',') ?></div>
                    </div>
                    <div class="text-warning fs-4">
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
                <h2 class="h5 mb-1">Daftar Mutasi Stok</h2>
                <div class="text-muted small">
                    Pilih jenis barang terlebih dahulu agar daftar barang tampil sesuai kategori.
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row gap-2">
                <a href="<?= esc($urlCetak) ?>" target="_blank" class="btn btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i>Cetak
                </a>
            </div>
        </div>

        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="menu" value="persediaan/mutasi-stok">
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
                <label class="form-label fw-semibold">Jenis Mutasi</label>
                <select name="jenis_mutasi" class="form-select">
                    <option value="semua" <?= $jenis_mutasi === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <?php foreach ($jenis_mutasi_options as $jm): ?>
                        <option value="<?= esc($jm->jenis_mutasi) ?>" <?= $jenis_mutasi === (string) $jm->jenis_mutasi ? 'selected' : '' ?>>
                            <?= esc(ucwords(str_replace('_', ' ', (string) $jm->jenis_mutasi))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
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

            <div class="col-md-3">
                <label class="form-label fw-semibold">Cari Sumber / Keterangan</label>
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="No sumber, gudang, keterangan..."
                    value="<?= esc($q) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?= esc($tanggal_awal) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?= esc($tanggal_akhir) ?>">
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

            <div class="col-md-7 d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search me-1"></i>Filter
                </button>

                <?php if (
                    $q !== '' ||
                    $jenis_barang !== 'semua' ||
                    $id_referensi_barang > 0 ||
                    $jenis_mutasi !== 'semua' ||
                    $id_gudang > 0 ||
                    $tanggal_awal !== '' ||
                    $tanggal_akhir !== '' ||
                    $perPage !== 10
                ): ?>
                    <a href="<?= esc(admin_page_url('persediaan/mutasi-stok')) ?>" class="btn btn-outline-secondary">
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive border rounded">
            <div style="max-height:460px; overflow-y:auto;">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th width="55" class="text-center">No</th>
                            <th width="150">
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_mutasi_stok('tanggal_mutasi')) ?>">
                                    Tanggal<?= sort_icon_mutasi_stok('tanggal_mutasi') ?>
                                </a>
                            </th>
                            <th width="125">
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_mutasi_stok('jenis_barang')) ?>">
                                    Jenis<?= sort_icon_mutasi_stok('jenis_barang') ?>
                                </a>
                            </th>
                            <th>
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_mutasi_stok('nama_barang')) ?>">
                                    Barang<?= sort_icon_mutasi_stok('nama_barang') ?>
                                </a>
                            </th>
                            <th width="160">Gudang</th>
                            <th width="135">
                                <a class="text-decoration-none text-dark" href="<?= esc(build_sort_url_mutasi_stok('jenis_mutasi')) ?>">
                                    Mutasi<?= sort_icon_mutasi_stok('jenis_mutasi') ?>
                                </a>
                            </th>
                            <th width="105" class="text-end">Masuk</th>
                            <th width="105" class="text-end">Keluar</th>
                            <th width="130" class="text-end">Harga</th>
                            <th width="145" class="text-end">Nilai</th>
                            <th width="160">Sumber</th>
                            <th width="90" class="text-end">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($data_mutasi->count() === 0): ?>
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
                                    Belum ada data mutasi stok.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($data_mutasi as $i => $row): ?>
                                <tr>
                                    <td class="text-center"><?= $offset + $i + 1 ?></td>

                                    <td>
                                        <div class="fw-semibold">
                                            <?= esc(!empty($row->tanggal_mutasi) ? date('d/m/Y', strtotime((string) $row->tanggal_mutasi)) : '-') ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= esc(!empty($row->tanggal_mutasi) ? date('H:i', strtotime((string) $row->tanggal_mutasi)) : '-') ?>
                                        </div>
                                    </td>

                                    <td><?= label_jenis_barang_mutasi_stok((string) ($row->jenis_barang ?? '')) ?></td>

                                    <td>
                                        <div class="fw-semibold"><?= esc($row->nama_barang ?? '-') ?></div>
                                        <div class="text-muted small"><?= esc($row->kode_barang ?? '-') ?> · <?= esc($row->nama_satuan ?? '-') ?></div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold"><?= esc($row->nama_gudang ?? '-') ?></div>
                                        <div class="text-muted small"><?= esc($row->kode_gudang ?? '-') ?></div>
                                    </td>

                                    <td><?= label_jenis_mutasi_stok((string) ($row->jenis_mutasi ?? '')) ?></td>

                                    <td class="text-end text-success fw-semibold">
                                        <?= (float) ($row->qty_masuk ?? 0) > 0 ? esc(format_qty_mutasi_stok($row->qty_masuk)) : '-' ?>
                                    </td>

                                    <td class="text-end text-danger fw-semibold">
                                        <?= (float) ($row->qty_keluar ?? 0) > 0 ? esc(format_qty_mutasi_stok($row->qty_keluar)) : '-' ?>
                                    </td>

                                    <td class="text-end">
                                        Rp <?= number_format((float) ($row->harga_satuan ?? 0), 2, '.', ',') ?>
                                    </td>

                                    <td class="text-end fw-semibold">
                                        Rp <?= number_format((float) ($row->nilai_total ?? 0), 2, '.', ',') ?>
                                    </td>

                                    <td>
                                        <div class="fw-semibold"><?= esc($row->no_sumber ?? '-') ?></div>
                                        <div class="text-muted small"><?= esc($row->tabel_sumber ?? '-') ?></div>
                                    </td>

                                    <td class="text-end">
                                        <a
                                            href="<?= esc(admin_page_url('persediaan/mutasi-stok/detail') . '&id=' . (int) $row->id_mutasi_stok . '&back_url=' . $back_url) ?>"
                                            class="btn btn-outline-info btn-sm"
                                            title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <tfoot class="table-light">
                        <tr>
                            <th colspan="9" class="text-end">Total Nilai Halaman Ini</th>
                            <th class="text-end">
                                Rp <?= number_format((float) $data_mutasi->sum('nilai_total'), 2, '.', ',') ?>
                            </th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3">
            <div class="text-muted small">
                Halaman <?= (int) $page ?> dari <?= (int) $totalPages ?>.
                Menampilkan <?= number_format($data_mutasi->count(), 0, '.', ',') ?>
                dari <?= number_format($totalRows, 0, '.', ',') ?> data.
            </div>

            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_mutasi_stok(max(1, $page - 1))) ?>">
                            Sebelumnya
                        </a>
                    </li>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    ?>

                    <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(build_page_url_mutasi_stok($p)) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(build_page_url_mutasi_stok(min($totalPages, $page + 1))) ?>">
                            Berikutnya
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>