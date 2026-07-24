<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pembayaran_pembelian = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('pembelian/pembayaran');
}

$row = PembayaranPembelianORM::query()
    ->from('tb_pembayaran_pembelian as pb')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pb.id_entitas')
    ->leftJoin('tb_faktur_pembelian as fp', 'fp.id_faktur_pembelian', '=', 'pb.id_faktur_pembelian')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'pb.id_pemasok')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'pb.id_coa_kas_bank')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'pb.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'pb.diubah_oleh')
    ->where('pb.id_entitas', $id_entitas)
    ->where('pb.id_pembayaran_pembelian', $id_pembayaran_pembelian)
    ->select([
        'pb.*',
        'e.nama_entitas',
        'fp.no_faktur_pembelian',
        'fp.tanggal_faktur',
        'fp.total as total_faktur',
        'fp.sisa_utang',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat as alamat_pemasok',
        'p.no_hp as no_hp_pemasok',
        'p.email as email_pemasok',
        'c.kode_coa',
        'c.nama_coa',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data pembayaran pembelian tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

$status_badge = ((string) ($row->status_posting ?? 'draft') === 'posted') ? 'success' : 'secondary';
$back_param = urlencode($back_url);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Pembayaran Pembelian</h1>
    <p class="page-subtitle">Informasi lengkap pembayaran utang faktur pembelian</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title">Header</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Pembayaran</div>
                <div class="detail-value"><?= esc($row->no_pembayaran_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Pembayaran</div>
                <div class="detail-value"><?= esc($row->tanggal_pembayaran ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Faktur</div>
                <div class="detail-value"><?= esc($row->no_faktur_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Faktur</div>
                <div class="detail-value"><?= esc($row->tanggal_faktur ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status Posting</div>
                <div class="detail-value">
                    <span class="badge text-bg-<?= esc($status_badge) ?>">
                        <?= esc(ucfirst((string) ($row->status_posting ?? '-'))) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Pemasok</div>
                <div class="detail-value"><?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Metode Pembayaran</div>
                <div class="detail-value"><?= esc(ucfirst((string) ($row->metode_pembayaran ?? '-'))) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Akun Kas/Bank</div>
                <div class="detail-value"><?= esc(($row->kode_coa ?? '-') . ' - ' . ($row->nama_coa ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Total Faktur</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->total_faktur ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jumlah Bayar</div>
                <div class="detail-value fw-semibold">Rp <?= esc(number_format((float) ($row->jumlah_bayar ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Sisa Utang Saat Ini</div>
                <div class="detail-value fw-semibold">Rp <?= esc(number_format((float) ($row->sisa_utang ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-12">
                <div class="detail-label">Catatan</div>
                <div class="detail-value"><?= esc($row->catatan ?? '-') ?></div>
            </div>
        </div>

        <hr class="my-4">

        <div class="detail-section-title">Informasi Audit</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Tanggal Dibuat</div>
                <div class="detail-value"><?= esc((string) ($row->tanggal_dibuat ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Dibuat Oleh</div>
                <div class="detail-value"><?= esc($row->nama_pembuat ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Tanggal Diubah</div>
                <div class="detail-value"><?= esc((string) ($row->tanggal_diubah ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Diubah Oleh</div>
                <div class="detail-value"><?= esc($row->nama_pengubah ?? '-') ?></div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if ((string) ($row->status_posting ?? 'draft') === 'draft'): ?>
                <a href="<?= esc(admin_page_url('pembelian/pembayaran/edit') . '&id=' . (int) $row->id_pembayaran_pembelian . '&back_url=' . $back_param) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>

                <a href="<?= esc(admin_url('menu/pembelian/pembayaran/posting.php?id=' . (int) $row->id_pembayaran_pembelian . '&back_url=' . $back_param)) ?>"
                   class="btn btn-success"
                   onclick="return confirm('Posting pembayaran ini? Sisa utang faktur akan berkurang dan jurnal pembayaran akan dibuat.');">
                    <i class="bi bi-journal-check me-1"></i>Posting
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('menu/pembelian/pembayaran/cetak.php?id=' . (int) $row->id_pembayaran_pembelian)) ?>"
               class="btn btn-outline-dark"
               target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>