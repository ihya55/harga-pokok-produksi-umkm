<?php
declare(strict_types=1);

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_produk = (int) ($_GET['id'] ?? 0);

$row = ProdukORM::query()
    ->from('tb_produk as p')
    ->leftJoin('tb_entitas as e', 'e.id_entitas', '=', 'p.id_entitas')
    ->leftJoin('tb_kategori_produk as k', 'k.id_kategori_produk', '=', 'p.id_kategori_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->leftJoin('tb_coa as cp', 'cp.id_coa', '=', 'p.id_coa_penjualan')
    ->leftJoin('tb_coa as ch', 'ch.id_coa', '=', 'p.id_coa_hpp')
    ->leftJoin('tb_coa as cs', 'cs.id_coa', '=', 'p.id_coa_persediaan')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'p.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'p.diubah_oleh')
    ->where('p.id_entitas', $id_entitas)
    ->where('p.id_produk', $id_produk)
    ->select([
        'p.*',
        'e.nama_entitas',
        'k.kode_kategori_produk',
        'k.nama_kategori_produk',
        's.nama_satuan',
        'cp.kode_coa as kode_coa_penjualan',
        'cp.nama_coa as nama_coa_penjualan',
        'ch.kode_coa as kode_coa_hpp',
        'ch.nama_coa as nama_coa_hpp',
        'cs.kode_coa as kode_coa_persediaan',
        'cs.nama_coa as nama_coa_persediaan',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$row) {
    set_flash('error', 'Data produk tidak ditemukan.');
    redirect_admin('master_setup/produk');
}

$barcode_produk_display = trim((string) ($row->barcode_produk ?? ''));
if ($barcode_produk_display === '') {
    $barcode_produk_display = trim((string) ($row->kode_produk ?? ''));
}

$barcode_url = admin_url('menu/master_setup/produk/barcode.php?id=' . (int) $row->id_produk);
$barcode_download_url = $barcode_url . '&download=1';
?>

<div class="page-header mb-4">
    <h1 class="page-title">Detail Produk</h1>
    <p class="page-subtitle">Informasi lengkap data produk</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="detail-section-title">Data Utama</div>
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Entitas</div>
                <div class="detail-value"><?= esc($row->nama_entitas ?? '-') ?></div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode Produk</div>
                <div class="detail-value"><?= esc($row->kode_produk ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kode Barcode</div>
                <div class="detail-value"><?= esc($barcode_produk_display !== '' ? $barcode_produk_display : '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Nama Produk</div>
                <div class="detail-value"><?= esc($row->nama_produk ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Kategori</div>
                <div class="detail-value"><?= esc(($row->kode_kategori_produk ?? '-') . ((isset($row->nama_kategori_produk) && $row->nama_kategori_produk !== null) ? ' - ' . $row->nama_kategori_produk : '')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Satuan</div>
                <div class="detail-value"><?= esc($row->nama_satuan ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Jenis Produk</div>
                <div class="detail-value"><?= esc($row->jenis_produk ?? '-') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Harga Jual</div>
                <div class="detail-value"><?= 'Rp ' . number_format((float) ($row->harga_jual ?? 0), 2, ',', '.') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">HPP Standar</div>
                <div class="detail-value"><?= 'Rp ' . number_format((float) ($row->hpp_standar ?? 0), 2, ',', '.') ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Stok Minimum</div>
                <div class="detail-value"><?= number_format((float) ($row->stok_minimum ?? 0)) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Berat Bersih</div>
                <div class="detail-value">
                    <?= $row->berat_bersih !== null
                        ? number_format((float) $row->berat_bersih) . ' ' . ($row->satuan_berat_bersih ?? 'gram')
                        : '-' ?>
                </div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Gambar Produk</div>
                <div class="detail-value">
                    <?php if (!empty($row->gambar_produk)): ?>
                        <img src="<?= esc(base_url('uploads/produk/' . $row->gambar_produk)) ?>" alt="Gambar Produk" style="max-width:180px; height:auto; border-radius:8px;">
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-xl-8">
                <div class="detail-label">Tampilan Barcode</div>
                <div class="detail-value">
                    <?php if ($barcode_produk_display !== ''): ?>
                        <div class="border rounded bg-white p-3 d-inline-block" style="max-width: 100%;">
                            <img
                                src="<?= esc($barcode_url) ?>"
                                alt="Barcode <?= esc($barcode_produk_display) ?>"
                                style="max-width: 420px; width: 100%; height: auto;"
                            >
                            <div class="mt-3 d-flex flex-column flex-sm-row gap-2 align-items-sm-center justify-content-sm-between">
                                <code><?= esc($barcode_produk_display) ?></code>
                                <a href="<?= esc($barcode_download_url) ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>Download Barcode PNG
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </div>
            </div>


            <div class="col-md-6 col-xl-4">
                <div class="detail-label">COA Penjualan</div>
                <div class="detail-value"><?= esc(($row->kode_coa_penjualan ?? '-') . ((isset($row->nama_coa_penjualan) && $row->nama_coa_penjualan !== null) ? ' - ' . $row->nama_coa_penjualan : '')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">COA HPP</div>
                <div class="detail-value"><?= esc(($row->kode_coa_hpp ?? '-') . ((isset($row->nama_coa_hpp) && $row->nama_coa_hpp !== null) ? ' - ' . $row->nama_coa_hpp : '')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">COA Persediaan</div>
                <div class="detail-value"><?= esc(($row->kode_coa_persediaan ?? '-') . ((isset($row->nama_coa_persediaan) && $row->nama_coa_persediaan !== null) ? ' - ' . $row->nama_coa_persediaan : '')) ?></div>
            </div>

            <div class="col-md-6 col-xl-4">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    <?php if ((int) ($row->status_produk ?? 0) === 1): ?>
                        <span class="badge text-bg-success">Aktif</span>
                    <?php else: ?>
                        <span class="badge text-bg-secondary">Nonaktif</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <a href="<?= esc(admin_page_url('master_setup/produk')) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_page_url('master_setup/produk/edit') . '&id=' . (int) $row->id_produk) ?>" class="btn btn-gradient">
                <i class="bi bi-pencil-square me-1"></i>Edit
            </a>
        </div>
    </div>
</div>