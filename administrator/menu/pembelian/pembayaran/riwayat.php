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

function redirect_menu_aman_pembayaran_riwayat(string $url): void
{
    echo '<script>window.location.href = ' . json_encode($url) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
    exit;
}

function label_jenis_bayar_riwayat_pembelian(?string $jenis): string
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

function badge_jenis_bayar_riwayat_pembelian(?string $jenis): string
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

$faktur = FakturPembelianORM::query()
    ->from('tb_faktur_pembelian as fp')
    ->leftJoin('tb_pemasok as p', 'p.id_pemasok', '=', 'fp.id_pemasok')
    ->leftJoin('tb_penerimaan_pembelian as pn', 'pn.id_penerimaan_pembelian', '=', 'fp.id_penerimaan_pembelian')
    ->where('fp.id_entitas', $id_entitas)
    ->where('fp.id_faktur_pembelian', $id_faktur_pembelian)
    ->where('fp.status_faktur', 'posted')
    ->select([
        'fp.*',
        'p.kode_pemasok',
        'p.nama_pemasok',
        'pn.no_penerimaan_pembelian',
    ])
    ->first();

if (!$faktur) {
    set_flash('error', 'Faktur pembelian tidak ditemukan.');
    redirect_menu_aman_pembayaran_riwayat($back_url);
}

