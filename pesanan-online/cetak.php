<?php

declare(strict_types=1);
require_once __DIR__ . '/_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$id_entitas = po_id_entitas();
$id = (int) ($_GET['id'] ?? 0);
$no = trim((string) ($_GET['no'] ?? ''));
$entitas = po_entitas($id_entitas);

$q = Capsule::table('tb_pesanan_penjualan as pp')
    ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 'pp.id_pelanggan')
    ->where('pp.id_entitas', $id_entitas);
if ($id > 0) {
    $q->where('pp.id_pesanan_penjualan', $id);
} elseif ($no !== '') {
    $q->where('pp.no_pesanan_penjualan', $no);
} else {
    $q->whereRaw('1=0');
}

$pesanan = $q->select(['pp.*', 'p.nama_pelanggan', 'p.no_hp', 'p.alamat'])->first();
$detail = collect();
if ($pesanan) {
    $detail = Capsule::table('tb_pesanan_penjualan_detail as d')
        ->join('tb_produk as pr', 'pr.id_produk', '=', 'd.id_produk')
        ->where('d.id_pesanan_penjualan', (int) $pesanan->id_pesanan_penjualan)
        ->select(['d.*', 'pr.kode_produk', 'pr.nama_produk'])
        ->get();
}

$code = $pesanan ? (string) $pesanan->no_pesanan_penjualan : '';
$detailUrl = $pesanan ? po_order_detail_url((int) $pesanan->id_pesanan_penjualan, $id_entitas) : '';
// QR pengambilan berisi No Pesanan, bukan URL, agar kasir tinggal scan no pesanan.
$qr = $code !== '' ? po_qr_image_url($code, 420) : '';
$namaUmkm = (string) ($entitas->nama_entitas ?? 'UMKM');
$noHpUmkm = trim((string) ($entitas->no_hp ?? ''));
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Struk Pesanan <?= po_esc($code) ?></title>
<style>
body{margin:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#111827}.toolbar{max-width:420px;margin:12px auto;display:flex;gap:8px;justify-content:center;flex-wrap:wrap}.btn{border:1px solid #ddd;background:#fff;border-radius:8px;padding:8px 12px;text-decoration:none;color:#111;font-weight:700}.ticket{width:58mm;max-width:96%;margin:0 auto 20px;background:#fff;border:1px solid #e5e7eb;padding:2.2mm;box-shadow:0 10px 28px rgba(15,23,42,.10);font-size:10px;line-height:1.25}.center{text-align:center}.brand{font-size:14px;font-weight:900;text-transform:uppercase;letter-spacing:.4px}.muted{font-size:10px;color:#64748b}.line{border-top:1px dashed #333;margin:6px 0}.code{font-size:22px;font-weight:900;letter-spacing:.6px;line-height:1.05;margin:6px 0}.row{display:flex;justify-content:space-between;gap:8px;margin:3px 0}.row span:last-child,.row strong:last-child{text-align:right}.item{margin:6px 0}.item-name{font-weight:700}.total{font-size:13px;font-weight:900}.qr{width:34mm;height:34mm;object-fit:contain;display:block;margin:8px auto 4px}.thanks{font-weight:800;font-size:12px;margin-top:4px}.service{font-size:10px;color:#334155}.note{font-size:10px;color:#475569;margin-top:4px}.umkm{margin-top:6px;font-size:10px;font-weight:700}.items{min-height:20px}@media print{.toolbar{display:none}body{background:#fff}.ticket{box-shadow:none;margin:0 auto;border:none;max-width:58mm;width:58mm}}
</style>
</head>
<body>
<div class="toolbar">
  <?php if ($pesanan): ?>
    <a class="btn" href="<?= po_esc(po_url('simpan_jpg.php?id=' . (int) $pesanan->id_pesanan_penjualan . '&entitas=' . (int) $id_entitas)) ?>">Simpan JPG</a>
  <?php endif; ?>
  <a class="btn" href="<?= po_esc(po_url('cek.php?entitas=' . (int) $id_entitas)) ?>">Kembali</a>
</div>
<div class="ticket">
<?php if (!$pesanan): ?>
  <strong>Pesanan tidak ditemukan.</strong>
<?php else: ?>
  <div class="center">
    <div class="brand"><?= po_esc($namaUmkm) ?></div>
    <div class="muted"><?= po_esc((string) ($entitas->alamat ?? '-')) ?></div>
    <?php if ($noHpUmkm !== ''): ?><div class="muted">Layanan: <?= po_esc($noHpUmkm) ?></div><?php endif; ?>
  </div>
  <div class="line"></div>
  <div class="center muted">NO PESANAN</div>
  <div class="center code"><?= po_esc($code) ?></div>
  <div class="row"><span>Tanggal</span><strong><?= po_esc(date('d/m/Y', strtotime((string) $pesanan->tanggal_pesanan))) ?></strong></div>
  <div class="row"><span>Pelanggan</span><strong><?= po_esc($pesanan->nama_pelanggan ?? '-') ?></strong></div>
  <div class="row"><span>No HP</span><strong><?= po_esc($pesanan->no_hp ?? '-') ?></strong></div>
  <div class="row"><span>Status</span><strong><?= po_esc(po_status_label($pesanan->status_pesanan ?? 'draft')) ?></strong></div>
  <div class="line"></div>
  <div class="items">
    <?php foreach ($detail as $d): ?>
      <div class="item">
        <div class="item-name"><?= po_esc($d->nama_produk) ?></div>
        <div class="row"><span><?= po_qty($d->qty) ?> × <?= po_uang($d->harga) ?></span><strong><?= po_uang($d->subtotal) ?></strong></div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="line"></div>
  <div class="row total"><span>Total</span><strong><?= po_uang($pesanan->total ?? 0) ?></strong></div>
  <div class="line"></div>
  <?php if ($qr !== ''): ?><img class="qr" src="<?= po_esc($qr) ?>" alt="QR Pesanan"><?php endif; ?>
  <div class="center thanks">Terima kasih sudah memesan</div>
  <div class="center note">Scan QR ini berisi No Pesanan untuk pengambilan produk.</div>
  <div class="center note">Tunjukkan QR ini ke kasir saat mengambil produk.</div>
  <?php if ($noHpUmkm !== ''): ?><div class="center service">Layanan konsumen: <?= po_esc($noHpUmkm) ?></div><?php endif; ?>
  <div class="center umkm"><?= po_esc($namaUmkm) ?></div>
<?php endif; ?>
</div>
</body>
</html>
