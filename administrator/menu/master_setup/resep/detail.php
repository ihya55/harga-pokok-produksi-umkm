<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_resep = (int) ($_GET['id'] ?? 0);

$row = ResepORM::query()
    ->from('tb_resep as r')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'r.id_entitas')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'r.id_produk')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'r.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'r.diubah_oleh')
    ->where('r.id_entitas', $id_entitas)
    ->where('r.id_resep', $id_resep)
    ->select([
        'r.*',
        'e.nama_entitas',
        'p.kode_produk',
        'p.nama_produk',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data resep tidak ditemukan.');
    redirect_admin('master_setup/resep');
}

$detail_rows = ResepDetailORM::query()
    ->from('tb_resep_detail as d')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'd.id_satuan')
    ->where('d.id_resep', $id_resep)
    ->select([
        'd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->get();
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Resep / BOM</h1>
    <p class="page-subtitle">Informasi lengkap header dan detail resep</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Header Resep</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode Resep</div>
                <div class="detail-value"><?= esc($row->kode_resep ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama Resep</div>
                <div class="detail-value"><?= esc($row->nama_resep ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Produk</div>
                <div class="detail-value"><?= esc(($row->kode_produk ?? '-') . ((isset($row->nama_produk) && $row->nama_produk !== null) ? ' - ' . $row->nama_produk : '')) ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jumlah Hasil</div>
                <div class="detail-value"><?= number_format((float) ($row->jumlah_hasil ?? 0)) ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Versi Resep</div>
                <div class="detail-value"><?= esc($row->versi_resep ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <?php if ((int) ($row->status_aktif ?? 0) === 1): ?>
                        <span class="badge text-bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </div>
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
        <div class="detail-section-title mb-3">Detail Bahan Resep</div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Bahan Baku</th>
                        <th>Jumlah Pakai</th>
                        <th>Satuan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($detail_rows->count() > 0): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($detail_rows as $item): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(($item->kode_bahan_baku ?? '-') . ((isset($item->nama_bahan_baku) && $item->nama_bahan_baku !== null) ? ' - ' . $item->nama_bahan_baku : '')) ?></td>
                                <td><?= number_format((int) round((float) ($item->jumlah_pakai ?? 0)), 0, ',', '.') ?></td>
                                <td><?= esc($item->nama_satuan ?? '-') ?></td>
                                <td><?= esc($item->keterangan ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">Belum ada detail bahan resep.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2 mt-4">
            <a href="<?= esc(admin_page_url('master_setup/resep')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <a href="<?= esc(admin_page_url('master_setup/resep/edit') . '&id=' . (int) $row->id_resep) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>