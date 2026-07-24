<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_fungsi_penjualan.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);

if (!function_exists('pos_riwayat_rupiah')) {
    function pos_riwayat_rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pos_riwayat_tanggal')) {
    function pos_riwayat_tanggal($tanggal): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date('d/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('pos_riwayat_badge_bayar')) {
    function pos_riwayat_badge_bayar(string $jenis): string
    {
        $jenis = strtolower(trim($jenis));

        if ($jenis === 'tunai') {
            return '<span class="badge bg-success-subtle text-success">Tunai</span>';
        }

        if ($jenis === 'transfer') {
            return '<span class="badge bg-primary-subtle text-primary">Transfer</span>';
        }

        if ($jenis === 'kredit') {
            return '<span class="badge bg-warning-subtle text-warning">Kredit</span>';
        }

        return '<span class="badge bg-secondary">-</span>';
    }
}

if (!function_exists('pos_riwayat_badge_status')) {
    function pos_riwayat_badge_status($sisa_piutang): string
    {
        $sisa_piutang = (float) $sisa_piutang;

        if ($sisa_piutang > 0) {
            return '<span class="badge bg-danger-subtle text-danger">Belum Lunas</span>';
        }

        return '<span class="badge bg-success-subtle text-success">Lunas</span>';
    }
}

$tanggal_awal = trim((string) ($_GET['tanggal_awal'] ?? date('Y-m-01')));
$tanggal_akhir = trim((string) ($_GET['tanggal_akhir'] ?? date('Y-m-d')));
$keyword = trim((string) ($_GET['keyword'] ?? ''));
$jenis_pembayaran = trim((string) ($_GET['jenis_pembayaran'] ?? ''));
$status_piutang = trim((string) ($_GET['status_piutang'] ?? ''));

$page = max(1, (int) ($_GET['page'] ?? 1));
$per_page = (int) ($_GET['per_page'] ?? 10);

if (!in_array($per_page, [10, 25, 50, 100], true)) {
    $per_page = 10;
}

$offset = ($page - 1) * $per_page;

$query = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 'f.id_pelanggan')
    ->leftJoin('tb_penyerahan_penjualan as pn', 'pn.id_penyerahan_penjualan', '=', 'f.id_penyerahan_penjualan')
    ->leftJoin('tb_pembayaran_penjualan as by', 'by.id_faktur_penjualan', '=', 'f.id_faktur_penjualan')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.status_faktur', 'posted')
    ->where(function ($q) {
        $q->where('f.catatan', 'like', 'POS%')
            ->orWhere('f.catatan', 'like', 'POS:%');
    });

if ($tanggal_awal !== '') {
    $query->whereDate('f.tanggal_faktur', '>=', $tanggal_awal);
}

if ($tanggal_akhir !== '') {
    $query->whereDate('f.tanggal_faktur', '<=', $tanggal_akhir);
}

if ($keyword !== '') {
    $query->where(function ($q) use ($keyword) {
        $q->where('f.no_faktur_penjualan', 'like', '%' . $keyword . '%')
            ->orWhere('pn.no_penyerahan_penjualan', 'like', '%' . $keyword . '%')
            ->orWhere('p.kode_pelanggan', 'like', '%' . $keyword . '%')
            ->orWhere('p.nama_pelanggan', 'like', '%' . $keyword . '%');
    });
}

if ($jenis_pembayaran !== '') {
    $query->where('f.jenis_pembayaran', $jenis_pembayaran);
}

if ($status_piutang === 'lunas') {
    $query->where('f.sisa_piutang', '<=', 0);
} elseif ($status_piutang === 'belum_lunas') {
    $query->where('f.sisa_piutang', '>', 0);
}

$count_query = clone $query;
$total_rows = (int) $count_query->count('f.id_faktur_penjualan');
$total_pages = max(1, (int) ceil($total_rows / $per_page));

if ($page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $per_page;
}

$kolom_ref_transfer = Capsule::schema()->hasColumn('tb_pembayaran_penjualan', 'no_referensi_transfer')
    ? 'by.no_referensi_transfer'
    : Capsule::raw('NULL as no_referensi_transfer');

$kolom_bukti_transfer = Capsule::schema()->hasColumn('tb_pembayaran_penjualan', 'bukti_bayar_pos')
    ? 'by.bukti_bayar_pos'
    : Capsule::raw('NULL as bukti_bayar_pos');

