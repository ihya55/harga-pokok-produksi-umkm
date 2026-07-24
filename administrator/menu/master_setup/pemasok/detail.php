<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../orm/PemasokORM.php';

$id_pemasok = (int) ($_GET['id'] ?? 0);

$row = PemasokORM::query()
    ->from('tb_pemasok as p')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'p.id_entitas')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'p.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'p.diubah_oleh')
    ->where('p.id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->where('p.id_pemasok', $id_pemasok)
    ->select([
        'p.id_pemasok',
        'p.id_entitas',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat',
        'p.no_hp',
        'p.email',
        'p.tempo_hari',
        'p.status_aktif',
        'p.tanggal_dibuat',
        'p.dibuat_oleh',
        'p.tanggal_diubah',
        'p.diubah_oleh',
        'e.nama_entitas',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data pemasok tidak ditemukan.');
    redirect_admin('master_setup/pemasok');
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Pemasok</h1>
    <p class="page-subtitle">Informasi lengkap data pemasok</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title">Data Utama</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode Pemasok</div>
                <div class="detail-value"><?= esc($row->kode_pemasok ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama Pemasok</div>
                <div class="detail-value"><?= esc($row->nama_pemasok ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No. HP</div>
                <div class="detail-value"><?= esc($row->no_hp ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Email</div>
                <div class="detail-value"><?= esc($row->email ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tempo (Hari)</div>
                <div class="detail-value"><?= (int) ($row->tempo_hari ?? 0) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
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
                <div class="detail-label">Alamat</div>
                <div class="detail-value detail-value-multiline"><?= esc($row->alamat ?? '-') ?></div>
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
            <a href="<?= esc(admin_page_url('master_setup/pemasok')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('master_setup/pemasok/edit') . '&id=' . (int) $row->id_pemasok) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>