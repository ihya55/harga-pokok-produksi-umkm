<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_penerimaan_pembelian = (int) ($_GET['id'] ?? 0);

$row = PenerimaanPembelianORM::query()
    ->from('tb_penerimaan_pembelian as ppn')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'ppn.id_entitas')
    ->leftJoin('tb_pesanan_pembelian as po', 'po.id_pesanan_pembelian', '=', 'ppn.id_pesanan_pembelian')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'ppn.id_pemasok')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ppn.id_gudang')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'ppn.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'ppn.diposting_oleh')
    ->leftJoin('tb_pengguna as u3', 'u3.id_pengguna', '=', 'ppn.diubah_oleh')
    ->where('ppn.id_entitas', $id_entitas)
    ->where('ppn.id_penerimaan_pembelian', $id_penerimaan_pembelian)
    ->select([
        'ppn.*',
        'e.nama_entitas',
        'po.no_pesanan_pembelian',
        'po.tanggal_pesanan',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat as alamat_pemasok',
        'p.no_hp as no_hp_pemasok',
        'p.email as email_pemasok',
        'g.kode_gudang',
        'g.nama_gudang',
        'g.jenis_gudang',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pemosting',
        'u3.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data penerimaan pembelian tidak ditemukan.');
    redirect_admin('pembelian/penerimaan');
}

$detail_rows = PenerimaanPembelianDetailORM::query()
    ->from('tb_penerimaan_pembelian_detail as d')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_penerimaan_pembelian', $id_penerimaan_pembelian)
    ->select([
        'd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('d.id_penerimaan_pembelian_detail', 'asc')
    ->get();

$status_badge = ((string) ($row->status_penerimaan ?? 'draft') === 'posted') ? 'success' : 'secondary';

$total_nilai = 0;
foreach ($detail_rows as $item) {
    $total_nilai += (float) $item->subtotal;
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Penerimaan Pembelian</h1>
    <p class="page-subtitle">Informasi lengkap dokumen penerimaan pembelian bahan baku</p>
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
                <div class="detail-label">No Penerimaan</div>
                <div class="detail-value"><?= esc($row->no_penerimaan_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Penerimaan</div>
                <div class="detail-value"><?= esc($row->tanggal_penerimaan ?? '-') ?></div>
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
                <div class="detail-label">Status Penerimaan</div>
                <div class="detail-value">
                    <span class="badge text-bg-<?= esc($status_badge) ?>">
                        <?= esc(ucfirst((string) ($row->status_penerimaan ?? '-'))) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Pemasok</div>
                <div class="detail-value">
                    <?= esc(($row->kode_pemasok ?? '-') . ((isset($row->nama_pemasok) && $row->nama_pemasok !== null) ? ' - ' . $row->nama_pemasok : '')) ?>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No HP Pemasok</div>
                <div class="detail-value"><?= esc($row->no_hp_pemasok ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Email Pemasok</div>
                <div class="detail-value"><?= esc($row->email_pemasok ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Gudang</div>
                <div class="detail-value">
                    <?= esc(($row->kode_gudang ?? '-') . ((isset($row->nama_gudang) && $row->nama_gudang !== null) ? ' - ' . $row->nama_gudang : '')) ?>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jenis Gudang</div>
                <div class="detail-value"><?= esc($row->jenis_gudang ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Total Nilai Diterima</div>
                <div class="detail-value fw-semibold">Rp <?= esc(number_format((float) $total_nilai, 2, '.', ',')) ?></div>
            </div>

            <div class="col-12">
                <div class="detail-label">Alamat Pemasok</div>
                <div class="detail-value"><?= esc($row->alamat_pemasok ?? '-') ?></div>
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
        <div class="detail-section-title mb-3">Detail Bahan Diterima</div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Bahan Baku</th>
                        <th class="text-end">Qty</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga</th>
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
                                <td class="text-end">Rp <?= esc(number_format((float) $item->subtotal, 2, '.', ',')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada detail bahan diterima.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end">Total Nilai Diterima</th>
                        <th class="text-end">Rp <?= esc(number_format((float) $total_nilai, 2, '.', ',')) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="<?= esc(admin_page_url('pembelian/penerimaan')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if ((string) ($row->status_penerimaan ?? 'draft') === 'draft'): ?>
                <a href="<?= esc(admin_page_url('pembelian/penerimaan/edit') . '&id=' . (int) $row->id_penerimaan_pembelian) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>

                <a href="<?= esc(admin_url('menu/pembelian/penerimaan/posting.php?id=' . (int) $row->id_penerimaan_pembelian)) ?>"
                   class="btn btn-success"
                   onclick="return confirm('Posting penerimaan ini? Setelah diposting, stok bahan baku akan bertambah dan dokumen tidak bisa diedit.');">
                    <i class="bi bi-box-arrow-in-down me-1"></i>Posting
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('menu/pembelian/penerimaan/cetak.php?id=' . (int) $row->id_penerimaan_pembelian)) ?>"
               class="btn btn-outline-dark"
               target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>