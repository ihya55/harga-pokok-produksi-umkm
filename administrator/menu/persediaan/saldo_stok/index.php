<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);

$q = trim((string) ($_GET['q'] ?? ''));
$jenis_barang = trim((string) ($_GET['jenis_barang'] ?? 'semua'));
$id_gudang = (int) ($_GET['id_gudang'] ?? 0);
$perPage = (int) ($_GET['per_page'] ?? 10);
$page = (int) ($_GET['hal'] ?? 1);

$allowedJenis = ['semua', 'bahan_baku', 'produk'];
$allowedPerPage = [10, 25, 50, 100];

if (!in_array($jenis_barang, $allowedJenis, true)) {
    $jenis_barang = 'semua';
}

if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 10;
}

if ($page < 1) {
    $page = 1;
}

function format_qty_saldo_stok($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

function saldo_stok_badge_jenis(string $jenis): string
{
    if ($jenis === 'bahan_baku') {
        return '<span class="badge bg-info-subtle text-info">Bahan Baku</span>';
    }

    if ($jenis === 'produk') {
        return '<span class="badge bg-success-subtle text-success">Produk</span>';
    }

    return '<span class="badge bg-secondary">-</span>';
}

function saldo_stok_status_minimum($qty, $stok_minimum): array
{
    $qty = (float) $qty;
    $stok_minimum = (float) $stok_minimum;

    if ($stok_minimum <= 0) {
        return ['secondary', 'Tidak diatur'];
    }

    if ($qty <= 0) {
        return ['danger', 'Stok kosong'];
    }

    if ($qty <= $stok_minimum) {
        return ['warning text-dark', 'dibawah minimum'];
    }

    return ['success', 'Aman'];
}

$gudang_options = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->orderBy('nama_gudang', 'asc')
    ->get();

/*
|--------------------------------------------------------------------------
| Query saldo stok
|--------------------------------------------------------------------------
| Catatan:
| - tb_saldo_stok tetap dipakai sebagai daftar item/gudang.
| - qty_saldo, hpp_rata_rata, dan nilai_saldo dihitung ulang dari tb_mutasi_stok.
| - Ini membuat tampilan saldo sesuai mutasi sebenarnya.
*/

$entitasSql = (int) $id_entitas;

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
    ->selectRaw("
        ss.id_saldo_stok,
        ss.id_entitas,
        ss.jenis_barang,
        ss.id_referensi_barang,
        ss.id_gudang,

        COALESCE((
            SELECT SUM(ms.qty_masuk) - SUM(ms.qty_keluar)
            FROM tb_mutasi_stok ms
            WHERE ms.id_entitas = {$entitasSql}
              AND ms.jenis_barang = 'bahan_baku'
              AND ms.id_referensi_barang = ss.id_referensi_barang
              AND ms.id_gudang = ss.id_gudang
        ), 0) AS qty_saldo,

        COALESCE((
            SELECT
                CASE
                    WHEN SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.qty_masuk ELSE 0 END) > 0 THEN
                        SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.nilai_total ELSE 0 END)
                        / NULLIF(SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.qty_masuk ELSE 0 END), 0)
                    ELSE 0
                END
            FROM tb_mutasi_stok ms
            WHERE ms.id_entitas = {$entitasSql}
              AND ms.jenis_barang = 'bahan_baku'
              AND ms.id_referensi_barang = ss.id_referensi_barang
              AND ms.id_gudang = ss.id_gudang
        ), 0) AS hpp_rata_rata,

        (
            COALESCE((
                SELECT SUM(ms.qty_masuk) - SUM(ms.qty_keluar)
                FROM tb_mutasi_stok ms
                WHERE ms.id_entitas = {$entitasSql}
                  AND ms.jenis_barang = 'bahan_baku'
                  AND ms.id_referensi_barang = ss.id_referensi_barang
                  AND ms.id_gudang = ss.id_gudang
            ), 0)
            *
            COALESCE((
                SELECT
                    CASE
                        WHEN SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.qty_masuk ELSE 0 END) > 0 THEN
                            SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.nilai_total ELSE 0 END)
                            / NULLIF(SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.qty_masuk ELSE 0 END), 0)
                        ELSE 0
                    END
                FROM tb_mutasi_stok ms
                WHERE ms.id_entitas = {$entitasSql}
                  AND ms.jenis_barang = 'bahan_baku'
                  AND ms.id_referensi_barang = ss.id_referensi_barang
                  AND ms.id_gudang = ss.id_gudang
            ), 0)
        ) AS nilai_saldo,

        COALESCE((
            SELECT MAX(ms.tanggal_mutasi)
            FROM tb_mutasi_stok ms
            WHERE ms.id_entitas = {$entitasSql}
              AND ms.jenis_barang = 'bahan_baku'
              AND ms.id_referensi_barang = ss.id_referensi_barang
              AND ms.id_gudang = ss.id_gudang
        ), ss.tanggal_update) AS tanggal_update,

        ss.qty_saldo AS qty_saldo_tabel,
        ss.nilai_saldo AS nilai_saldo_tabel,
        ss.hpp_rata_rata AS hpp_rata_rata_tabel,

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
    ->selectRaw("
        ss.id_saldo_stok,
        ss.id_entitas,
        ss.jenis_barang,
        ss.id_referensi_barang,
        ss.id_gudang,

        COALESCE((
            SELECT SUM(ms.qty_masuk) - SUM(ms.qty_keluar)
            FROM tb_mutasi_stok ms
            WHERE ms.id_entitas = {$entitasSql}
              AND ms.jenis_barang = 'produk'
              AND ms.id_referensi_barang = ss.id_referensi_barang
              AND ms.id_gudang = ss.id_gudang
        ), 0) AS qty_saldo,

        COALESCE((
            SELECT
                CASE
                    WHEN SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.qty_masuk ELSE 0 END) > 0 THEN
                        SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.nilai_total ELSE 0 END)
                        / NULLIF(SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.qty_masuk ELSE 0 END), 0)
                    ELSE 0
                END
            FROM tb_mutasi_stok ms
            WHERE ms.id_entitas = {$entitasSql}
              AND ms.jenis_barang = 'produk'
              AND ms.id_referensi_barang = ss.id_referensi_barang
              AND ms.id_gudang = ss.id_gudang
        ), 0) AS hpp_rata_rata,

        (
            COALESCE((
                SELECT SUM(ms.qty_masuk) - SUM(ms.qty_keluar)
                FROM tb_mutasi_stok ms
                WHERE ms.id_entitas = {$entitasSql}
                  AND ms.jenis_barang = 'produk'
                  AND ms.id_referensi_barang = ss.id_referensi_barang
                  AND ms.id_gudang = ss.id_gudang
            ), 0)
            *
            COALESCE((
                SELECT
                    CASE
                        WHEN SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.qty_masuk ELSE 0 END) > 0 THEN
                            SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.nilai_total ELSE 0 END)
                            / NULLIF(SUM(CASE WHEN ms.qty_masuk > 0 THEN ms.qty_masuk ELSE 0 END), 0)
                        ELSE 0
                    END
                FROM tb_mutasi_stok ms
                WHERE ms.id_entitas = {$entitasSql}
                  AND ms.jenis_barang = 'produk'
                  AND ms.id_referensi_barang = ss.id_referensi_barang
                  AND ms.id_gudang = ss.id_gudang
            ), 0)
        ) AS nilai_saldo,

        COALESCE((
            SELECT MAX(ms.tanggal_mutasi)
            FROM tb_mutasi_stok ms
            WHERE ms.id_entitas = {$entitasSql}
              AND ms.jenis_barang = 'produk'
              AND ms.id_referensi_barang = ss.id_referensi_barang
              AND ms.id_gudang = ss.id_gudang
        ), ss.tanggal_update) AS tanggal_update,

        ss.qty_saldo AS qty_saldo_tabel,
        ss.nilai_saldo AS nilai_saldo_tabel,
        ss.hpp_rata_rata AS hpp_rata_rata_tabel,

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

