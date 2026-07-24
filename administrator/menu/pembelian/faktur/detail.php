<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_faktur_pembelian = (int) ($_GET['id'] ?? 0);

$row = FakturPembelianORM::query()
    ->from('tb_faktur_pembelian as fp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'fp.id_entitas')
    ->leftJoin('tb_penerimaan_pembelian as pn', 'pn.id_penerimaan_pembelian', '=', 'fp.id_penerimaan_pembelian')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'fp.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'fp.diposting_oleh')
    ->leftJoin('tb_pengguna as u3', 'u3.id_pengguna', '=', 'fp.diubah_oleh')
    ->where('fp.id_entitas', $id_entitas)
    ->where('fp.id_faktur_pembelian', $id_faktur_pembelian)
    ->select([
        'fp.*',
        'e.nama_entitas',
        'pn.no_penerimaan_pembelian',
        'pn.tanggal_penerimaan',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat as alamat_pemasok',
        'p.no_hp as no_hp_pemasok',
        'p.email as email_pemasok',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pemosting',
        'u3.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data faktur pembelian tidak ditemukan.');
    redirect_admin('pembelian/faktur');
}

$detail_rows = FakturPembelianDetailORM::query()
    ->from('tb_faktur_pembelian_detail as d')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_faktur_pembelian', $id_faktur_pembelian)
    ->select([
        'd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('d.id_faktur_pembelian_detail', 'asc')
    ->get();

$status_badge = ((string) ($row->status_faktur ?? 'draft') === 'posted') ? 'success' : 'secondary';
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Faktur Pembelian</h1>
    <p class="page-subtitle">Informasi lengkap faktur pembelian dan utang pemasok</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Header</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
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
                <div class="detail-label">No Penerimaan</div>
                <div class="detail-value"><?= esc($row->no_penerimaan_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Penerimaan</div>
                <div class="detail-value"><?= esc($row->tanggal_penerimaan ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status Faktur</div>
                <div class="detail-value">
                    <span class="badge text-bg-<?= esc($status_badge) ?>">
                        <?= esc(ucfirst((string) ($row->status_faktur ?? '-'))) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Pemasok</div>
                <div class="detail-value"><?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jenis Pembayaran</div>
                <div class="detail-value"><?= esc(ucfirst((string) ($row->jenis_pembayaran ?? '-'))) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jatuh Tempo</div>
                <div class="detail-value"><?= esc($row->jatuh_tempo ?? '-') ?></div>
            </div>

            <div class="col-12">
                <div class="detail-label">Catatan</div>
                <div class="detail-value"><?= esc($row->catatan ?? '-') ?></div>
            </div>
        </div>

        <hr class="my-4">

        <div class="detail-section-title">Ringkasan Nilai</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Subtotal</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->subtotal ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Diskon</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->diskon ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">PPN</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->ppn ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Biaya Kirim / Ongkir Pembelian</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->biaya_kirim ?? 0), 2, '.', ',')) ?></div><div class="text-muted small">Masuk ke Persediaan Bahan Baku saat posting.</div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Total</div>
                <div class="detail-value fw-semibold">Rp <?= esc(number_format((float) ($row->total ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Sisa Utang</div>
                <div class="detail-value fw-semibold">Rp <?= esc(number_format((float) ($row->sisa_utang ?? 0), 2, '.', ',')) ?></div>
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
                <div class="detail-label">Tanggal Posting</div>
                <div class="detail-value"><?= esc((string) ($row->tanggal_posting ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Diposting Oleh</div>
                <div class="detail-value"><?= esc($row->nama_pemosting ?? '-') ?></div>
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
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title mb-3">Detail Faktur</div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Bahan Baku</th>
                        <th class="text-end">Qty</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail_rows->count() > 0): ?>
                        <?php $no = 1; foreach ($detail_rows as $item): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(($item->kode_bahan_baku ?? '-') . ' - ' . ($item->nama_bahan_baku ?? '-')) ?></td>
                                <td class="text-end"><?= esc(number_format((int) $item->qty, 0, '.', ',')) ?></td>
                                <td><?= esc($item->nama_satuan ?? '-') ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->harga, 2, '.', ',')) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->diskon, 2, '.', ',')) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->subtotal, 2, '.', ',')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada detail faktur.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="6" class="text-end">Subtotal</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->subtotal ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">Diskon</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->diskon ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">PPN</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->ppn ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">Total</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->total ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="<?= esc(admin_page_url('pembelian/faktur')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if ((string) ($row->status_faktur ?? 'draft') === 'draft'): ?>
                <a href="<?= esc(admin_page_url('pembelian/faktur/edit') . '&id=' . (int) $row->id_faktur_pembelian) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>

                <a href="<?= esc(admin_url('menu/pembelian/faktur/posting.php?id=' . (int) $row->id_faktur_pembelian)) ?>"
                   class="btn btn-success"
                   onclick="return confirm('Posting faktur ini? Jurnal utang akan dibuat dan faktur tidak bisa diedit.');">
                    <i class="bi bi-journal-check me-1"></i>Posting
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('menu/pembelian/faktur/cetak.php?id=' . (int) $row->id_faktur_pembelian)) ?>"
               class="btn btn-outline-dark"
               target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>  