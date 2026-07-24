<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

wajib_super_admin($user ?? user_login());

$reset_groups = [
    'transaksi_stok_jurnal' => [
        'label' => 'Transaksi, stok, mutasi, dan jurnal',
        'desc' => 'Wajib untuk membersihkan data dummy. Menghapus pembelian, penjualan, produksi, kas, jurnal, saldo stok, mutasi stok, saldo awal, dan stok opname.',
        'recommend' => 'Wajib/direkomendasikan',
        'default' => true,
        'warning' => false,
        'tables' => [
            'tb_pembatalan_transaksi' => 'Pembatalan transaksi',
            'tb_log_jurnal_sumber' => 'Log jurnal sumber',
            'tb_jurnal_detail' => 'Detail jurnal',
            'tb_jurnal' => 'Jurnal',
            'tb_kas_masuk' => 'Kas masuk',
            'tb_kas_keluar' => 'Kas keluar',
            'tb_saldo_stok' => 'Saldo stok',
            'tb_mutasi_stok' => 'Mutasi stok',
            'tb_konversi_produk' => 'Konversi produk',
            'tb_saldo_awal_stok_detail' => 'Detail saldo awal stok',
            'tb_saldo_awal_stok' => 'Saldo awal stok',
            'tb_stok_opname_detail' => 'Detail stok opname',
            'tb_stok_opname' => 'Stok opname',
            'tb_pembayaran_pembelian' => 'Pembayaran pembelian',
            'tb_faktur_pembelian_detail' => 'Detail faktur pembelian',
            'tb_faktur_pembelian' => 'Faktur pembelian',
            'tb_penerimaan_pembelian_detail' => 'Detail penerimaan pembelian',
            'tb_penerimaan_pembelian' => 'Penerimaan pembelian',
            'tb_pesanan_pembelian_detail' => 'Detail pesanan pembelian',
            'tb_pesanan_pembelian' => 'Pesanan pembelian',
            'tb_pembayaran_penjualan' => 'Pembayaran penjualan',
            'tb_faktur_penjualan_detail' => 'Detail faktur penjualan',
            'tb_faktur_penjualan' => 'Faktur penjualan / POS',
            'tb_penyerahan_penjualan_detail' => 'Detail penyerahan penjualan',
            'tb_penyerahan_penjualan' => 'Penyerahan penjualan',
            'tb_pesanan_penjualan_detail' => 'Detail pesanan penjualan',
            'tb_pesanan_penjualan' => 'Pesanan penjualan',
            'tb_biaya_produksi_detail' => 'Detail biaya produksi',
            'tb_biaya_produksi' => 'Biaya produksi',
            'tb_hasil_produksi' => 'Hasil produksi',
            'tb_pengambilan_bahan_detail' => 'Detail pengambilan bahan',
            'tb_pengambilan_bahan' => 'Pengambilan bahan',
            'tb_perintah_produksi' => 'Perintah produksi',
        ],
    ],
    'produk_bahan_resep' => [
        'label' => 'Produk, bahan baku, kategori, dan resep',
        'desc' => 'Centang jika data produk/bahan/resep masih data dummy dan ingin input ulang dari awal.',
        'recommend' => 'Centang jika produk/resep dummy',
        'default' => false,
        'warning' => false,
        'tables' => [
            'tb_resep_detail' => 'Detail resep',
            'tb_resep' => 'Resep',
            'tb_produk' => 'Produk',
            'tb_bahan_baku' => 'Bahan baku',
            'tb_kategori_produk' => 'Kategori produk',
        ],
    ],
    'pelanggan' => [
        'label' => 'Pelanggan',
        'desc' => 'Centang jika data pelanggan masih dummy.',
        'recommend' => 'Centang jika pelanggan dummy',
        'default' => false,
        'warning' => false,
        'tables' => ['tb_pelanggan' => 'Pelanggan'],
    ],
    'pemasok' => [
        'label' => 'Pemasok',
        'desc' => 'Centang jika data pemasok/supplier masih dummy.',
        'recommend' => 'Centang jika pemasok dummy',
        'default' => false,
        'warning' => false,
        'tables' => ['tb_pemasok' => 'Pemasok'],
    ],
    'gudang' => [
        'label' => 'Gudang',
        'desc' => 'Centang hanya jika gudang masih dummy. Jangan centang jika gudang utama sudah benar.',
        'recommend' => 'Opsional',
        'default' => false,
        'warning' => false,
        'tables' => ['tb_gudang' => 'Gudang'],
    ],
    'satuan' => [
        'label' => 'Satuan',
        'desc' => 'Centang hanya jika satuan masih dummy. Jika produk/bahan memakai satuan ini dan ikut dihapus, aman untuk input ulang.',
        'recommend' => 'Opsional',
        'default' => false,
        'warning' => false,
        'tables' => ['tb_satuan' => 'Satuan'],
    ],
    'pajak' => [
        'label' => 'Pajak / PPN',
        'desc' => 'Centang hanya jika data PPN/pajak ingin dibuat ulang. Jika PPN default sudah benar, jangan centang.',
        'recommend' => 'Biasanya jangan dicentang',
        'default' => false,
        'warning' => true,
        'tables' => ['tb_pajak' => 'Pajak / PPN'],
    ],
    'akun_template_jurnal' => [
        'label' => 'Akun COA, mapping, periode, dan template jurnal',
        'desc' => 'Opsi lanjutan. Centang hanya jika akun/template jurnal masih dummy dan benar-benar ingin setting ulang akuntansi dari nol.',
        'recommend' => 'Tidak disarankan kecuali testing total',
        'default' => false,
        'warning' => true,
        'tables' => [
            'tb_konfigurasi_akun' => 'Konfigurasi akun',
            'tb_mapping_akun' => 'Mapping akun',
            'tb_mapping_laporan_detail' => 'Detail mapping laporan',
            'tb_mapping_laporan' => 'Mapping laporan',
            'tb_template_jurnal_detail' => 'Detail template jurnal',
            'tb_template_jurnal' => 'Template jurnal',
            'tb_periode_akuntansi' => 'Periode akuntansi',
            'tb_coa' => 'Akun COA',
        ],
    ],
];

