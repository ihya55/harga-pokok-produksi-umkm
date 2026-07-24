<?php
declare(strict_types=1);

$id_template_jurnal = (int) ($_GET['id'] ?? 0);
$id_entitas = (int) ($user['id_entitas'] ?? 0);

$header = TemplateJurnalORM::query()
    ->from('tb_template_jurnal as t')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 't.id_entitas')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 't.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 't.diubah_oleh')
    ->where('t.id_entitas', $id_entitas)
    ->where('t.id_template_jurnal', $id_template_jurnal)
    ->select([
        't.*',
        'e.nama_entitas',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$header) {
    set_flash('error', 'Data template jurnal tidak ditemukan.');
    redirect_admin('master_setup/template_jurnal');
}

$details = TemplateJurnalDetailORM::query()
    ->from('tb_template_jurnal_detail as d')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'd.id_coa_default')
    ->where('d.id_template_jurnal', $id_template_jurnal)
    ->select([
        'd.*',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderBy('d.urutan', 'asc')
    ->get();
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Template Jurnal</h1>
    <p class="page-subtitle">Informasi lengkap header dan detail template jurnal</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Header Template</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode Template</div>
                <div class="detail-value"><?= esc($header->kode_template_jurnal ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama Template</div>
                <div class="detail-value"><?= esc($header->nama_template_jurnal ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jenis Transaksi</div>
                <div class="detail-value"><?= esc($header->kode_jenis_transaksi ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($header->nama_entitas ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <?php if ((int) ($header->status_aktif ?? 0) === 1): ?>
                        <span class="badge text-bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-12">
                <div class="detail-label">Keterangan</div>
                <div class="detail-value detail-value-multiline"><?= esc($header->keterangan ?? '-') ?></div>
            </div>
        </div>

        <hr class="my-4">

        <div class="detail-section-title">Informasi Audit</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Tanggal Dibuat</div>
                <div class="detail-value"><?= esc((string) ($header->tanggal_dibuat ?? '-')) ?></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Dibuat Oleh</div>
                <div class="detail-value"><?= esc($header->nama_pembuat ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Tanggal Diubah</div>
                <div class="detail-value"><?= esc((string) ($header->tanggal_diubah ?? '-')) ?></div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Diubah Oleh</div>
                <div class="detail-value"><?= esc($header->nama_pengubah ?? '-') ?></div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <a href="<?= esc(admin_page_url('master_setup/template_jurnal')) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
            <a href="<?= esc(admin_page_url('master_setup/template_jurnal/edit') . '&id=' . (int) $header->id_template_jurnal) ?>" class="btn btn-gradient"><i class="bi bi-pencil-square me-1"></i>Edit</a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-light fw-semibold">Detail Baris Template</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Urutan</th>
                        <th>Posisi</th>
                        <th>Sumber Akun</th>
                        <th>COA Default</th>
                        <th>Sumber Nominal</th>
                        <th>Rumus</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($details->count() > 0): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($details as $d): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= (int) $d->urutan ?></td>
                                <td><?= esc($d->posisi_dc) ?></td>
                                <td><?= esc($d->sumber_akun) ?></td>
                                <td><?= esc(($d->kode_coa ?? '-') . ((isset($d->nama_coa) && $d->nama_coa !== null) ? ' - ' . $d->nama_coa : '')) ?></td>
                                <td><?= esc($d->sumber_nominal) ?></td>
                                <td><?= esc($d->rumus_nominal ?? '-') ?></td>
                                <td><?= esc($d->keterangan_baris ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada detail template jurnal.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>