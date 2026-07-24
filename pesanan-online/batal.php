<?php
declare(strict_types=1);
require_once __DIR__ . '/_helper.php';
use Illuminate\Database\Capsule\Manager as Capsule;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . po_url('cek.php')); exit; }
$id_entitas = po_id_entitas();
$id = (int)($_POST['id_pesanan_penjualan'] ?? 0);
$no_hp = trim((string)($_POST['no_hp'] ?? ''));
try {
    $q = Capsule::table('tb_pesanan_penjualan as pp')
        ->join('tb_pelanggan as p','p.id_pelanggan','=','pp.id_pelanggan')
        ->where('pp.id_entitas',$id_entitas)
        ->where('pp.id_pesanan_penjualan',$id)
        ->select(['pp.*','p.no_hp']);
    $pesanan = $q->first();
    if (!$pesanan) throw new RuntimeException('Pesanan tidak ditemukan.');
    if ($no_hp !== '' && preg_replace('/\D+/','',(string)$pesanan->no_hp) !== preg_replace('/\D+/','',$no_hp)) throw new RuntimeException('No HP tidak cocok dengan pesanan.');
    $status = strtolower((string)($pesanan->status_pesanan ?? ''));
    $pay = strtolower((string)($pesanan->status_pembayaran_online ?? 'belum_bayar'));
    if (in_array($status, ['diproses','selesai','batal'], true)) throw new RuntimeException('Pesanan tidak bisa dibatalkan karena sudah diproses/selesai/batal.');
    if (in_array($pay, ['lunas','paid','settlement'], true)) throw new RuntimeException('Pesanan sudah dibayar dan dikonfirmasi. Hubungi admin untuk proses refund.');
    Capsule::table('tb_pesanan_penjualan')->where('id_entitas',$id_entitas)->where('id_pesanan_penjualan',$id)->update([
        'status_pesanan'=>'batal','tanggal_diubah'=>date('Y-m-d H:i:s')
    ]);
    header('Location: ' . po_url('sukses.php?cancelled=1&id=' . $id . '&entitas=' . $id_entitas)); exit;
} catch (Throwable $e) {
    header('Location: ' . po_url('sukses.php?error_batal=' . urlencode($e->getMessage()) . '&id=' . $id . '&entitas=' . $id_entitas)); exit;
}
