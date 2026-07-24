<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_fungsi_penjualan.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id'] ?? 0);

if (!function_exists('pos_detail_rupiah')) {
    function pos_detail_rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pos_detail_qty')) {
    function pos_detail_qty($value): string
    {
        $value = (float) $value;

        if (abs($value - round($value)) < 0.000001) {
            return number_format($value, 0, '.', ',');
        }

        return number_format($value, 2, '.', ',');
    }
}

if (!function_exists('pos_detail_tanggal')) {
    function pos_detail_tanggal($tanggal, bool $dengan_jam = false): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date($dengan_jam ? 'd/m/Y H:i' : 'd/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('pos_detail_badge_bayar')) {
    function pos_detail_badge_bayar(string $jenis): string
    {
        $jenis = strtolower(trim($jenis));

        if ($jenis === 'tunai') {
            return '<span class="badge bg-success-subtle text-success">Tunai</span>';
        }

        if ($jenis === 'transfer') {
            return '<span class="badge bg-primary-subtle text-primary">Transfer</span>';
        }

        if ($jenis === 'kredit') {
            return '<span class="badge bg-warning-subtle text-warning">Kredit</span>';
        }

        return '<span class="badge bg-secondary">-</span>';
    }
}

if (!function_exists('pos_detail_badge_status')) {
    function pos_detail_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        if (in_array($status, ['posted', 'lunas'], true)) {
            return '<span class="badge bg-success">Posted</span>';
        }

        if (in_array($status, ['draft'], true)) {
            return '<span class="badge bg-secondary">Draft</span>';
        }

        if (in_array($status, ['batal', 'dibatalkan'], true)) {
            return '<span class="badge bg-danger">Batal</span>';
        }

        return '<span class="badge bg-secondary">' . esc(ucwords(str_replace('_', ' ', $status ?: '-'))) . '</span>';
    }
}

$faktur = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 'f.id_pelanggan')
    ->leftJoin('tb_penyerahan_penjualan as pn', 'pn.id_penyerahan_penjualan', '=', 'f.id_penyerahan_penjualan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'pn.id_gudang')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'f.*',
        'p.kode_pelanggan',
        'p.nama_pelanggan',
        'p.no_hp as no_hp_pelanggan',
        'p.alamat as alamat_pelanggan',
        'pn.id_penyerahan_penjualan',
        'pn.no_penyerahan_penjualan',
        'pn.tanggal_penyerahan',
        'pn.status_penyerahan',
        'pn.tanggal_posting as tanggal_posting_penyerahan',
        'g.kode_gudang',
        'g.nama_gudang',
    ])
    ->first();

if (!$faktur) {
    ?>
    <div class="alert alert-danger">
        Data transaksi POS tidak ditemukan.
    </div>

    <a href="<?= esc(admin_page_url('penjualan/kasir/riwayat')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
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
        'p.barcode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_faktur_penjualan_detail', 'asc')
    ->get();

