<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pembayaran_penjualan = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if (!function_exists('pembayaran_detail_rupiah')) {
    function pembayaran_detail_rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pembayaran_detail_tanggal')) {
    function pembayaran_detail_tanggal($tanggal, bool $dengan_jam = false): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date($dengan_jam ? 'd/m/Y H:i' : 'd/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('pembayaran_detail_badge_status')) {
    function pembayaran_detail_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        if ($status === 'posted') {
            return '<span class="badge bg-success">Posted</span>';
        }

        if ($status === 'batal') {
            return '<span class="badge bg-danger">Batal</span>';
        }

        return '<span class="badge bg-secondary">Draft</span>';
    }
}

$pembayaran = Capsule::table('tb_pembayaran_penjualan as p')
    ->leftJoin('tb_faktur_penjualan as f', 'f.id_faktur_penjualan', '=', 'p.id_faktur_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'p.id_pelanggan')
    ->leftJoin('tb_coa as coa', 'coa.id_coa', '=', 'p.id_coa_kas_bank')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'p.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'p.diubah_oleh')
    ->where('p.id_entitas', $id_entitas)
    ->where('p.id_pembayaran_penjualan', $id_pembayaran_penjualan)
    ->select([
        'p.*',
        'f.no_faktur_penjualan',
        'f.tanggal_faktur',
        'f.jatuh_tempo',
        'f.total as total_faktur',
        'f.sisa_piutang',
        'f.status_faktur',
        'f.jenis_pembayaran',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.email as email_pelanggan',
        'coa.kode_coa',
        'coa.nama_coa',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$pembayaran) {
    ?>
    <div class="alert alert-danger">
        Data pembayaran penjualan tidak ditemukan.
    </div>

    <a href="<?= esc(admin_page_url('penjualan/pembayaran')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <?php
    return;
}

$jurnal_rows = Capsule::table('tb_log_jurnal_sumber as l')
    ->join('tb_jurnal as j', 'j.id_jurnal', '=', 'l.id_jurnal')
    ->where('l.id_entitas', $id_entitas)
    ->where('l.tabel_sumber', 'tb_pembayaran_penjualan')
    ->where('l.id_sumber', $id_pembayaran_penjualan)
    ->select([
        'j.id_jurnal',
        'j.no_jurnal',
        'j.tanggal_jurnal',
        'j.kode_jenis_transaksi',
        'j.keterangan',
        'j.total_debit',
        'j.total_kredit',
        'j.status_jurnal',
        'l.tabel_sumber',
        'l.id_sumber',
        'l.no_sumber',
    ])
    ->orderBy('j.tanggal_jurnal', 'asc')
    ->orderBy('j.id_jurnal', 'asc')
    ->get();

/*
 * Catatan alur aplikasi:
 * - Pembayaran manual piutang kredit membuat jurnal dengan sumber tb_pembayaran_penjualan.
 * - Pembayaran yang terbentuk otomatis dari POS/COD online biasanya tidak membuat jurnal kedua
 *   di tb_pembayaran_penjualan, karena jurnal penerimaan kas/bank sudah dibuat saat faktur
 *   penjualan terkait diposting.
 *
 * Supaya halaman detail pembayaran tidak menampilkan "Belum ada jurnal" padahal status sudah
 * posted, tampilkan juga jurnal dari faktur penjualan terkait apabila jurnal pembayaran langsung
 * tidak ditemukan.
 */
$jurnal_dari_faktur = false;

if ($jurnal_rows->count() === 0 && (string) ($pembayaran->status_posting ?? '') === 'posted' && !empty($pembayaran->id_faktur_penjualan)) {
    $jurnal_rows = Capsule::table('tb_log_jurnal_sumber as l')
        ->join('tb_jurnal as j', 'j.id_jurnal', '=', 'l.id_jurnal')
        ->where('l.id_entitas', $id_entitas)
        ->where('l.tabel_sumber', 'tb_faktur_penjualan')
        ->where('l.id_sumber', (int) $pembayaran->id_faktur_penjualan)
        ->whereIn('j.kode_jenis_transaksi', [
            'FAKTUR_PENJUALAN_TUNAI',
            'FAKTUR_PENJUALAN_TRANSFER',
            'FAKTUR_PENJUALAN_COD',
            'PENJUALAN_TUNAI',
            'PENJUALAN_TRANSFER',
            'PENJUALAN_COD',
        ])
        ->select([
            'j.id_jurnal',
            'j.no_jurnal',
            'j.tanggal_jurnal',
            'j.kode_jenis_transaksi',
            'j.keterangan',
            'j.total_debit',
            'j.total_kredit',
            'j.status_jurnal',
            'l.tabel_sumber',
            'l.id_sumber',
            'l.no_sumber',
        ])
        ->orderBy('j.tanggal_jurnal', 'asc')
        ->orderBy('j.id_jurnal', 'asc')
        ->get();

    $jurnal_dari_faktur = $jurnal_rows->count() > 0;
}

$jurnal_ids = $jurnal_rows->pluck('id_jurnal')->map(fn ($v) => (int) $v)->all();
$jurnal_detail_rows = collect();

if (!empty($jurnal_ids)) {
    $jurnal_detail_rows = Capsule::table('tb_jurnal_detail as d')
        ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'd.id_coa')
        ->whereIn('d.id_jurnal', $jurnal_ids)
        ->select([
            'd.*',
            'c.kode_coa',
            'c.nama_coa',
        ])
        ->orderBy('d.id_jurnal', 'asc')
        ->orderBy('d.urutan', 'asc')
        ->get()
        ->groupBy('id_jurnal');
}

$url_kembali = $back_url !== '' ? $back_url : admin_page_url('penjualan/pembayaran');
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Pembayaran Penjualan</h1>
            <p class="page-subtitle">Detail penerimaan pembayaran, faktur, akun kas/bank, dan jurnal.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= esc($url_kembali) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_url('index.php?menu=penjualan/pembayaran/cetak&id=' . (int) $pembayaran->id_pembayaran_penjualan)) ?>" class="btn btn-outline-primary" target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>

            <?php if ((string) $pembayaran->status_posting === 'draft'): ?>
                <a
                    href="<?= esc(admin_url('index.php?menu=penjualan/pembayaran/posting&id=' . (int) $pembayaran->id_pembayaran_penjualan)) ?>"
                    class="btn btn-success"
                    onclick="return confirm('Posting pembayaran ini? Jurnal penerimaan kas/bank akan dibuat dan sisa piutang faktur akan berkurang.')">
                    <i class="bi bi-check2-circle me-1"></i>Posting
                </a>

                <a
                    href="<?= esc(admin_url('index.php?menu=penjualan/pembayaran/hapus&id=' . (int) $pembayaran->id_pembayaran_penjualan)) ?>"
                    class="btn btn-outline-danger"
                    onclick="return confirm('Hapus pembayaran draft ini?')">
                    <i class="bi bi-trash me-1"></i>Hapus
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">No Pembayaran</div>
                <div class="h5 mb-1"><?= esc((string) ($pembayaran->no_pembayaran_penjualan ?? '-')) ?></div>
                <?= pembayaran_detail_badge_status((string) ($pembayaran->status_posting ?? 'draft')) ?>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal Pembayaran</div>
                <div class="h5 mb-1"><?= esc(pembayaran_detail_tanggal($pembayaran->tanggal_pembayaran ?? null)) ?></div>
                <div class="text-muted small"><?= esc(ucfirst((string) ($pembayaran->metode_pembayaran ?? '-'))) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Jumlah Bayar</div>
                <div class="h5 mb-1"><?= pembayaran_detail_rupiah($pembayaran->jumlah_bayar ?? 0) ?></div>
                <div class="text-muted small">Nilai penerimaan</div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Sisa Piutang</div>
                <div class="h5 mb-1"><?= pembayaran_detail_rupiah($pembayaran->sisa_piutang ?? 0) ?></div>
                <div class="text-muted small">Setelah pembayaran posted</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Informasi Pembayaran</h2>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <tr>
                            <th width="190" class="text-muted">Faktur</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($pembayaran->no_faktur_penjualan ?? '-')) ?></div>
                                <div class="text-muted small">
                                    Tanggal: <?= esc(pembayaran_detail_tanggal($pembayaran->tanggal_faktur ?? null)) ?>
                                    · Jatuh tempo: <?= esc(pembayaran_detail_tanggal($pembayaran->jatuh_tempo ?? null)) ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Pelanggan</th>
                            <td>
                                <div class="fw-semibold"><?= esc(($pembayaran->kode_pelanggan ?? '-') . ' - ' . ($pembayaran->nama_pelanggan ?? '-')) ?></div>
                                <div class="text-muted small"><?= esc((string) ($pembayaran->no_hp_pelanggan ?? '-')) ?></div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Alamat Pelanggan</th>
                            <td><?= nl2br(esc((string) ($pembayaran->alamat_pelanggan ?? '-'))) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Akun Kas/Bank</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($pembayaran->kode_coa ?? '-')) ?></div>
                                <div class="text-muted small"><?= esc((string) ($pembayaran->nama_coa ?? '-')) ?></div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Catatan</th>
                            <td><?= nl2br(esc((string) ($pembayaran->catatan ?? '-'))) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Dibuat Oleh</th>
                            <td>
                                <?= esc((string) ($pembayaran->nama_pembuat ?? '-')) ?>
                                <div class="text-muted small"><?= esc(pembayaran_detail_tanggal($pembayaran->tanggal_dibuat ?? null, true)) ?></div>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Ringkasan Faktur</h2>

                <table class="table table-sm table-borderless align-middle mb-0">
                    <tr>
                        <th class="text-muted">Total Belanja Kredit</th>
                        <td class="text-end fw-semibold"><?= pembayaran_detail_rupiah($pembayaran->total_faktur ?? 0) ?></td>
                    </tr>

                    <tr>
                        <th class="text-muted">Jumlah Bayar Ini</th>
                        <td class="text-end fw-semibold"><?= pembayaran_detail_rupiah($pembayaran->jumlah_bayar ?? 0) ?></td>
                    </tr>

                    <tr>
                        <th class="text-muted">Sisa Piutang Saat Ini</th>
                        <td class="text-end fw-semibold"><?= pembayaran_detail_rupiah($pembayaran->sisa_piutang ?? 0) ?></td>
                    </tr>
                </table>

                <?php if ((string) $pembayaran->status_posting === 'posted'): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        Pembayaran sudah posted. Jurnal penerimaan sudah dibuat dan sisa piutang faktur sudah dikurangi.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Pembayaran masih draft. Jurnal belum dibuat dan sisa piutang faktur belum berkurang.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Jurnal Terbentuk</h2>
            <?php if ($jurnal_dari_faktur): ?>
                <span class="badge bg-info text-dark">Jurnal dari faktur penjualan terkait</span>
            <?php endif; ?>
        </div>

        <?php if ($jurnal_rows->count() === 0): ?>
            <?php if ((string) ($pembayaran->status_posting ?? '') === 'posted'): ?>
                <div class="alert alert-danger mb-0">
                    Pembayaran sudah posted, tetapi jurnal penerimaan belum ditemukan pada pembayaran maupun faktur terkait.
                    Silakan cek ulang proses posting atau data log jurnal sumber.
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-0">
                    Belum ada jurnal. Jurnal dibuat saat pembayaran diposting.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <?php if ($jurnal_dari_faktur): ?>
                <div class="alert alert-info">
                    Pembayaran ini terbentuk otomatis dari faktur/penjualan. Jurnal penerimaan kas/bank disimpan pada faktur penjualan terkait,
                    sehingga jurnal di bawah ini diambil dari sumber faktur penjualan.
                </div>
            <?php endif; ?>

            <div class="accordion" id="accordionJurnalPembayaran">
                <?php foreach ($jurnal_rows as $idx => $jurnal): ?>
                    <?php
                    $collapse_id = 'jurnalPembayaranCollapse' . (int) $jurnal->id_jurnal;
                    $detail_jurnal = $jurnal_detail_rows->get((int) $jurnal->id_jurnal, collect());
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button
                                class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= esc($collapse_id) ?>">
                                <div class="d-flex justify-content-between align-items-center w-100 pe-3 flex-wrap gap-2">
                                    <span class="fw-semibold"><?= esc((string) ($jurnal->no_jurnal ?? '-')) ?></span>
                                    <span class="text-muted small"><?= esc((string) ($jurnal->kode_jenis_transaksi ?? '-')) ?></span>
                                </div>
                            </button>
                        </h2>

                        <div
                            id="<?= esc($collapse_id) ?>"
                            class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>"
                            data-bs-parent="#accordionJurnalPembayaran">
                            <div class="accordion-body">
                                <div class="table-responsive border rounded">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="130">Kode COA</th>
                                                <th>Akun</th>
                                                <th class="text-end">Debit</th>
                                                <th class="text-end">Kredit</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($detail_jurnal as $d): ?>
                                                <tr>
                                                    <td class="fw-semibold"><?= esc((string) ($d->kode_coa ?? '-')) ?></td>
                                                    <td><?= esc((string) ($d->nama_coa ?? '-')) ?></td>
                                                    <td class="text-end"><?= pembayaran_detail_rupiah($d->debit ?? 0) ?></td>
                                                    <td class="text-end"><?= pembayaran_detail_rupiah($d->kredit ?? 0) ?></td>
                                                    <td><?= esc((string) ($d->keterangan_baris ?? '-')) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>

                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="2" class="text-end">Total</th>
                                                <th class="text-end"><?= pembayaran_detail_rupiah($jurnal->total_debit ?? 0) ?></th>
                                                <th class="text-end"><?= pembayaran_detail_rupiah($jurnal->total_kredit ?? 0) ?></th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>