if ($q !== '') {
    $baseQuery->where(function ($sub) use ($q) {
        $sub->where('kode_barang', 'like', '%' . $q . '%')
            ->orWhere('nama_barang', 'like', '%' . $q . '%')
            ->orWhere('kode_gudang', 'like', '%' . $q . '%')
            ->orWhere('nama_gudang', 'like', '%' . $q . '%');
    });
}

if ($id_gudang > 0) {
    $baseQuery->where('id_gudang', $id_gudang);
}

$totalRows = (clone $baseQuery)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$data_saldo = (clone $baseQuery)
    ->orderBy('jenis_barang', 'asc')
    ->orderBy('nama_barang', 'asc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$total_nilai = (clone $baseQuery)->sum('nilai_saldo');

$stok_minimum_count = (clone $baseQuery)
    ->whereRaw('CAST(qty_saldo AS DECIMAL(18,3)) <= CAST(stok_minimum AS DECIMAL(18,3))')
    ->whereRaw('CAST(stok_minimum AS DECIMAL(18,3)) > 0')
    ->count();

function saldo_stok_url(array $params = []): string
{
    $query = array_merge($_GET, $params);
    $query['menu'] = 'persediaan/saldo-stok';

    return admin_url('index.php?' . http_build_query($query));
}

$cetakQuery = $_GET;
unset($cetakQuery['menu']);

$urlCetak = admin_url(
    'index.php?menu=persediaan/saldo-stok/cetak' .
    (count($cetakQuery) ? '&' . http_build_query($cetakQuery) : '')
);
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title"><?= esc($page_title ?? 'Saldo Stok') ?></h1>
            <p class="page-subtitle">Pantau saldo persediaan bahan baku dan produk per gudang.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= esc(admin_page_url('persediaan/saldo-stok/saldo-awal')) ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Saldo Awal Stok
            </a>

            <a href="<?= esc($urlCetak) ?>" target="_blank" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
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

    <div class="col-md-4">
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

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="text-muted small">Stok Minimum</div>
                        <div class="h4 mb-0"><?= number_format((int) $stok_minimum_count, 0, '.', ',') ?></div>
                    </div>
                    <div class="text-danger fs-4">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <input type="hidden" name="menu" value="persediaan/saldo-stok">

            <div class="col-md-4">
                <label class="form-label fw-semibold">Cari</label>
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    value="<?= esc($q) ?>"
                    placeholder="Kode barang, nama barang, atau gudang">
            </div>

            <div class="col-md-2">
                <label class="form-label fw-semibold">Jenis Barang</label>
                <select name="jenis_barang" class="form-select">
                    <option value="semua" <?= $jenis_barang === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="bahan_baku" <?= $jenis_barang === 'bahan_baku' ? 'selected' : '' ?>>Bahan Baku</option>
                    <option value="produk" <?= $jenis_barang === 'produk' ? 'selected' : '' ?>>Produk</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold">Gudang</label>
                <select name="id_gudang" class="form-select">
                    <option value="0">Semua Gudang</option>
                    <?php foreach ($gudang_options as $g): ?>
                        <option value="<?= (int) $g->id_gudang ?>" <?= $id_gudang === (int) $g->id_gudang ? 'selected' : '' ?>>
                            <?= esc(($g->kode_gudang ?? '-') . ' - ' . ($g->nama_gudang ?? '-')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1">
                <label class="form-label fw-semibold">Limit</label>
                <select name="per_page" class="form-select">
                    <?php foreach ($allowedPerPage as $pp): ?>
                        <option value="<?= $pp ?>" <?= $perPage === $pp ? 'selected' : '' ?>>
                            <?= $pp ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>Filter
                </button>

                <a href="<?= esc(admin_page_url('persediaan/saldo-stok')) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Daftar Saldo Stok</h2>
                <div class="text-muted small">Qty tampil per baris sesuai satuan barang. Total qty tidak dijumlahkan karena satuan barang bisa berbeda.</div>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="130">Jenis</th>
                        <th width="130">Kode</th>
                        <th>Nama Barang</th>
                        <th width="190">Gudang</th>
                        <th width="120">Satuan</th>
                        <th width="130" class="text-end">Qty</th>
                        <th width="170" class="text-end">HPP Rata-rata</th>
                        <th width="180" class="text-end">Nilai Saldo</th>
                        <th width="150" class="text-center">Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($data_saldo->count() === 0): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                Belum ada data saldo stok.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data_saldo as $i => $row): ?>
                            <?php
                            [$status_color, $status_text] = saldo_stok_status_minimum($row->qty_saldo ?? 0, $row->stok_minimum ?? 0);
                            ?>
                            <tr>
                                <td class="text-center"><?= $offset + $i + 1 ?></td>

                                <td><?= saldo_stok_badge_jenis((string) ($row->jenis_barang ?? '')) ?></td>

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

                                <td class="text-end fw-semibold">
                                    <?= esc(format_qty_saldo_stok($row->qty_saldo ?? 0)) ?>
                                </td>

                                <td class="text-end">
                                    Rp <?= number_format((float) ($row->hpp_rata_rata ?? 0), 2, '.', ',') ?>
                                </td>

                                <td class="text-end fw-semibold">
                                    Rp <?= number_format((float) ($row->nilai_saldo ?? 0), 2, '.', ',') ?>
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-<?= esc($status_color) ?>">
                                        <?= esc($status_text) ?>
                                    </span>

                                    <?php if ((float) ($row->stok_minimum ?? 0) > 0): ?>
                                        <div class="text-muted small mt-1">
                                            Min: <?= esc(format_qty_saldo_stok($row->stok_minimum ?? 0)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="8" class="text-end">Total Nilai Halaman Ini</th>
                        <th class="text-end">
                            Rp <?= number_format((float) $data_saldo->sum('nilai_saldo'), 2, '.', ',') ?>
                        </th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
            <div class="text-muted small">
                Menampilkan <?= number_format($data_saldo->count(), 0, '.', ',') ?>
                dari <?= number_format($totalRows, 0, '.', ',') ?> data.
            </div>

            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(saldo_stok_url(['hal' => $page - 1])) ?>">Prev</a>
                    </li>

                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(saldo_stok_url(['hal' => $p])) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(saldo_stok_url(['hal' => $page + 1])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>