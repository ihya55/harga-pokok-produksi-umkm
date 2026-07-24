<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_template_jurnal = (int) ($_GET['id'] ?? 0);

$header = TemplateJurnalORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_template_jurnal);

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
    <h1 class="page-title">Simulasi Template Jurnal</h1>
    <p class="page-subtitle"><?= esc($header->kode_template_jurnal . ' - ' . $header->nama_template_jurnal) ?></p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="alert alert-info">
            Simulasi ini menampilkan struktur jurnal dari template yang sudah disusun. Nilai nominal nyata akan diisi pada proses integrasi transaksi berikutnya.
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Urutan</th>
                        <th>Posisi</th>
                        <th>COA</th>
                        <th>Sumber Akun</th>
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
                                <td><?= strtoupper(esc($d->posisi_dc)) ?></td>
                                <td><?= esc(($d->kode_coa ?? '-') . ((isset($d->nama_coa) && $d->nama_coa !== null) ? ' - ' . $d->nama_coa : '')) ?></td>
                                <td><?= esc($d->sumber_akun) ?></td>
                                <td><?= esc($d->sumber_nominal) ?></td>
                                <td><?= esc($d->rumus_nominal ?? '-') ?></td>
                                <td><?= esc($d->keterangan_baris ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Belum ada detail yang bisa disimulasikan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex gap-2 mt-4">
            <a href="<?= esc(admin_page_url('master_setup/template_jurnal')) ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
            <a href="<?= esc(admin_page_url('master_setup/template_jurnal/edit') . '&id=' . (int) $id_template_jurnal) ?>" class="btn btn-gradient"><i class="bi bi-pencil-square me-1"></i>Edit</a>
        </div>
    </div>
</div>