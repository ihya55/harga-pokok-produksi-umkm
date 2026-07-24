<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pengambilan_bahan = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('produksi/pengambilan-bahan');
}

$row = PengambilanBahanORM::query()
    ->from('tb_pengambilan_bahan as pb')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'pb.id_entitas')
    ->leftJoin('tb_perintah_produksi as pp', 'pp.id_perintah_produksi', '=', 'pb.id_perintah_produksi')
    ->leftJoin('tb_produk as pr', 'pr.id_produk', '=', 'pp.id_produk')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'pb.id_gudang')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'pb.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'pb.diubah_oleh')
    ->where('pb.id_entitas', $id_entitas)
    ->where('pb.id_pengambilan_bahan', $id_pengambilan_bahan)
    ->select([
        'pb.*',
        'e.nama_entitas',
        'pp.no_perintah_produksi',
        'pp.tanggal_perintah',
        'pp.qty_rencana',
        'pp.qty_hasil',
        'pp.status_produksi',
        'pr.kode_produk',
        'pr.nama_produk',
        'g.kode_gudang',
        'g.nama_gudang',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data pengambilan bahan tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

$detail_rows = PengambilanBahanDetailORM::query()
    ->from('tb_pengambilan_bahan_detail as pbd')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'pbd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('pbd.id_pengambilan_bahan', $id_pengambilan_bahan)
    ->select([
        'pbd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('pbd.id_pengambilan_bahan_detail', 'asc')
    ->get();

$total_bahan = 0;
foreach ($detail_rows as $d) {
    $total_bahan += (float) $d->subtotal;
}

$status_row = (string) ($row->status_posting ?? 'draft');
$badge = 'secondary';
if ($status_row === 'diperiksa') $badge = 'warning';
if ($status_row === 'posted') $badge = 'success';

$back_param = urlencode($back_url);

/*
|--------------------------------------------------------------------------
| Modal hasil periksa stok
|--------------------------------------------------------------------------
| Data modal dikirim dari periksa_stok.php lewat $_SESSION['modal_periksa_stok']
|--------------------------------------------------------------------------
*/
$modal_periksa_stok = $_SESSION['modal_periksa_stok'] ?? null;
unset($_SESSION['modal_periksa_stok']);

$show_modal_stok = (($_GET['show_modal'] ?? '') === 'stok') && is_array($modal_periksa_stok);

$modal_status = (string) ($modal_periksa_stok['status'] ?? 'info');
$modal_title = (string) ($modal_periksa_stok['title'] ?? 'Hasil Periksa Stok');
$modal_message = (string) ($modal_periksa_stok['message'] ?? '');
$modal_rows = $modal_periksa_stok['rows'] ?? [];

$modal_header_class = 'bg-info text-white';

if ($modal_status === 'success') {
    $modal_header_class = 'bg-success text-white';
} elseif ($modal_status === 'warning') {
    $modal_header_class = 'bg-warning text-dark';
} elseif ($modal_status === 'error') {
    $modal_header_class = 'bg-danger text-white';
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Pengambilan Bahan</h1>
    <p class="page-subtitle">Detail bon pengambilan bahan produksi</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Header Pengambilan Bahan</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Bon</div>
                <div class="detail-value"><?= esc($row->no_pengambilan_bahan ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Pengambilan</div>
                <div class="detail-value"><?= esc($row->tanggal_pengambilan ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status</div>
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
                <div class="detail-label">Produk</div>
                <div class="detail-value"><?= esc(($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) ?></div>
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
                <div class="detail-label">Gudang</div>
                <div class="detail-value"><?= esc(($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) ?></div>
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
        <div class="detail-section-title mb-3">Detail Bahan Baku</div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Bahan Baku</th>
                        <th class="text-end">Qty</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Subtotal</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail_rows->count() > 0): ?>
                        <?php $no = 1; ?>
                        <?php foreach ($detail_rows as $d): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(($d->kode_bahan_baku ?? '-') . ' - ' . ($d->nama_bahan_baku ?? '-')) ?></td>
                                <td class="text-end"><?= esc(number_format((float) ($d->qty ?? 0))) ?></td>
                                <td><?= esc($d->nama_satuan ?? '-') ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) ($d->harga_satuan ?? 0), 2, '.', ',')) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) ($d->subtotal ?? 0), 2, '.', ',')) ?></td>
                                <td><?= esc($d->catatan ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Detail bahan belum ada.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end">Total Nilai Bahan</th>
                        <th class="text-end">Rp <?= esc(number_format($total_bahan, 2, '.', ',')) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
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

            <?php if ($status_row === 'draft' || $status_row === 'diperiksa'): ?>
                <a href="<?= esc(admin_page_url('produksi/pengambilan-bahan/edit') . '&id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_param) ?>" class="btn btn-gradient">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>
            <?php endif; ?>

            <?php if ($status_row === 'draft'): ?>
                <a href="<?= esc(admin_url('index.php?menu=produksi/pengambilan-bahan/periksa-stok&id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_param)) ?>"
                   class="btn btn-warning"
                   onclick="return confirm('Periksa stok bahan untuk bon ini?');">
                    <i class="bi bi-clipboard-check me-1"></i>Periksa Stok
                </a>

                <a href="<?= esc(admin_url('menu/produksi/pengambilan_bahan/hapus.php?id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_param)) ?>"
                   class="btn btn-outline-danger"
                   onclick="return confirm('Yakin ingin menghapus bon draft ini?');">
                    <i class="bi bi-trash me-1"></i>Hapus
                </a>
            <?php endif; ?>

            <?php if ($status_row === 'diperiksa'): ?>
                <a href="<?= esc(admin_url('index.php?menu=produksi/pengambilan-bahan/posting&id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . $back_param)) ?>"
                   class="btn btn-success"
                   onclick="return confirm('Posting pengambilan bahan ini? Stok akan keluar dan jurnal akan dibuat.');">
                    <i class="bi bi-journal-check me-1"></i>Posting
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('menu/produksi/pengambilan_bahan/cetak.php?id=' . (int) $row->id_pengambilan_bahan)) ?>"
               class="btn btn-outline-dark"
               target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>
        </div>
    </div>
</div>

<?php if ($show_modal_stok): ?>
    <div class="modal fade" id="modalPeriksaStok" tabindex="-1" aria-labelledby="modalPeriksaStokLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header <?= esc($modal_header_class) ?>">
                    <h5 class="modal-title" id="modalPeriksaStokLabel">
                        <?= esc($modal_title) ?>
                    </h5>
                    <button
                        type="button"
                        class="btn-close <?= $modal_status === 'warning' ? '' : 'btn-close-white' ?>"
                        data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-3"><?= esc($modal_message) ?></p>

                    <?php if (is_array($modal_rows) && count($modal_rows) > 0): ?>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="60" class="text-center">No</th>
                                        <th>Bahan Baku</th>
                                        <th class="text-end">Stok Tersedia</th>
                                        <th class="text-end">Kebutuhan</th>
                                        <th class="text-end">Kurang</th>
                                        <th width="120" class="text-center">Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php $no_modal = 1; ?>
                                    <?php foreach ($modal_rows as $stok_row): ?>
                                        <?php
                                        $status_stok = (string) ($stok_row['status'] ?? 'kurang');
                                        $badge_stok = $status_stok === 'cukup' ? 'success' : 'danger';
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $no_modal++ ?></td>
                                            <td><?= esc($stok_row['bahan'] ?? '-') ?></td>
                                            <td class="text-end"><?= esc(number_format((float) ($stok_row['stok'] ?? 0))) ?></td>
                                            <td class="text-end"><?= esc(number_format((float) ($stok_row['butuh'] ?? 0))) ?></td>
                                            <td class="text-end"><?= esc(number_format((float) ($stok_row['kurang'] ?? 0))) ?></td>
                                            <td class="text-center">
                                                <span class="badge text-bg-<?= esc($badge_stok) ?>">
                                                    <?= esc(ucfirst($status_stok)) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer">
                    <?php if ($modal_status === 'success'): ?>
                        <a
                            href="<?= esc(admin_url('index.php?menu=produksi/pengambilan-bahan/posting&id=' . (int) $row->id_pengambilan_bahan . '&back_url=' . urlencode($back_url))) ?>"
                            class="btn btn-success"
                            onclick="return confirm('Posting pengambilan bahan ini? Stok akan keluar dan jurnal akan dibuat.');">
                            <i class="bi bi-journal-check me-1"></i>Posting Sekarang
                        </a>
                    <?php endif; ?>

                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalEl = document.getElementById('modalPeriksaStok');

        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }
    });
    </script>
<?php endif; ?>