<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../orm/KategoriProdukORM.php';

$id_kategori_produk = (int) ($_GET['id'] ?? 0);

$row = KategoriProdukORM::query()
    ->from('tb_kategori_produk as kp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'kp.id_entitas')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'kp.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'kp.diubah_oleh')
    ->where('kp.id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->where('kp.id_kategori_produk', $id_kategori_produk)
    ->select([
        'kp.id_kategori_produk',
        'kp.id_entitas',
        'kp.kode_kategori_produk',
        'kp.nama_kategori_produk',
        'kp.keterangan',
        'kp.status_aktif',
        'kp.tanggal_dibuat',
        'kp.dibuat_oleh',
        'kp.tanggal_diubah',
        'kp.diubah_oleh',
        'e.nama_entitas',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data kategori produk tidak ditemukan.');
    redirect_admin('master_setup/kategori_produk');
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Kategori Produk</h1>
    <p class="page-subtitle">Informasi lengkap data kategori produk</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title">Data Utama</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode Kategori</div>
                <div class="detail-value"><?= esc($row->kode_kategori_produk ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama Kategori Produk</div>
                <div class="detail-value"><?= esc($row->nama_kategori_produk ?? '-') ?></div>
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
                <div class="detail-label">Keterangan</div>
                <div class="detail-value detail-value-multiline"><?= esc($row->keterangan ?? '-') ?></div>
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
            <a href="<?= esc(admin_page_url('master_setup/kategori_produk')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('master_setup/kategori_produk/edit') . '&id=' . (int) $row->id_kategori_produk) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>