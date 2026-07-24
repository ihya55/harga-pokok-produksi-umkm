<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();
$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id = (int) ($_GET['id'] ?? 0);

$entitas = Capsule::table('tb_entitas')->where('id_entitas', $id_entitas)->first();
$logo_umkm = (string) ($entitas->logo_umkm ?? '');
$pesanan = Capsule::table('tb_pesanan_penjualan as ps')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'ps.id_pelanggan')
    ->where('ps.id_entitas', $id_entitas)
    ->where('ps.id_pesanan_penjualan', $id)
    ->where('ps.sumber_pesanan', 'website')
    ->select(['ps.*', 'pl.nama_pelanggan', 'pl.no_hp as no_hp_pelanggan', 'pl.alamat as alamat_pelanggan'])
    ->first();
if (!$pesanan) {
    exit('Pesanan online tidak ditemukan.');
}
$detail = Capsule::table('tb_pesanan_penjualan_detail as d')
    ->leftJoin('tb_produk as p', 'p.id_produk', '=', 'd.id_produk')
    ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
    ->where('d.id_pesanan_penjualan', $id)
    ->select(['d.*', 'p.kode_produk', 'p.nama_produk', 's.nama_satuan'])
    ->orderBy('d.id_pesanan_penjualan_detail')
    ->get();