$rows = $query
    ->select([
        'f.id_faktur_penjualan',
        'f.no_faktur_penjualan',
        'f.tanggal_faktur',
        'f.jenis_pembayaran',
        'f.status_faktur',
        'f.subtotal',
        'f.diskon',
        'f.ppn',
        'f.total',
        'f.sisa_piutang',
        'f.catatan',
        'p.kode_pelanggan',
        'p.nama_pelanggan',
        'pn.no_penyerahan_penjualan',
        'by.no_pembayaran_penjualan',
        'by.jumlah_bayar',
        'by.metode_pembayaran',
        $kolom_ref_transfer,
        $kolom_bukti_transfer,
    ])
    ->orderByDesc('f.tanggal_faktur')
    ->orderByDesc('f.id_faktur_penjualan')
    ->offset($offset)
    ->limit($per_page)
    ->get();

$total_nilai_query = clone $query;
$total_nilai = (float) $total_nilai_query->sum('f.total');

$total_piutang_query = clone $query;
$total_piutang = (float) $total_piutang_query->sum('f.sisa_piutang');

$query_params = $_GET;
unset($query_params['page']);

$base_query_string = http_build_query($query_params);
$base_page_url = admin_page_url('penjualan/kasir/riwayat');
$pagination_base = $base_page_url . ($base_query_string !== '' ? '&' . $base_query_string : '');

