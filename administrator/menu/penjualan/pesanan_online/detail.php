<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pesanan_penjualan = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if (!function_exists('pesanan_online_detail_uang')) {
    function pesanan_online_detail_uang($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('pesanan_online_detail_qty')) {
    function pesanan_online_detail_qty($value): string
    {
        $number = (float) $value;

        if (floor($number) == $number) {
            return number_format($number, 0, '.', ',');
        }

        return rtrim(rtrim(number_format($number, 3, '.', ','), '0'), '.');
    }
}

if (!function_exists('pesanan_online_detail_tanggal')) {
    function pesanan_online_detail_tanggal($tanggal): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date('d/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('pesanan_online_detail_badge_status')) {
    function pesanan_online_detail_badge_status(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'draft' => '<span class="badge bg-secondary">Draft</span>',
            'terkonfirmasi' => '<span class="badge bg-success">Terkonfirmasi</span>',
            'diproses' => '<span class="badge bg-warning text-dark">Diproses</span>',
            'selesai' => '<span class="badge bg-primary">Selesai</span>',
            'batal' => '<span class="badge bg-danger">Batal</span>',
            default => '<span class="badge bg-light text-dark">' . esc(ucfirst($status ?: '-')) . '</span>',
        };
    }
}

$pesanan = Capsule::table('tb_pesanan_penjualan as ps')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
    ->leftJoin('tb_pengguna as u1', 'u1.id_pengguna', '=', 'ps.dibuat_oleh')
    ->leftJoin('tb_pengguna as u2', 'u2.id_pengguna', '=', 'ps.diubah_oleh')
    ->where('ps.id_entitas', $id_entitas)
    ->where('ps.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->where('ps.sumber_pesanan', 'website')
    ->select([
        'ps.*',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.alamat as alamat_pelanggan',
        'pl.no_hp as no_hp_pelanggan',
        'pl.email as email_pelanggan',
        'u1.nama_lengkap as nama_pembuat',
        'u2.nama_lengkap as nama_pengubah',
    ])
    ->first();

if (!$pesanan) {
    ?>
    <div class="alert alert-danger">Data pesanan online tidak ditemukan.</div>
    <a href="<?= esc(admin_page_url('penjualan/pesanan-online')) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
    <?php
    return;
}

$detail_rows = Capsule::table('tb_pesanan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_pesanan_penjualan', $id_pesanan_penjualan)
    ->select([
        'd.*',
        'p.kode_produk',
        'p.nama_produk',
        's.nama_satuan',
    ])
    ->selectRaw("
        COALESCE((
            SELECT SUM(ss.qty_saldo)
            FROM tb_saldo_stok ss
            WHERE ss.id_entitas = ?
              AND ss.jenis_barang = 'produk'
              AND ss.id_referensi_barang = d.id_produk
        ), 0) AS qty_saldo_total
    ", [$id_entitas])
    ->orderBy('d.id_pesanan_penjualan_detail', 'asc')
    ->get();

$penyerahan_rows = Capsule::table('tb_penyerahan_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
    ->orderBy('id_penyerahan_penjualan', 'desc')
    ->get();

$produksi_rows = Capsule::table('tb_perintah_produksi')
    ->where('id_entitas', $id_entitas)
    ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
    ->orderBy('id_perintah_produksi', 'desc')
    ->get();

$id_pelanggan_pesanan = (int) ($pesanan->id_pelanggan ?? 0);

$latest_bukti = null;
$bukti_rows = collect();
$chat_rows = collect();
$metodeBuktiPesanan = strtolower(trim((string) ($pesanan->metode_pembayaran_online ?? 'cod')));
$metodePerluBukti = in_array($metodeBuktiPesanan, ['transfer', 'qris'], true);

$buktiButuhSqlPatch = false;

if ($metodePerluBukti && Capsule::schema()->hasTable('tb_pembayaran_online_bukti')) {
    $buktiQuery = Capsule::table('tb_pembayaran_online_bukti as b')
        ->join('tb_pesanan_penjualan as psb', function ($join) use ($id_entitas) {
            $join->on('psb.id_pesanan_penjualan', '=', 'b.id_pesanan_penjualan')
                ->where('psb.id_entitas', '=', $id_entitas);
        })
        ->where('b.id_entitas', $id_entitas)
        ->where('b.id_pesanan_penjualan', $id_pesanan_penjualan)
        ->where('psb.id_pelanggan', $id_pelanggan_pesanan)
        ->where('psb.metode_pembayaran_online', $metodeBuktiPesanan)
        ->where('b.metode_pembayaran', $metodeBuktiPesanan);

    if (Capsule::schema()->hasColumn('tb_pembayaran_online_bukti', 'id_pelanggan')) {
        $buktiQuery->where('b.id_pelanggan', $id_pelanggan_pesanan);
    }

    $bukti_rows = $buktiQuery
        ->select('b.*')
        ->orderBy('b.id_bukti_pembayaran_online', 'desc')
        ->get();
    $latest_bukti = $bukti_rows->first();
}

$chatButuhSqlPatch = Capsule::schema()->hasTable('tb_pesanan_online_chat') && !Capsule::schema()->hasColumn('tb_pesanan_online_chat', 'id_pelanggan');

if (Capsule::schema()->hasTable('tb_pesanan_online_chat') && Capsule::schema()->hasColumn('tb_pesanan_online_chat', 'id_pelanggan')) {
    $chat_rows = Capsule::table('tb_pesanan_online_chat as ch')
        ->join('tb_pesanan_penjualan as psch', function ($join) use ($id_entitas) {
            $join->on('psch.id_pesanan_penjualan', '=', 'ch.id_pesanan_penjualan')
                ->where('psch.id_entitas', '=', $id_entitas);
        })
        ->where('ch.id_entitas', $id_entitas)
        ->where('ch.id_pesanan_penjualan', $id_pesanan_penjualan)
        ->where('ch.id_pelanggan', $id_pelanggan_pesanan)
        ->where('psch.id_pelanggan', $id_pelanggan_pesanan)
        ->select('ch.*')
        ->orderBy('ch.id_chat', 'asc')
        ->get();
}

if (!function_exists('pesanan_online_payment_status_label')) {
    function pesanan_online_payment_status_label(?string $status): string
    {
        $status = strtolower(trim((string) $status));
        return match ($status) {
            'lunas', 'paid', 'settlement', 'capture' => 'Lunas',
            'menunggu_verifikasi' => 'Menunggu Verifikasi',
            'menunggu_bayar', 'pending' => 'Menunggu Bayar',
            'ditolak' => 'Ditolak',
            'perlu_refund' => 'Perlu Refund',
            'sudah_refund' => 'Sudah Refund',
            'belum_bayar', '' => 'Belum Bayar',
            default => ucwords(str_replace('_', ' ', $status)),
        };
    }
}


$total_qty = 0.0;
$stok_kurang = 0;

foreach ($detail_rows as $d) {
    $total_qty += (float) ($d->qty ?? 0);

    if ((float) ($d->qty_saldo_total ?? 0) < (float) ($d->qty ?? 0)) {
        $stok_kurang++;
    }
}

$url_kembali = $back_url !== '' ? $back_url : admin_page_url('penjualan/pesanan-online');
$statusBayarHeader = strtolower((string) ($pesanan->status_pembayaran_online ?? 'belum_bayar'));
$metodeBayarHeader = strtolower((string) ($pesanan->metode_pembayaran_online ?? 'cod'));
$punyaBuktiMenunggu = $latest_bukti && in_array(strtolower((string) ($latest_bukti->status_verifikasi ?? '')), ['menunggu_verifikasi', 'pending'], true);
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
            <h1 class="page-title">Detail Pesanan Online</h1>
            <p class="page-subtitle">Ringkasan pesanan, pembayaran, dan chat customer.</p>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= esc($url_kembali) ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>

            <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/cetak&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>" target="_blank" class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i>Cetak A4
            </a>
            <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/struk&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>" target="_blank" class="btn btn-warning">
                <i class="bi bi-receipt-cutoff me-1"></i>Cetak Struk Kantong
            </a>


            <?php if ((string) $pesanan->status_pesanan === 'draft'): ?>
                <?php if (!$punyaBuktiMenunggu && !in_array($statusBayarHeader, ['lunas', 'paid', 'settlement'], true)): ?>
                    <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/konfirmasi&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>" class="btn btn-success" onclick="return confirm('Konfirmasi pesanan ini tanpa mencatat uang masuk? Gunakan tombol ini untuk COD / belum bayar. Jika ada bukti transfer, gunakan tombol Uang Sudah Masuk di bagian pembayaran.')">
                        <i class="bi bi-check2-circle me-1"></i>Konfirmasi Pesanan
                    </a>
                <?php endif; ?>

                <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/batal&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>" class="btn btn-outline-danger" onclick="return confirm('Batalkan pesanan online ini?')">
                    <i class="bi bi-x-circle me-1"></i>Batal
                </a>
            <?php endif; ?>

            <?php if ((string) $pesanan->status_pesanan === 'terkonfirmasi'): ?>
                <a href="<?= esc(admin_page_url('penjualan/penyerahan/tambah') . '&id_pesanan_penjualan=' . (int) $pesanan->id_pesanan_penjualan) ?>" class="btn btn-primary">
                    <i class="bi bi-truck me-1"></i>Buat Penyerahan
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">No Pesanan</div>
                <div class="h5 mb-1"><?= esc((string) $pesanan->no_pesanan_penjualan) ?></div>
                <?= pesanan_online_detail_badge_status((string) $pesanan->status_pesanan) ?>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Tanggal Pesanan</div>
                <div class="h5 mb-1"><?= esc(pesanan_online_detail_tanggal($pesanan->tanggal_pesanan)) ?></div>
                <div class="text-muted small">Rencana kirim: <?= esc(pesanan_online_detail_tanggal($pesanan->tanggal_kirim_rencana ?? null)) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Total Pesanan</div>
                <div class="h5 mb-1"><?= pesanan_online_detail_uang($pesanan->total ?? 0) ?></div>
                <div class="text-muted small">Qty: <?= esc(pesanan_online_detail_qty($total_qty)) ?></div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="text-muted small">Status Stok</div>
                <div class="h5 mb-1"><?= $stok_kurang > 0 ? 'Kurang' : 'Cukup' ?></div>
                <div class="text-muted small"><?= $stok_kurang > 0 ? $stok_kurang . ' item kurang' : 'Semua item tersedia' ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Informasi Pesanan</h2>

                <table class="table table-sm table-borderless align-middle mb-0">
                    <tr>
                        <th width="190" class="text-muted">Pelanggan</th>
                        <td>
                            <div class="fw-semibold"><?= esc(($pesanan->kode_pelanggan ?? '-') . ' - ' . ($pesanan->nama_pelanggan ?? '-')) ?></div>
                            <div class="text-muted small"><?= esc((string) ($pesanan->no_hp_pelanggan ?? '-')) ?></div>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Email</th>
                        <td><?= esc((string) ($pesanan->email_pelanggan ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Alamat</th>
                        <td><?= nl2br(esc((string) ($pesanan->alamat_pelanggan ?? '-'))) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Sumber</th>
                        <td><span class="badge bg-primary">Website</span></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Catatan</th>
                        <td><?= nl2br(esc((string) ($pesanan->catatan ?? '-'))) ?></td>
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
                        <td class="text-end fw-semibold"><?= pesanan_online_detail_uang($pesanan->subtotal ?? 0) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Diskon</th>
                        <td class="text-end fw-semibold"><?= pesanan_online_detail_uang($pesanan->diskon ?? 0) ?></td>
                    </tr>
                    <tr>
                        <td colspan="2"><hr class="my-2"></td>
                    </tr>
                    <tr>
                        <th class="fs-5">Total</th>
                        <td class="text-end fs-5 fw-bold"><?= pesanan_online_detail_uang($pesanan->total ?? 0) ?></td>
                    </tr>
                </table>

                <?php if ($stok_kurang > 0): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        Stok kurang. Produksi terlebih dahulu.
                    </div>
                <?php else: ?>
                    <div class="alert alert-success mt-3 mb-0">
                        Stok cukup.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Pembayaran</h2>
                <table class="table table-sm table-borderless mb-3">
                    <tr><th class="text-muted" width="180">Metode</th><td><?= esc(function_exists('po_payment_method_label') ? po_payment_method_label($pesanan->metode_pembayaran_online ?? 'cod') : (string) ($pesanan->metode_pembayaran_online ?? '-')) ?></td></tr>
                    <tr><th class="text-muted">Status Pembayaran</th><td><span class="badge bg-info text-dark"><?= esc(pesanan_online_payment_status_label($pesanan->status_pembayaran_online ?? 'belum_bayar')) ?></span></td></tr>
                    <tr><th class="text-muted">Nominal</th><td class="fw-bold"><?= pesanan_online_detail_uang($pesanan->nominal_pembayaran_online ?? $pesanan->total ?? 0) ?></td></tr>
                </table>

                <?php $statusBayar = strtolower((string) ($pesanan->status_pembayaran_online ?? 'belum_bayar')); ?>

                <?php if (!$metodePerluBukti): ?>
                    <div class="alert alert-light border mb-0">Metode COD. Tidak perlu upload bukti pembayaran.</div>
                <?php elseif (!empty($buktiButuhSqlPatch)): ?>
                    <div class="alert alert-warning mb-0">SQL patch belum diimport. Bukti pembayaran tidak ditampilkan agar tidak tercampur dengan pesanan/customer lain.</div>
                <?php elseif (!$latest_bukti): ?>
                    <div class="alert alert-light border mb-0">Belum ada bukti pembayaran untuk pesanan ini.</div>
                <?php else: ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="fw-semibold mb-1">Bukti terakhir: <?= esc(pesanan_online_payment_status_label($latest_bukti->status_verifikasi ?? 'menunggu_verifikasi')) ?></div>
                        <div class="small text-muted mb-2">
                            Pengirim: <?= esc((string) ($latest_bukti->nama_pengirim ?? '-')) ?> ·
                            Nominal: <?= pesanan_online_detail_uang($latest_bukti->nominal_bayar ?? 0) ?> ·
                            Tanggal bayar: <?= esc((string) ($latest_bukti->tanggal_bayar ?? '-')) ?>
                        </div>
                        <?php $fileBukti = (string) ($latest_bukti->file_bukti ?? ''); ?>
                        <?php if ($fileBukti !== ''): ?>
                            <?php if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $fileBukti)): ?>
                                <a href="<?= esc(base_url($fileBukti)) ?>" target="_blank"><img src="<?= esc(base_url($fileBukti)) ?>" alt="Bukti pembayaran" style="max-width:280px;width:100%;border:1px solid #e5e7eb;border-radius:18px;padding:8px;background:#fff;box-shadow:0 16px 35px rgba(15,23,42,.12);"></a>
                            <?php else: ?>
                                <a href="<?= esc(base_url($fileBukti)) ?>" target="_blank" class="btn btn-outline-primary btn-sm">Buka File Bukti</a>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if (!empty($latest_bukti->catatan)): ?>
                            <div class="small mt-2">Catatan customer: <?= nl2br(esc((string) $latest_bukti->catatan)) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if (!in_array($statusBayar, ['lunas', 'paid', 'settlement', 'perlu_refund', 'sudah_refund'], true)): ?>
                            <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/verifikasi-pembayaran&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>" class="btn btn-success" onclick="return confirm('Pastikan uang sudah masuk. Verifikasi pembayaran ini?')"><i class="bi bi-check-circle me-1"></i>Uang Sudah Masuk</a>
                            <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/tolak-pembayaran&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>" class="btn btn-outline-danger" onclick="return confirm('Tolak bukti pembayaran ini?')"><i class="bi bi-x-circle me-1"></i>Tolak / Pending</a>
                        <?php elseif (in_array($statusBayar, ['lunas', 'paid', 'settlement'], true)): ?>
                            <span class="btn btn-success disabled"><i class="bi bi-check2-circle me-1"></i>Uang Sudah Diterima</span>
                            <a href="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/tolak-pembayaran&id=' . (int) $pesanan->id_pesanan_penjualan)) ?>" class="btn btn-outline-danger" onclick="return confirm('Pembayaran sudah diterima. Jika ditolak sekarang, sistem akan meminta proses pengembalian uang/refund. Lanjutkan?')"><i class="bi bi-arrow-counterclockwise me-1"></i>Tolak & Minta Refund</a>
                        <?php elseif ($statusBayar === 'perlu_refund'): ?>
                            <span class="btn btn-warning disabled"><i class="bi bi-exclamation-triangle me-1"></i>Menunggu Pengembalian Uang</span>
                        <?php elseif ($statusBayar === 'sudah_refund'): ?>
                            <span class="btn btn-secondary disabled"><i class="bi bi-check2-square me-1"></i>Uang Sudah Dikembalikan</span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($statusBayar === 'perlu_refund'): ?>
                    <div class="card border-warning mt-3">
                        <div class="card-body">
                            <h3 class="h6 mb-2 text-warning"><i class="bi bi-arrow-left-right me-1"></i>Konfirmasi Pengembalian Uang</h3>
                            <p class="small text-muted mb-3">Upload bukti refund jika uang sudah dikembalikan.</p>
                            <form method="post" enctype="multipart/form-data" action="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/simpan-refund')) ?>" class="row g-2">
                                <input type="hidden" name="id_pesanan_penjualan" value="<?= (int) $pesanan->id_pesanan_penjualan ?>">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nominal Refund</label>
                                    <input type="number" step="0.01" min="0" name="nominal_refund" class="form-control" value="<?= esc((string) ($pesanan->nominal_refund_online ?? $pesanan->nominal_pembayaran_online ?? $pesanan->total ?? 0)) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Bukti Transfer Balik</label>
                                    <input type="file" name="bukti_refund" class="form-control" accept="image/*,.pdf" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Catatan Refund</label>
                                    <textarea name="catatan_refund" class="form-control" rows="2" placeholder="Contoh: Dana dikembalikan ke rekening customer pada tanggal ..."></textarea>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-warning" onclick="return confirm('Pastikan uang sudah benar-benar ditransfer balik ke customer. Simpan bukti refund?')"><i class="bi bi-upload me-1"></i>Simpan Bukti Pengembalian Uang</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif ($statusBayar === 'sudah_refund'): ?>
                    <div class="alert alert-secondary mt-3 mb-0">
                        <strong>Refund selesai.</strong> Nominal: <?= pesanan_online_detail_uang($pesanan->nominal_refund_online ?? 0) ?> · Tanggal: <?= esc((string) ($pesanan->tanggal_refund_online ?? '-')) ?>
                        <?php if (!empty($pesanan->bukti_refund_online)): ?>
                            · <a href="<?= esc(base_url((string) $pesanan->bukti_refund_online)) ?>" target="_blank">Lihat bukti transfer balik</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h2 class="h5 mb-0">Chat Customer</h2>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalChatPesanan">
                        <i class="bi bi-chat-dots"></i>
                    </button>
                </div>
                <div id="adminChatList" style="max-height:330px;overflow:auto;background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:12px;">
                    <?php if (!empty($chatButuhSqlPatch)): ?>
                        <div class="text-warning text-center py-3">SQL patch belum diimport. Chat disembunyikan agar tidak tercampur dengan pesanan/customer lain.</div>
                    <?php elseif ($chat_rows->count() === 0): ?>
                        <div class="text-muted text-center py-3">Belum ada chat.</div>
                    <?php else: ?>
                        <?php foreach ($chat_rows as $chat): ?>
                            <?php $isAdmin = (string) $chat->pengirim_tipe === 'admin'; ?>
                            <div class="mb-2 <?= $isAdmin ? 'text-end' : 'text-start' ?>">
                                <div class="d-inline-block p-2 rounded-3 <?= $isAdmin ? 'bg-primary text-white' : 'bg-white border' ?>" style="max-width:80%;">
                                    <div class="small fw-semibold"><?= esc($isAdmin ? ($chat->nama_pengirim ?: 'Admin') : ($chat->nama_pengirim ?: 'Customer')) ?></div>
                                    <div><?= nl2br(esc((string) $chat->pesan)) ?></div>
                                    <div class="small opacity-75 mt-1"><?= esc(date('d/m/Y H:i', strtotime((string) $chat->tanggal_dibuat))) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <form method="post" action="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/chat-kirim')) ?>" class="mt-3 admin-chat-form">
                    <input type="hidden" name="ajax" value="1">
                    <input type="hidden" name="id_pesanan_penjualan" value="<?= (int) $pesanan->id_pesanan_penjualan ?>">
                    <input type="hidden" name="id_pelanggan" value="<?= (int) $id_pelanggan_pesanan ?>">
                    <textarea name="pesan" class="form-control" rows="2" placeholder="Tulis balasan untuk customer..." required></textarea>
                    <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-send me-1"></i>Kirim Balasan</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="modalChatPesanan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content border-0 rounded-4 overflow-hidden">
      <div class="modal-header text-white" style="background:linear-gradient(135deg,#2563eb,#7c3aed,#f97316);">
        <div><h5 class="modal-title fw-bold">Chat Pesanan Online</h5><div class="small opacity-75"><?= esc((string) $pesanan->no_pesanan_penjualan) ?></div></div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-light">
        <div id="adminChatListModal" style="max-height:420px;overflow:auto;background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:12px;">
          <div class="text-muted text-center py-3">Memuat chat...</div>
        </div>
      </div>
      <div class="modal-footer d-block bg-white">
        <form method="post" action="<?= esc(admin_url('index.php?menu=penjualan/pesanan-online/chat-kirim')) ?>" class="admin-chat-form">
          <input type="hidden" name="ajax" value="1">
          <input type="hidden" name="id_pesanan_penjualan" value="<?= (int) $pesanan->id_pesanan_penjualan ?>">
          <input type="hidden" name="id_pelanggan" value="<?= (int) $id_pelanggan_pesanan ?>">
          <textarea name="pesan" class="form-control" rows="2" placeholder="Tulis balasan untuk customer..." required></textarea>
          <button type="submit" class="btn btn-primary mt-2"><i class="bi bi-send me-1"></i>Kirim Balasan</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
    function escapeHtml(text){
        return String(text || '').replace(/[&<>"']/g, function(m){
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
    }
    function chatHtml(){
        const list = document.getElementById('adminChatList');
        return list ? list.innerHTML : '<div class="text-muted text-center py-3">Belum ada chat.</div>';
    }
    function syncModal(){
        const modalList = document.getElementById('adminChatListModal');
        if(modalList){
            modalList.innerHTML = chatHtml();
            modalList.scrollTop = modalList.scrollHeight;
        }
    }
    const mainList = document.getElementById('adminChatList');
    if(mainList){ mainList.scrollTop = mainList.scrollHeight; }
    syncModal();

    const modalEl = document.getElementById('modalChatPesanan');
    if(modalEl){ modalEl.addEventListener('shown.bs.modal', syncModal); }

    document.querySelectorAll('.admin-chat-form').forEach(function(form){
        form.addEventListener('submit', async function(e){
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const textarea = form.querySelector('textarea[name="pesan"]');
            const message = textarea ? textarea.value.trim() : '';
            if(!message){ return; }
            const oldText = btn ? btn.innerHTML : '';
            if(btn){ btn.disabled = true; btn.innerHTML = 'Mengirim...'; }
            try{
                const fd = new FormData(form);
                fd.set('ajax','1');
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: fd,
                    headers: {'X-Requested-With':'XMLHttpRequest'}
                });
                const raw = await res.text();
                let data;
                try { data = JSON.parse(raw); }
                catch(parseErr){ throw new Error('Respons server bukan JSON: ' + raw.substring(0,120)); }
                if(!res.ok || !data.ok){
                    alert(data.message || 'Gagal mengirim chat.');
                    return;
                }
                const bubble = '<div class="mb-2 text-end">'
                    + '<div class="d-inline-block p-2 rounded-3 bg-primary text-white" style="max-width:80%;">'
                    + '<div class="small fw-semibold">' + escapeHtml(data.nama_pengirim || 'Admin') + '</div>'
                    + '<div>' + escapeHtml(data.pesan || message).replace(/\n/g,'<br>') + '</div>'
                    + '<div class="small opacity-75 mt-1">' + escapeHtml(data.tanggal || 'Baru saja') + '</div>'
                    + '</div></div>';
                const list = document.getElementById('adminChatList');
                if(list){
                    if(list.textContent.includes('Belum ada chat')){ list.innerHTML = ''; }
                    list.insertAdjacentHTML('beforeend', bubble);
                    list.scrollTop = list.scrollHeight;
                }
                syncModal();
                document.querySelectorAll('.admin-chat-form textarea[name="pesan"]').forEach(function(t){ t.value=''; });
                if(textarea){ textarea.focus(); }
            }catch(err){
                alert('Gagal mengirim chat. ' + err.message);
            }finally{
                if(btn){ btn.disabled = false; btn.innerHTML = oldText; }
            }
        });
    });
})();
</script>

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
                        <th width="110">Satuan</th>
                        <th width="110" class="text-end">Qty</th>
                        <th width="140" class="text-end">Stok Total</th>
                        <th width="140" class="text-end">Harga</th>
                        <th width="140" class="text-end">Diskon</th>
                        <th width="160" class="text-end">Subtotal</th>
                        <th width="110">Status Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($detail_rows->count() === 0): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Detail pesanan masih kosong.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detail_rows as $i => $row): ?>
                            <?php
                            $qty = (float) ($row->qty ?? 0);
                            $stok = (float) ($row->qty_saldo_total ?? 0);
                            ?>
                            <tr>
                                <td class="text-center"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= esc((string) ($row->kode_produk ?? '-')) ?></td>
                                <td><?= esc((string) ($row->nama_produk ?? '-')) ?></td>
                                <td><?= esc((string) ($row->nama_satuan ?? '-')) ?></td>
                                <td class="text-end"><?= esc(pesanan_online_detail_qty($qty)) ?></td>
                                <td class="text-end"><?= esc(pesanan_online_detail_qty($stok)) ?></td>
                                <td class="text-end"><?= pesanan_online_detail_uang($row->harga ?? 0) ?></td>
                                <td class="text-end"><?= pesanan_online_detail_uang($row->diskon ?? 0) ?></td>
                                <td class="text-end fw-semibold"><?= pesanan_online_detail_uang($row->subtotal ?? 0) ?></td>
                                <td>
                                    <?php if ($stok >= $qty): ?>
                                        <span class="badge bg-success">Cukup</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Kurang</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Penyerahan Terkait</h2>

                <?php if ($penyerahan_rows->count() === 0): ?>
                    <div class="alert alert-light border mb-0">Belum ada penyerahan dari pesanan ini.</div>
                <?php else: ?>
                    <div class="table-responsive border rounded">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No Penyerahan</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($penyerahan_rows as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc((string) $p->no_penyerahan_penjualan) ?></td>
                                        <td><?= esc((string) $p->tanggal_penyerahan) ?></td>
                                        <td><?= esc((string) $p->status_penyerahan) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h2 class="h5 mb-3">Produksi Terkait</h2>

                <?php if ($produksi_rows->count() === 0): ?>
                    <div class="alert alert-light border mb-0">Belum ada perintah produksi dari pesanan ini.</div>
                <?php else: ?>
                    <div class="table-responsive border rounded">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No Produksi</th>
                                    <th>Qty</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($produksi_rows as $p): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= esc((string) $p->no_perintah_produksi) ?></td>
                                        <td><?= esc(pesanan_online_detail_qty($p->qty_rencana ?? 0)) ?></td>
                                        <td><?= esc((string) $p->status_produksi) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>