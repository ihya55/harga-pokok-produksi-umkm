<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if (!function_exists('faktur_detail_rupiah')) {
    function faktur_detail_rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('faktur_detail_qty')) {
    function faktur_detail_qty($value): string
    {
        $value = (float) $value;
        if (abs($value - round($value)) < 0.000001) {
            return number_format($value, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');
    }
}

if (!function_exists('faktur_detail_tanggal')) {
    function faktur_detail_tanggal($tanggal, bool $dengan_jam = false): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date($dengan_jam ? 'd/m/Y H:i' : 'd/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('faktur_detail_badge_status')) {
    function faktur_detail_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'posted' => '<span class="badge bg-success">Posted</span>',
            'batal' => '<span class="badge bg-danger">Batal</span>',
            default => '<span class="badge bg-secondary">Draft</span>',
        };
    }
}

$faktur = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_penyerahan_penjualan as pp', 'pp.id_penyerahan_penjualan', '=', 'f.id_penyerahan_penjualan')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'f.id_pelanggan')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'f.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'f.diposting_oleh')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'f.*',
        'pp.no_penyerahan_penjualan',
        'pp.tanggal_penyerahan',
        'ps.no_pesanan_penjualan',
        'ps.tanggal_pesanan',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.email as email_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_posting',
    ])
    ->first();

if (!$faktur) {
    ?>
    <div class="alert alert-danger">Data piutang pelanggan tidak ditemukan.</div>
    <a href="<?= esc(admin_page_url('penjualan/faktur')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <?php
    return;
}

$detail_rows = Capsule::table('tb_faktur_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_faktur_penjualan_detail', 'asc')
    ->get();

$pembayaran_rows = Capsule::table('tb_pembayaran_penjualan as p')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'p.id_coa_kas_bank')
    ->where('p.id_entitas', $id_entitas)
    ->where('p.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'p.*',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderBy('p.tanggal_pembayaran', 'asc')
    ->orderBy('p.id_pembayaran_penjualan', 'asc')
    ->get();

$jurnal_rows = Capsule::table('tb_log_jurnal_sumber as l')
    ->join('tb_jurnal as j', 'j.id_jurnal', '=', 'l.id_jurnal')
    ->where('l.id_entitas', $id_entitas)
    ->where('l.tabel_sumber', 'tb_faktur_penjualan')
    ->where('l.id_sumber', $id_faktur_penjualan)
    ->select([
        'j.*',
        'l.no_sumber',
    ])
    ->orderBy('j.id_jurnal', 'asc')
    ->get();

$jurnal_ids = $jurnal_rows->pluck('id_jurnal')->map(fn ($v) => (int) $v)->all();
$jurnal_detail_rows = collect();

if (!empty($jurnal_ids)) {
    $jurnal_detail_rows = Capsule::table('tb_jurnal_detail as d')
        ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'd.id_coa')
        ->whereIn('d.id_jurnal', $jurnal_ids)
        ->select([
            'd.*',
            'c.kode_coa',
            'c.nama_coa',
        ])
        ->orderBy('d.id_jurnal', 'asc')
        ->orderBy('d.urutan', 'asc')
        ->get()
        ->groupBy('id_jurnal');
}

$total_qty = 0.0;
foreach ($detail_rows as $d) {
    $total_qty += (float) $d->qty;
}

