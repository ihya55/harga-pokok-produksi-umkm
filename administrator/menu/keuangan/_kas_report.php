<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/_keuangan_helper.php';

$id_entitas = keu_id_entitas();

$mode_kas = $mode_kas ?? 'masuk';
$is_masuk = $mode_kas === 'masuk';

$manual_table = $is_masuk ? 'tb_kas_masuk' : 'tb_kas_keluar';
$manual_pk = $is_masuk ? 'id_kas_masuk' : 'id_kas_keluar';
$manual_no_col = $is_masuk ? 'no_kas_masuk' : 'no_kas_keluar';
$manual_tgl_col = $is_masuk ? 'tanggal_kas_masuk' : 'tanggal_kas_keluar';
$manual_jenis_col = $is_masuk ? 'sumber_kas_masuk' : 'jenis_kas_keluar';

$menu_kas = $is_masuk ? 'keuangan/kas-masuk' : 'keuangan/kas-keluar';
$title_kas = $is_masuk ? 'Kas Masuk' : 'Kas Keluar';

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

$rows_data = [];

/*
|--------------------------------------------------------------------------
| 1. Data manual dari tb_kas_masuk / tb_kas_keluar
|    Data manual posted tetap diambil dari tabel manual supaya tombol detail
|    dan sumbernya jelas. Supaya tidak dobel, jurnal dari sumber manual nanti
|    dikecualikan dari query jurnal.
|--------------------------------------------------------------------------
*/
if (keu_table_exists($manual_table)) {
    $manualQuery = Capsule::table($manual_table . ' as k')
        ->join('tb_coa as kas', 'kas.id_coa', '=', 'k.id_coa_kas_bank')
        ->where('k.id_entitas', $id_entitas)
        ->whereBetween('k.' . $manual_tgl_col, [$tanggal_awal, $tanggal_akhir]);

    if (!$is_masuk) {
        $manualQuery->leftJoin('tb_coa as beban', 'beban.id_coa', '=', 'k.id_coa_beban');
    }

    if ($status !== 'semua') {
        $manualQuery->where('k.status_posting', $status);
    }

    if ($q !== '') {
        $manualQuery->where(function ($sub) use ($q, $manual_no_col, $manual_jenis_col, $is_masuk) {
            $sub->where('k.' . $manual_no_col, 'like', '%' . $q . '%')
                ->orWhere('k.' . $manual_jenis_col, 'like', '%' . $q . '%')
                ->orWhere('k.keterangan', 'like', '%' . $q . '%')
                ->orWhere('kas.kode_coa', 'like', '%' . $q . '%')
                ->orWhere('kas.nama_coa', 'like', '%' . $q . '%');

            if (!$is_masuk) {
                $sub->orWhere('beban.kode_coa', 'like', '%' . $q . '%')
                    ->orWhere('beban.nama_coa', 'like', '%' . $q . '%');
            }
        });
    }

    $manualSelect = [
        'k.*',
        'kas.kode_coa as kode_coa_kas',
        'kas.nama_coa as nama_coa_kas',
    ];

    if (!$is_masuk) {
        $manualSelect[] = 'beban.kode_coa as kode_coa_lawan';
        $manualSelect[] = 'beban.nama_coa as nama_coa_lawan';
    }

    $manualRows = $manualQuery
        ->select($manualSelect)
        ->orderBy('k.' . $manual_tgl_col, 'desc')
        ->orderBy('k.' . $manual_pk, 'desc')
        ->get();

    foreach ($manualRows as $r) {
        $id_manual = (int) $r->{$manual_pk};
        $status_posting = (string) ($r->status_posting ?? 'draft');

        $jurnal = null;

        if ($status_posting === 'posted') {
            $jurnal = Capsule::table('tb_jurnal')
                ->where('id_entitas', $id_entitas)
                ->where('tabel_sumber', $manual_table)
                ->where('id_sumber', $id_manual)
                ->first();
        }

        $rows_data[] = [
            'tipe' => 'manual',
            'id_manual' => $id_manual,
            'id_jurnal' => $jurnal ? (int) $jurnal->id_jurnal : 0,
            'tanggal' => (string) $r->{$manual_tgl_col},
            'nomor' => (string) $r->{$manual_no_col},
            'jenis' => (string) $r->{$manual_jenis_col},
            'kode_coa_kas' => (string) $r->kode_coa_kas,
            'nama_coa_kas' => (string) $r->nama_coa_kas,
            'kode_coa_lawan' => !$is_masuk ? (string) ($r->kode_coa_lawan ?? '') : '',
            'nama_coa_lawan' => !$is_masuk ? (string) ($r->nama_coa_lawan ?? '') : '',
            'sumber_label' => 'Input Manual',
            'sumber_html' => '<a href="' . esc(admin_page_url($menu_kas . '/detail') . '&id=' . $id_manual) . '" class="text-decoration-none fw-semibold">Input Manual</a>',
            'keterangan' => (string) ($r->keterangan ?? '-'),
            'nominal' => (float) ($r->jumlah ?? 0),
            'status' => $status_posting,
            'waktu_sort' => strtotime((string) $r->{$manual_tgl_col}) ?: 0,
        ];
    }
}

