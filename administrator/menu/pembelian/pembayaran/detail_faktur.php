<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_faktur_pembelian = (int) ($_GET['id_faktur_pembelian'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $back_url = (string) $_SERVER['HTTP_REFERER'];
}

if ($back_url === '') {
    $back_url = admin_page_url('pembelian/pembayaran');
}

function redirect_menu_aman_pembayaran_detail_faktur(string $url): void
{
    echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
    exit;
}

function label_jenis_bayar_detail_faktur_pembelian(?string $jenis): string
{
    $jenis = strtolower(trim((string) $jenis));

    if ($jenis === 'tunai') {
        return 'Tunai';
    }

    if ($jenis === 'transfer') {
        return 'Transfer';
    }

    return 'Kredit';
}

function badge_jenis_bayar_detail_faktur_pembelian(?string $jenis): string
{
    $jenis = strtolower(trim((string) $jenis));

    if ($jenis === 'tunai') {
        return 'text-bg-success';
    }

    if ($jenis === 'transfer') {
        return 'text-bg-primary';
    }

    return 'text-bg-warning';
}

$row = FakturPembelianORM::query()
    ->from('tb_faktur_pembelian as fp')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'fp.id_entitas')
    ->leftJoin('tb_penerimaan_pembelian as pn', 'pn.id_penerimaan_pembelian', '=', 'fp.id_penerimaan_pembelian')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
    ->where('fp.id_entitas', $id_entitas)
    ->where('fp.id_faktur_pembelian', $id_faktur_pembelian)
    ->where('fp.status_faktur', 'posted')
    ->select([
        'fp.*',
        'e.nama_entitas',
        'pn.no_penerimaan_pembelian',
        'pn.tanggal_penerimaan',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'p.alamat as alamat_pemasok',
        'p.no_hp as no_hp_pemasok',
        'p.email as email_pemasok',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data tagihan faktur pembelian tidak ditemukan.');
    redirect_menu_aman_pembayaran_detail_faktur($back_url);
}

$detail_rows = FakturPembelianDetailORM::query()
    ->from('tb_faktur_pembelian_detail as d')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'd.id_bahan_baku')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_faktur_pembelian', $id_faktur_pembelian)
    ->select([
        'd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
        's.nama_satuan',
    ])
    ->orderBy('d.id_faktur_pembelian_detail', 'asc')
    ->get();

$pembayaran_rows = PembayaranPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_faktur_pembelian', $id_faktur_pembelian)
    ->orderBy('tanggal_pembayaran', 'asc')
    ->orderBy('id_pembayaran_pembelian', 'asc')
    ->get();

$total_dibayar = $pembayaran_rows
    ->where('status_posting', 'posted')
    ->sum('jumlah_bayar');

