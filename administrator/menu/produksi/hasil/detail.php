<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_hasil_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/hasil');
}

$row = HasilProduksiORM::query()
    ->from('tb_hasil_produksi as hp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'hp.id_entitas')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'hp.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'hp.id_produk')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'hp.id_gudang')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'hp.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'hp.diubah_oleh')
    ->leftJoin('tb_pengguna as u3', 'u3.id_pengguna', '=', 'hp.diposting_oleh')
    ->where('hp.id_entitas', $id_entitas)
    ->where('hp.id_hasil_produksi', $id_hasil_produksi)
    ->select([
        'hp.*',
        'e.nama_entitas',
        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.status_produksi',
        'pr.kode_produk',
        'pr.nama_produk',
        'pr.id_coa_persediaan',
        'pr.id_coa_hpp',
        'g.kode_gudang',
        'g.nama_gudang',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
        'u3.nama_lengkap as nama_posting',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data hasil produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

$status_row = (string) ($row->status_posting ?? 'draft');
$badge = $status_row === 'posted' ? 'success' : 'secondary';
$back_param = urlencode($back_url);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Hasil Produksi</h1>
    <p class="page-subtitle">Detail hasil produksi dan perhitungan HPP</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Header Hasil Produksi</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Hasil Produksi</div>
                <div class="detail-value"><?= esc($row->no_hasil_produksi ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Hasil</div>
                <div class="detail-value"><?= esc($row->tanggal_hasil ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status Posting</div>
                <div class="detail-value">
                    <span class="badge text-bg-<?= esc($badge) ?>">
                        <?= esc(ucfirst($status_row)) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Perintah Produksi</div>
                <div class="detail-value"><?= esc($row->no_perintah_produksi ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Perintah</div>
                <div class="detail-value"><?= esc($row->tanggal_perintah ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Produk</div>
                <div class="detail-value"><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Gudang Hasil</div>
                <div class="detail-value"><?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Qty Rencana</div>
                <div class="detail-value"><?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Qty Hasil</div>
                <div class="detail-value fw-semibold"><?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></div>
            </div>

            <div class="col-12">
                <div class="detail-label">Catatan</div>
                <div class="detail-value"><?= esc($row->catatan ?? '-') ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title mb-3">Ringkasan HPP</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Biaya Bahan</div>
                    <div class="h5 mb-0">Rp <?= esc(number_format((float) ($row->total_biaya_bahan ?? 0), 2, '.', ',')) ?></div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Biaya Tenaga Kerja</div>
                    <div class="h5 mb-0">Rp <?= esc(number_format((float) ($row->total_biaya_tenaga_kerja ?? 0), 2, '.', ',')) ?></div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Biaya BOP</div>
                    <div class="h5 mb-0">Rp <?= esc(number_format((float) ($row->total_biaya_bop ?? 0), 2, '.', ',')) ?></div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-muted small mb-1">Total HPP</div>
                    <div class="h5 mb-0 text-danger">Rp <?= esc(number_format((float) ($row->total_hpp ?? 0), 2, '.', ',')) ?></div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="border rounded p-3 h-100 bg-light">
                    <div class="text-muted small mb-1">HPP per Unit</div>
                    <div class="h5 mb-0 text-primary">Rp <?= esc(number_format((float) ($row->hpp_per_unit ?? 0), 2, '.', ',')) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
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

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Tanggal Posting</div>
                <div class="detail-value"><?= esc((string) ($row->tanggal_posting ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Diposting Oleh</div>
                <div class="detail-value"><?= esc($row->nama_posting ?? '-') ?></div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if ($status_row === 'draft'): ?>
                <a href="<?= esc(admin_page_url('produksi/hasil/edit') . '&id=' . (int) $row->id_hasil_produksi . '&back_url=' . $back_param) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>

                <a href="<?= esc(admin_url('index.php?menu=produksi/hasil/posting&id=' . (int) $row->id_hasil_produksi . '&back_url=' . $back_param)) ?>"
                   class="btn btn-success"
                   onclick="return confirm('Posting hasil produksi ini? Stok produk jadi akan bertambah dan jurnal akan dibuat.');">
                    <i class="bi bi-journal-check me-1"></i>Posting
                </a>

                <a href="<?= esc(admin_url('menu/produksi/hasil/hapus.php?id=' . (int) $row->id_hasil_produksi . '&back_url=' . $back_param)) ?>"
                   class="btn btn-outline-danger"
                   onclick="return confirm('Yakin ingin menghapus hasil produksi draft ini?');">
                    <i class="bi bi-trash me-1"></i>Hapus
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('index.php?menu=produksi/hasil/cetak&id=' . (int) $row->id_hasil_produksi)) ?>"
               class="btn btn-outline-dark"
               target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>