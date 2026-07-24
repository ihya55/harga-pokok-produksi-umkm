<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_fungsi_penjualan.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id'] ?? 0);

if (!function_exists('pesanan_detail_rupiah')) {
    function pesanan_detail_rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pesanan_detail_qty')) {
    function pesanan_detail_qty($value): string
    {
        return number_format((int) round((float) $value), 0, '.', ',');
    }
}

if (!function_exists('pesanan_detail_tanggal')) {
    function pesanan_detail_tanggal($tanggal): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date('d/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('pesanan_detail_diskon_persen')) {
    function pesanan_detail_diskon_persen($subtotal_kotor, $diskon): string
    {
        $subtotal_kotor = (float) $subtotal_kotor;
        $diskon = (float) $diskon;

        if ($subtotal_kotor <= 0 || $diskon <= 0) {
            return '0%';
        }

        return number_format((int) round(($diskon / $subtotal_kotor) * 100), 0, '.', ',') . '%';
    }
}

if (!function_exists('pesanan_detail_badge_status')) {
    function pesanan_detail_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        if ($status === 'draft') {
            return '<span class="badge bg-secondary-subtle text-secondary">Draft</span>';
        }

        if (in_array($status, ['terkonfirmasi', 'dikonfirmasi', 'confirmed'], true)) {
            return '<span class="badge bg-primary-subtle text-primary">Terkonfirmasi</span>';
        }

        if (in_array($status, ['diproses', 'proses'], true)) {
            return '<span class="badge bg-warning-subtle text-warning">Diproses</span>';
        }

        if ($status === 'selesai') {
            return '<span class="badge bg-success-subtle text-success">Selesai</span>';
        }

        if (in_array($status, ['batal', 'dibatalkan'], true)) {
            return '<span class="badge bg-danger-subtle text-danger">Batal</span>';
        }

        return '<span class="badge bg-light text-dark">' . esc(ucwords(str_replace('_', ' ', $status ?: '-'))) . '</span>';
    }
}

if (!function_exists('pesanan_detail_badge_sumber')) {
    function pesanan_detail_badge_sumber(string $sumber): string
    {
        $sumber = strtolower(trim($sumber));

        if ($sumber === 'toko') {
            return '<span class="badge bg-info-subtle text-info">Toko</span>';
        }

        if ($sumber === 'whatsapp') {
            return '<span class="badge bg-success-subtle text-success">WhatsApp</span>';
        }

        if ($sumber === 'website') {
            return '<span class="badge bg-primary-subtle text-primary">Website</span>';
        }

        if ($sumber === 'reseller') {
            return '<span class="badge bg-warning-subtle text-warning">Reseller</span>';
        }

        return '<span class="badge bg-light text-dark">' . esc(ucwords($sumber ?: '-')) . '</span>';
    }
}

$pesanan = Capsule::table('tb_pesanan_penjualan as pp')
    ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 'pp.id_pelanggan')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'pp.*',
        'p.kode_pelanggan',
        'p.nama_pelanggan',
        'p.no_hp as no_hp_pelanggan',
        'p.alamat as alamat_pelanggan',
    ])
    ->first();

if (!$pesanan) {
    ?>
    <div class="alert alert-danger">
        Data pesanan penjualan tidak ditemukan.
    </div>

    <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <?php
    return;
}

$detail_rows = Capsule::table('tb_pesanan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'd.id_pesanan_penjualan_detail',
        'd.id_produk',
        'd.qty',
        'd.harga',
        'd.diskon',
        'd.subtotal',
        'd.catatan',
        'p.kode_produk',
        'p.nama_produk',
        'p.jenis_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_pesanan_penjualan_detail', 'asc')
    ->get();

$total_qty = 0;
$total_item = $detail_rows->count();

foreach ($detail_rows as $d) {
    $total_qty += (int) round((float) ($d->qty ?? 0));
}

