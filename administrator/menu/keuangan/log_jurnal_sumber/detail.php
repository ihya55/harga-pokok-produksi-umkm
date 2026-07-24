<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas = keu_id_entitas();
$id_jurnal = (int) ($_GET['id'] ?? 0);

$row = Capsule::table('tb_jurnal')
    ->where('id_entitas', $id_entitas)
    ->where('id_jurnal', $id_jurnal)
    ->first();

if (!$row) {
    ?>
    <div class="alert alert-danger">Data log jurnal sumber tidak ditemukan.</div>
    <a href="<?= esc(admin_page_url('keuangan/log-jurnal-sumber')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <?php
    return;
}

$detail = Capsule::table('tb_jurnal_detail as jd')
    ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
    ->where('jd.id_jurnal', $id_jurnal)
    ->select([
        'jd.*',
        'c.kode_coa',
        'c.nama_coa',
        'c.kategori_coa',
    ])
    ->orderBy('jd.urutan', 'asc')
    ->orderBy('jd.id_jurnal_detail', 'asc')
    ->get();

$url_sumber = keu_sumber_url($row->tabel_sumber ?? null, $row->id_sumber ?? null);

$params_cetak = [
    'menu' => 'keuangan/log-jurnal-sumber/cetak',
    'id' => $id_jurnal,
];

$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Log Jurnal Sumber</h1>
            <p class="page-subtitle">Relasi jurnal dengan dokumen sumber transaksi.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc(admin_page_url('keuangan/log-jurnal-sumber')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if ($url_sumber): ?>
                <a href="<?= esc($url_sumber) ?>" class="btn btn-outline-success">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Buka Sumber
                </a>
            <?php endif; ?>

            <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Dokumen Sumber</div>
                <div class="h5 mb-1"><?= esc((string) ($row->no_sumber ?? '-')) ?></div>
                <div class="text-muted small"><?= esc((string) ($row->tabel_sumber ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">No Jurnal</div>
                <div class="h5 mb-1"><?= esc((string) $row->no_jurnal) ?></div>
                <?= keu_badge_status($row->status_jurnal ?? '-') ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total</div>
                <div class="h6 mb-1">Debit: <?= keu_uang($row->total_debit ?? 0) ?></div>
                <div class="h6 mb-0">Kredit: <?= keu_uang($row->total_kredit ?? 0) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Informasi Sumber</h2>

        <table class="table table-sm table-borderless align-middle mb-0">
            <tr>
                <th width="180" class="text-muted">Tabel Sumber</th>
                <td><?= esc((string) ($row->tabel_sumber ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">ID Sumber</th>
                <td><?= esc((string) ($row->id_sumber ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">No Sumber</th>
                <td><?= keu_sumber_link($row->tabel_sumber ?? null, $row->id_sumber ?? null, $row->no_sumber ?? null) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Jenis Transaksi</th>
                <td><?= esc((string) ($row->kode_jenis_transaksi ?? '-')) ?></td>
            </tr>
            <tr>
                <th class="text-muted">No Jurnal</th>
                <td>
                    <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row->id_jurnal) ?>" class="text-decoration-none fw-semibold">
                        <?= esc((string) $row->no_jurnal) ?>
                    </a>
                </td>
            </tr>
            <tr>
                <th class="text-muted">Tanggal Jurnal</th>
                <td><?= esc(keu_tanggal($row->tanggal_jurnal)) ?></td>
            </tr>
            <tr>
                <th class="text-muted">Keterangan</th>
                <td><?= esc((string) ($row->keterangan ?? '-')) ?></td>
            </tr>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Detail Jurnal</h2>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="60" class="text-center">No</th>
                        <th>Akun</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th class="text-end">Debit</th>
                        <th class="text-end">Kredit</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail->count() === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Detail jurnal belum tersedia.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detail as $i => $d): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) $d->kode_coa) ?></div>
                                    <div class="text-muted small"><?= esc((string) $d->nama_coa) ?></div>
                                </td>
                                <td><?= esc((string) ($d->kategori_coa ?? '-')) ?></td>
                                <td><?= esc((string) ($d->keterangan_baris ?? '-')) ?></td>
                                <td class="text-end"><?= keu_uang($d->debit ?? 0) ?></td>
                                <td class="text-end"><?= keu_uang($d->kredit ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="text-end"><?= keu_uang($row->total_debit ?? 0) ?></th>
                        <th class="text-end"><?= keu_uang($row->total_kredit ?? 0) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>