<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../_fungsi_penjualan.php';

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_penyerahan_penjualan = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if (!function_exists('penyerahan_detail_rupiah')) {
    function penyerahan_detail_rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('penyerahan_detail_qty')) {
    function penyerahan_detail_qty($value): string
    {
        $value = (float) $value;

        if (abs($value - round($value)) < 0.000001) {
            return number_format($value, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($value, 3, '.', ','), '0'), '.');
    }
}

if (!function_exists('penyerahan_detail_tanggal')) {
    function penyerahan_detail_tanggal($tanggal, bool $dengan_jam = false): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date($dengan_jam ? 'd/m/Y H:i' : 'd/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('penyerahan_detail_badge_status')) {
    function penyerahan_detail_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        if ($status === 'posted') {
            return '<span class="badge bg-success">Posted</span>';
        }

        if ($status === 'draft') {
            return '<span class="badge bg-secondary">Draft</span>';
        }

        if ($status === 'batal') {
            return '<span class="badge bg-danger">Batal</span>';
        }

        return '<span class="badge bg-secondary">' . esc(ucwords(str_replace('_', ' ', $status ?: '-'))) . '</span>';
    }
}

if (!function_exists('penyerahan_detail_badge_pesanan')) {
    function penyerahan_detail_badge_pesanan(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        if ($status === 'selesai') {
            return '<span class="badge bg-success-subtle text-success">Selesai</span>';
        }

        if ($status === 'diproses') {
            return '<span class="badge bg-primary-subtle text-primary">Diproses</span>';
        }

        if ($status === 'terkonfirmasi') {
            return '<span class="badge bg-info-subtle text-info">Terkonfirmasi</span>';
        }

        if ($status === 'draft') {
            return '<span class="badge bg-secondary-subtle text-secondary">Draft</span>';
        }

        if ($status === 'batal') {
            return '<span class="badge bg-danger-subtle text-danger">Batal</span>';
        }

        return '<span class="badge bg-secondary-subtle text-secondary">-</span>';
    }
}

if (!function_exists('penyerahan_detail_badge_faktur')) {
    function penyerahan_detail_badge_faktur(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        if ($status === 'posted') {
            return '<span class="badge bg-success">Posted</span>';
        }

        if ($status === 'draft') {
            return '<span class="badge bg-secondary">Draft</span>';
        }

        if ($status === 'batal') {
            return '<span class="badge bg-danger">Batal</span>';
        }

        return '<span class="badge bg-secondary">-</span>';
    }
}

$penyerahan = Capsule::table('tb_penyerahan_penjualan as pp')
    ->leftJoin('tb_pesanan_penjualan as ps', 'ps.id_pesanan_penjualan', '=', 'pp.id_pesanan_penjualan')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'pp.id_pelanggan')
    ->leftJoin('tb_gudang as gd', 'gd.id_gudang', '=', 'pp.id_gudang')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'pp.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'pp.diposting_oleh')
    ->where('pp.id_entitas', $id_entitas)
    ->where('pp.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select([
        'pp.*',
        'ps.no_pesanan_penjualan',
        'ps.tanggal_pesanan',
        'ps.status_pesanan',
        'ps.sumber_pesanan',
        'ps.tanggal_kirim_rencana',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'pl.email as email_pelanggan',
        'gd.kode_gudang',
        'gd.nama_gudang',
        'gd.jenis_gudang',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_posting',
    ])
    ->first();

if (!$penyerahan) {
    ?>
    <div class="alert alert-danger">
        Data penyerahan penjualan tidak ditemukan.
    </div>

    <a href="<?= esc(admin_page_url('penjualan/penyerahan')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
    <?php
    return;
}

$detail_rows = Capsule::table('tb_penyerahan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_saldo_stok as ss', function ($join) use ($id_entitas, $penyerahan) {
        $join->on('ss.id_referensi_barang', '=', 'd.id_produk')
            ->where('ss.id_entitas', '=', $id_entitas)
            ->where('ss.jenis_barang', '=', 'produk')
            ->where('ss.id_gudang', '=', (int) ($penyerahan->id_gudang ?? 0));
    })
    ->where('d.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
        'ss.qty_saldo',
        'ss.nilai_saldo',
        'ss.hpp_rata_rata',
    ])
    ->orderBy('d.id_penyerahan_penjualan_detail', 'asc')
    ->get();

