<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas = keu_id_entitas();

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
$id_coa = (int) ($_GET['id_coa'] ?? 0);
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = (int) ($_GET['per_page'] ?? 25);
$page = max(1, (int) ($_GET['hal'] ?? 1));

if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 25;
}

$akun_options = Capsule::table('tb_coa')
    ->where('id_entitas', $id_entitas)
    ->where('boleh_transaksi', 1)
    ->where('status_aktif', 1)
    ->orderBy('kode_coa', 'asc')
    ->get();

$query = Capsule::table('vw_buku_besar')
    ->where('id_entitas', $id_entitas)
    ->whereBetween('tanggal_jurnal', [$tanggal_awal, $tanggal_akhir]);

if ($id_coa > 0) {
    $query->where('id_coa', $id_coa);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('no_jurnal', 'like', '%' . $q . '%')
            ->orWhere('kode_jenis_transaksi', 'like', '%' . $q . '%')
            ->orWhere('no_sumber', 'like', '%' . $q . '%')
            ->orWhere('keterangan_baris', 'like', '%' . $q . '%')
            ->orWhere('kode_coa', 'like', '%' . $q . '%')
            ->orWhere('nama_coa', 'like', '%' . $q . '%');
    });
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$total_debit = (float) (clone $query)->sum('debit');
$total_kredit = (float) (clone $query)->sum('kredit');

$rows = $query
    ->orderBy('tanggal_jurnal', 'asc')
    ->orderBy('id_jurnal', 'asc')
    ->orderBy('urutan', 'asc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$params_cetak = $_GET;
$params_cetak['menu'] = 'keuangan/buku-besar/cetak';
$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Buku Besar</h1>
            <p class="page-subtitle">Mutasi debit dan kredit per akun dari jurnal posted.</p>
        </div>

        <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-printer me-1"></i>Cetak
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Debit</div>
                <div class="h4 mb-0"><?= keu_uang($total_debit) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Kredit</div>
                <div class="h4 mb-0"><?= keu_uang($total_kredit) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Selisih</div>
                <div class="h4 mb-0"><?= keu_uang($total_debit - $total_kredit) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="menu" value="keuangan/buku-besar">

            <div class="col-md-2">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?= esc($tanggal_awal) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?= esc($tanggal_akhir) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Akun</label>
                <select name="id_coa" class="form-select">
                    <option value="0">Semua Akun</option>
                    <?php foreach ($akun_options as $a): ?>
                        <option value="<?= (int) $a->id_coa ?>" <?= $id_coa === (int) $a->id_coa ? 'selected' : '' ?>>
                            <?= esc($a->kode_coa . ' - ' . $a->nama_coa) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Jurnal/sumber...">
            </div>

            <div class="col-md-1">
                <label class="form-label">Baris</label>
                <select name="per_page" class="form-select">
                    <?php foreach ([10, 25, 50, 100] as $limit): ?>
                        <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
            </div>
        </form>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60" class="text-center">No</th>
                        <th>Tanggal</th>
                        <th>No Jurnal</th>
                        <th>Akun</th>
                        <th>Sumber</th>
                        <th>Keterangan</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($rows->count() === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Data buku besar tidak ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(keu_tanggal($row->tanggal_jurnal)) ?></td>
                                <td>
                                    <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row->id_jurnal) ?>" class="text-decoration-none fw-semibold">
                                        <?= esc((string) $row->no_jurnal) ?>
                                    </a>
                                    <div class="text-muted small"><?= esc((string) $row->kode_jenis_transaksi) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) $row->kode_coa) ?></div>
                                    <div class="text-muted small"><?= esc((string) $row->nama_coa) ?></div>
                                </td>
                                <td><?= keu_sumber_link($row->tabel_sumber ?? null, $row->id_sumber ?? null, $row->no_sumber ?? null) ?></td>
                                <td><?= esc((string) ($row->keterangan_baris ?? '-')) ?></td>
                                <td class="text-end"><?= keu_uang($row->debit ?? 0) ?></td>
                                <td class="text-end"><?= keu_uang($row->kredit ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="6" class="text-end">Total Filter</th>
                        <th class="text-end"><?= keu_uang($total_debit) ?></th>
                        <th class="text-end"><?= keu_uang($total_kredit) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/buku-besar', max(1, $page - 1))) ?>">Prev</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/buku-besar', $i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/buku-besar', min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>