$counts = [];
$group_totals = [];
foreach ($reset_groups as $key => $group) {
    $group_totals[$key] = 0;
    foreach ($group['tables'] as $table => $label) {
        try {
            $counts[$table] = (int) Capsule::table($table)->count();
        } catch (Throwable $e) {
            $counts[$table] = null;
        }
        if (is_int($counts[$table])) {
            $group_totals[$key] += $counts[$table];
        }
    }
}
$total_data_default = (int) ($group_totals['transaksi_stok_jurnal'] ?? 0);
$total_all = array_sum($group_totals);

$last_logs = [];
try {
    $last_logs = Capsule::table('tb_reset_data_log')
        ->orderByDesc('id_reset_data_log')
        ->limit(10)
        ->get();
} catch (Throwable $e) {
    $last_logs = [];
}
?>

<div class="page-header mb-4">
    <h1 class="page-title">Reset Data Percobaan</h1>
    <p class="page-subtitle">Kosongkan data dummy sebelum input data real. Sekarang Anda bisa memilih data master yang ikut dihapus melalui kolom centang.</p>
</div>

<div class="alert alert-warning border-0 shadow-sm">
    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Perhatian penting</div>
    Untuk awal input data real, centang <strong>Transaksi, stok, mutasi, dan jurnal</strong>. Jika produk, pelanggan, pemasok, resep, atau master lain masih dummy, centang juga kelompok master yang ingin dihapus.
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total baris transaksi/stok default</div>
                <div class="display-6 fw-semibold mb-0"><?= number_format($total_data_default, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total semua kelompok jika dicentang</div>
                <div class="display-6 fw-semibold mb-0"><?= number_format((int) $total_all, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="fw-semibold mb-2">Data yang tetap tidak dihapus</div>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach (['User & Role', 'Menu & Hak Akses', 'Entitas', 'Log Reset'] as $item): ?>
                        <span class="badge text-bg-light border"><?= esc($item) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Pilih Kelompok Data yang Akan Dihapus</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width:70px" class="text-center">Centang</th>
                        <th>Kelompok Data</th>
                        <th>Keterangan</th>
                        <th>Rekomendasi</th>
                        <th class="text-end">Jumlah Baris</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reset_groups as $key => $group): ?>
                        <tr class="<?= !empty($group['warning']) ? 'table-warning' : '' ?>">
                            <td class="text-center">
                                <input class="form-check-input reset-group-check" form="form-reset-data" type="checkbox" name="reset_group[]" value="<?= esc($key) ?>" id="group_<?= esc($key) ?>" <?= !empty($group['default']) ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <label for="group_<?= esc($key) ?>" class="fw-semibold mb-0"><?= esc($group['label']) ?></label>
                                <div class="small text-muted">
                                    <?php $tableNames = array_keys($group['tables']); ?>
                                    <?= esc(implode(', ', $tableNames)) ?>
                                </div>
                            </td>
                            <td><?= esc($group['desc']) ?></td>
                            <td>
                                <?php if (!empty($group['warning'])): ?>
                                    <span class="badge text-bg-warning"><?= esc($group['recommend']) ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-light border"><?= esc($group['recommend']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= number_format((int) ($group_totals[$key] ?? 0), 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-info mb-0">
            <strong>Saran untuk kasus Anda:</strong> centang transaksi/stok/jurnal, produk+bahan baku+resep, pelanggan, dan pemasok jika semuanya masih data percobaan. Gudang, satuan, pajak, dan akun/template jurnal hanya dicentang jika memang masih dummy dan ingin dibuat ulang.
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Rincian Jumlah Per Tabel</h2>
        <div class="accordion" id="accordionResetTables">
            <?php $i = 0; foreach ($reset_groups as $key => $group): $i++; ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading_<?= esc($key) ?>">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_<?= esc($key) ?>" aria-expanded="false" aria-controls="collapse_<?= esc($key) ?>">
                            <?= esc($group['label']) ?> — <?= number_format((int) ($group_totals[$key] ?? 0), 0, ',', '.') ?> baris
                        </button>
                    </h2>
                    <div id="collapse_<?= esc($key) ?>" class="accordion-collapse collapse" aria-labelledby="heading_<?= esc($key) ?>" data-bs-parent="#accordionResetTables">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nama Data</th>
                                            <th>Nama Tabel</th>
                                            <th class="text-end">Jumlah Baris</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($group['tables'] as $table => $label): ?>
                                            <tr>
                                                <td><?= esc($label) ?></td>
                                                <td><code><?= esc($table) ?></code></td>
                                                <td class="text-end">
                                                    <?= $counts[$table] === null ? '<span class="text-muted">-</span>' : number_format((int) $counts[$table], 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="card border-danger shadow-sm mb-4">
    <div class="card-body">
        <h2 class="h5 text-danger mb-3">Eksekusi Reset Data</h2>
        <form id="form-reset-data" method="post" action="<?= esc(admin_url('menu/master_setup/reset_data/proses.php')) ?>" onsubmit="return confirm('Yakin ingin menghapus data sesuai kelompok yang dicentang? Tindakan ini tidak bisa dibatalkan.')">
            <div class="mb-3">
                <label class="form-label fw-semibold">Alasan reset <span class="text-danger">*</span></label>
                <textarea name="alasan" class="form-control" rows="3" required placeholder="Contoh: Menghapus data dummy/percobaan sebelum input data real."></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Konfirmasi <span class="text-danger">*</span></label>
                <input type="text" name="konfirmasi" class="form-control" required placeholder="Ketik RESET DATA">
                <div class="form-text">Untuk menjalankan reset, ketik persis: <strong>RESET DATA</strong></div>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="setuju" name="setuju" required>
                <label class="form-check-label" for="setuju">
                    Saya paham bahwa data pada kelompok yang dicentang akan dihapus permanen.
                </label>
            </div>
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-trash3 me-1"></i> Reset Data Terpilih
            </button>
            <a href="<?= esc(admin_page_url('dashboard')) ?>" class="btn btn-outline-secondary ms-2">Batal</a>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-3">Riwayat Reset Terakhir</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Total Baris</th>
                        <th>Alasan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($last_logs) || (is_object($last_logs) && $last_logs->count() === 0)): ?>
                        <tr><td colspan="4" class="text-muted text-center py-3">Belum ada riwayat reset.</td></tr>
                    <?php else: ?>
                        <?php foreach ($last_logs as $log): ?>
                            <tr>
                                <td><?= esc((string) ($log->tanggal_reset ?? '-')) ?></td>
                                <td><?= esc((string) ($log->username ?? '-')) ?></td>
                                <td><?= number_format((int) ($log->total_baris_dihapus ?? 0), 0, ',', '.') ?></td>
                                <td><?= esc((string) ($log->alasan ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