/*
|--------------------------------------------------------------------------
| 2. Data dari jurnal posted.
|    Jurnal dari tb_kas_masuk / tb_kas_keluar dikecualikan supaya tidak dobel
|    dengan data manual di atas.
|--------------------------------------------------------------------------
*/
if ($status !== 'draft') {
    $jurnalQuery = Capsule::table('tb_jurnal as j')
    ->join('tb_jurnal_detail as jd', 'jd.id_jurnal', '=', 'j.id_jurnal')
    ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
    ->where('j.id_entitas', $id_entitas)
    ->where('j.status_jurnal', 'posted')
    ->whereBetween('j.tanggal_jurnal', [$tanggal_awal, $tanggal_akhir])
    ->where(function ($sub) use ($manual_table) {
        $sub->whereNull('j.tabel_sumber')
            ->orWhere('j.tabel_sumber', '<>', $manual_table);
    });

    keu_exclude_saldo_awal_query($jurnalQuery, 'j');

    keu_is_kas_bank_query($jurnalQuery, 'c', $id_entitas);

    if ($is_masuk) {
        $jurnalQuery->whereRaw('(jd.debit - jd.kredit) > 0');
        $nominal_sql = '(jd.debit - jd.kredit)';
    } else {
        $jurnalQuery->whereRaw('(jd.kredit - jd.debit) > 0');
        $nominal_sql = '(jd.kredit - jd.debit)';
    }

    if ($q !== '') {
        $jurnalQuery->where(function ($sub) use ($q) {
            $sub->where('j.no_jurnal', 'like', '%' . $q . '%')
                ->orWhere('j.no_sumber', 'like', '%' . $q . '%')
                ->orWhere('j.kode_jenis_transaksi', 'like', '%' . $q . '%')
                ->orWhere('j.keterangan', 'like', '%' . $q . '%')
                ->orWhere('jd.keterangan_baris', 'like', '%' . $q . '%')
                ->orWhere('c.kode_coa', 'like', '%' . $q . '%')
                ->orWhere('c.nama_coa', 'like', '%' . $q . '%');
        });
    }

    $jurnalRows = $jurnalQuery
        ->select([
            'j.id_jurnal',
            'j.no_jurnal',
            'j.tanggal_jurnal',
            'j.kode_jenis_transaksi',
            'j.tabel_sumber',
            'j.id_sumber',
            'j.no_sumber',
            'j.keterangan',
            'jd.id_jurnal_detail',
            'jd.keterangan_baris',
            'c.kode_coa',
            'c.nama_coa',
        ])
        ->selectRaw($nominal_sql . ' as nominal')
        ->orderBy('j.tanggal_jurnal', 'desc')
        ->orderBy('j.id_jurnal', 'desc')
        ->orderBy('jd.urutan', 'asc')
        ->get();

    foreach ($jurnalRows as $r) {
        $rows_data[] = [
            'tipe' => 'jurnal',
            'id_manual' => 0,
            'id_jurnal' => (int) $r->id_jurnal,
            'tanggal' => (string) $r->tanggal_jurnal,
            'nomor' => (string) $r->no_jurnal,
            'jenis' => (string) ($r->kode_jenis_transaksi ?? '-'),
            'kode_coa_kas' => (string) $r->kode_coa,
            'nama_coa_kas' => (string) $r->nama_coa,
            'kode_coa_lawan' => '',
            'nama_coa_lawan' => '',
            'sumber_label' => (string) ($r->no_sumber ?? '-'),
            'sumber_html' => keu_sumber_link($r->tabel_sumber ?? null, $r->id_sumber ?? null, $r->no_sumber ?? null),
            'keterangan' => (string) ($r->keterangan_baris ?? $r->keterangan ?? '-'),
            'nominal' => (float) ($r->nominal ?? 0),
            'status' => 'posted',
            'waktu_sort' => strtotime((string) $r->tanggal_jurnal) ?: 0,
        ];
    }
}

usort($rows_data, function ($a, $b) {
    if ($a['waktu_sort'] === $b['waktu_sort']) {
        return strcmp((string) $b['nomor'], (string) $a['nomor']);
    }

    return $b['waktu_sort'] <=> $a['waktu_sort'];
});

$totalRows = count($rows_data);
$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$rows_page = array_slice($rows_data, $offset, $perPage);
$total_nominal = array_sum(array_map(static fn ($r) => (float) $r['nominal'], $rows_data));