$url_kembali = $back_url !== '' ? $back_url : admin_page_url('penjualan/faktur');
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Piutang Pelanggan</h1>
            <p class="page-subtitle">Detail invoice, pembayaran, dan jurnal penjualan.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= esc($url_kembali) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_url('index.php?menu=penjualan/faktur/cetak&id=' . (int) $faktur->id_faktur_penjualan)) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Cetak
            </a>

            <?php if ((string) $faktur->status_faktur === 'draft'): ?>
                <a href="<?= esc(admin_page_url('penjualan/faktur/edit') . '&id=' . (int) $faktur->id_faktur_penjualan) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                </a>

                <a href="<?= esc(admin_url('index.php?menu=penjualan/faktur/posting&id=' . (int) $faktur->id_faktur_penjualan)) ?>" class="btn btn-success" onclick="return confirm('Posting piutang ini? Jurnal penjualan akan dibuat.')">
                    <i class="bi bi-check2-circle me-1"></i>Posting
                </a>

                <a href="<?= esc(admin_url('index.php?menu=penjualan/faktur/hapus&id=' . (int) $faktur->id_faktur_penjualan)) ?>" class="btn btn-outline-danger" onclick="return confirm('Hapus piutang draft ini?')">
                    <i class="bi bi-trash me-1"></i>Hapus
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">No Piutang</div>
                <div class="h5 mb-1"><?= esc((string) $faktur->no_faktur_penjualan) ?></div>
                <?= faktur_detail_badge_status((string) $faktur->status_faktur) ?>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal Piutang</div>
                <div class="h5 mb-1"><?= esc(faktur_detail_tanggal($faktur->tanggal_faktur)) ?></div>
                <div class="text-muted small">Jatuh tempo: <?= esc(faktur_detail_tanggal($faktur->jatuh_tempo)) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Belanja Kredit</div>
                <div class="h5 mb-1"><?= faktur_detail_rupiah($faktur->total ?? 0) ?></div>
                <div class="text-muted small">Qty: <?= esc(faktur_detail_qty($total_qty)) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Sisa Piutang</div>
                <div class="h5 mb-1"><?= faktur_detail_rupiah($faktur->sisa_piutang ?? 0) ?></div>
                <div class="text-muted small"><?= esc(ucfirst((string) $faktur->jenis_pembayaran)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Informasi Piutang</h2>

                <table class="table table-sm table-borderless align-middle mb-0">
                    <tr>
                        <th width="190" class="text-muted">Pelanggan</th>
                        <td>
                            <div class="fw-semibold"><?= esc(($faktur->kode_pelanggan ?? '-') . ' - ' . ($faktur->nama_pelanggan ?? '-')) ?></div>
                            <div class="text-muted small"><?= esc((string) ($faktur->no_hp_pelanggan ?? '-')) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Alamat</th>
                        <td><?= esc((string) ($faktur->alamat_pelanggan ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Penyerahan</th>
                        <td><?= esc((string) ($faktur->no_penyerahan_penjualan ?? '-')) ?> · <?= esc(faktur_detail_tanggal($faktur->tanggal_penyerahan ?? null)) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Pesanan</th>
                        <td><?= esc((string) ($faktur->no_pesanan_penjualan ?? '-')) ?> · <?= esc(faktur_detail_tanggal($faktur->tanggal_pesanan ?? null)) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Catatan</th>
                        <td><?= esc((string) ($faktur->catatan ?? '-')) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Ringkasan Nilai</h2>

                <table class="table table-sm table-borderless align-middle mb-0">
                    <tr>
                        <th class="text-muted">Subtotal</th>
                        <td class="text-end fw-semibold"><?= faktur_detail_rupiah($faktur->subtotal ?? 0) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Diskon</th>
                        <td class="text-end fw-semibold"><?= faktur_detail_rupiah($faktur->diskon ?? 0) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">PPN</th>
                        <td class="text-end fw-semibold"><?= faktur_detail_rupiah($faktur->ppn ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><hr class="my-2"></td>
                    </tr>
                    <tr>
                        <th class="fs-5">Total</th>
                        <td class="text-end fs-5 fw-bold"><?= faktur_detail_rupiah($faktur->total ?? 0) ?></td>
                    </tr>
                </table>

                <?php if ((string) $faktur->status_faktur === 'posted'): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        Piutang sudah tercatat dan jurnal penjualan sudah dibuat.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Piutang masih draft dan belum membentuk jurnal.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Detail Produk</h2>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="140">Kode</th>
                        <th>Produk</th>
                        <th width="100">Satuan</th>
                        <th width="100" class="text-end">Qty</th>
                        <th width="150" class="text-end">Harga</th>
                        <th width="150" class="text-end">Diskon</th>
                        <th width="160" class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detail_rows as $i => $row): ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= esc((string) ($row->kode_produk ?? '-')) ?></td>
                            <td><?= esc((string) ($row->nama_produk ?? '-')) ?></td>
                            <td><?= esc((string) ($row->nama_satuan ?? '-')) ?></td>
                            <td class="text-end"><?= esc(faktur_detail_qty($row->qty ?? 0)) ?></td>
                            <td class="text-end"><?= faktur_detail_rupiah($row->harga ?? 0) ?></td>
                            <td class="text-end"><?= faktur_detail_rupiah($row->diskon ?? 0) ?></td>
                            <td class="text-end fw-semibold"><?= faktur_detail_rupiah($row->subtotal ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th class="text-end"><?= esc(faktur_detail_qty($total_qty)) ?></th>
                        <th colspan="2"></th>
                        <th class="text-end"><?= faktur_detail_rupiah($faktur->subtotal ?? 0) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Pembayaran Terkait</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Pembayaran</th>
                                <th>Metode</th>
                                <th>Status</th>
                                <th class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pembayaran_rows->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Belum ada pembayaran.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pembayaran_rows as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) $row->no_pembayaran_penjualan) ?></div>
                                            <div class="text-muted small"><?= esc(faktur_detail_tanggal($row->tanggal_pembayaran ?? null)) ?></div>
                                        </td>
                                        <td><?= esc(ucfirst((string) $row->metode_pembayaran)) ?></td>
                                        <td><?= esc((string) $row->status_posting) ?></td>
                                        <td class="text-end fw-semibold"><?= faktur_detail_rupiah($row->jumlah_bayar ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Jurnal Terbentuk</h2>

                <?php if ($jurnal_rows->count() === 0): ?>
                    <div class="alert alert-warning mb-0">
                        Belum ada jurnal. Jurnal dibuat saat posting faktur.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="accordionJurnalFaktur">
                        <?php foreach ($jurnal_rows as $idx => $jurnal): ?>
                            <?php
                            $collapse_id = 'jurnalFakturCollapse' . (int) $jurnal->id_jurnal;
                            $detail_jurnal = $jurnal_detail_rows->get((int) $jurnal->id_jurnal, collect());
                            ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= esc($collapse_id) ?>">
                                        <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                            <span class="fw-semibold"><?= esc((string) $jurnal->no_jurnal) ?></span>
                                            <span class="text-muted small"><?= esc((string) $jurnal->kode_jenis_transaksi) ?></span>
                                        </div>
                                    </button>
                                </h2>

                                <div id="<?= esc($collapse_id) ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#accordionJurnalFaktur">
                                    <div class="accordion-body">
                                        <div class="table-responsive border rounded">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>COA</th>
                                                        <th>Akun</th>
                                                        <th class="text-end">Debit</th>
                                                        <th class="text-end">Kredit</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($detail_jurnal as $d): ?>
                                                        <tr>
                                                            <td class="fw-semibold"><?= esc((string) ($d->kode_coa ?? '-')) ?></td>
                                                            <td><?= esc((string) ($d->nama_coa ?? '-')) ?></td>
                                                            <td class="text-end"><?= faktur_detail_rupiah($d->debit ?? 0) ?></td>
                                                            <td class="text-end"><?= faktur_detail_rupiah($d->kredit ?? 0) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot class="table-light">
                                                    <tr>
                                                        <th colspan="2" class="text-end">Total</th>
                                                        <th class="text-end"><?= faktur_detail_rupiah($jurnal->total_debit ?? 0) ?></th>
                                                        <th class="text-end"><?= faktur_detail_rupiah($jurnal->total_kredit ?? 0) ?></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>