$penyerahan_detail_rows = Capsule::table('tb_penyerahan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_penyerahan_penjualan', (int) ($faktur->id_penyerahan_penjualan ?? 0))
    ->select([
        'd.*',
        'p.kode_produk',
        'p.barcode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->orderBy('d.id_penyerahan_penjualan_detail', 'asc')
    ->get();

$pembayaran_rows = Capsule::table('tb_pembayaran_penjualan as by')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'by.id_coa_kas_bank')
    ->where('by.id_entitas', $id_entitas)
    ->where('by.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'by.*',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->orderByDesc('by.id_pembayaran_penjualan')
    ->get();

$jurnal_rows = Capsule::table('tb_log_jurnal_sumber as l')
    ->join('tb_jurnal as j', 'j.id_jurnal', '=', 'l.id_jurnal')
    ->where('l.id_entitas', $id_entitas)
    ->where(function ($q) use ($faktur, $id_faktur_penjualan) {
        $q->where(function ($qq) use ($id_faktur_penjualan) {
            $qq->where('l.tabel_sumber', 'tb_faktur_penjualan')
                ->where('l.id_sumber', $id_faktur_penjualan);
        });

        if (!empty($faktur->id_penyerahan_penjualan)) {
            $q->orWhere(function ($qq) use ($faktur) {
                $qq->where('l.tabel_sumber', 'tb_penyerahan_penjualan')
                    ->where('l.id_sumber', (int) $faktur->id_penyerahan_penjualan);
            });
        }
    })
    ->select([
        'j.id_jurnal',
        'j.no_jurnal',
        'j.tanggal_jurnal',
        'j.kode_jenis_transaksi',
        'j.keterangan',
        'j.total_debit',
        'j.total_kredit',
        'j.status_jurnal',
        'l.tabel_sumber',
        'l.id_sumber',
        'l.no_sumber',
    ])
    ->orderBy('j.tanggal_jurnal', 'asc')
    ->orderBy('j.id_jurnal', 'asc')
    ->get();

$jurnal_ids = $jurnal_rows->pluck('id_jurnal')->map(fn($v) => (int) $v)->all();

$jurnal_detail_rows = collect();

if (!empty($jurnal_ids)) {
    $jurnal_detail_rows = Capsule::table('tb_jurnal_detail as d')
        ->join('tb_jurnal as j', 'j.id_jurnal', '=', 'd.id_jurnal')
        ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'd.id_coa')
        ->whereIn('d.id_jurnal', $jurnal_ids)
        ->select([
            'd.*',
            'j.no_jurnal',
            'j.kode_jenis_transaksi',
            'c.kode_coa',
            'c.nama_coa',
        ])
        ->orderBy('j.tanggal_jurnal', 'asc')
        ->orderBy('j.id_jurnal', 'asc')
        ->orderBy('d.urutan', 'asc')
        ->get()
        ->groupBy('id_jurnal');
}

$total_qty = 0.0;
foreach ($detail_rows as $d) {
    $total_qty += (float) ($d->qty ?? 0);
}

$is_pos = str_starts_with((string) ($faktur->catatan ?? ''), 'POS');
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Transaksi Kasir / POS</h1>
            <p class="page-subtitle">
                Detail transaksi POS, penyerahan stok, pembayaran, dan jurnal.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= esc(admin_page_url('penjualan/kasir/riwayat')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

            <a
                href="<?= esc(admin_url('index.php?menu=penjualan/kasir/struk&id=' . (int) $faktur->id_faktur_penjualan)) ?>"
                class="btn btn-outline-primary"
                target="_blank">
                <i class="bi bi-printer me-1"></i> Cetak Struk
            </a>


            <?php if (($faktur->status_faktur ?? '') === 'posted'): ?>
                <a
                    href="<?= esc(admin_url('index.php?menu=penjualan/kasir/batal&id=' . (int) $faktur->id_faktur_penjualan)) ?>"
                    class="btn btn-outline-danger"
                    onclick="return confirm('Batalkan transaksi POS ini? Stok akan dikembalikan dan status dokumen akan menjadi batal.')">
                    <i class="bi bi-x-circle me-1"></i> Batalkan
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$is_pos): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Catatan transaksi ini tidak diawali dengan POS. Pastikan data yang dibuka memang transaksi kasir.
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">No Transaksi</div>
                <div class="h5 mb-1"><?= esc((string) ($faktur->no_faktur_penjualan ?? '-')) ?></div>
                <div><?= pos_detail_badge_status((string) ($faktur->status_faktur ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal Transaksi</div>
                <div class="h5 mb-1"><?= esc(pos_detail_tanggal($faktur->tanggal_faktur ?? null)) ?></div>
                <div class="text-muted small">
                    Posting: <?= esc(pos_detail_tanggal($faktur->tanggal_posting ?? null, true)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Jenis Pembayaran</div>
                <div class="h5 mb-1"><?= pos_detail_badge_bayar((string) ($faktur->jenis_pembayaran ?? '-')) ?></div>
                <div class="text-muted small">
                    Sisa piutang: <?= pos_detail_rupiah($faktur->sisa_piutang ?? 0) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Transaksi</div>
                <div class="h5 mb-1"><?= pos_detail_rupiah($faktur->total ?? 0) ?></div>
                <div class="text-muted small">
                    Qty item: <?= esc(pos_detail_qty($total_qty)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Informasi Transaksi</h2>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <tr>
                            <th width="190" class="text-muted">Pelanggan</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($faktur->nama_pelanggan ?? '-')) ?></div>
                                <div class="text-muted small">
                                    <?= esc((string) ($faktur->kode_pelanggan ?? '-')) ?>
                                    <?php if (!empty($faktur->no_hp_pelanggan)): ?>
                                        · <?= esc((string) $faktur->no_hp_pelanggan) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Alamat Pelanggan</th>
                            <td><?= esc((string) ($faktur->alamat_pelanggan ?? '-')) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">No Penyerahan</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($faktur->no_penyerahan_penjualan ?? '-')) ?></div>
                                <div class="text-muted small">
                                    Tanggal: <?= esc(pos_detail_tanggal($faktur->tanggal_penyerahan ?? null)) ?>
                                    · Status: <?= strip_tags(pos_detail_badge_status((string) ($faktur->status_penyerahan ?? '-'))) ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Gudang</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($faktur->nama_gudang ?? '-')) ?></div>
                                <div class="text-muted small"><?= esc((string) ($faktur->kode_gudang ?? '-')) ?></div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Jatuh Tempo</th>
                            <td><?= esc(pos_detail_tanggal($faktur->jatuh_tempo ?? null)) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Catatan</th>
                            <td><?= esc((string) ($faktur->catatan ?? '-')) ?></td>
                        </tr>
                    </table>
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
                            <td class="text-end fw-semibold"><?= pos_detail_rupiah($faktur->subtotal ?? 0) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Diskon</th>
                            <td class="text-end text-danger fw-semibold">
                                <?= pos_detail_rupiah($faktur->diskon ?? 0) ?>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">PPN</th>
                            <td class="text-end fw-semibold"><?= pos_detail_rupiah($faktur->ppn ?? 0) ?></td>
                        </tr>

                        <tr>
                            <td colspan="2"><hr class="my-2"></td>
                        </tr>

                        <tr>
                            <th class="fs-5">Total</th>
                            <td class="text-end fs-5 fw-bold"><?= pos_detail_rupiah($faktur->total ?? 0) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Sisa Piutang</th>
                            <td class="text-end text-danger fw-semibold">
                                <?= pos_detail_rupiah($faktur->sisa_piutang ?? 0) ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php if ((float) ($faktur->sisa_piutang ?? 0) > 0): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Transaksi ini masih memiliki sisa piutang.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        Transaksi ini sudah lunas.
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
                <h2 class="h5 mb-1">Detail Produk Dijual</h2>
                <div class="text-muted small">Produk yang masuk ke faktur POS.</div>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="120">Kode</th>
                        <th width="155">Barcode</th>
                        <th>Produk</th>
                        <th width="110">Satuan</th>
                        <th width="110" class="text-end">Qty</th>
                        <th width="145" class="text-end">Harga</th>
                        <th width="130" class="text-end">Diskon</th>
                        <th width="160" class="text-end">Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail_rows->count() === 0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                Detail produk belum tersedia.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detail_rows as $i => $row): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= esc((string) ($row->kode_produk ?? '-')) ?></td>
                                <td><code><?= esc((string) ($row->barcode_produk ?? '-')) ?></code></td>
                                <td><?= esc((string) ($row->nama_produk ?? '-')) ?></td>
                                <td><?= esc((string) ($row->nama_satuan ?? '-')) ?></td>
                                <td class="text-end fw-semibold"><?= esc(pos_detail_qty($row->qty ?? 0)) ?></td>
                                <td class="text-end"><?= pos_detail_rupiah($row->harga ?? 0) ?></td>
                                <td class="text-end text-danger"><?= pos_detail_rupiah($row->diskon ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= pos_detail_rupiah($row->subtotal ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <?php if ($detail_rows->count() > 0): ?>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="8" class="text-end">Total</th>
                            <th class="text-end"><?= pos_detail_rupiah($faktur->subtotal ?? 0) ?></th>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Penyerahan Stok</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th>Produk</th>
                                <th width="95" class="text-end">Qty</th>
                                <th width="135" class="text-end">HPP Satuan</th>
                                <th width="145" class="text-end">HPP Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($penyerahan_detail_rows->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Detail penyerahan belum tersedia.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($penyerahan_detail_rows as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_produk ?? '-')) ?></div>
                                            <div class="text-muted small"><?= esc((string) ($row->kode_produk ?? '-')) ?> · Barcode: <?= esc((string) ($row->barcode_produk ?? '-')) ?></div>
                                        </td>
                                        <td class="text-end"><?= esc(pos_detail_qty($row->qty ?? 0)) ?></td>
                                        <td class="text-end"><?= pos_detail_rupiah($row->hpp_satuan ?? 0) ?></td>
                                        <td class="text-end fw-semibold"><?= pos_detail_rupiah($row->hpp_total ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Penyerahan posted otomatis mengurangi saldo stok dan membuat jurnal HPP.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Pembayaran</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th>No Pembayaran</th>
                                <th width="110">Tanggal</th>
                                <th width="115">Metode</th>
                                <th width="150" class="text-end">Jumlah</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($pembayaran_rows->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada pembayaran. Transaksi ini kemungkinan kredit.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pembayaran_rows as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->no_pembayaran_penjualan ?? '-')) ?></div>
                                            <div class="text-muted small">
                                                <?= esc((string) ($row->kode_coa ?? '-')) ?>
                                                -
                                                <?= esc((string) ($row->nama_coa ?? '-')) ?>
                                            </div>
                                            <?php if ((string) ($row->metode_pembayaran ?? '') === 'transfer'): ?>
                                                <div class="small mt-1">
                                                    Ref/Kode cek: <b><?= esc((string) ($row->no_referensi_transfer ?? '-')) ?></b>
                                                </div>
                                                <?php if (!empty($row->nama_pengirim_transfer)): ?>
                                                    <div class="text-muted small">Pengirim: <?= esc((string) $row->nama_pengirim_transfer) ?></div>
                                                <?php endif; ?>
                                                <?php if (!empty($row->bukti_bayar_pos)): ?>
                                                    <a class="small" target="_blank" href="<?= esc(base_url((string) $row->bukti_bayar_pos)) ?>">Lihat bukti transfer</a>
                                                <?php else: ?>
                                                    <div class="text-muted small">Bukti foto tidak wajib/opsional.</div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc(pos_detail_tanggal($row->tanggal_pembayaran ?? null)) ?></td>
                                        <td><?= pos_detail_badge_bayar((string) ($row->metode_pembayaran ?? '')) ?></td>
                                        <td class="text-end fw-semibold"><?= pos_detail_rupiah($row->jumlah_bayar ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Pembayaran tunai/transfer POS dicatat sebagai bukti bayar. Jurnal kas/bank berasal dari faktur POS agar tidak dobel.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Jurnal Terbentuk</h2>

        <?php if ($jurnal_rows->count() === 0): ?>
            <div class="alert alert-warning mb-0">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Belum ada jurnal yang terhubung dengan transaksi POS ini.
            </div>
        <?php else: ?>
            <div class="accordion" id="accordionJurnalPos">
                <?php foreach ($jurnal_rows as $idx => $jurnal): ?>
                    <?php
                    $collapse_id = 'jurnalPosCollapse' . (int) $jurnal->id_jurnal;
                    $detail_jurnal = $jurnal_detail_rows->get((int) $jurnal->id_jurnal, collect());
                    ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading<?= esc($collapse_id) ?>">
                            <button
                                class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= esc($collapse_id) ?>"
                                aria-expanded="<?= $idx === 0 ? 'true' : 'false' ?>"
                                aria-controls="<?= esc($collapse_id) ?>">
                                <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2 pe-3">
                                    <div>
                                        <span class="fw-semibold"><?= esc((string) ($jurnal->no_jurnal ?? '-')) ?></span>
                                        <span class="text-muted small ms-2">
                                            <?= esc((string) ($jurnal->kode_jenis_transaksi ?? '-')) ?>
                                        </span>
                                    </div>
                                    <div class="text-muted small">
                                        Debit <?= pos_detail_rupiah($jurnal->total_debit ?? 0) ?>
                                        · Kredit <?= pos_detail_rupiah($jurnal->total_kredit ?? 0) ?>
                                    </div>
                                </div>
                            </button>
                        </h2>

                        <div
                            id="<?= esc($collapse_id) ?>"
                            class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>"
                            aria-labelledby="heading<?= esc($collapse_id) ?>"
                            data-bs-parent="#accordionJurnalPos">
                            <div class="accordion-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-3">
                                        <div class="text-muted small">Tanggal</div>
                                        <div class="fw-semibold"><?= esc(pos_detail_tanggal($jurnal->tanggal_jurnal ?? null)) ?></div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="text-muted small">Status</div>
                                        <div><?= pos_detail_badge_status((string) ($jurnal->status_jurnal ?? '-')) ?></div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="text-muted small">Sumber</div>
                                        <div class="fw-semibold"><?= esc((string) ($jurnal->no_sumber ?? '-')) ?></div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="text-muted small">Tabel Sumber</div>
                                        <div class="fw-semibold"><?= esc((string) ($jurnal->tabel_sumber ?? '-')) ?></div>
                                    </div>
                                </div>

                                <div class="table-responsive border rounded">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="55" class="text-center">No</th>
                                                <th width="130">Kode COA</th>
                                                <th>Akun</th>
                                                <th width="155" class="text-end">Debit</th>
                                                <th width="155" class="text-end">Kredit</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php if ($detail_jurnal->count() === 0): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">
                                                        Detail jurnal tidak tersedia.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($detail_jurnal as $i => $d): ?>
                                                    <tr>
                                                        <td class="text-center"><?= $i + 1 ?></td>
                                                        <td class="fw-semibold"><?= esc((string) ($d->kode_coa ?? '-')) ?></td>
                                                        <td><?= esc((string) ($d->nama_coa ?? '-')) ?></td>
                                                        <td class="text-end"><?= pos_detail_rupiah($d->debit ?? 0) ?></td>
                                                        <td class="text-end"><?= pos_detail_rupiah($d->kredit ?? 0) ?></td>
                                                        <td><?= esc((string) ($d->keterangan_baris ?? '-')) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>

                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="3" class="text-end">Total</th>
                                                <th class="text-end"><?= pos_detail_rupiah($jurnal->total_debit ?? 0) ?></th>
                                                <th class="text-end"><?= pos_detail_rupiah($jurnal->total_kredit ?? 0) ?></th>
                                                <th></th>
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
