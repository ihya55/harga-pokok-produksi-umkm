<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas = keu_id_entitas();

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = (int) ($_GET['per_page'] ?? 25);
$page = max(1, (int) ($_GET['hal'] ?? 1));

if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 25;
}

$query = Capsule::table('tb_jurnal')
    ->where('id_entitas', $id_entitas)
    ->whereBetween('tanggal_jurnal', [$tanggal_awal, $tanggal_akhir])
    ->whereNotNull('tabel_sumber')
    ->where('tabel_sumber', '<>', '');

if ($q !== '') {
    $query->where(function ($sub) use ($q) {
        $sub->where('no_jurnal', 'like', '%' . $q . '%')
            ->orWhere('kode_jenis_transaksi', 'like', '%' . $q . '%')
            ->orWhere('tabel_sumber', 'like', '%' . $q . '%')
            ->orWhere('no_sumber', 'like', '%' . $q . '%')
            ->orWhere('keterangan', 'like', '%' . $q . '%');
    });
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$rows = $query
    ->orderBy('tanggal_jurnal', 'desc')
    ->orderBy('id_jurnal', 'desc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$total_sumber = (int) (clone $query)
    ->selectRaw('COUNT(DISTINCT CONCAT(COALESCE(tabel_sumber, ""), "#", COALESCE(id_sumber, 0))) as total')
    ->value('total');

$params_cetak = $_GET;
$params_cetak['menu'] = 'keuangan/log-jurnal-sumber/cetak';
$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Log Jurnal Sumber</h1>
            <p class="page-subtitle">Pelacakan jurnal berdasarkan transaksi sumber.</p>
        </div>

        <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-primary">
            <i class="bi bi-printer me-1"></i>Cetak
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Jurnal Sumber</div>
                <div class="h4 mb-0"><?= keu_angka($totalRows) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Dokumen Sumber Unik</div>
                <div class="h4 mb-0"><?= keu_angka($total_sumber) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="menu" value="keuangan/log-jurnal-sumber">

            <div class="col-md-3">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?= esc($tanggal_awal) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?= esc($tanggal_akhir) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Sumber, no jurnal, keterangan...">
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
                        <th>Jurnal</th>
                        <th>Jenis Transaksi</th>
                        <th>Tabel Sumber</th>
                        <th>No Sumber</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th>Status</th>
                        <th width="90" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($rows->count() === 0): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Data log jurnal sumber tidak ditemukan.</td>
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
                                </td>
                                <td><?= esc((string) ($row->kode_jenis_transaksi ?? '-')) ?></td>
                                <td><?= esc((string) ($row->tabel_sumber ?? '-')) ?></td>
                                <td><?= keu_sumber_link($row->tabel_sumber ?? null, $row->id_sumber ?? null, $row->no_sumber ?? null) ?></td>
                                <td class="text-end"><?= keu_uang($row->total_debit ?? 0) ?></td>
                                <td class="text-end"><?= keu_uang($row->total_kredit ?? 0) ?></td>
                                <td><?= keu_badge_status($row->status_jurnal ?? '-') ?></td>
                                <td class="text-center">
                                    <a href="<?= esc(admin_page_url('keuangan/log-jurnal-sumber/detail') . '&id=' . (int) $row->id_jurnal) ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/log-jurnal-sumber', max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/log-jurnal-sumber', $i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/log-jurnal-sumber', min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>