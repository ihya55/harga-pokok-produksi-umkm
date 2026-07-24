<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pesanan_pembelian = (int) ($_GET['id'] ?? 0);

$row = PesananPembelianORM::query()
    ->from('tb_pesanan_pembelian as pp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pp.id_entitas')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'pp.id_pemasok')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'pp.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'pp.diubah_oleh')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_pesanan_pembelian', $id_pesanan_pembelian)
    ->select([
        'pp.*',
        'e.nama_entitas',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat',
        'p.no_hp',
        'p.email',
        'p.tempo_hari',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data pesanan pembelian tidak ditemukan.');
    redirect_admin('pembelian/pesanan');
}

$detail_rows = PesananPembelianDetailORM::query()
    ->from('tb_pesanan_pembelian_detail as d')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_pesanan_pembelian', $id_pesanan_pembelian)
    ->select([
        'd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('d.id_pesanan_pembelian_detail', 'asc')
    ->get();

$status_badge = ((string) ($row->status_pesanan ?? 'draft') === 'terkonfirmasi') ? 'success' : 'secondary';
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Pesanan Pembelian</h1>
    <p class="page-subtitle">Informasi lengkap dokumen pesanan pembelian bahan baku</p>
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
                <div class="detail-label">No Pesanan Pembelian</div>
                <div class="detail-value"><?= esc($row->no_pesanan_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Pesanan</div>
                <div class="detail-value"><?= esc($row->tanggal_pesanan ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Pemasok</div>
                <div class="detail-value">
                    <?= esc(($row->kode_pemasok ?? '-') . ((isset($row->nama_pemasok) && $row->nama_pemasok !== null) ? ' - ' . $row->nama_pemasok : '')) ?>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Datang Rencana</div>
                <div class="detail-value"><?= esc($row->tanggal_datang_rencana ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status Pesanan</div>
                <div class="detail-value">
                    <span class="badge text-bg-<?= esc($status_badge) ?>">
                        <?= esc(ucfirst((string) ($row->status_pesanan ?? '-'))) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No HP Pemasok</div>
                <div class="detail-value"><?= esc($row->no_hp ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Email Pemasok</div>
                <div class="detail-value"><?= esc($row->email ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tempo Hari</div>
                <div class="detail-value"><?= esc((string) ($row->tempo_hari ?? '0')) ?> hari</div>
            </div>

            <div class="col-12">
                <div class="detail-label">Alamat Pemasok</div>
                <div class="detail-value"><?= esc($row->alamat ?? '-') ?></div>
            </div>

            <div class="col-12">
                <div class="detail-label">Catatan</div>
                <div class="detail-value"><?= esc($row->catatan ?? '-') ?></div>
            </div>
        </div>

        <hr class="my-4">

        <div class="detail-section-title">Ringkasan Nilai</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Subtotal</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->subtotal ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Diskon Header</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->diskon ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Total</div>
                <div class="detail-value fw-semibold">Rp <?= esc(number_format((float) ($row->total ?? 0), 2, '.', ',')) ?></div>
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
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title mb-3">Detail Bahan Baku</div>

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
                                <td>
                                    <?= esc(($item->kode_bahan_baku ?? '-') . ((isset($item->nama_bahan_baku) && $item->nama_bahan_baku !== null) ? ' - ' . $item->nama_bahan_baku : '')) ?>
                                </td>
                                <td class="text-end"><?= esc(number_format((int) $item->qty, 0, '.', ',')) ?></td>
                                <td><?= esc($item->nama_satuan ?? '-') ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->harga, 2, '.', ',')) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->diskon, 2, '.', ',')) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->subtotal, 2, '.', ',')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada detail bahan baku.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="6" class="text-end">Subtotal</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->subtotal ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">Diskon Header</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->diskon ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">Total</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->total ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="<?= esc(admin_page_url('pembelian/pesanan')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if ((string) ($row->status_pesanan ?? 'draft') === 'draft'): ?>
                <a href="<?= esc(admin_page_url('pembelian/pesanan/edit') . '&id=' . (int) $row->id_pesanan_pembelian) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>

                <a href="<?= esc(admin_url('menu/pembelian/pesanan/konfirmasi.php?id=' . (int) $row->id_pesanan_pembelian)) ?>"
                   class="btn btn-success"
                   onclick="return confirm('Konfirmasi pesanan pembelian ini? Setelah dikonfirmasi, pesanan tidak bisa diedit.');">
                    <i class="bi bi-check-circle me-1"></i>Konfirmasi
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('menu/pembelian/pesanan/cetak.php?id=' . (int) $row->id_pesanan_pembelian)) ?>"
               class="btn btn-outline-dark"
               target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak PO
            </a>
        </div>
    </div>
</div>