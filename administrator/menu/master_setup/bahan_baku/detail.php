<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_bahan_baku = (int) ($_GET['id'] ?? 0);

$row = BahanBakuORM::query()
    ->from('tb_bahan_baku as b')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'b.id_entitas')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'b.id_coa_persediaan')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'b.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'b.diubah_oleh')
    ->where('b.id_entitas', $id_entitas)
    ->where('b.id_bahan_baku', $id_bahan_baku)
    ->select([
        'b.*',
        'e.nama_entitas',
        's.nama_satuan',
        'c.kode_coa',
        'c.nama_coa',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data bahan baku tidak ditemukan.');
    redirect_admin('master_setup/bahan_baku');
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Bahan Baku</h1>
    <p class="page-subtitle">Informasi lengkap data bahan baku</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title">Data Utama</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode Bahan</div>
                <div class="detail-value"><?= esc($row->kode_bahan_baku ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama Bahan</div>
                <div class="detail-value"><?= esc($row->nama_bahan_baku ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Satuan</div>
                <div class="detail-value"><?= esc($row->nama_satuan ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Harga Standar</div>
                <div class="detail-value"><?= 'Rp ' . number_format((float) ($row->harga_standar ?? 0), 2, ',', '.') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Stok Minimum</div>
                <div class="detail-value"><?= (int) ($row->stok_minimum ?? 0) ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">COA Persediaan</div>
                <div class="detail-value"><?= esc(($row->kode_coa ?? '-') . ((isset($row->nama_coa) && $row->nama_coa !== null) ? ' - ' . $row->nama_coa : '')) ?></div>
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

        <div class="d-flex gap-2 mt-4">
            <a href="<?= esc(admin_page_url('master_setup/bahan_baku')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <a href="<?= esc(admin_page_url('master_setup/bahan_baku/edit') . '&id=' . (int) $row->id_bahan_baku) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>