function s_esc($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function s_uang($v): string { return 'Rp ' . number_format((float) $v, 0, ',', '.'); }
function s_qty($v): string { $n=(float)$v; return abs($n-round($n))<0.000001 ? number_format($n,0,',','.') : rtrim(rtrim(number_format($n,2,',','.'),'0'),','); }
function s_qr(string $data, int $size = 260): string { $data = trim($data); return $data === '' ? '' : 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&margin=12&data=' . rawurlencode($data); }

$orderViewUrl = base_url('pesanan-online/sukses.php?id=' . (int) $pesanan->id_pesanan_penjualan . '&entitas=' . $id_entitas);
$qrOrder = s_qr($orderViewUrl, 260);
$namaEntitas = (string) ($entitas->nama_entitas ?? APP_NAME);
$alamatEntitas = (string) ($entitas->alamat ?? '-');
$teleponEntitas = trim((string) ($entitas->no_hp ?? ''));
$metodeBayar = trim((string) ($pesanan->metode_pembayaran_online ?? 'cod'));
$statusPembayaran = trim((string) ($pesanan->status_pembayaran_online ?? 'belum_bayar'));
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Struk <?= s_esc($pesanan->no_pesanan_penjualan) ?></title>
<style>
@page{size:58mm auto;margin:0}
*{box-sizing:border-box}html,body{margin:0;padding:0;background:#fff;color:#111;font-family:Arial,Helvetica,sans-serif;font-size:10px;line-height:1.22}
.receipt{width:58mm;margin:0 auto;padding:1.8mm}
.center{text-align:center}.logo{max-width:17mm;max-height:17mm;object-fit:contain;margin:0 auto 3px;display:block}.brand{font-weight:900;font-size:14px;text-transform:uppercase;letter-spacing:.4px}.muted{color:#555;font-size:10px}.line{border-top:1px dashed #333;margin:6px 0}.row{display:flex;justify-content:space-between;gap:6px;margin:3px 0}.row span:last-child,.row b:last-child{text-align:right}.item{margin:5px 0}.item-name{font-weight:700}.total{font-size:13px;font-weight:900}.pocket-no{font-size:22px;font-weight:900;letter-spacing:.8px;line-height:1.05;word-break:break-word;margin:4px 0}.btn{display:inline-block;padding:8px 11px;border-radius:8px;border:1px solid #ccc;background:#fff;cursor:pointer;margin:8px 3px}.print-actions{text-align:center;margin:10px}.note-box{border:1px dashed #555;padding:5px;margin-top:6px}.qr{width:26mm;height:26mm;object-fit:contain;display:block;margin:8px auto 4px}.thanks{font-weight:800;font-size:12px;margin-top:4px}.service{font-size:10px;color:#334155}.footer-note{font-size:10px;color:#475569}.pill{display:inline-block;background:#f8fafc;border:1px solid #e5e7eb;border-radius:999px;padding:3px 8px;font-size:10px;font-weight:700;margin-top:4px}@media print{.print-actions{display:none}.receipt{width:58mm;margin:0;padding:1.5mm}}
</style>
</head>
<body>
<div class="print-actions"><button class="btn" onclick="window.print()">Cetak Struk</button><button class="btn" onclick="window.close()">Tutup</button></div>
<div class="receipt">
  <div class="center">
    <?php if ($logo_umkm !== ''): ?><img class="logo" src="<?= s_esc(base_url($logo_umkm)) ?>" alt="Logo UMKM"><?php endif; ?>
    <div class="brand"><?= s_esc($namaEntitas) ?></div>
    <div class="muted"><?= s_esc($alamatEntitas) ?></div>
    <?php if ($teleponEntitas !== ''): ?><div class="muted">Layanan: <?= s_esc($teleponEntitas) ?></div><?php endif; ?>
  </div>
  <div class="line"></div>
  <div class="center muted">NO PESANAN</div>
  <div class="center pocket-no"><?= s_esc($pesanan->no_pesanan_penjualan) ?></div>
  <div class="center"><span class="pill"><?= s_esc(strtoupper($metodeBayar)) ?></span> <span class="pill"><?= s_esc(strtoupper(str_replace('_', ' ', $statusPembayaran))) ?></span></div>
  <div class="line"></div>
  <div class="row"><span>Tanggal</span><span><?= s_esc(date('d/m/Y', strtotime((string) $pesanan->tanggal_pesanan))) ?></span></div>
  <div class="row"><span>Pelanggan</span><span><?= s_esc($pesanan->nama_pelanggan ?? '-') ?></span></div>
  <div class="row"><span>No HP</span><span><?= s_esc($pesanan->no_hp_pelanggan ?? '-') ?></span></div>
  <div class="line"></div>
  <?php foreach ($detail as $d): ?>
    <div class="item">
      <div class="item-name"><?= s_esc($d->nama_produk ?? '-') ?></div>
      <div class="row"><span><?= s_qty($d->qty) ?> <?= s_esc($d->nama_satuan ?? '') ?> × <?= s_uang($d->harga) ?></span><b><?= s_uang($d->subtotal) ?></b></div>
    </div>
  <?php endforeach; ?>
  <div class="line"></div>
  <div class="row"><span>Subtotal</span><span><?= s_uang($pesanan->subtotal ?? 0) ?></span></div>
  <div class="row"><span>Diskon</span><span><?= s_uang($pesanan->diskon ?? 0) ?></span></div>
  <div class="row total"><span>Total</span><span><?= s_uang($pesanan->total ?? 0) ?></span></div>
  <div class="line"></div>
  <div class="note-box"><b>Alamat/Catatan:</b><br><?= nl2br(s_esc($pesanan->alamat_pelanggan ?? '-')) ?></div>
  <?php if (!empty($pesanan->catatan)): ?><div style="margin-top:6px"><b>Catatan:</b><br><?= nl2br(s_esc($pesanan->catatan)) ?></div><?php endif; ?>
  <div class="line"></div>
  <?php if ($qrOrder !== ''): ?><img class="qr" src="<?= s_esc($qrOrder) ?>" alt="QR Pesanan Online"><?php endif; ?>
  <div class="center thanks">Terima kasih sudah memesan</div>
  <div class="center footer-note">Scan QR ini untuk membuka tampilan pesanan di sistem.</div>
  <div class="center footer-note">Struk ini bisa ditempel di kantong/kresek pesanan.</div>
  <?php if ($teleponEntitas !== ''): ?><div class="center service">Layanan konsumen: <?= s_esc($teleponEntitas) ?></div><?php endif; ?>
</div>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),250));</script>
</body>
</html>
