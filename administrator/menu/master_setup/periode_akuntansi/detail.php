<?php
declare(strict_types=1);

$id_periode = (int) ($_GET['id'] ?? 0);

$row = PeriodeAkuntansiORM::query()
    ->from('tb_periode_akuntansi as p')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'p.id_entitas')
    ->leftJoin('tb_pengguna as u', 'u.id_pengguna', '=', 'p.dibuat_oleh')
    ->where('p.id_entitas', (int) ($user['id_entitas'] ?? 0))
    ->where('p.id_periode', $id_periode)
    ->select([
        'p.*',
        'e.nama_entitas',
        'u.nama_lengkap as nama_pembuat',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data periode akuntansi tidak ditemukan.');
    redirect_admin('master_setup/periode_akuntansi');
}

function nama_bulan_detail(int $bulan): string
{
    $list = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    return $list[$bulan] ?? (string) $bulan;
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Periode Akuntansi</h1>
    <p class="page-subtitle">Informasi lengkap data periode akuntansi</p>
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
                <div class="detail-label">Tahun</div>
                <div class="detail-value"><?= esc((string) $row->tahun) ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Bulan</div>
                <div class="detail-value"><?= esc(nama_bulan_detail((int) $row->bulan)) ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Mulai</div>
                <div class="detail-value"><?= esc((string) $row->tanggal_mulai) ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Selesai</div>
                <div class="detail-value"><?= esc((string) $row->tanggal_selesai) ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status Periode</div>
                <div class="detail-value">
                    <?php if (($row->status_periode ?? '') === 'terbuka'): ?>
                        <span class="badge text-bg-success">Terbuka</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">Tertutup</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr class="my-4">

        <div class="detail-section-title">Informasi Audit</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Dibuat</div>
                <div class="detail-value"><?= esc((string) ($row->tanggal_dibuat ?? '-')) ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Dibuat Oleh</div>
                <div class="detail-value"><?= esc($row->nama_pembuat ?? '-') ?></div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <a href="<?= esc(admin_page_url('master_setup/periode_akuntansi')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('master_setup/periode_akuntansi/edit') . '&id=' . (int) $row->id_periode) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>