$start_no = $total_rows > 0 ? $offset + 1 : 0;
$end_no = min($offset + $per_page, $total_rows);
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Riwayat Kasir / POS</h1>
            <p class="page-subtitle">Daftar transaksi POS yang sudah diposting.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= esc(admin_page_url('penjualan/kasir')) ?>" class="btn btn-gradient">
                <i class="bi bi-plus-circle me-1"></i> Transaksi Baru
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Transaksi</div>
                <div class="h4 mb-0"><?= number_format($total_rows, 0, '.', ',') ?></div>
                <div class="text-muted small">Berdasarkan filter aktif</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Nilai POS</div>
                <div class="h4 mb-0"><?= pos_riwayat_rupiah($total_nilai) ?></div>
                <div class="text-muted small">Total transaksi POS posted</div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Sisa Piutang POS</div>
                <div class="h4 mb-0"><?= pos_riwayat_rupiah($total_piutang) ?></div>
                <div class="text-muted small">Untuk transaksi kredit</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= esc(admin_url('index.php')) ?>">
            <input type="hidden" name="menu" value="penjualan/kasir/riwayat">

            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" class="form-control" value="<?= esc($tanggal_awal) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" class="form-control" value="<?= esc($tanggal_akhir) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">Pencarian</label>
                    <input
                        type="text"
                        name="keyword"
                        class="form-control"
                        value="<?= esc($keyword) ?>"
                        placeholder="No transaksi / pelanggan">
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Pembayaran</label>
                    <select name="jenis_pembayaran" class="form-select">
                        <option value="">Semua</option>
                        <option value="tunai" <?= $jenis_pembayaran === 'tunai' ? 'selected' : '' ?>>Tunai</option>
                        <option value="transfer" <?= $jenis_pembayaran === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                        <option value="kredit" <?= $jenis_pembayaran === 'kredit' ? 'selected' : '' ?>>Kredit</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status_piutang" class="form-select">
                        <option value="">Semua</option>
                        <option value="lunas" <?= $status_piutang === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                        <option value="belum_lunas" <?= $status_piutang === 'belum_lunas' ? 'selected' : '' ?>>Belum Lunas</option>
                    </select>
                </div>

                <div class="col-md-1">
                    <label class="form-label fw-semibold">Baris</label>
                    <select name="per_page" class="form-select">
                        <option value="10" <?= $per_page === 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $per_page === 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $per_page === 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $per_page === 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 flex-wrap">
                    <a href="<?= esc(admin_page_url('penjualan/kasir/riwayat')) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </a>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-1"></i> Tampilkan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Daftar Transaksi POS</h2>
                <div class="text-muted small">
                    Menampilkan <?= number_format($start_no, 0, '.', ',') ?>-<?= number_format($end_no, 0, '.', ',') ?>
                    dari <?= number_format($total_rows, 0, '.', ',') ?> transaksi.
                </div>
            </div>

        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="130">Tanggal</th>
                        <th width="170">No Transaksi</th>
                        <th>Pelanggan</th>
                        <th width="125">Pembayaran</th>
                        <th width="145" class="text-end">Subtotal</th>
                        <th width="130" class="text-end">Diskon</th>
                        <th width="130" class="text-end">PPN</th>
                        <th width="150" class="text-end">Total</th>
                        <th width="130" class="text-end">Sisa</th>
                        <th width="115" class="text-center">Status</th>
                        <th width="130" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($rows->count() === 0): ?>
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">
                                Belum ada transaksi POS sesuai filter.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td class="text-center"><?= number_format($offset + $i + 1, 0, '.', ',') ?></td>

                                <td>
                                    <div class="fw-semibold"><?= esc(pos_riwayat_tanggal($row->tanggal_faktur ?? null)) ?></div>
                                    <div class="text-muted small">Posted</div>
                                </td>

                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->no_faktur_penjualan ?? '-')) ?></div>
                                    <div class="text-muted small">
                                        <?php if (in_array((string) ($row->jenis_pembayaran ?? ''), ['tunai', 'transfer'], true)): ?>
                                            Bayar: <?= esc((string) ($row->no_pembayaran_penjualan ?? '-')) ?>
                                            <?php if ((string) ($row->jenis_pembayaran ?? '') === 'transfer' && !empty($row->no_referensi_transfer)): ?>
                                                <br>Ref: <?= esc((string) $row->no_referensi_transfer) ?>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            Piutang: <?= esc((string) ($row->no_penyerahan_penjualan ?? '-')) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->nama_pelanggan ?? '-')) ?></div>
                                    <div class="text-muted small"><?= esc((string) ($row->kode_pelanggan ?? '-')) ?></div>
                                </td>

                                <td>
                                    <?= pos_riwayat_badge_bayar((string) ($row->jenis_pembayaran ?? '')) ?>
                                    <?php if (in_array((string) ($row->jenis_pembayaran ?? ''), ['tunai', 'transfer'], true)): ?>
                                        <div class="text-muted small mt-1">Pembayaran penjualan</div>
                                        <?php if ((string) ($row->jenis_pembayaran ?? '') === 'transfer'): ?>
                                            <div class="small mt-1 <?= !empty($row->bukti_bayar_pos) ? 'text-success' : 'text-muted' ?>">
                                                <?= !empty($row->bukti_bayar_pos) ? 'Bukti tersimpan' : 'Bukti foto opsional' ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ((string) ($row->jenis_pembayaran ?? '') === 'kredit'): ?>
                                        <div class="text-muted small mt-1">Masuk piutang</div>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end"><?= pos_riwayat_rupiah($row->subtotal ?? 0) ?></td>
                                <td class="text-end"><?= pos_riwayat_rupiah($row->diskon ?? 0) ?></td>
                                <td class="text-end"><?= pos_riwayat_rupiah($row->ppn ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= pos_riwayat_rupiah($row->total ?? 0) ?></td>
                                <td class="text-end text-danger fw-semibold"><?= pos_riwayat_rupiah($row->sisa_piutang ?? 0) ?></td>

                                <td class="text-center"><?= pos_riwayat_badge_status($row->sisa_piutang ?? 0) ?></td>

                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a
                                            href="<?= esc(admin_page_url('penjualan/kasir/detail') . '&id=' . (int) $row->id_faktur_penjualan) ?>"
                                            class="btn btn-outline-primary"
                                            title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>

                                        <a
                                            href="<?= esc(admin_url('index.php?menu=penjualan/kasir/struk&id=' . (int) $row->id_faktur_penjualan)) ?>"
                                            class="btn btn-outline-secondary"
                                            target="_blank"
                                            title="Cetak Struk">
                                            <i class="bi bi-printer"></i>
                                        </a>

                                        <?php if ((string) ($row->jenis_pembayaran ?? '') === 'kredit'): ?>
                                            <a
                                                href="<?= esc(admin_page_url('penjualan/faktur/detail') . '&id=' . (int) $row->id_faktur_penjualan) ?>"
                                                class="btn btn-outline-warning"
                                                title="Daftar Piutang Pelanggan">
                                                <i class="bi bi-cash-stack"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                <div class="text-muted small">
                    Halaman <?= number_format($page, 0, '.', ',') ?> dari <?= number_format($total_pages, 0, '.', ',') ?>
                </div>

                <nav aria-label="Pagination Riwayat POS">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $prev_disabled = $page <= 1 ? 'disabled' : '';
                        $next_disabled = $page >= $total_pages ? 'disabled' : '';

                        $prev_url = $pagination_base . '&page=' . max(1, $page - 1);
                        $next_url = $pagination_base . '&page=' . min($total_pages, $page + 1);

                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        ?>

                        <li class="page-item <?= esc($prev_disabled) ?>">
                            <a class="page-link" href="<?= esc($prev_url) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>

                        <?php if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= esc($pagination_base . '&page=1') ?>">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= esc($pagination_base . '&page=' . $p) ?>">
                                    <?= number_format($p, 0, '.', ',') ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= esc($pagination_base . '&page=' . $total_pages) ?>">
                                    <?= number_format($total_pages, 0, '.', ',') ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="page-item <?= esc($next_disabled) ?>">
                            <a class="page-link" href="<?= esc($next_url) ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>