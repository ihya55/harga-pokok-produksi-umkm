<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas = keu_id_entitas();

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$jenis = trim((string) ($_GET['jenis'] ?? 'semua'));
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = (int) ($_GET['per_page'] ?? 25);
$page = max(1, (int) ($_GET['hal'] ?? 1));

if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 25;
}

if (!in_array($status, ['semua', 'draft', 'posted', 'batal'], true)) {
    $status = 'semua';
}

if (!in_array($jenis, ['semua', 'JURNAL_MANUAL', 'SALDO_AWAL_COA'], true)) {
    $jenis = 'semua';
}

$query = Capsule::table('tb_jurnal')
    ->where('id_entitas', $id_entitas)
    ->whereBetween('tanggal_jurnal', [$tanggal_awal, $tanggal_akhir]);

if ($status !== 'semua') {
    $query->where('status_jurnal', $status);
}

if ($jenis !== 'semua') {
    $query->where('kode_jenis_transaksi', $jenis);
}

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

$total_debit = (float) (clone $query)->sum('total_debit');
$total_kredit = (float) (clone $query)->sum('total_kredit');

$rows = $query
    ->orderBy('tanggal_jurnal', 'desc')
    ->orderBy('id_jurnal', 'desc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$params_cetak = $_GET;
$params_cetak['menu'] = 'keuangan/jurnal/cetak';
$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Jurnal</h1>
            <p class="page-subtitle">Daftar jurnal akuntansi dari transaksi sumber, jurnal manual, dan saldo awal COA.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>

            <a href="<?= esc(admin_page_url('keuangan/jurnal/tambah') . '&jenis=SALDO_AWAL_COA') ?>" class="btn btn-outline-success">
                <i class="bi bi-database-add me-1"></i>Saldo Awal COA
            </a>

            <a href="<?= esc(admin_page_url('keuangan/jurnal/tambah') . '&jenis=JURNAL_MANUAL') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah Jurnal
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Debit</div>
                <div class="h4 mb-0 text-primary"><?= keu_uang($total_debit) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Kredit</div>
                <div class="h4 mb-0" style="color:#f97316;"><?= keu_uang($total_kredit) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Selisih</div>
                <div class="h4 mb-0 <?= abs($total_debit - $total_kredit) < 0.01 ? 'text-success' : 'text-danger' ?>">
                    <?= keu_uang($total_debit - $total_kredit) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="menu" value="keuangan/jurnal">

            <div class="col-md-2">
                <label class="form-label">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" class="form-control" value="<?= esc($tanggal_awal) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" class="form-control" value="<?= esc($tanggal_akhir) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="semua" <?= $status === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="posted" <?= $status === 'posted' ? 'selected' : '' ?>>Posted</option>
                    <option value="batal" <?= $status === 'batal' ? 'selected' : '' ?>>Batal</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Jenis</label>
                <select name="jenis" class="form-select">
                    <option value="semua" <?= $jenis === 'semua' ? 'selected' : '' ?>>Semua</option>
                    <option value="JURNAL_MANUAL" <?= $jenis === 'JURNAL_MANUAL' ? 'selected' : '' ?>>Jurnal Manual</option>
                    <option value="SALDO_AWAL_COA" <?= $jenis === 'SALDO_AWAL_COA' ? 'selected' : '' ?>>Saldo Awal COA</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="No jurnal, sumber...">
            </div>

            <div class="col-md-1">
                <label class="form-label">Baris</label>
                <select name="per_page" class="form-select">
                    <?php foreach ([10, 25, 50, 100] as $limit): ?>
                        <option value="<?= $limit ?>" <?= $perPage === $limit ? 'selected' : '' ?>><?= $limit ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-1 d-grid">
                <button class="btn btn-outline-primary" type="submit">
                    <i class="bi bi-search"></i>
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
                        <th>Jenis Transaksi</th>
                        <th>Sumber</th>
                        <th>Keterangan</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                        <th>Status</th>
                        <th width="170" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($rows->count() === 0): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Data jurnal tidak ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $id = (int) $row->id_jurnal;
                            $is_draft = (string) $row->status_jurnal === 'draft';
                            $is_manual = in_array((string) $row->kode_jenis_transaksi, ['JURNAL_MANUAL', 'SALDO_AWAL_COA'], true);
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(keu_tanggal($row->tanggal_jurnal)) ?></td>
                                <td>
                                    <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . $id) ?>" class="text-decoration-none fw-semibold">
                                        <?= esc((string) $row->no_jurnal) ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->kode_jenis_transaksi ?? '-')) ?></div>
                                    <?php if ($is_manual): ?>
                                        <div class="text-muted small">Input manual</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= keu_sumber_link($row->tabel_sumber ?? null, $row->id_sumber ?? null, $row->no_sumber ?? null) ?></td>
                                <td><?= esc((string) ($row->keterangan ?? '-')) ?></td>
                                <td class="text-end text-primary fw-semibold"><?= keu_uang($row->total_debit ?? 0) ?></td>
                                <td class="text-end fw-semibold" style="color:#f97316;"><?= keu_uang($row->total_kredit ?? 0) ?></td>
                                <td><?= keu_badge_status($row->status_jurnal ?? '-') ?></td>
                                <td class="text-center">
                                    <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . $id) ?>" class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if ($is_draft && $is_manual): ?>
                                        <a href="<?= esc(admin_page_url('keuangan/jurnal/edit') . '&id=' . $id) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <a href="<?= esc(admin_page_url('keuangan/jurnal/posting') . '&id=' . $id) ?>" class="btn btn-sm btn-outline-success" title="Posting" onclick="return confirm('Posting jurnal ini? Setelah posted tidak bisa diedit.')">
                                            <i class="bi bi-check2-circle"></i>
                                        </a>

                                        <a href="<?= esc(admin_page_url('keuangan/jurnal/hapus') . '&id=' . $id) ?>" class="btn btn-sm btn-outline-danger" title="Hapus" onclick="return confirm('Hapus jurnal draft ini?')">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="6" class="text-end">Total Filter</th>
                        <th class="text-end text-primary"><?= keu_uang($total_debit) ?></th>
                        <th class="text-end" style="color:#f97316;"><?= keu_uang($total_kredit) ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/jurnal', max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/jurnal', $i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url('keuangan/jurnal', min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>