$riwayat_rows = PembayaranPembelianORM::query()
    ->from('tb_pembayaran_pembelian as pb')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'pb.id_coa_kas_bank')
    ->where('pb.id_entitas', $id_entitas)
    ->where('pb.id_faktur_pembelian', $id_faktur_pembelian)
    ->select([
        'pb.*',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderBy('pb.tanggal_pembayaran', 'asc')
    ->orderBy('pb.id_pembayaran_pembelian', 'asc')
    ->get();

$total_draft = 0;
$total_posted = 0;

foreach ($riwayat_rows as $item) {
    if ((string) $item->status_posting === 'posted') {
        $total_posted += (float) $item->jumlah_bayar;
    } else {
        $total_draft += (float) $item->jumlah_bayar;
    }
}

$status_lunas = ((float) $faktur->sisa_utang <= 0);
$jenis_bayar = strtolower((string) ($faktur->jenis_pembayaran ?? 'kredit'));
$back_param = urlencode($back_url);
$current_url = admin_page_url('pembelian/pembayaran/riwayat') . '&id_faktur_pembelian=' . (int) $faktur->id_faktur_pembelian . '&back_url=' . $back_param;
$current_param = urlencode($current_url);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Riwayat Pembayaran Pembelian</h1>
    <p class="page-subtitle">Riwayat pembayaran untuk satu faktur pembelian</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="detail-section-title">Informasi Faktur</div>

        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Faktur</div>
                <div class="detail-value"><?= esc($faktur->no_faktur_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Tanggal Faktur</div>
                <div class="detail-value"><?= esc($faktur->tanggal_faktur ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">No Penerimaan</div>
                <div class="detail-value"><?= esc($faktur->no_penerimaan_pembelian ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Pemasok</div>
                <div class="detail-value"><?= esc(($faktur->kode_pemasok ?? '-') . ' - ' . ($faktur->nama_pemasok ?? '-')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jenis Bayar</div>
                <div class="detail-value">
                    <span class="badge <?= esc(badge_jenis_bayar_riwayat_pembelian($jenis_bayar)) ?>">
                        <?= esc(label_jenis_bayar_riwayat_pembelian($jenis_bayar)) ?>
                    </span>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jatuh Tempo</div>
                <div class="detail-value"><?= esc($faktur->jatuh_tempo ?? '-') ?></div>
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

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Total Faktur</div>
                <div class="detail-value fw-semibold">Rp <?= esc(number_format((float) ($faktur->total ?? 0), 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Total Posted</div>
                <div class="detail-value text-success fw-semibold">Rp <?= esc(number_format((float) $total_posted, 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Total Draft</div>
                <div class="detail-value text-secondary fw-semibold">Rp <?= esc(number_format((float) $total_draft, 2, '.', ',')) ?></div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="detail-label">Sisa Utang</div>
                <div class="detail-value <?= $status_lunas ? 'text-success' : 'text-danger' ?> fw-semibold">
                    Rp <?= esc(number_format((float) ($faktur->sisa_utang ?? 0), 2, '.', ',')) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="detail-section-title mb-0">Daftar Riwayat Pembayaran</div>

            <?php if (!$status_lunas): ?>
                <a href="<?= esc(admin_page_url('pembelian/pembayaran/tambah') . '&id_faktur_pembelian=' . (int) $faktur->id_faktur_pembelian . '&back_url=' . $current_param) ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-cash-coin me-1"></i>Bayar
                </a>
            <?php endif; ?>
        </div>

        <div class="table-responsive border rounded">
            <table class="table align-middle table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="70" class="text-center">No</th>
                        <th>No Pembayaran</th>
                        <th>Tanggal</th>
                        <th>Metode</th>
                        <th>Akun Kas/Bank</th>
                        <th class="text-end">Jumlah Bayar</th>
                        <th>Status</th>
                        <th width="230" class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($riwayat_rows->count() > 0): ?>
                        <?php $no = 1; foreach ($riwayat_rows as $row): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>

                                <td class="fw-semibold">
                                    <?= esc($row->no_pembayaran_pembelian ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc($row->tanggal_pembayaran ?? '-') ?>
                                </td>

                                <td>
                                    <?= esc(ucfirst((string) ($row->metode_pembayaran ?? '-'))) ?>
                                </td>

                                <td>
                                    <?= esc(($row->kode_coa ?? '-') . ' - ' . ($row->nama_coa ?? '-')) ?>
                                </td>

                                <td class="text-end">
                                    Rp <?= esc(number_format((float) ($row->jumlah_bayar ?? 0), 2, '.', ',')) ?>
                                </td>

                                <td>
                                    <?php $badge = ((string) $row->status_posting === 'posted') ? 'success' : 'secondary'; ?>
                                    <span class="badge text-bg-<?= esc($badge) ?>">
                                        <?= esc(ucfirst((string) ($row->status_posting ?? '-'))) ?>
                                    </span>
                                </td>

                                <td class="text-center">
                                    <a
                                        href="<?= esc(admin_page_url('pembelian/pembayaran/detail') . '&id=' . (int) $row->id_pembayaran_pembelian . '&back_url=' . $current_param) ?>"
                                        class="btn btn-sm btn-outline-info"
                                        title="Detail Pembayaran">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <?php if ((string) $row->status_posting === 'draft'): ?>
                                        <a
                                            href="<?= esc(admin_page_url('pembelian/pembayaran/edit') . '&id=' . (int) $row->id_pembayaran_pembelian . '&back_url=' . $current_param) ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit Pembayaran">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <a
                                            href="<?= esc(admin_url('menu/pembelian/pembayaran/posting.php?id=' . (int) $row->id_pembayaran_pembelian . '&back_url=' . $current_param)) ?>"
                                            class="btn btn-sm btn-outline-success"
                                            onclick="return confirm('Posting pembayaran ini? Sisa utang faktur akan berkurang dan jurnal pembayaran akan dibuat.');"
                                            title="Posting Pembayaran">
                                            <i class="bi bi-journal-check"></i>
                                        </a>

                                        <a
                                            href="<?= esc(admin_url('menu/pembelian/pembayaran/hapus.php?id=' . (int) $row->id_pembayaran_pembelian . '&back_url=' . $current_param)) ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Yakin ingin menghapus pembayaran draft ini?');"
                                            title="Hapus Pembayaran">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a
                                        href="<?= esc(admin_url('menu/pembelian/pembayaran/cetak.php?id=' . (int) $row->id_pembayaran_pembelian)) ?>"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Cetak Pembayaran">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada riwayat pembayaran untuk faktur ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="5" class="text-end">Total Posted</th>
                        <th class="text-end">Rp <?= esc(number_format((float) $total_posted, 2, '.', ',')) ?></th>
                        <th colspan="2"></th>
                    </tr>
                    <tr>
                        <th colspan="5" class="text-end">Total Draft</th>
                        <th class="text-end">Rp <?= esc(number_format((float) $total_draft, 2, '.', ',')) ?></th>
                        <th colspan="2"></th>
                    </tr>
                    <tr>
                        <th colspan="5" class="text-end">Sisa Utang</th>
                        <th class="text-end">Rp <?= esc(number_format((float) ($faktur->sisa_utang ?? 0), 2, '.', ',')) ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="d-flex gap-2 mt-4 flex-wrap">
            <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('pembelian/pembayaran/detail-faktur') . '&id_faktur_pembelian=' . (int) $faktur->id_faktur_pembelian . '&back_url=' . $current_param) ?>" class="btn btn-outline-info">
                <i class="bi bi-eye me-1"></i>Detail Tagihan
            </a>

            <?php if (!$status_lunas): ?>
                <a href="<?= esc(admin_page_url('pembelian/pembayaran/tambah') . '&id_faktur_pembelian=' . (int) $faktur->id_faktur_pembelian . '&back_url=' . $current_param) ?>" class="btn btn-success">
                    <i class="bi bi-cash-coin me-1"></i>Bayar
                </a>
            <?php endif; ?>

            <a href="<?= esc(admin_url('menu/pembelian/pembayaran/cetak_faktur.php?id_faktur_pembelian=' . (int) $faktur->id_faktur_pembelian)) ?>" class="btn btn-outline-dark" target="_blank">
                <i class="bi bi-printer me-1"></i>Cetak Tagihan
            </a>
        </div>
    </div>
</div>