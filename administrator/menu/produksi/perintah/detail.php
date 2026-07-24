<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_perintah_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/perintah');
}

$row = PerintahProduksiORM::query()
    ->from('tb_perintah_produksi as pp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pp.id_entitas')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_resep as r', 'r.id_resep', '=', 'pp.id_resep')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'pp.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'pp.diubah_oleh')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_perintah_produksi', $id_perintah_produksi)
    ->select([
        'pp.*',
        'e.nama_entitas',
        'pr.kode_produk',
        'pr.nama_produk',
        'pr.jenis_produk',
        'r.kode_resep',
        'r.nama_resep',
        'r.jumlah_hasil',
        'r.versi_resep',
        'ps.no_pesanan_penjualan',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data perintah produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

$resep_detail_rows = Capsule::table('tb_resep_detail as rd')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'rd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'rd.id_satuan')
    ->where('rd.id_resep', (int) $row->id_resep)
    ->select([
        'rd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('rd.id_resep_detail', 'asc')
    ->get();

$status_badge = ((string) ($row->status_produksi ?? 'draft') === 'posted') ? 'success' : 'secondary';
$back_param = urlencode($back_url);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Perintah Produksi</h1>
    <p class="page-subtitle">Informasi lengkap rencana produksi</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Header Perintah Produksi</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Perintah</div>
                <div class="detail-value"><?= esc($row->no_perintah_produksi ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Perintah</div>
                <div class="detail-value"><?= esc($row->tanggal_perintah ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status Produksi</div>
                <div class="detail-value">
                    <span class="badge text-bg-<?= esc($status_badge) ?>">
                        <?= esc(ucfirst((string) ($row->status_produksi ?? '-'))) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Mulai</div>
                <div class="detail-value"><?= esc($row->tanggal_mulai ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Selesai Rencana</div>
                <div class="detail-value"><?= esc($row->tanggal_selesai ?? '-') ?></div>
            </div>

            <div class="col-md-6">
                <div class="detail-label">Produk</div>
                <div class="detail-value"><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></div>
            </div>

            <div class="col-md-6">
                <div class="detail-label">Resep / BOM</div>
                <div class="detail-value"><?= esc(($row->kode_resep ?? '-') . ' - ' . ($row->nama_resep ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Qty Rencana</div>
                <div class="detail-value"><?= esc(number_format((int) ($row->qty_rencana ?? 0), 0, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Qty Hasil</div>
                <div class="detail-value"><?= esc(number_format((int) ($row->qty_hasil ?? 0), 0, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Pesanan Penjualan</div>
                <div class="detail-value"><?= esc($row->no_pesanan_penjualan ?? '-') ?></div>
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
        <div class="detail-section-title mb-3">Detail Resep / BOM</div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Bahan Baku</th>
                        <th class="text-end">Jumlah Pakai / Hasil Resep</th>
                        <th>Satuan</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($resep_detail_rows->count() > 0): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($resep_detail_rows as $d): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(($d->kode_bahan_baku ?? '-') . ' - ' . ($d->nama_bahan_baku ?? '-')) ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($d->jumlah_pakai ?? 0), 3, '.', ',')) ?></td>
                                <td><?= esc($d->nama_satuan ?? '-') ?></td>
                                <td><?= esc($d->keterangan ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Detail resep belum tersedia.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-muted small mt-2">
            Qty detail resep akan dipakai modul Pengambilan Bahan sesuai kebutuhan produksi.
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
        </div>

        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if ((string) ($row->status_produksi ?? 'draft') === 'draft'): ?>
                <a href="<?= esc(admin_page_url('produksi/perintah/edit') . '&id=' . (int) $row->id_perintah_produksi . '&back_url=' . $back_param) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>

                <a href="<?= esc(admin_url('menu/produksi/perintah/posting.php?id=' . (int) $row->id_perintah_produksi . '&back_url=' . $back_param)) ?>"
                   class="btn btn-success"
                   onclick="return confirm('Posting perintah produksi ini? Setelah posted, data bisa digunakan untuk pengambilan bahan, biaya produksi, dan hasil produksi.');">
                    <i class="bi bi-journal-check me-1"></i>Posting
                </a>

                <a href="<?= esc(admin_url('menu/produksi/perintah/hapus.php?id=' . (int) $row->id_perintah_produksi . '&back_url=' . $back_param)) ?>"
                   class="btn btn-outline-danger"
                   onclick="return confirm('Yakin ingin menghapus perintah produksi draft ini?');">
                    <i class="bi bi-trash me-1"></i>Hapus
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('menu/produksi/perintah/cetak.php?id=' . (int) $row->id_perintah_produksi)) ?>"
               class="btn btn-outline-dark"
               target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>