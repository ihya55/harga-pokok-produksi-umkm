<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/_keuangan_helper.php';

$id_entitas = keu_id_entitas();

$mode_kas = $mode_kas ?? 'masuk';
$is_masuk = $mode_kas === 'masuk';

$table = $is_masuk ? 'tb_kas_masuk' : 'tb_kas_keluar';
$pk = $is_masuk ? 'id_kas_masuk' : 'id_kas_keluar';
$no_col = $is_masuk ? 'no_kas_masuk' : 'no_kas_keluar';
$tgl_col = $is_masuk ? 'tanggal_kas_masuk' : 'tanggal_kas_keluar';
$jenis_col = $is_masuk ? 'sumber_kas_masuk' : 'jenis_kas_keluar';
$menu_base = $is_masuk ? 'keuangan/kas-masuk' : 'keuangan/kas-keluar';
$page_label = $is_masuk ? 'Kas Masuk' : 'Kas Keluar';

$tanggal_awal = keu_tanggal_mysql($_GET['tanggal_awal'] ?? null, date('Y-m-01'));
$tanggal_akhir = keu_tanggal_mysql($_GET['tanggal_akhir'] ?? null, date('Y-m-t'));
$status = trim((string) ($_GET['status'] ?? 'semua'));
$q = trim((string) ($_GET['q'] ?? ''));
$perPage = (int) ($_GET['per_page'] ?? 25);
$page = max(1, (int) ($_GET['hal'] ?? 1));

if (!in_array($perPage, [10, 25, 50, 100], true)) {
    $perPage = 25;
}

if (!in_array($status, ['semua', 'draft', 'posted'], true)) {
    $status = 'semua';
}

$query = Capsule::table($table . ' as k')
    ->join('tb_coa as kas', 'kas.id_coa', '=', 'k.id_coa_kas_bank')
    ->where('k.id_entitas', $id_entitas)
    ->whereBetween('k.' . $tgl_col, [$tanggal_awal, $tanggal_akhir]);

if (!$is_masuk) {
    $query->join('tb_coa as beban', 'beban.id_coa', '=', 'k.id_coa_beban');
}

if ($status !== 'semua') {
    $query->where('k.status_posting', $status);
}

if ($q !== '') {
    $query->where(function ($sub) use ($q, $no_col, $jenis_col, $is_masuk) {
        $sub->where('k.' . $no_col, 'like', '%' . $q . '%')
            ->orWhere('k.' . $jenis_col, 'like', '%' . $q . '%')
            ->orWhere('k.keterangan', 'like', '%' . $q . '%')
            ->orWhere('kas.kode_coa', 'like', '%' . $q . '%')
            ->orWhere('kas.nama_coa', 'like', '%' . $q . '%');

        if (!$is_masuk) {
            $sub->orWhere('beban.kode_coa', 'like', '%' . $q . '%')
                ->orWhere('beban.nama_coa', 'like', '%' . $q . '%');
        }
    });
}

$totalRows = (int) (clone $query)->count();
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$total_nominal = (float) (clone $query)->sum('k.jumlah');

$select = [
    'k.*',
    'kas.kode_coa as kode_coa_kas',
    'kas.nama_coa as nama_coa_kas',
];

if (!$is_masuk) {
    $select[] = 'beban.kode_coa as kode_coa_beban';
    $select[] = 'beban.nama_coa as nama_coa_beban';
}

$rows = $query
    ->select($select)
    ->orderBy('k.' . $tgl_col, 'desc')
    ->orderBy('k.' . $pk, 'desc')
    ->skip($offset)
    ->take($perPage)
    ->get();

$params_cetak = $_GET;
$params_cetak['menu'] = $menu_base . '/cetak';
$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title"><?= esc($page_label) ?></h1>
            <p class="page-subtitle">
                <?= $is_masuk ? 'Input penerimaan kas manual dan posting ke jurnal.' : 'Input pengeluaran kas manual dan posting ke jurnal.' ?>
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>

            <a href="<?= esc(admin_page_url($menu_base . '/tambah')) ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="text-muted small">Total <?= esc($page_label) ?></div>
        <div class="h3 mb-0 <?= $is_masuk ? 'text-success' : 'text-danger' ?>">
            <?= keu_uang($total_nominal) ?>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="menu" value="<?= esc($menu_base) ?>">

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
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Nomor, akun, keterangan...">
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
                        <th>Nomor</th>
                        <th>Tanggal</th>
                        <th>Jenis/Sumber</th>
                        <th>Akun Kas/Bank</th>
                        <?php if (!$is_masuk): ?>
                            <th>Akun Beban</th>
                        <?php endif; ?>
                        <th class="text-end">Jumlah</th>
                        <th>Status</th>
                        <th width="210" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($rows->count() === 0): ?>
                        <tr>
                            <td colspan="<?= $is_masuk ? 8 : 9 ?>" class="text-center text-muted py-4">Data belum tersedia.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $id = (int) $row->{$pk};
                            $is_posted = (string) $row->status_posting === 'posted';
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td class="fw-semibold"><?= esc((string) $row->{$no_col}) ?></td>
                                <td><?= esc(keu_tanggal($row->{$tgl_col})) ?></td>
                                <td><?= esc((string) $row->{$jenis_col}) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) $row->kode_coa_kas) ?></div>
                                    <div class="text-muted small"><?= esc((string) $row->nama_coa_kas) ?></div>
                                </td>

                                <?php if (!$is_masuk): ?>
                                    <td>
                                        <div class="fw-semibold"><?= esc((string) $row->kode_coa_beban) ?></div>
                                        <div class="text-muted small"><?= esc((string) $row->nama_coa_beban) ?></div>
                                    </td>
                                <?php endif; ?>

                                <td class="text-end fw-semibold <?= $is_masuk ? 'text-success' : 'text-danger' ?>">
                                    <?= keu_uang($row->jumlah ?? 0) ?>
                                </td>
                                <td><?= keu_badge_status($row->status_posting ?? '-') ?></td>
                                <td class="text-center">
                                    <a href="<?= esc(admin_page_url($menu_base . '/detail') . '&id=' . $id) ?>" class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if (!$is_posted): ?>
                                        <a href="<?= esc(admin_page_url($menu_base . '/edit') . '&id=' . $id) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <a href="<?= esc(admin_page_url($menu_base . '/posting') . '&id=' . $id) ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Posting transaksi ini ke jurnal?')">
                                            <i class="bi bi-check2-circle"></i>
                                        </a>

                                        <a href="<?= esc(admin_page_url($menu_base . '/hapus') . '&id=' . $id) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')">
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
                        <th colspan="<?= $is_masuk ? 5 : 6 ?>" class="text-end">Total</th>
                        <th class="text-end"><?= keu_uang($total_nominal) ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url($menu_base, max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(keu_pagination_url($menu_base, $i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url($menu_base, min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>