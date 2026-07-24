<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_mutasi_stok = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/mutasi-stok');
}

function format_qty_mutasi_detail($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

function badge_jenis_barang_mutasi_detail(string $jenis): string
{
    if ($jenis === 'bahan_baku') {
        return '<span class="badge bg-info-subtle text-info">Bahan Baku</span>';
    }

    if ($jenis === 'produk') {
        return '<span class="badge bg-success-subtle text-success">Produk</span>';
    }

    return '<span class="badge bg-secondary">-</span>';
}

function badge_jenis_mutasi_detail(string $jenis): string
{
    $jenis = strtolower($jenis);
    $label = ucwords(str_replace('_', ' ', $jenis));
    $class = 'bg-secondary';

    if (in_array($jenis, ['masuk', 'penerimaan_pembelian', 'hasil_produksi', 'saldo_awal'], true)) {
        $class = 'bg-success';
    }

    if (in_array($jenis, ['keluar', 'pengambilan_bahan', 'penyerahan_penjualan'], true)) {
        $class = 'bg-danger';
    }

    if (in_array($jenis, ['opname_plus', 'opname_masuk'], true)) {
        $class = 'bg-primary';
    }

    if (in_array($jenis, ['opname_minus', 'opname_keluar'], true)) {
        $class = 'bg-warning text-dark';
    }

    return '<span class="badge ' . $class . '">' . esc($label) . '</span>';
}

$mutasi_bahan = MutasiStokORM::query()
    ->from('tb_mutasi_stok as ms')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'ms.id_referensi_barang')
            ->where('ms.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'b.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ms.id_gudang')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'ms.id_entitas')
    ->where('ms.id_entitas', $id_entitas)
    ->where('ms.id_mutasi_stok', $id_mutasi_stok)
    ->where('ms.jenis_barang', 'bahan_baku')
    ->selectRaw("
        ms.id_mutasi_stok,
        ms.id_entitas,
        ms.tanggal_mutasi,
        ms.jenis_barang,
        ms.id_referensi_barang,
        ms.id_gudang,
        ms.jenis_mutasi,
        ms.qty_masuk,
        ms.qty_keluar,
        ms.harga_satuan,
        ms.nilai_total,
        ms.tabel_sumber,
        ms.id_sumber,
        ms.no_sumber,
        ms.keterangan,
        ms.tanggal_dibuat,
        ms.dibuat_oleh,
        ms.tanggal_diubah,
        ms.diubah_oleh,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
        b.harga_standar,
        b.stok_minimum,
        st.nama_satuan,
        g.kode_gudang,
        g.nama_gudang,
        e.kode_entitas,
        e.nama_entitas
    ");

$mutasi_produk = MutasiStokORM::query()
    ->from('tb_mutasi_stok as ms')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'ms.id_referensi_barang')
            ->where('ms.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as st', 'st.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'ms.id_gudang')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'ms.id_entitas')
    ->where('ms.id_entitas', $id_entitas)
    ->where('ms.id_mutasi_stok', $id_mutasi_stok)
    ->where('ms.jenis_barang', 'produk')
    ->selectRaw("
        ms.id_mutasi_stok,
        ms.id_entitas,
        ms.tanggal_mutasi,
        ms.jenis_barang,
        ms.id_referensi_barang,
        ms.id_gudang,
        ms.jenis_mutasi,
        ms.qty_masuk,
        ms.qty_keluar,
        ms.harga_satuan,
        ms.nilai_total,
        ms.tabel_sumber,
        ms.id_sumber,
        ms.no_sumber,
        ms.keterangan,
        ms.tanggal_dibuat,
        ms.dibuat_oleh,
        ms.tanggal_diubah,
        ms.diubah_oleh,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
        p.hpp_standar as harga_standar,
        p.stok_minimum,
        st.nama_satuan,
        g.kode_gudang,
        g.nama_gudang,
        e.kode_entitas,
        e.nama_entitas
    ");

$row = MutasiStokORM::query()
    ->fromSub($mutasi_bahan->unionAll($mutasi_produk), 'x')
    ->first();

if (!$row) {
    set_flash('error', 'Data mutasi stok tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

$qty_masuk = (float) ($row->qty_masuk ?? 0);
$qty_keluar = (float) ($row->qty_keluar ?? 0);
$arah_mutasi = $qty_masuk > 0 ? 'Masuk' : ($qty_keluar > 0 ? 'Keluar' : 'Netral');
$qty_mutasi = $qty_masuk > 0 ? $qty_masuk : $qty_keluar;

$related_mutasi = MutasiStokORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('jenis_barang', (string) $row->jenis_barang)
    ->where('id_referensi_barang', (int) $row->id_referensi_barang)
    ->where('id_gudang', (int) $row->id_gudang)
    ->where('id_mutasi_stok', '<>', (int) $row->id_mutasi_stok)
    ->orderBy('tanggal_mutasi', 'desc')
    ->orderBy('id_mutasi_stok', 'desc')
    ->take(5)
    ->get();

$saldo_sekarang = SaldoStokORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('jenis_barang', (string) $row->jenis_barang)
    ->where('id_referensi_barang', (int) $row->id_referensi_barang)
    ->where('id_gudang', (int) $row->id_gudang)
    ->first();

$url_cetak_item = admin_url(
    'index.php?menu=persediaan/mutasi-stok/cetak'
    . '&jenis_barang=' . urlencode((string) $row->jenis_barang)
    . '&id_referensi_barang=' . (int) $row->id_referensi_barang
    . '&id_gudang=' . (int) $row->id_gudang
);

$url_lihat_semua_item = admin_page_url('persediaan/mutasi-stok')
    . '&jenis_barang=' . urlencode((string) $row->jenis_barang)
    . '&id_referensi_barang=' . (int) $row->id_referensi_barang
    . '&id_gudang=' . (int) $row->id_gudang;
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Mutasi Stok</h1>
    <p class="page-subtitle">Informasi detail mutasi persediaan dan dokumen sumber</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1"><?= esc((string) ($row->no_sumber ?? 'Mutasi Stok')) ?></h2>
                <div class="text-muted small">
                    ID Mutasi:
                    <?= (int) $row->id_mutasi_stok ?>
                    ·
                    Dibuat:
                    <?= esc(!empty($row->tanggal_dibuat) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= esc((string) $back_url) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <a href="<?= esc((string) $url_cetak_item) ?>" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-1"></i>Cetak Riwayat Item
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Tanggal Mutasi</div>
                    <div class="fw-semibold">
                        <?= esc(!empty($row->tanggal_mutasi) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_mutasi)) : '-') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Jenis Barang</div>
                    <div><?= badge_jenis_barang_mutasi_detail((string) ($row->jenis_barang ?? '')) ?></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Jenis Mutasi</div>
                    <div><?= badge_jenis_mutasi_detail((string) ($row->jenis_mutasi ?? '')) ?></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Arah Mutasi</div>
                    <?php if ($arah_mutasi === 'Masuk'): ?>
                        <span class="badge bg-success">Masuk</span>
                    <?php elseif ($arah_mutasi === 'Keluar'): ?>
                        <span class="badge bg-danger">Keluar</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Netral</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Barang</div>
                    <div class="fw-semibold"><?= esc((string) ($row->nama_barang ?? '-')) ?></div>
                    <div class="text-muted small">
                        <?= esc((string) ($row->kode_barang ?? '-')) ?>
                        ·
                        <?= esc((string) ($row->nama_satuan ?? '-')) ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Gudang</div>
                    <div class="fw-semibold"><?= esc((string) ($row->nama_gudang ?? '-')) ?></div>
                    <div class="text-muted small"><?= esc((string) ($row->kode_gudang ?? '-')) ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Satuan</div>
                    <div class="fw-semibold"><?= esc((string) ($row->nama_satuan ?? '-')) ?></div>
                    <div class="text-muted small">Stok Minimum: <?= esc(format_qty_mutasi_detail($row->stok_minimum ?? 0)) ?></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Qty Masuk</div>
                    <div class="fw-semibold text-success">
                        <?= $qty_masuk > 0 ? esc(format_qty_mutasi_detail($qty_masuk)) : '-' ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Qty Keluar</div>
                    <div class="fw-semibold text-danger">
                        <?= $qty_keluar > 0 ? esc(format_qty_mutasi_detail($qty_keluar)) : '-' ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Harga Satuan</div>
                    <div class="fw-semibold">
                        Rp <?= number_format((float) ($row->harga_satuan ?? 0), 2, '.', ',') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Nilai Total</div>
                    <div class="fw-semibold">
                        Rp <?= number_format((float) ($row->nilai_total ?? 0), 2, '.', ',') ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Saldo Sekarang</div>
                    <div class="fw-semibold">
                        <?= $saldo_sekarang ? esc(format_qty_mutasi_detail($saldo_sekarang->qty_saldo ?? 0)) : '-' ?>
                    </div>
                    <div class="text-muted small">
                        Nilai:
                        <?= $saldo_sekarang ? 'Rp ' . number_format((float) ($saldo_sekarang->nilai_saldo ?? 0), 2, '.', ',') : '-' ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">HPP Rata-rata Saat Ini</div>
                    <div class="fw-semibold">
                        <?= $saldo_sekarang ? 'Rp ' . number_format((float) ($saldo_sekarang->hpp_rata_rata ?? 0), 2, '.', ',') : '-' ?>
                    </div>
                    <div class="text-muted small">
                        Update:
                        <?= $saldo_sekarang && !empty($saldo_sekarang->tanggal_update) ? esc(date('d/m/Y H:i', strtotime((string) $saldo_sekarang->tanggal_update))) : '-' ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Qty Mutasi</div>
                    <div class="fw-semibold"><?= esc(format_qty_mutasi_detail($qty_mutasi)) ?></div>
                    <div class="text-muted small"><?= esc((string) $arah_mutasi) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Dokumen Sumber</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0 align-middle">
                        <tbody>
                            <tr>
                                <th class="table-light" width="180">No Sumber</th>
                                <td><?= esc((string) ($row->no_sumber ?? '-')) ?></td>
                            </tr>
                            <tr>
                                <th class="table-light">Tabel Sumber</th>
                                <td><?= esc((string) ($row->tabel_sumber ?? '-')) ?></td>
                            </tr>
                            <tr>
                                <th class="table-light">ID Sumber</th>
                                <td><?= esc((string) ($row->id_sumber ?? '-')) ?></td>
                            </tr>
                            <tr>
                                <th class="table-light">Keterangan</th>
                                <td><?= nl2br(esc((string) ($row->keterangan ?? '-'))) ?></td>
                            </tr>
                            <tr>
                                <th class="table-light">Dibuat Oleh</th>
                                <td><?= esc((string) ($row->dibuat_oleh ?? '-')) ?></td>
                            </tr>
                            <tr>
                                <th class="table-light">Diubah</th>
                                <td>
                                    <?= esc(!empty($row->tanggal_diubah) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_diubah)) : '-') ?>
                                    <?php if (!empty($row->diubah_oleh)): ?>
                                        oleh User ID <?= esc((string) $row->diubah_oleh) ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Mutasi stok bersifat audit trail. Jika terjadi kesalahan, lakukan koreksi melalui transaksi sumber atau stok opname, bukan mengubah mutasi langsung.
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Mutasi Terakhir Item Ini</h2>

                <div class="table-responsive border rounded">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Mutasi</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($related_mutasi->count() === 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Belum ada mutasi lain.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($related_mutasi as $rm): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                <?= esc(!empty($rm->tanggal_mutasi) ? date('d/m/Y', strtotime((string) $rm->tanggal_mutasi)) : '-') ?>
                                            </div>
                                            <div class="text-muted small"><?= esc((string) ($rm->no_sumber ?? '-')) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= esc(ucwords(str_replace('_', ' ', (string) ($rm->jenis_mutasi ?? '-')))) ?>
                                            </span>
                                        </td>
                                        <td class="text-end text-success">
                                            <?= (float) ($rm->qty_masuk ?? 0) > 0 ? esc(format_qty_mutasi_detail($rm->qty_masuk)) : '-' ?>
                                        </td>
                                        <td class="text-end text-danger">
                                            <?= (float) ($rm->qty_keluar ?? 0) > 0 ? esc(format_qty_mutasi_detail($rm->qty_keluar)) : '-' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <a href="<?= esc((string) $url_lihat_semua_item) ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-list-ul me-1"></i>Lihat Semua Mutasi Item
                    </a>

                    <a href="<?= esc((string) $url_cetak_item) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-printer me-1"></i>Cetak Riwayat Item
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>