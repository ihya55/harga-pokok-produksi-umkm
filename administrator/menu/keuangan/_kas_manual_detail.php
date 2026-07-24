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

$id = (int) ($_GET['id'] ?? 0);

$query = Capsule::table($table . ' as k')
    ->join('tb_coa as kas', 'kas.id_coa', '=', 'k.id_coa_kas_bank')
    ->where('k.id_entitas', $id_entitas)
    ->where('k.' . $pk, $id);

if (!$is_masuk) {
    $query->join('tb_coa as beban', 'beban.id_coa', '=', 'k.id_coa_beban');
}

$select = [
    'k.*',
    'kas.kode_coa as kode_coa_kas',
    'kas.nama_coa as nama_coa_kas',
];

if (!$is_masuk) {
    $select[] = 'beban.kode_coa as kode_coa_beban';
    $select[] = 'beban.nama_coa as nama_coa_beban';
}

$row = $query->select($select)->first();

if (!$row) {
    ?>
    <div class="alert alert-danger">Data tidak ditemukan.</div>
    <a href="<?= esc(admin_page_url($menu_base)) ?>" class="btn btn-outline-secondary">Kembali</a>
    <?php
    return;
}

$jurnal = Capsule::table('tb_jurnal')
    ->where('id_entitas', $id_entitas)
    ->where('tabel_sumber', $table)
    ->where('id_sumber', $id)
    ->first();

$url_cetak = admin_page_url($menu_base . '/cetak') . '&id=' . $id;
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail <?= esc($page_label) ?></h1>
            <p class="page-subtitle">Detail transaksi manual dan jurnal sumbernya.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc(admin_page_url($menu_base)) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>

            <?php if ((string) $row->status_posting !== 'posted'): ?>
                <a href="<?= esc(admin_page_url($menu_base . '/posting') . '&id=' . $id) ?>" class="btn btn-success" onclick="return confirm('Posting transaksi ini ke jurnal?')">
                    <i class="bi bi-check2-circle me-1"></i>Posting
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Nomor</div>
                <div class="h5 mb-1"><?= esc((string) $row->{$no_col}) ?></div>
                <?= keu_badge_status($row->status_posting ?? '-') ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal</div>
                <div class="h5 mb-1"><?= esc(keu_tanggal($row->{$tgl_col})) ?></div>
                <div class="text-muted small"><?= esc((string) $row->{$jenis_col}) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Jumlah</div>
                <div class="h4 mb-0 <?= $is_masuk ? 'text-success' : 'text-danger' ?>"><?= keu_uang($row->jumlah ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Informasi Transaksi</h2>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">Akun Kas/Bank</div>
                <div class="fw-semibold"><?= esc($row->kode_coa_kas . ' - ' . $row->nama_coa_kas) ?></div>
            </div>

            <?php if (!$is_masuk): ?>
                <div class="col-md-3">
                    <div class="text-muted small">Akun Beban/HPP</div>
                    <div class="fw-semibold"><?= esc($row->kode_coa_beban . ' - ' . $row->nama_coa_beban) ?></div>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <div class="text-muted small">Jurnal</div>
                <?php if ($jurnal): ?>
                    <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $jurnal->id_jurnal) ?>" class="fw-semibold text-decoration-none">
                        <?= esc((string) $jurnal->no_jurnal) ?>
                    </a>
                <?php else: ?>
                    <div class="fw-semibold">Belum posted</div>
                <?php endif; ?>
            </div>

            <div class="col-md-12">
                <div class="text-muted small">Keterangan</div>
                <div class="fw-semibold"><?= esc((string) ($row->keterangan ?? '-')) ?></div>
            </div>
        </div>
    </div>
</div>