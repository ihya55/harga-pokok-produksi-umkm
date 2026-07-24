<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
use Illuminate\Database\Capsule\Manager as Capsule;
harus_login(); header('Content-Type: application/json; charset=utf-8');
$id_entitas=(int)(user_login()['id_entitas']??0); $kode=trim((string)($_GET['kode']??''));
try{
 if($kode==='') throw new RuntimeException('Kode pesanan wajib diisi.');
 $pesanan=Capsule::table('tb_pesanan_penjualan as ps')->leftJoin('tb_pelanggan as p','p.id_pelanggan','=','ps.id_pelanggan')
  ->where('ps.id_entitas',$id_entitas)->where('ps.sumber_pesanan','website')->where('ps.no_pesanan_penjualan',$kode)
  ->select(['ps.*','p.kode_pelanggan','p.nama_pelanggan','p.alamat as alamat_pelanggan'])->first();
 if(!$pesanan) throw new RuntimeException('Pesanan online tidak ditemukan.');
 $metode=strtolower((string)($pesanan->metode_pembayaran_online??''));
 $status=(string)$pesanan->status_pesanan;
 if($metode==='cod'){
  if(!in_array($status,['draft','terkonfirmasi','diproses'],true)) throw new RuntimeException('Pesanan COD tidak bisa diserahkan karena statusnya '.$status.'.');
 } else {
  if(!in_array($status,['terkonfirmasi','diproses'],true)) throw new RuntimeException('Pesanan belum siap diserahkan. Pastikan pembayaran sudah dikonfirmasi/admin sudah memproses pesanan.');
 }
 $exists=Capsule::table('tb_penyerahan_penjualan')->where('id_entitas',$id_entitas)->where('id_pesanan_penjualan',(int)$pesanan->id_pesanan_penjualan)->whereIn('status_penyerahan',['draft','posted'])->exists();
 if($exists) throw new RuntimeException('Pesanan ini sudah memiliki penyerahan draft/posted.');
 echo json_encode(['success'=>true,'message'=>'Pesanan online ditemukan.','pesanan'=>[
  'id_pesanan_penjualan'=>(int)$pesanan->id_pesanan_penjualan,
  'no_pesanan_penjualan'=>(string)$pesanan->no_pesanan_penjualan,
  'id_pelanggan'=>(int)$pesanan->id_pelanggan,
  'pelanggan_label'=>(string)(($pesanan->kode_pelanggan??'-').' - '.($pesanan->nama_pelanggan??'-')),
  'alamat_pelanggan'=>(string)($pesanan->alamat_pelanggan??''),
  'metode_pembayaran_online'=>(string)($pesanan->metode_pembayaran_online??''),
  'status_pembayaran_online'=>(string)($pesanan->status_pembayaran_online??''),
  'total'=>(float)($pesanan->nominal_pembayaran_online??$pesanan->total??0),
 ]],JSON_UNESCAPED_UNICODE); exit;
}catch(Throwable $e){ echo json_encode(['success'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE); exit; }