$mutasi_rows = Capsule::table('tb_mutasi_stok as m')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'm.id_referensi_barang')
            ->where('m.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'm.id_gudang')
    ->where('m.id_entitas', $id_entitas)
    ->where('m.tabel_sumber', 'tb_penyerahan_penjualan')
    ->where('m.id_sumber', $id_penyerahan_penjualan)
    ->select([
        'm.*',
        'p.kode_produk',
        'p.nama_produk',
        'g.kode_gudang',
        'g.nama_gudang',
    ])
    ->orderBy('m.id_mutasi_stok', 'asc')
    ->get();

$faktur_rows = Capsule::table('tb_faktur_penjualan as f')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.id_penyerahan_penjualan', $id_penyerahan_penjualan)
    ->select(['f.*'])
    ->orderByDesc('f.id_faktur_penjualan')
    ->get();

$jurnal_rows = Capsule::table('tb_log_jurnal_sumber as l')
    ->join('tb_jurnal as j', 'j.id_jurnal', '=', 'l.id_jurnal')
    ->where('l.id_entitas', $id_entitas)
    ->where('l.tabel_sumber', 'tb_penyerahan_penjualan')
    ->where('l.id_sumber', $id_penyerahan_penjualan)
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

$jurnal_ids = $jurnal_rows->pluck('id_jurnal')->map(fn ($v) => (int) $v)->all();

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
$total_hpp = 0.0;

foreach ($detail_rows as $d) {
    $total_qty += (float) ($d->qty ?? 0);
    $total_hpp += (float) ($d->hpp_total ?? 0);
}

$url_kembali = $back_url !== '' ? $back_url : admin_page_url('penjualan/penyerahan');
$label_alamat_tujuan = match ((string) ($penyerahan->jenis_alamat_tujuan ?? 'pelanggan')) {
    'toko' => 'Di tempat / toko',
    'lain' => 'Alamat lain',
    default => 'Alamat pelanggan',
};
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Penyerahan Penjualan</h1>
            <p class="page-subtitle">
                Detail surat jalan, stok keluar, faktur terkait, dan jurnal HPP.
            </p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= esc($url_kembali) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>

            <a
                href="<?= esc(admin_url('index.php?menu=penjualan/penyerahan/cetak&id=' . (int) $penyerahan->id_penyerahan_penjualan)) ?>"
                class="btn btn-outline-primary"
                target="_blank">
                <i class="bi bi-printer me-1"></i> Cetak Surat Jalan
            </a>

            <?php if (($penyerahan->status_penyerahan ?? '') === 'posted'): ?>
                <a
                    href="<?= esc(admin_page_url('penjualan/faktur/tambah') . '&id_penyerahan_penjualan=' . (int) $penyerahan->id_penyerahan_penjualan) ?>"
                    class="btn btn-outline-info">
                    <i class="bi bi-receipt me-1"></i> Buat Faktur
                </a>
            <?php endif; ?>

            <?php if (($penyerahan->status_penyerahan ?? '') === 'draft'): ?>
                <a
                    href="<?= esc(admin_page_url('penjualan/penyerahan/edit') . '&id=' . (int) $penyerahan->id_penyerahan_penjualan) ?>"
                    class="btn btn-outline-primary">
                    <i class="bi bi-pencil-square me-1"></i> Edit
                </a>

                <a
                    href="<?= esc(admin_url('index.php?menu=penjualan/penyerahan/posting&id=' . (int) $penyerahan->id_penyerahan_penjualan)) ?>"
                    class="btn btn-success"
                    onclick="return confirm('Posting penyerahan ini? Stok produk akan berkurang dan jurnal HPP akan dibuat.')">
                    <i class="bi bi-check2-circle me-1"></i> Posting
                </a>

                <a
                    href="<?= esc(admin_url('index.php?menu=penjualan/penyerahan/hapus&id=' . (int) $penyerahan->id_penyerahan_penjualan)) ?>"
                    class="btn btn-outline-danger"
                    onclick="return confirm('Hapus penyerahan draft ini?')">
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
                <div class="text-muted small">No Penyerahan</div>
                <div class="h5 mb-1"><?= esc((string) ($penyerahan->no_penyerahan_penjualan ?? '-')) ?></div>
                <div><?= penyerahan_detail_badge_status((string) ($penyerahan->status_penyerahan ?? '-')) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal Penyerahan</div>
                <div class="h5 mb-1"><?= esc(penyerahan_detail_tanggal($penyerahan->tanggal_penyerahan ?? null)) ?></div>
                <div class="text-muted small">
                    Posting: <?= esc(penyerahan_detail_tanggal($penyerahan->tanggal_posting ?? null, true)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Pesanan Penjualan</div>
                <div class="h5 mb-1"><?= esc((string) ($penyerahan->no_pesanan_penjualan ?? '-')) ?></div>
                <div><?= penyerahan_detail_badge_pesanan($penyerahan->status_pesanan ?? null) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total HPP</div>
                <div class="h5 mb-1"><?= penyerahan_detail_rupiah($total_hpp) ?></div>
                <div class="text-muted small">
                    Qty: <?= esc(penyerahan_detail_qty($total_qty)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Informasi Penyerahan</h2>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <tr>
                            <th width="190" class="text-muted">Pelanggan</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($penyerahan->nama_pelanggan ?? '-')) ?></div>
                                <div class="text-muted small">
                                    <?= esc((string) ($penyerahan->kode_pelanggan ?? '-')) ?>
                                    <?php if (!empty($penyerahan->no_hp_pelanggan)): ?>
                                        · <?= esc((string) $penyerahan->no_hp_pelanggan) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Alamat Master Pelanggan</th>
                            <td><?= nl2br(esc((string) ($penyerahan->alamat_pelanggan ?? '-'))) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Alamat Tujuan Surat Jalan</th>
                            <td>
                                <div class="fw-semibold"><?= esc($label_alamat_tujuan) ?></div>
                                <div><?= nl2br(esc((string) ($penyerahan->alamat_tujuan ?? '-'))) ?></div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Email Pelanggan</th>
                            <td><?= esc((string) ($penyerahan->email_pelanggan ?? '-')) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">No Pesanan</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($penyerahan->no_pesanan_penjualan ?? '-')) ?></div>
                                <div class="text-muted small">
                                    Tanggal: <?= esc(penyerahan_detail_tanggal($penyerahan->tanggal_pesanan ?? null)) ?>
                                    · Sumber: <?= esc((string) ($penyerahan->sumber_pesanan ?? '-')) ?>
                                    · Status: <?= strip_tags(penyerahan_detail_badge_pesanan($penyerahan->status_pesanan ?? null)) ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Rencana Kirim</th>
                            <td><?= esc(penyerahan_detail_tanggal($penyerahan->tanggal_kirim_rencana ?? null)) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Gudang</th>
                            <td>
                                <div class="fw-semibold"><?= esc((string) ($penyerahan->nama_gudang ?? '-')) ?></div>
                                <div class="text-muted small">
                                    <?= esc((string) ($penyerahan->kode_gudang ?? '-')) ?>
                                    <?php if (!empty($penyerahan->jenis_gudang)): ?>
                                        · <?= esc((string) $penyerahan->jenis_gudang) ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th class="text-muted">Catatan</th>
                            <td><?= nl2br(esc((string) ($penyerahan->catatan ?? '-'))) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Ringkasan HPP</h2>

                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0">
                        <tr>
                            <th class="text-muted">Jumlah Item</th>
                            <td class="text-end fw-semibold"><?= esc(number_format($detail_rows->count(), 0, '.', ',')) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Total Qty</th>
                            <td class="text-end fw-semibold"><?= esc(penyerahan_detail_qty($total_qty)) ?></td>
                        </tr>

                        <tr>
                            <th class="text-muted">Total HPP</th>
                            <td class="text-end fw-semibold"><?= penyerahan_detail_rupiah($total_hpp) ?></td>
                        </tr>

                        <tr>
                            <td colspan="2"><hr class="my-2"></td>
                        </tr>

                        <tr>
                            <th class="fs-5">Nilai Stok Keluar</th>
                            <td class="text-end fs-5 fw-bold"><?= penyerahan_detail_rupiah($total_hpp) ?></td>
                        </tr>
                    </table>
                </div>

                <?php if (($penyerahan->status_penyerahan ?? '') === 'posted'): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="bi bi-check-circle me-1"></i>
                        Penyerahan sudah posted. Stok produk sudah berkurang dan jurnal HPP sudah dibuat.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Penyerahan masih draft. Stok belum berkurang sampai dokumen diposting.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Informasi Surat Jalan / Pengiriman</h2>
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">Kurir</div>
                <div class="fw-semibold"><?= esc((string) ($penyerahan->kurir_pengiriman ?? '-')) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">No Resi / Driver</div>
                <div class="fw-semibold"><?= esc((string) ($penyerahan->no_resi_pengiriman ?? '-')) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Biaya Pengiriman</div>
                <div class="fw-semibold"><?= penyerahan_detail_rupiah($penyerahan->biaya_pengiriman ?? 0) ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Jurnal Ongkir</div>
                <div class="fw-semibold"><?= ((float) ($penyerahan->biaya_pengiriman ?? 0) > 0) ? 'Debit Beban Pengiriman / Kredit Kas' : '-' ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h2 class="h5 mb-1">Detail Produk Diserahkan</h2>
                <div class="text-muted small">
                    Produk yang dikirim ke pelanggan. HPP satuan berasal dari HPP rata-rata saldo stok saat draft/posting.
                </div>
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
                        <th width="110" class="text-end">Qty</th>
                        <th width="150" class="text-end">HPP Satuan</th>
                        <th width="160" class="text-end">HPP Total</th>
                        <th width="130" class="text-end">Stok Saat Ini</th>
                        <th width="190">Catatan</th>
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
                                <td><?= esc((string) ($row->nama_produk ?? '-')) ?></td>
                                <td><?= esc((string) ($row->nama_satuan ?? '-')) ?></td>
                                <td class="text-end fw-semibold"><?= esc(penyerahan_detail_qty($row->qty ?? 0)) ?></td>
                                <td class="text-end"><?= penyerahan_detail_rupiah($row->hpp_satuan ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= penyerahan_detail_rupiah($row->hpp_total ?? 0) ?></td>
                                <td class="text-end"><?= esc(penyerahan_detail_qty($row->qty_saldo ?? 0)) ?></td>
                                <td><?= esc((string) ($row->catatan ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <?php if ($detail_rows->count() > 0): ?>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="4" class="text-end">Total</th>
                            <th class="text-end"><?= esc(penyerahan_detail_qty($total_qty)) ?></th>
                            <th></th>
                            <th class="text-end"><?= penyerahan_detail_rupiah($total_hpp) ?></th>
                            <th colspan="2"></th>
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
                <h2 class="h5 mb-3">Mutasi Stok</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th>Produk</th>
                                <th width="100" class="text-end">Keluar</th>
                                <th width="135" class="text-end">Harga</th>
                                <th width="145" class="text-end">Nilai</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($mutasi_rows->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada mutasi stok. Mutasi dibuat saat penyerahan diposting.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($mutasi_rows as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->nama_produk ?? '-')) ?></div>
                                            <div class="text-muted small">
                                                <?= esc((string) ($row->kode_produk ?? '-')) ?>
                                                · <?= esc((string) ($row->nama_gudang ?? '-')) ?>
                                            </div>
                                        </td>
                                        <td class="text-end"><?= esc(penyerahan_detail_qty($row->qty_keluar ?? 0)) ?></td>
                                        <td class="text-end"><?= penyerahan_detail_rupiah($row->harga_satuan ?? 0) ?></td>
                                        <td class="text-end fw-semibold"><?= penyerahan_detail_rupiah($row->nilai_total ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Mutasi stok keluar terbentuk saat posting penyerahan.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Faktur Terkait</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="55" class="text-center">No</th>
                                <th>No Faktur</th>
                                <th width="115">Tanggal</th>
                                <th width="105">Status</th>
                                <th width="150" class="text-end">Total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($faktur_rows->count() === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada faktur penjualan dari penyerahan ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faktur_rows as $i => $row): ?>
                                    <tr>
                                        <td class="text-center"><?= $i + 1 ?></td>
                                        <td>
                                            <div class="fw-semibold"><?= esc((string) ($row->no_faktur_penjualan ?? '-')) ?></div>
                                            <div class="text-muted small">
                                                Jenis bayar: <?= esc((string) ($row->jenis_pembayaran ?? '-')) ?>
                                            </div>
                                        </td>
                                        <td><?= esc(penyerahan_detail_tanggal($row->tanggal_faktur ?? null)) ?></td>
                                        <td><?= penyerahan_detail_badge_faktur($row->status_faktur ?? null) ?></td>
                                        <td class="text-end fw-semibold"><?= penyerahan_detail_rupiah($row->total ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small mt-2">
                    Faktur penjualan dibuat setelah penyerahan berstatus posted.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Jurnal Terbentuk</h2>
                <div class="text-muted small">
                    Jurnal HPP/pengurangan stok dibuat saat penyerahan diposting.
                </div>
            </div>

            <?php if ($jurnal_rows->count() > 0): ?>
                <span class="badge bg-success-subtle text-success">
                    <?= esc(number_format($jurnal_rows->count(), 0, '.', ',')) ?> jurnal ditemukan
                </span>
            <?php endif; ?>
        </div>

        <?php if ($jurnal_rows->count() === 0): ?>
            <div class="alert alert-warning mb-0">
                <div class="fw-semibold mb-1">Belum ada jurnal untuk penyerahan ini.</div>
                <div class="small">
                    Jika penyerahan sudah posted, coba cek ulang proses posting atau jurnal sumber
                    <strong>tb_penyerahan_penjualan</strong> dengan ID <?= (int) $id_penyerahan_penjualan ?>.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($jurnal_rows as $jr): ?>
                <?php $details = $jurnal_detail_rows->get($jr->id_jurnal, collect()); ?>

                <div class="border rounded-4 mb-3 overflow-hidden">
                    <div class="bg-light px-3 py-3 d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="fw-bold"><?= esc((string) ($jr->no_jurnal ?? '-')) ?></div>
                            <div class="text-muted small">
                                <?= esc(penyerahan_detail_tanggal($jr->tanggal_jurnal ?? null)) ?>
                                · <?= esc((string) ($jr->kode_jenis_transaksi ?? '-')) ?>
                                · Status: <?= esc((string) ($jr->status_jurnal ?? '-')) ?>
                            </div>
                            <div class="small mt-1"><?= esc((string) ($jr->keterangan ?? '-')) ?></div>
                        </div>

                        <div class="text-end small">
                            <div>Debit: <strong><?= penyerahan_detail_rupiah($jr->total_debit ?? 0) ?></strong></div>
                            <div>Kredit: <strong><?= penyerahan_detail_rupiah($jr->total_kredit ?? 0) ?></strong></div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="55" class="text-center">No</th>
                                    <th width="135">Kode Akun</th>
                                    <th>Nama Akun</th>
                                    <th width="160" class="text-end">Debit</th>
                                    <th width="160" class="text-end">Kredit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($details->count() === 0): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-3">
                                            Detail jurnal belum ditemukan.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($details as $idx => $jd): ?>
                                        <tr>
                                            <td class="text-center"><?= $idx + 1 ?></td>
                                            <td class="fw-semibold"><?= esc((string) ($jd->kode_coa ?? '-')) ?></td>
                                            <td><?= esc((string) ($jd->nama_coa ?? '-')) ?></td>
                                            <td class="text-end"><?= ((float) ($jd->debit ?? 0) > 0) ? penyerahan_detail_rupiah($jd->debit ?? 0) : '-' ?></td>
                                            <td class="text-end"><?= ((float) ($jd->kredit ?? 0) > 0) ? penyerahan_detail_rupiah($jd->kredit ?? 0) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="3" class="text-end">Total</th>
                                    <th class="text-end"><?= penyerahan_detail_rupiah($jr->total_debit ?? 0) ?></th>
                                    <th class="text-end"><?= penyerahan_detail_rupiah($jr->total_kredit ?? 0) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="alert alert-info mb-0 small">
                Untuk penyerahan online/penyerahan barang, jurnal pengurangan stok yang benar adalah
                <strong>Debit HPP</strong> dan <strong>Kredit Persediaan Barang Jadi</strong>.
            </div>
        <?php endif; ?>
    </div>
</div>

