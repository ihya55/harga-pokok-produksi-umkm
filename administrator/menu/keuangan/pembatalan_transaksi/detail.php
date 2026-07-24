<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_keuangan_helper.php';

$id_entitas_login = keu_id_entitas();
$user = keu_user();

$role_user = strtolower(trim((string) (
    $user['kode_role'] ??
    $user['role'] ??
    $user['nama_role'] ??
    ''
)));

$is_super_admin = $role_user === 'super_admin';
$is_admin_entitas = $role_user === 'admin_entitas';

if (!$is_super_admin && !$is_admin_entitas) {
    ?>
    <div class="alert alert-danger">Anda tidak memiliki akses ke detail pembatalan transaksi.</div>
    <?php
    return;
}

$id = (int) ($_GET['id'] ?? 0);

$query = Capsule::table('tb_pembatalan_transaksi as p')
    ->join('tb_jurnal as ja', 'ja.id_jurnal', '=', 'p.id_jurnal_asal')
    ->leftJoin('tb_jurnal as jr', 'jr.id_jurnal', '=', 'p.id_jurnal_reversal')
    ->where('p.id_pembatalan_transaksi', $id);

if (!$is_super_admin) {
    $query->where('p.id_entitas', $id_entitas_login);
}

$row = $query
    ->select([
        'p.*',
        'ja.tanggal_jurnal as tanggal_jurnal_asal',
        'ja.keterangan as keterangan_jurnal_asal',
        'ja.total_debit as total_debit_asal',
        'ja.total_kredit as total_kredit_asal',
        'jr.tanggal_jurnal as tanggal_jurnal_reversal',
        'jr.keterangan as keterangan_jurnal_reversal',
        'jr.total_debit as total_debit_reversal',
        'jr.total_kredit as total_kredit_reversal',
    ])
    ->first();

if (!$row) {
    ?>
    <div class="alert alert-danger">Data pembatalan tidak ditemukan.</div>
    <a href="<?= esc(admin_page_url('keuangan/pembatalan-transaksi')) ?>" class="btn btn-outline-secondary">
        Kembali
    </a>
    <?php
    return;
}

$detailAsal = Capsule::table('tb_jurnal_detail as jd')
    ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
    ->where('jd.id_jurnal', (int) $row->id_jurnal_asal)
    ->select(['jd.*', 'c.kode_coa', 'c.nama_coa'])
    ->orderBy('jd.urutan', 'asc')
    ->get();

$detailReversal = collect();

if (!empty($row->id_jurnal_reversal)) {
    $detailReversal = Capsule::table('tb_jurnal_detail as jd')
        ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
        ->where('jd.id_jurnal', (int) $row->id_jurnal_reversal)
        ->select(['jd.*', 'c.kode_coa', 'c.nama_coa'])
        ->orderBy('jd.urutan', 'asc')
        ->get();
}

$url_cetak = admin_page_url('keuangan/pembatalan-transaksi/cetak') . '&id=' . (int) $row->id_pembatalan_transaksi;
?>

<style>
    .rev-detail-hero {
        border: 0;
        border-radius: 24px;
        color: #fff;
        background:
            radial-gradient(circle at top left, rgba(255,255,255,.24), transparent 30%),
            linear-gradient(135deg, #2563eb 0%, #7c3aed 58%, #f97316 128%);
        box-shadow: 0 16px 38px rgba(37, 99, 235, .20);
    }

    .rev-card {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(15, 23, 42, .08);
    }

    .text-debit { color:#2563eb !important; }
    .text-kredit { color:#f97316 !important; }
</style>

<div class="card rev-detail-hero mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="badge bg-white bg-opacity-25 mb-3">Detail Pembatalan</div>
                <h1 class="fw-bold mb-1"><?= esc((string) $row->no_pembatalan) ?></h1>
                <div class="opacity-75">
                    Jurnal asal tetap disimpan, jurnal reversal dibuat otomatis.
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="<?= esc(admin_url('index.php?menu=keuangan/pembatalan-transaksi&mode=sudah_batal')) ?>" class="btn btn-light">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <a href="<?= esc($url_cetak) ?>" target="_blank" class="btn btn-outline-light">
                    <i class="bi bi-printer me-1"></i>Cetak
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card rev-card h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal Pembatalan</div>
                <div class="h5 mb-0"><?= esc(keu_tanggal($row->tanggal_pembatalan)) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card rev-card h-100">
            <div class="card-body">
                <div class="text-muted small">Jurnal Asal</div>
                <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row->id_jurnal_asal) ?>" class="h5 mb-0 d-block text-decoration-none">
                    <?= esc((string) $row->no_jurnal_asal) ?>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card rev-card h-100">
            <div class="card-body">
                <div class="text-muted small">Jurnal Reversal</div>
                <?php if (!empty($row->id_jurnal_reversal)): ?>
                    <a href="<?= esc(admin_page_url('keuangan/jurnal/detail') . '&id=' . (int) $row->id_jurnal_reversal) ?>" class="h5 mb-0 d-block text-decoration-none">
                        <?= esc((string) $row->no_jurnal_reversal) ?>
                    </a>
                <?php else: ?>
                    <div class="h5 mb-0">-</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card rev-card mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Informasi Pembatalan</h2>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">No Pembatalan</div>
                <div class="fw-semibold"><?= esc((string) $row->no_pembatalan) ?></div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">Jenis Transaksi Asal</div>
                <div class="fw-semibold"><?= esc((string) $row->kode_jenis_transaksi_asal) ?></div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">Sumber</div>
                <div class="fw-semibold">
                    <?= keu_sumber_link($row->tabel_sumber ?? null, $row->id_sumber ?? null, $row->no_sumber ?? null) ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="text-muted small">Nominal</div>
                <div class="fw-semibold"><?= keu_uang($row->total_debit_asal ?? 0) ?></div>
            </div>

            <div class="col-md-12">
                <div class="text-muted small">Alasan Pembatalan</div>
                <div class="fw-semibold"><?= esc((string) $row->alasan_pembatalan) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-6">
        <div class="card rev-card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Jurnal Asal</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Akun</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detailAsal as $d): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= esc((string) $d->kode_coa) ?></div>
                                        <div class="text-muted small"><?= esc((string) $d->nama_coa) ?></div>
                                    </td>
                                    <td class="text-end text-debit fw-semibold"><?= (float) $d->debit > 0 ? keu_uang($d->debit) : '-' ?></td>
                                    <td class="text-end text-kredit fw-semibold"><?= (float) $d->kredit > 0 ? keu_uang($d->kredit) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-end text-debit"><?= keu_uang($row->total_debit_asal ?? 0) ?></th>
                                <th class="text-end text-kredit"><?= keu_uang($row->total_kredit_asal ?? 0) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card rev-card h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Jurnal Reversal</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Akun</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Kredit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($detailReversal->count() === 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">Jurnal reversal belum tersedia.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($detailReversal as $d): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) $d->kode_coa) ?></div>
                                            <div class="text-muted small"><?= esc((string) $d->nama_coa) ?></div>
                                        </td>
                                        <td class="text-end text-debit fw-semibold"><?= (float) $d->debit > 0 ? keu_uang($d->debit) : '-' ?></td>
                                        <td class="text-end text-kredit fw-semibold"><?= (float) $d->kredit > 0 ? keu_uang($d->kredit) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th>Total</th>
                                <th class="text-end text-debit"><?= keu_uang($row->total_debit_reversal ?? 0) ?></th>
                                <th class="text-end text-kredit"><?= keu_uang($row->total_kredit_reversal ?? 0) ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>