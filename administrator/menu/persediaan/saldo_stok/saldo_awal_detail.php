<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_saldo_awal_stok = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/saldo-stok/saldo-awal');
}

function format_qty_saldo_awal_detail($value): string
{
    $value = (float) $value;

    if (abs($value - round($value)) < 0.000001) {
        return number_format($value, 0, '.', ',');
    }

    return number_format($value, 2, '.', ',');
}

$row = SaldoAwalStokORM::query()
    ->from('tb_saldo_awal_stok as sa')
    ->leftJoin('tb_gudang as g', 'g.id_gudang', '=', 'sa.id_gudang')
    ->leftJoin('tb_coa as c', 'c.id_coa', '=', 'sa.id_coa_lawan')
    ->where('sa.id_entitas', $id_entitas)
    ->where('sa.id_saldo_awal_stok', $id_saldo_awal_stok)
    ->select([
        'sa.*',
        'g.kode_gudang',
        'g.nama_gudang',
        'c.kode_coa',
        'c.nama_coa',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data saldo awal stok tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

$detail_bahan = SaldoAwalStokDetailORM::query()
    ->from('tb_saldo_awal_stok_detail as d')
    ->leftJoin('tb_bahan_baku as b', function ($join) {
        $join->on('b.id_bahan_baku', '=', 'd.id_referensi_barang')
            ->where('d.jenis_barang', '=', 'bahan_baku');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'b.id_satuan')
    ->where('d.id_saldo_awal_stok', $id_saldo_awal_stok)
    ->where('d.jenis_barang', 'bahan_baku')
    ->selectRaw("
        d.id_saldo_awal_stok_detail,
        d.id_saldo_awal_stok,
        d.jenis_barang,
        d.id_referensi_barang,
        d.qty_awal,
        d.harga_satuan,
        d.nilai_total,
        d.keterangan,
        b.kode_bahan_baku as kode_barang,
        b.nama_bahan_baku as nama_barang,
        s.nama_satuan
    ");

$detail_produk = SaldoAwalStokDetailORM::query()
    ->from('tb_saldo_awal_stok_detail as d')
    ->leftJoin('tb_produk as p', function ($join) {
        $join->on('p.id_produk', '=', 'd.id_referensi_barang')
            ->where('d.jenis_barang', '=', 'produk');
    })
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_saldo_awal_stok', $id_saldo_awal_stok)
    ->where('d.jenis_barang', 'produk')
    ->selectRaw("
        d.id_saldo_awal_stok_detail,
        d.id_saldo_awal_stok,
        d.jenis_barang,
        d.id_referensi_barang,
        d.qty_awal,
        d.harga_satuan,
        d.nilai_total,
        d.keterangan,
        p.kode_produk as kode_barang,
        p.nama_produk as nama_barang,
        s.nama_satuan
    ");

$detail_rows = SaldoAwalStokDetailORM::query()
    ->fromSub($detail_bahan->unionAll($detail_produk), 'x')
    ->orderBy('jenis_barang', 'asc')
    ->orderBy('nama_barang', 'asc')
    ->get();

$cetak_url = admin_url('index.php?menu=persediaan/saldo-stok/saldo-awal/cetak&id=' . (int) $row->id_saldo_awal_stok);
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Saldo Awal Stok</h1>
    <p class="page-subtitle">Informasi dokumen saldo awal stok dan detail barang</p>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1"><?= esc($row->no_saldo_awal_stok ?? '-') ?></h2>
                <div class="text-muted small">
                    Dibuat pada:
                    <?= esc(!empty($row->tanggal_dibuat) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_dibuat)) : '-') ?>
                </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
                <a href="<?= esc($back_url) ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>

                <?php if ((string) $row->status_posting === 'draft'): ?>
                    <a
                        href="<?= esc(admin_page_url('persediaan/saldo-stok/saldo-awal/edit') . '&id=' . (int) $row->id_saldo_awal_stok . '&back_url=' . urlencode($back_url)) ?>"
                        class="btn btn-outline-warning">
                        <i class="bi bi-pencil-square me-1"></i>Edit
                    </a>

                    <a
                        href="<?= esc(admin_url('index.php?menu=persediaan/saldo-stok/saldo-awal/posting&id=' . (int) $row->id_saldo_awal_stok . '&back_url=' . urlencode($back_url))) ?>"
                        class="btn btn-success"
                        onclick="return confirm('Posting saldo awal stok ini? Setelah posting, data akan masuk ke mutasi stok, saldo stok, dan jurnal.')">
                        <i class="bi bi-check-circle me-1"></i>Posting
                    </a>
                <?php endif; ?>

                <a href="<?= esc($cetak_url) ?>" target="_blank" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-1"></i>Cetak
                </a>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">No Saldo Awal</div>
                    <div class="fw-semibold"><?= esc($row->no_saldo_awal_stok ?? '-') ?></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Tanggal</div>
                    <div class="fw-semibold"><?= esc(date('d/m/Y', strtotime((string) $row->tanggal_saldo_awal))) ?></div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Status</div>
                    <div>
                        <?php if ((string) $row->status_posting === 'posted'): ?>
                            <span class="badge bg-success">Posted</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Draft</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Total Nilai</div>
                    <div class="fw-semibold">Rp <?= number_format((float) ($row->total_nilai ?? 0), 2, '.', ',') ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Gudang</div>
                    <div class="fw-semibold"><?= esc($row->nama_gudang ?? '-') ?></div>
                    <div class="text-muted small"><?= esc($row->kode_gudang ?? '-') ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Akun Lawan / Ekuitas</div>
                    <div class="fw-semibold"><?= esc($row->kode_coa ?? '-') ?></div>
                    <div class="text-muted small"><?= esc($row->nama_coa ?? '-') ?></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted small mb-1">Posting</div>
                    <div class="fw-semibold">
                        <?= esc(!empty($row->tanggal_posting) ? date('d/m/Y H:i', strtotime((string) $row->tanggal_posting)) : '-') ?>
                    </div>
                    <div class="text-muted small">User ID: <?= esc($row->diposting_oleh ?? '-') ?></div>
                </div>
            </div>

            <div class="col-12">
                <div class="border rounded p-3">
                    <div class="text-muted small mb-1">Catatan</div>
                    <div><?= nl2br(esc($row->catatan ?? '-')) ?></div>
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
                    Qty tampil per baris sesuai satuan barang. Total qty tidak dijumlahkan karena satuan dapat berbeda.
                </div>
            </div>

            <div class="text-muted small">
                <?= (int) $detail_rows->count() ?> item
            </div>
        </div>

        <div class="table-responsive border rounded">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="55" class="text-center">No</th>
                        <th width="130">Jenis</th>
                        <th width="130">Kode</th>
                        <th>Nama Barang</th>
                        <th width="110">Satuan</th>
                        <th width="130" class="text-end">Qty</th>
                        <th width="170" class="text-end">Harga Satuan</th>
                        <th width="180" class="text-end">Nilai Total</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if ($detail_rows->count() === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada detail barang.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detail_rows as $i => $detail): ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>

                                <td>
                                    <?php if ((string) $detail->jenis_barang === 'bahan_baku'): ?>
                                        <span class="badge bg-info-subtle text-info">Bahan Baku</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success">Produk</span>
                                    <?php endif; ?>
                                </td>

                                <td class="fw-semibold"><?= esc($detail->kode_barang ?? '-') ?></td>

                                <td><?= esc($detail->nama_barang ?? '-') ?></td>

                                <td><?= esc($detail->nama_satuan ?? '-') ?></td>

                                <td class="text-end fw-semibold">
                                    <?= esc(format_qty_saldo_awal_detail($detail->qty_awal ?? 0)) ?>
                                </td>

                                <td class="text-end">
                                    Rp <?= number_format((float) ($detail->harga_satuan ?? 0), 2, '.', ',') ?>
                                </td>

                                <td class="text-end fw-semibold">
                                    Rp <?= number_format((float) ($detail->nilai_total ?? 0), 2, '.', ',') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>

                <tfoot class="table-light">
                    <tr>
                        <th colspan="7" class="text-end">Total Nilai</th>
                        <th class="text-end">Rp <?= number_format((float) ($row->total_nilai ?? 0), 2, '.', ',') ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>