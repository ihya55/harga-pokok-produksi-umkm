<?php
declare(strict_types=1);

$id_coa = (int) ($_GET['id'] ?? 0);

$row = CoaORM::query()
    ->from('tb_coa as c')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'c.id_entitas')
    ->leftJoin('tb_coa as p', 'p.id_coa', '=', 'c.id_coa_induk')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'c.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'c.diubah_oleh')
    ->where('c.id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->where('c.id_coa', $id_coa)
    ->select([
        'c.*',
        'e.nama_entitas',
        'p.kode_coa as kode_coa_induk',
        'p.nama_coa as nama_coa_induk',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data COA tidak ditemukan.');
    redirect_admin('master_setup/coa');
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail COA</h1>
    <p class="page-subtitle">Informasi lengkap data COA</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title">Data Utama</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode COA</div>
                <div class="detail-value"><?= esc($row->kode_coa ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama COA</div>
                <div class="detail-value"><?= esc($row->nama_coa ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">COA Induk</div>
                <div class="detail-value">
                    <?= esc(
                        ($row->kode_coa_induk ?? '-') .
                        ((isset($row->nama_coa_induk) && $row->nama_coa_induk !== null) ? ' - ' . $row->nama_coa_induk : '')
                    ) ?>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Level COA</div>
                <div class="detail-value"><?= esc((string) ($row->level_coa ?? '1')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kategori COA</div>
                <div class="detail-value"><?= esc($row->kategori_coa ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Posisi Saldo Normal</div>
                <div class="detail-value"><?= esc($row->posisi_saldo_normal ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Boleh Transaksi</div>
                <div class="detail-value"><?= ((int) ($row->boleh_transaksi ?? 0) === 1) ? 'Ya' : 'Tidak' ?></div>
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
            <a href="<?= esc(admin_page_url('master_setup/coa')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('master_setup/coa/edit') . '&id=' . (int) $row->id_coa) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>