$params_cetak = $_GET;
$params_cetak['menu'] = $menu_kas . '/cetak';
$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title"><?= esc($title_kas) ?></h1>
            <p class="page-subtitle">
                Gabungan <?= esc(strtolower($title_kas)) ?> dari transaksi posted dan input manual.
            </p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>

            <a href="<?= esc(admin_page_url($menu_kas . '/tambah')) ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah Manual
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="text-muted small">Total <?= esc($title_kas) ?></div>
        <div class="h3 mb-0 <?= $is_masuk ? 'text-success' : 'text-danger' ?>">
            <?= keu_uang($total_nominal) ?>
        </div>
        <div class="text-muted small mt-1">
            Termasuk transaksi sistem dan kas manual. Manual posted tidak dihitung dobel dari jurnal.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="menu" value="<?= esc($menu_kas) ?>">

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
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft Manual</option>
                    <option value="posted" <?= $status === 'posted' ? 'selected' : '' ?>>Posted</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Pencarian</label>
                <input type="text" name="q" class="form-control" value="<?= esc($q) ?>" placeholder="Nomor, jurnal, sumber, akun...">
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
                        <th>Nomor</th>
                        <th>Jenis/Sumber</th>
                        <th>Akun Kas/Bank</th>
                        <?php if (!$is_masuk): ?>
                            <th>Akun Beban/HPP</th>
                        <?php endif; ?>
                        <th>Sumber</th>
                        <th>Keterangan</th>
                        <th class="text-end">Nominal</th>
                        <th>Status</th>
                        <th width="190" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($rows_page)): ?>
                        <tr>
                            <td colspan="<?= $is_masuk ? 10 : 11 ?>" class="text-center text-muted py-4">Data tidak ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = $offset + 1; ?>
                        <?php foreach ($rows_page as $row): ?>
                            <?php
                            $isManual = $row['tipe'] === 'manual';
                            $isPosted = $row['status'] === 'posted';
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(keu_tanggal($row['tanggal'])) ?></td>

                                <td>
                                    <?php if ($isManual): ?>
                                        <a href="<?= esc(admin_page_url($menu_kas . '/detail') . '&id=' . (int) $row['id_manual']) ?>" class="text-decoration-none fw-semibold">
                                            <?= esc($row['nomor']) ?>
                                        </a>
                                        <div class="text-muted small">Manual</div>
                                    <?php else: ?>
                                        <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row['id_jurnal']) ?>" class="text-decoration-none fw-semibold">
                                            <?= esc($row['nomor']) ?>
                                        </a>
                                        <div class="text-muted small">Jurnal</div>
                                    <?php endif; ?>
                                </td>

                                <td><?= esc($row['jenis']) ?></td>

                                <td>
                                    <div class="fw-semibold"><?= esc($row['kode_coa_kas']) ?></div>
                                    <div class="text-muted small"><?= esc($row['nama_coa_kas']) ?></div>
                                </td>

                                <?php if (!$is_masuk): ?>
                                    <td>
                                        <?php if ($row['kode_coa_lawan'] !== ''): ?>
                                            <div class="fw-semibold"><?= esc($row['kode_coa_lawan']) ?></div>
                                            <div class="text-muted small"><?= esc($row['nama_coa_lawan']) ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <td><?= $row['sumber_html'] ?></td>
                                <td><?= esc($row['keterangan']) ?></td>

                                <td class="text-end fw-semibold <?= $is_masuk ? 'text-success' : 'text-danger' ?>">
                                    <?= keu_uang($row['nominal']) ?>
                                </td>

                                <td><?= keu_badge_status($row['status']) ?></td>

                                <td class="text-center">
                                    <?php if ($isManual): ?>
                                        <a href="<?= esc(admin_page_url($menu_kas . '/detail') . '&id=' . (int) $row['id_manual']) ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <?php if (!$isPosted): ?>
                                            <a href="<?= esc(admin_page_url($menu_kas . '/edit') . '&id=' . (int) $row['id_manual']) ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <a href="<?= esc(admin_page_url($menu_kas . '/posting') . '&id=' . (int) $row['id_manual']) ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Posting transaksi manual ini ke jurnal?')">
                                                <i class="bi bi-check2-circle"></i>
                                            </a>

                                            <a href="<?= esc(admin_page_url($menu_kas . '/hapus') . '&id=' . (int) $row['id_manual']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row['id_jurnal']) ?>" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-journal-text"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="<?= $is_masuk ? 8 : 9 ?>" class="text-end">Total Filter</th>
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
                        <a class="page-link" href="<?= esc(keu_pagination_url($menu_kas, max(1, $page - 1))) ?>">Prev</a>
                    </li>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= esc(keu_pagination_url($menu_kas, $i)) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= esc(keu_pagination_url($menu_kas, min($totalPages, $page + 1))) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>