$status_lunas = ((float) $row->sisa_utang <= 0);
$jenis_bayar = strtolower((string) ($row->jenis_pembayaran ?? 'kredit'));
$back_param = urlencode($back_url);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Tagihan Pembelian</h1>
    <p class="page-subtitle">Detail faktur dari sisi pembayaran pembelian</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Header Tagihan</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Faktur</div>
                <div class="detail-value"><?= esc($row->no_faktur_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Faktur</div>
                <div class="detail-value"><?= esc($row->tanggal_faktur ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Penerimaan</div>
                <div class="detail-value"><?= esc($row->no_penerimaan_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Penerimaan</div>
                <div class="detail-value"><?= esc($row->tanggal_penerimaan ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Pemasok</div>
                <div class="detail-value"><?= esc(($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jenis Bayar</div>
                <div class="detail-value">
                    <span class="badge <?= esc(badge_jenis_bayar_detail_faktur_pembelian($jenis_bayar)) ?>">
                        <?= esc(label_jenis_bayar_detail_faktur_pembelian($jenis_bayar)) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No HP Pemasok</div>
                <div class="detail-value"><?= esc($row->no_hp_pemasok ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Email Pemasok</div>
                <div class="detail-value"><?= esc($row->email_pemasok ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jatuh Tempo</div>
                <div class="detail-value"><?= esc($row->jatuh_tempo ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status Pelunasan</div>
                <div class="detail-value">
                    <?php if ($status_lunas): ?>
                        <span class="badge text-bg-success">Sudah Lunas</span>
                    <?php else: ?>
                        <span class="badge text-bg-danger">Belum Lunas</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12">
                <div class="detail-label">Alamat Pemasok</div>
                <div class="detail-value"><?= esc($row->alamat_pemasok ?? '-') ?></div>
            </div>

            <div class="col-12">
                <div class="detail-label">Catatan Faktur</div>
                <div class="detail-value"><?= esc($row->catatan ?? '-') ?></div>
            </div>
        </div>

        <hr class="my-4">

        <div class="detail-section-title">Ringkasan Pembayaran</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Subtotal</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->subtotal ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Diskon</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->diskon ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">PPN</div>
                <div class="detail-value">Rp <?= esc(number_format((float) ($row->ppn ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Total Faktur</div>
                <div class="detail-value fw-semibold">Rp <?= esc(number_format((float) ($row->total ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Total Dibayar</div>
                <div class="detail-value fw-semibold text-success">Rp <?= esc(number_format((float) $total_dibayar, 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Sisa Utang</div>
                <div class="detail-value fw-semibold <?= $status_lunas ? 'text-success' : 'text-danger' ?>">
                    Rp <?= esc(number_format((float) ($row->sisa_utang ?? 0), 2, '.', ',')) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($pembayaran_rows->count() > 0): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="detail-section-title mb-3">Riwayat Pembayaran</div>

            <div class="table-responsive border rounded">
                <table class="table align-middle table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="70" class="text-center">No</th>
                            <th>No Pembayaran</th>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th class="text-end">Jumlah Bayar</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no_bayar = 1; foreach ($pembayaran_rows as $bayar): ?>
                            <tr>
                                <td class="text-center"><?= $no_bayar++ ?></td>
                                <td class="fw-semibold"><?= esc($bayar->no_pembayaran_pembelian ?? '-') ?></td>
                                <td><?= esc($bayar->tanggal_pembayaran ?? '-') ?></td>
                                <td><?= esc(ucfirst((string) ($bayar->metode_pembayaran ?? '-'))) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) ($bayar->jumlah_bayar ?? 0), 2, '.', ',')) ?></td>
                                <td>
                                    <?php if (($bayar->status_posting ?? '') === 'posted'): ?>
                                        <span class="badge text-bg-success">Posted</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-warning">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($bayar->catatan ?? '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title mb-3">Detail Bahan</div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>Bahan Baku</th>
                        <th class="text-end">Qty</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Diskon</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail_rows->count() > 0): ?>
                        <?php $no = 1; foreach ($detail_rows as $item): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= esc(($item->kode_bahan_baku ?? '-') . ' - ' . ($item->nama_bahan_baku ?? '-')) ?></td>
                                <td class="text-end"><?= esc(number_format((int) $item->qty, 0, '.', ',')) ?></td>
                                <td><?= esc($item->nama_satuan ?? '-') ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->harga, 2, '.', ',')) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->diskon, 2, '.', ',')) ?></td>
                                <td class="text-end">Rp <?= esc(number_format((float) $item->subtotal, 2, '.', ',')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Detail faktur belum ada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="6" class="text-end">Subtotal</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->subtotal ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">Diskon</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->diskon ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">PPN</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->ppn ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">Total Faktur</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->total ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">Total Dibayar</th>
                        <th class="text-end">Rp <?= esc(number_format((float) $total_dibayar, 2, '.', ',')) ?></th>
                    </tr>
                    <tr>
                        <th colspan="6" class="text-end">Sisa Utang</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($row->sisa_utang ?? 0), 2, '.', ',')) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <?php if (!$status_lunas): ?>
                <a href="<?= esc(admin_page_url('pembelian/pembayaran/tambah') . '&id_faktur_pembelian=' . (int) $row->id_faktur_pembelian . '&back_url=' . $back_param) ?>" class="btn btn-success">
                    <i class="bi bi-cash-coin me-1"></i>Bayar
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_page_url('pembelian/pembayaran/riwayat') . '&id_faktur_pembelian=' . (int) $row->id_faktur_pembelian . '&back_url=' . $back_param) ?>" class="btn btn-outline-primary">
                <i class="bi bi-clock-history me-1"></i>Riwayat Pembayaran
            </a>

            <a href="<?= esc(admin_url('menu/pembelian/pembayaran/cetak_faktur.php?id_faktur_pembelian=' . (int) $row->id_faktur_pembelian)) ?>" class="btn btn-outline-dark" target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak Tagihan
            </a>
        </div>
    </div>
</div>