<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_biaya_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/biaya');
}

$row = BiayaProduksiORM::query()
    ->from('tb_biaya_produksi as bp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'bp.id_entitas')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'bp.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'bp.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'bp.diubah_oleh')
    ->where('bp.id_entitas', $id_entitas)
    ->where('bp.id_biaya_produksi', $id_biaya_produksi)
    ->select([
        'bp.*',
        'e.nama_entitas',

        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.qty_hasil',
        'pp.status_produksi',

        'pr.kode_produk',
        'pr.nama_produk',

        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data biaya produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

$detail_rows = BiayaProduksiDetailORM::query()
    ->from('tb_biaya_produksi_detail as bpd')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'bpd.id_coa_lawan')
    ->where('bpd.id_biaya_produksi', $id_biaya_produksi)
    ->select([
        'bpd.*',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderBy('bpd.id_biaya_produksi_detail', 'asc')
    ->get();

$status_badge = ((string) ($row->status_posting ?? 'draft') === 'posted') ? 'success' : 'secondary';
$back_param = urlencode($back_url);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Biaya Produksi</h1>
    <p class="page-subtitle">Biaya produksi per perintah produksi dengan banyak jenis biaya</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Header Biaya Produksi</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Biaya Produksi</div>
                <div class="detail-value"><?= esc($row->no_biaya_produksi ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Biaya</div>
                <div class="detail-value"><?= esc($row->tanggal_biaya ?? '-') ?></div>
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
                <div class="detail-label">Total Biaya</div>
                <div class="detail-value fw-semibold text-danger">
                    Rp <?= esc(number_format((float) ($row->jumlah_biaya ?? 0), 2, '.', ',')) ?>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Perintah Produksi</div>
                <div class="detail-value"><?= esc($row->no_perintah_produksi ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Perintah</div>
                <div class="detail-value"><?= esc($row->tanggal_perintah ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Produk</div>
                <div class="detail-value"><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status Produksi</div>
                <div class="detail-value"><?= esc(ucfirst((string) ($row->status_produksi ?? '-'))) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Qty Rencana</div>
                <div class="detail-value"><?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Qty Hasil</div>
                <div class="detail-value"><?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></div>
            </div>

            <div class="col-12">
                <div class="detail-label">Keterangan Header</div>
                <div class="detail-value"><?= esc($row->keterangan ?? '-') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title mb-3">Detail Jenis Biaya</div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Jenis Biaya</th>
                        <th>Akun Lawan / Kredit</th>
                        <th class="text-end">Jumlah Biaya</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail_rows->count() > 0): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($detail_rows as $d): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(ucfirst(str_replace('_', ' ', (string) ($d->jenis_biaya_produksi ?? '-')))) ?></td>
                                <td><?= esc(($d->kode_coa ?? '-') . ' - ' . ($d->nama_coa ?? '-')) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) ($d->jumlah_biaya ?? 0), 2, '.', ',')) ?></td>
                                <td><?= esc($d->keterangan ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Detail biaya produksi belum ada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="text-end">Total</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->jumlah_biaya ?? 0), 2, '.', ',')) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
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
                <a href="<?= esc(admin_page_url('produksi/biaya/edit') . '&id=' . (int) $row->id_biaya_produksi . '&back_url=' . $back_param) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>

                <a href="<?= esc(admin_url('menu/produksi/biaya/posting.php?id=' . (int) $row->id_biaya_produksi . '&back_url=' . $back_param)) ?>"
                   class="btn btn-success"
                   onclick="return confirm('Posting biaya produksi ini? Jurnal biaya produksi akan dibuat otomatis dari template.');">
                    <i class="bi bi-journal-check me-1"></i>Posting
                </a>

                <a href="<?= esc(admin_url('menu/produksi/biaya/hapus.php?id=' . (int) $row->id_biaya_produksi . '&back_url=' . $back_param)) ?>"
                   class="btn btn-outline-danger"
                   onclick="return confirm('Yakin ingin menghapus biaya produksi draft ini?');">
                    <i class="bi bi-trash me-1"></i>Hapus
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('menu/produksi/biaya/cetak.php?id=' . (int) $row->id_biaya_produksi)) ?>"
               class="btn btn-outline-dark"
               target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>