<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_stok_opname = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/stok-opname');
}

function format_qty_stok_opname_detail($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

function format_rupiah_stok_opname_detail($value): string
{
    return 'Rp ' . number_format((float) $value, 2, '.', ',');
}

function badge_jenis_barang_stok_opname_detail(string $jenis): string
{
    if ($jenis === 'bahan_baku') {
        return '<span class="badge bg-info-subtle text-info">Bahan Baku</span>';
    }

    if ($jenis === 'produk') {
        return '<span class="badge bg-success-subtle text-success">Produk</span>';
    }

    return '<span class="badge bg-secondary">-</span>';
}

function badge_selisisih_stok_opname_detail($selisih): string
{
    $selisih = (float) $selisih;

    if ($selisih > 0) {
        return '<span class="badge bg-success">Lebih</span>';
    }

    if ($selisih < 0) {
        return '<span class="badge bg-danger">Kurang</span>';
    }

    return '<span class="badge bg-secondary">Sesuai</span>';
}

$row = StokOpnameORM::query()
    ->from('tb_stok_opname as so')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'so.id_gudang')
    ->where('so.id_entitas', $id_entitas)
    ->where('so.id_stok_opname', $id_stok_opname)
    ->select([
        'so.*',
        'g.kode_gudang',
        'g.nama_gudang',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data stok opname tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

$detail_bahan = StokOpnameDetailORM::query()
    ->from('tb_stok_opname_detail as d')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'd.id_referensi_barang')
            ->where('d.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_stok_opname', $id_stok_opname)
    ->where('d.jenis_barang', 'bahan_baku')
    ->selectRaw("
        d.id_stok_opname_detail,
        d.id_stok_opname,
        d.jenis_barang,
        d.id_referensi_barang,
        d.qty_sistem,
        d.qty_fisik,
        d.selisih_qty,
        d.harga_satuan,
        d.nilai_selisih,
        d.keterangan,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
        s.nama_satuan
    ");

$detail_produk = StokOpnameDetailORM::query()
    ->from('tb_stok_opname_detail as d')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'd.id_referensi_barang')
            ->where('d.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_stok_opname', $id_stok_opname)
    ->where('d.jenis_barang', 'produk')
    ->selectRaw("
        d.id_stok_opname_detail,
        d.id_stok_opname,
        d.jenis_barang,
        d.id_referensi_barang,
        d.qty_sistem,
        d.qty_fisik,
        d.selisih_qty,
        d.harga_satuan,
        d.nilai_selisih,
        d.keterangan,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
        s.nama_satuan
    ");

$detail_rows = StokOpnameDetailORM::query()
    ->fromSub($detail_bahan->unionAll($detail_produk), 'x')
    ->orderBy('jenis_barang', 'asc')
    ->orderBy('nama_barang', 'asc')
    ->get();

$jumlah_plus = 0;
$jumlah_minus = 0;
$jumlah_sesuai = 0;
$total_nilai_selisih = 0;

foreach ($detail_rows as $detail) {
    $selisih = (float) ($detail->selisih_qty ?? 0);

    if ($selisih > 0) {
        $jumlah_plus++;
    } elseif ($selisih < 0) {
        $jumlah_minus++;
    } else {
        $jumlah_sesuai++;
    }

    $total_nilai_selisih += (float) ($detail->nilai_selisih ?? 0);
}

$cetak_url = admin_url('index.php?menu=persediaan/stok-opname/cetak&id=' . (int) $row->id_stok_opname);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Stok Opname</h1>
    <p class="page-subtitle">Informasi dokumen stok opname dan detail selisih persediaan</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1"><?= esc((string) ($row->no_stok_opname ?? '-')) ?></h2>
                <div class="text-muted small">
                    Dibuat:
                    <?= esc(!empty($row->tanggal_dibuat) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= esc((string) $back_url) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <?php if ((string) $row->status_posting === 'draft'): ?>
                    <a
                        href="<?= esc(admin_page_url('persediaan/stok-opname/edit') . '&id=' . (int) $row->id_stok_opname . '&back_url=' . urlencode($back_url)) ?>"
                        class="btn btn-outline-warning">
                        <i class="bi bi-pencil-square me-1"></i>Edit
                    </a>

                    <a
                        href="<?= esc(admin_url('index.php?menu=persediaan/stok-opname/posting&id=' . (int) $row->id_stok_opname . '&back_url=' . urlencode($back_url))) ?>"
                        class="btn btn-success"
                        onclick="return confirm('Posting stok opname ini? Selisih stok akan membentuk mutasi stok, update saldo stok, dan jurnal koreksi.')">
                        <i class="bi bi-check-circle me-1"></i>Posting
                    </a>
                <?php endif; ?>

                <a href="<?= esc((string) $cetak_url) ?>" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-1"></i>Cetak
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">No Opname</div>
                    <div class="fw-semibold"><?= esc((string) ($row->no_stok_opname ?? '-')) ?></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Tanggal</div>
                    <div class="fw-semibold"><?= esc(date('d/m/Y', strtotime((string) $row->tanggal_stok_opname))) ?></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Status</div>
                    <?php if ((string) $row->status_posting === 'posted'): ?>
                        <span class="badge bg-success">Posted</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Draft</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Total Nilai Selisih</div>
                    <div class="fw-semibold"><?= format_rupiah_stok_opname_detail($total_nilai_selisih) ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Gudang</div>
                    <div class="fw-semibold"><?= esc((string) ($row->nama_gudang ?? '-')) ?></div>
                    <div class="text-muted small"><?= esc((string) ($row->kode_gudang ?? '-')) ?></div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Item</div>
                    <div class="fw-semibold"><?= number_format((int) $detail_rows->count(), 0, '.', ',') ?></div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Selisih +</div>
                    <div class="fw-semibold text-success"><?= number_format((int) $jumlah_plus, 0, '.', ',') ?></div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Selisih -</div>
                    <div class="fw-semibold text-danger"><?= number_format((int) $jumlah_minus, 0, '.', ',') ?></div>
                </div>
            </div>

            <div class="col-md-2">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Sesuai</div>
                    <div class="fw-semibold text-secondary"><?= number_format((int) $jumlah_sesuai, 0, '.', ',') ?></div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded p-3">
                    <div class="text-muted small mb-1">Catatan</div>
                    <div><?= nl2br(esc((string) ($row->catatan ?? '-'))) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">Detail Barang</h2>
                <div class="text-muted small">
                    Qty tidak ditotal karena satuan barang dapat berbeda. Nilai selisih tetap ditotal.
                </div>
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="125">Jenis</th>
                        <th width="120">Kode</th>
                        <th>Nama Barang</th>
                        <th width="95">Satuan</th>
                        <th width="120" class="text-end">Qty Sistem</th>
                        <th width="120" class="text-end">Qty Fisik</th>
                        <th width="120" class="text-end">Selisih</th>
                        <th width="150" class="text-end">Harga</th>
                        <th width="160" class="text-end">Nilai Selisih</th>
                        <th width="110" class="text-center">Status</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail_rows->count() === 0): ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">
                                Belum ada detail barang.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detail_rows as $i => $detail): ?>
                            <?php $selisih = (float) ($detail->selisih_qty ?? 0); ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>

                                <td><?= badge_jenis_barang_stok_opname_detail((string) ($detail->jenis_barang ?? '')) ?></td>

                                <td class="fw-semibold"><?= esc((string) ($detail->kode_barang ?? '-')) ?></td>

                                <td><?= esc((string) ($detail->nama_barang ?? '-')) ?></td>

                                <td><?= esc((string) ($detail->nama_satuan ?? '-')) ?></td>

                                <td class="text-end"><?= esc(format_qty_stok_opname_detail($detail->qty_sistem ?? 0)) ?></td>

                                <td class="text-end fw-semibold"><?= esc(format_qty_stok_opname_detail($detail->qty_fisik ?? 0)) ?></td>

                                <td class="text-end fw-semibold <?= $selisih > 0 ? 'text-success' : ($selisih < 0 ? 'text-danger' : 'text-secondary') ?>">
                                    <?= esc(format_qty_stok_opname_detail($selisih)) ?>
                                </td>

                                <td class="text-end">
                                    <?= format_rupiah_stok_opname_detail($detail->harga_satuan ?? 0) ?>
                                </td>

                                <td class="text-end fw-semibold">
                                    <?= format_rupiah_stok_opname_detail($detail->nilai_selisih ?? 0) ?>
                                </td>

                                <td class="text-center">
                                    <?= badge_selisisih_stok_opname_detail($selisih) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="9" class="text-end">Total Nilai Selisih</th>
                        <th class="text-end"><?= format_rupiah_stok_opname_detail($total_nilai_selisih) ?></th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>