$subtotal_header = (float) ($pesanan->subtotal ?? 0);
$diskon_header = (float) ($pesanan->diskon ?? 0);
$diskon_header_persen = pesanan_detail_diskon_persen($subtotal_header, $diskon_header);
$status_pesanan = (string) ($pesanan->status_pesanan ?? '');
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Pesanan Penjualan</h1>
            <p class="page-subtitle">
                Detail pesanan pelanggan sebelum penyerahan dan faktur penjualan.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

            <a
                href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/cetak&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>"
                class="btn btn-outline-primary"
                target="_blank">
                <i class="bi bi-printer me-1"></i> Cetak
            </a>

            <?php if ($status_pesanan === 'draft'): ?>
                <a
                    href="<?= esc(admin_page_url('penjualan/pesanan/edit') . '&id=' . (int) $pesanan->id_pesanan_penjualan) ?>"
                    class="btn btn-outline-warning">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </a>

                <a
                    href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/konfirmasi&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>"
                    class="btn btn-gradient"
                    onclick="return confirm('Konfirmasi pesanan ini? Setelah dikonfirmasi, pesanan tidak bisa diedit sebagai draft.')">
                    <i class="bi bi-check2-circle me-1"></i> Konfirmasi
                </a>

                <a
                    href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/hapus&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>"
                    class="btn btn-outline-danger"
                    onclick="return confirm('Hapus pesanan draft ini?')">
                    <i class="bi bi-trash me-1"></i> Hapus
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">No Pesanan</div>
                <div class="h5 mb-1"><?= esc((string) ($pesanan->no_pesanan_penjualan ?? '-')) ?></div>
                <div><?= pesanan_detail_badge_status($status_pesanan) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal Pesanan</div>
                <div class="h5 mb-1"><?= esc(pesanan_detail_tanggal($pesanan->tanggal_pesanan ?? null)) ?></div>
                <div class="text-muted small">
                    Kirim: <?= esc(pesanan_detail_tanggal($pesanan->tanggal_kirim_rencana ?? null)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Qty</div>
                <div class="h5 mb-1"><?= esc(pesanan_detail_qty($total_qty)) ?></div>
                <div class="text-muted small">
                    <?= number_format((int) $total_item, 0, '.', ',') ?> item produk
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Pesanan</div>
                <div class="h5 mb-1"><?= pesanan_detail_rupiah($pesanan->total ?? 0) ?></div>
                <div><?= pesanan_detail_badge_sumber((string) ($pesanan->sumber_pesanan ?? '')) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Informasi Pesanan</h2>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <tr>
                            <th width="190" class="text-muted">Pelanggan</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($pesanan->nama_pelanggan ?? '-')) ?></div>
                                <div class="text-muted small">
                                    <?= esc((string) ($pesanan->kode_pelanggan ?? '-')) ?>
                                    <?php if (!empty($pesanan->no_hp_pelanggan)): ?>
                                        · <?= esc((string) $pesanan->no_hp_pelanggan) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Alamat</th>
                            <td><?= esc((string) ($pesanan->alamat_pelanggan ?? '-')) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Sumber Pesanan</th>
                            <td><?= pesanan_detail_badge_sumber((string) ($pesanan->sumber_pesanan ?? '')) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Status Pesanan</th>
                            <td><?= pesanan_detail_badge_status($status_pesanan) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Catatan</th>
                            <td><?= esc((string) ($pesanan->catatan ?? '-')) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Dibuat</th>
                            <td><?= esc((string) ($pesanan->tanggal_dibuat ?? '-')) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Diubah</th>
                            <td><?= esc((string) ($pesanan->tanggal_diubah ?? '-')) ?></td>
                        </tr>
                    </table>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Pesanan penjualan belum mengurangi stok dan belum membuat jurnal.
                    Stok berkurang saat penyerahan penjualan diposting.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Ringkasan Nilai</h2>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <tr>
                            <th class="text-muted">Subtotal</th>
                            <td class="text-end fw-semibold"><?= pesanan_detail_rupiah($pesanan->subtotal ?? 0) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Diskon Header</th>
                            <td class="text-end">
                                <div class="fw-semibold"><?= esc($diskon_header_persen) ?></div>
                                <div class="text-muted small"><?= pesanan_detail_rupiah($pesanan->diskon ?? 0) ?></div>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2"><hr class="my-2"></td>
                        </tr>

                        <tr>
                            <th class="fs-5">Total</th>
                            <td class="text-end fs-5 fw-bold"><?= pesanan_detail_rupiah($pesanan->total ?? 0) ?></td>
                        </tr>
                    </table>
                </div>

                <?php if ($status_pesanan === 'draft'): ?>
                    <div class="alert alert-secondary mt-3 mb-0">
                        <i class="bi bi-pencil-square me-1"></i>
                        Pesanan masih draft dan masih bisa diedit.
                    </div>
                <?php elseif ($status_pesanan === 'terkonfirmasi'): ?>
                    <div class="alert alert-primary mt-3 mb-0">
                        <i class="bi bi-check2-circle me-1"></i>
                        Pesanan sudah dikonfirmasi dan siap diproses ke penyerahan.
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Status pesanan: <?= esc(ucwords(str_replace('_', ' ', $status_pesanan))) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Detail Produk Pesanan</h2>
                <div class="text-muted small">Qty tampil sebagai integer dan diskon tampil dalam persen.</div>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="140">Kode</th>
                        <th>Produk</th>
                        <th width="110">Satuan</th>
                        <th width="105" class="text-end">Qty</th>
                        <th width="145" class="text-end">Harga</th>
                        <th width="130" class="text-end">Diskon</th>
                        <th width="160" class="text-end">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail_rows->count() === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Detail produk belum tersedia.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detail_rows as $i => $row): ?>
                            <?php
                            $qty = (int) round((float) ($row->qty ?? 0));
                            $harga = (float) ($row->harga ?? 0);
                            $subtotal_kotor = $qty * $harga;
                            $diskon = (float) ($row->diskon ?? 0);
                            $diskon_persen = pesanan_detail_diskon_persen($subtotal_kotor, $diskon);
                            ?>
                            <tr>
                                <td class="text-center"><?= number_format($i + 1, 0, '.', ',') ?></td>
                                <td class="fw-semibold"><?= esc((string) ($row->kode_produk ?? '-')) ?></td>
                                <td>
                                    <div class="fw-semibold"><?= esc((string) ($row->nama_produk ?? '-')) ?></div>
                                    <?php if (!empty($row->catatan)): ?>
                                        <div class="text-muted small"><?= esc((string) $row->catatan) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc((string) ($row->nama_satuan ?? '-')) ?></td>
                                <td class="text-end fw-semibold"><?= esc(pesanan_detail_qty($row->qty ?? 0)) ?></td>
                                <td class="text-end"><?= pesanan_detail_rupiah($row->harga ?? 0) ?></td>
                                <td class="text-end">
                                    <div class="fw-semibold"><?= esc($diskon_persen) ?></div>
                                    <div class="text-muted small"><?= pesanan_detail_rupiah($diskon) ?></div>
                                </td>
                                <td class="text-end fw-semibold"><?= pesanan_detail_rupiah($row->subtotal ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <?php if ($detail_rows->count() > 0): ?>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">Total Qty</th>
                            <th class="text-end"><?= esc(pesanan_detail_qty($total_qty)) ?></th>
                            <th colspan="2" class="text-end">Subtotal</th>
                            <th class="text-end"><?= pesanan_detail_rupiah($pesanan->subtotal ?? 0) ?></th>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
    <a href="<?= esc(admin_page_url('penjualan/pesanan')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
    </a>

    <div class="d-flex gap-2 flex-wrap">
        <a
            href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/cetak&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>"
            class="btn btn-outline-primary"
            target="_blank">
            <i class="bi bi-printer me-1"></i> Cetak
        </a>

        <?php if ($status_pesanan === 'draft'): ?>
            <a
                href="<?= esc(admin_url('index.php?menu=penjualan/pesanan/konfirmasi&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>"
                class="btn btn-gradient"
                onclick="return confirm('Konfirmasi pesanan ini?')">
                <i class="bi bi-check2-circle me-1"></i> Konfirmasi
            </a>
        <?php endif; ?>
    </div>
</div>