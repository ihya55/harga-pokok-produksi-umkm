<?php
declare(strict_types=1);

$id_gudang = (int) ($_GET['id'] ?? 0);

$row = GudangORM::query()
    ->from('tb_gudang as g')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'g.id_entitas')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'g.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'g.diubah_oleh')
    ->where('g.id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->where('g.id_gudang', $id_gudang)
    ->select([
        'g.id_gudang',
        'g.id_entitas',
        'g.kode_gudang',
        'g.nama_gudang',
        'g.jenis_gudang',
        'g.alamat',
        'g.status_aktif',
        'g.tanggal_dibuat',
        'g.dibuat_oleh',
        Illuminate\Database\Capsule\Manager::raw('NULL as tanggal_diubah'),
        Illuminate\Database\Capsule\Manager::raw('NULL as diubah_oleh'),
        'e.nama_entitas',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data gudang tidak ditemukan.');
    redirect_admin('master_setup/gudang');
}

$jenisGudangLabel = match ((string) ($row->jenis_gudang ?? '')) {
    'bahan_baku'  => 'Bahan Baku',
    'barang_jadi' => 'Barang Jadi',
    'toko'        => 'Toko',
    'lainnya'     => 'Lainnya',
    default       => (string) ($row->jenis_gudang ?? '-'),
};

$statusLabel = ((int) ($row->status_aktif ?? 0) === 1) ? 'Aktif' : 'Nonaktif';
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Gudang</h1>
    <p class="page-subtitle">Informasi lengkap data gudang</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title">Data Utama</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode Gudang</div>
                <div class="detail-value"><?= esc($row->kode_gudang ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama Gudang</div>
                <div class="detail-value"><?= esc($row->nama_gudang ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jenis Gudang</div>
                <div class="detail-value"><?= esc($jenisGudangLabel) ?></div>
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

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">ID Gudang</div>
                <div class="detail-value"><?= (int) $row->id_gudang ?></div>
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
            <a href="<?= esc(admin_page_url('master_setup/gudang')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('master_setup/gudang/edit') . '&id=' . (int) $row->id_gudang) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>