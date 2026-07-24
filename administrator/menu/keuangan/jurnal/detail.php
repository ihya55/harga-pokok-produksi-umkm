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
    <div class="alert alert-danger">Data jurnal tidak ditemukan.</div>
    <a href="<?= esc(admin_page_url('keuangan/jurnal')) ?>" class="btn btn-outline-secondary">
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
        'c.posisi_saldo_normal',
    ])
    ->orderBy('jd.urutan', 'asc')
    ->orderBy('jd.id_jurnal_detail', 'asc')
    ->get();

$params_cetak = [
    'menu' => 'keuangan/jurnal/cetak',
    'id' => $id_jurnal,
];

$url_cetak = admin_url('index.php?' . http_build_query($params_cetak));
?>

<style>
    .journal-info-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
        overflow: hidden;
    }

    .journal-info-head {
        background:
            radial-gradient(circle at top left, rgba(255,255,255,.28), transparent 28%),
            linear-gradient(135deg, #2563eb 0%, #7c3aed 60%, #f97316 135%);
        color: #fff;
        padding: 16px 18px;
    }

    .journal-info-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        padding: 16px 18px;
    }

    .journal-info-item {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 12px;
        min-height: 76px;
    }

    .journal-info-label {
        color: #64748b;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .journal-info-value {
        color: #111827;
        font-weight: 700;
        line-height: 1.25;
        word-break: break-word;
    }

    .journal-info-wide {
        grid-column: span 2;
    }

    .journal-total-card {
        border: 0;
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .08);
    }

    .journal-total-debit {
        background: linear-gradient(135deg, rgba(37, 99, 235, .12), rgba(56, 189, 248, .08));
        border-left: 5px solid #2563eb;
    }

    .journal-total-kredit {
        background: linear-gradient(135deg, rgba(249, 115, 22, .14), rgba(251, 191, 36, .10));
        border-left: 5px solid #f97316;
    }

    .text-debit {
        color: #2563eb !important;
    }

    .text-kredit {
        color: #f97316 !important;
    }

    .row-debit {
        background: rgba(37, 99, 235, .035);
    }

    .row-kredit {
        background: rgba(249, 115, 22, .045);
    }

    .journal-detail-table thead th {
        white-space: nowrap;
        font-size: 12px;
        color: #64748b;
    }

    .journal-detail-table td {
        vertical-align: middle;
    }

    @media (max-width: 992px) {
        .journal-info-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .journal-info-wide {
            grid-column: span 2;
        }
    }

    @media (max-width: 576px) {
        .journal-info-grid {
            grid-template-columns: 1fr;
        }

        .journal-info-wide {
            grid-column: span 1;
        }
    }
</style>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Jurnal</h1>
            <p class="page-subtitle">Rincian header dan detail jurnal akuntansi.</p>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= esc(admin_page_url('keuangan/jurnal')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

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
                <div class="text-muted small">No Jurnal</div>
                <div class="h5 mb-1"><?= esc((string) $row->no_jurnal) ?></div>
                <?= keu_badge_status($row->status_jurnal ?? '-') ?>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal Jurnal</div>
                <div class="h5 mb-1"><?= esc(keu_tanggal($row->tanggal_jurnal)) ?></div>
                <div class="text-muted small"><?= esc((string) ($row->kode_jenis_transaksi ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card journal-total-card h-100">
            <div class="card-body">
                <div class="text-muted small">Total Debit / Kredit</div>

                <div class="d-flex justify-content-between align-items-center rounded-3 px-3 py-2 mb-2 journal-total-debit">
                    <span class="fw-semibold text-debit">Debit</span>
                    <span class="fw-bold text-debit"><?= keu_uang($row->total_debit ?? 0) ?></span>
                </div>

                <div class="d-flex justify-content-between align-items-center rounded-3 px-3 py-2 journal-total-kredit">
                    <span class="fw-semibold text-kredit">Kredit</span>
                    <span class="fw-bold text-kredit"><?= keu_uang($row->total_kredit ?? 0) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card journal-info-card mb-4">
    <div class="journal-info-head">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Informasi Jurnal</h2>
                <div class="small opacity-75">Ringkasan jurnal dalam tampilan kolom.</div>
            </div>

            <div>
                <?= keu_badge_status($row->status_jurnal ?? '-') ?>
            </div>
        </div>
    </div>

    <div class="journal-info-grid">
        <div class="journal-info-item">
            <div class="journal-info-label">No Jurnal</div>
            <div class="journal-info-value"><?= esc((string) $row->no_jurnal) ?></div>
        </div>

        <div class="journal-info-item">
            <div class="journal-info-label">Tanggal</div>
            <div class="journal-info-value"><?= esc(keu_tanggal($row->tanggal_jurnal)) ?></div>
        </div>

        <div class="journal-info-item">
            <div class="journal-info-label">Jenis Transaksi</div>
            <div class="journal-info-value"><?= esc((string) ($row->kode_jenis_transaksi ?? '-')) ?></div>
        </div>

        <div class="journal-info-item">
            <div class="journal-info-label">Sumber</div>
            <div class="journal-info-value">
                <?= keu_sumber_link($row->tabel_sumber ?? null, $row->id_sumber ?? null, $row->no_sumber ?? null) ?>
            </div>
        </div>

        <div class="journal-info-item journal-info-wide">
            <div class="journal-info-label">Keterangan</div>
            <div class="journal-info-value"><?= esc((string) ($row->keterangan ?? '-')) ?></div>
        </div>

        <div class="journal-info-item">
            <div class="journal-info-label">Tanggal Dibuat</div>
            <div class="journal-info-value">
                <?= esc(!empty($row->tanggal_dibuat) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?>
            </div>
        </div>

        <div class="journal-info-item">
            <div class="journal-info-label">Tanggal Posting</div>
            <div class="journal-info-value">
                <?= esc(!empty($row->tanggal_posting) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_posting)) : '-') ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Detail Jurnal</h2>
                <div class="text-muted small">Debit diberi warna biru, kredit diberi warna oren.</div>
            </div>

            <div class="d-flex gap-2 small">
                <span class="badge rounded-pill" style="background:#2563eb;">Debit</span>
                <span class="badge rounded-pill" style="background:#f97316;">Kredit</span>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0 journal-detail-table">
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
                            <?php
                            $debit = (float) ($d->debit ?? 0);
                            $kredit = (float) ($d->kredit ?? 0);
                            $row_class = $debit > 0 ? 'row-debit' : ($kredit > 0 ? 'row-kredit' : '');
                            ?>
                            <tr class="<?= esc($row_class) ?>">
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) $d->kode_coa) ?></div>
                                    <div class="text-muted small"><?= esc((string) $d->nama_coa) ?></div>
                                </td>
                                <td><?= esc((string) ($d->kategori_coa ?? '-')) ?></td>
                                <td><?= esc((string) ($d->keterangan_baris ?? '-')) ?></td>
                                <td class="text-end fw-semibold text-debit">
                                    <?= $debit > 0 ? keu_uang($debit) : '-' ?>
                                </td>
                                <td class="text-end fw-semibold text-kredit">
                                    <?= $kredit > 0 ? keu_uang($kredit) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="text-end fw-bold text-debit"><?= keu_uang($row->total_debit ?? 0) ?></th>
                        <th class="text-end fw-bold text-kredit"><?= keu_uang($row->total_kredit ?? 0) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>