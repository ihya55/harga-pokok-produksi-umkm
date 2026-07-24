<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_konfigurasi_akun = (int) ($_GET['id'] ?? 0);

$row = KonfigurasiAkunORM::query()
    ->from('tb_konfigurasi_akun as k')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'k.id_entitas')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'k.id_coa')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'k.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'k.diubah_oleh')
    ->where('k.id_entitas', $id_entitas)
    ->where('k.id_konfigurasi_akun', $id_konfigurasi_akun)
    ->select([
        'k.*',
        'e.nama_entitas',
        'c.kode_coa',
        'c.nama_coa',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data konfigurasi akun tidak ditemukan.');
    redirect_admin('master_setup/konfigurasi_akun');
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Konfigurasi Akun</h1>
    <p class="page-subtitle">Informasi lengkap konfigurasi akun</p>
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
                <div class="detail-label">Kode Konfigurasi</div>
                <div class="detail-value"><?= esc($row->kode_konfigurasi ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama Konfigurasi</div>
                <div class="detail-value"><?= esc($row->nama_konfigurasi ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Digunakan di Menu / Modul</div>
                <div class="detail-value"><?= esc($row->digunakan_di_menu ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">COA</div>
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
            <div class="col-12">
                <div class="detail-label">Keterangan</div>
                <div class="detail-value"><?= esc($row->keterangan ?? '-') ?></div>
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
            <a href="<?= esc(admin_page_url('master_setup/konfigurasi_akun')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <a href="<?= esc(admin_page_url('master_setup/konfigurasi_akun/edit') . '&id=' . (int) $row->id_konfigurasi_akun) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>