<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
use Illuminate\Database\Capsule\Manager as Capsule;
harus_login();
$user=user_login(); $id_entitas=(int)($user['id_entitas']??0); $id_pengguna=(int)($user['id_pengguna']??0);

function kp_get_produk_hpp_saldo(int $id_entitas, int $id_produk, int $id_gudang): array {
    $saldo = Capsule::table('tb_saldo_stok')
        ->where('id_entitas', $id_entitas)
        ->where('jenis_barang', 'produk')
        ->where('id_referensi_barang', $id_produk)
        ->where('id_gudang', $id_gudang)
        ->first();

    $qty = $saldo ? (float) ($saldo->qty_saldo ?? 0) : 0.0;
    $nilai = $saldo ? (float) ($saldo->nilai_saldo ?? 0) : 0.0;
    $hpp = $saldo ? (float) ($saldo->hpp_rata_rata ?? 0) : 0.0;

    if ($hpp <= 0 && $qty > 0 && $nilai > 0) {
        $hpp = $nilai / $qty;
    }

    if ($hpp <= 0) {
        $produk = Capsule::table('tb_produk')
            ->where('id_entitas', $id_entitas)
            ->where('id_produk', $id_produk)
            ->first();
        $hpp = (float) ($produk->hpp_standar ?? 0);
    }

    return ['qty' => $qty, 'hpp' => $hpp, 'nilai' => $nilai];
}

function kp_hitung_nilai_konversi(int $id_entitas, int $id_gudang, int $src, float $qty_src, float $qty_dst): array {
    $saldo = kp_get_produk_hpp_saldo($id_entitas, $src, $id_gudang);
    $hpp_sumber = (float) $saldo['hpp'];
    $nilai_sumber = $hpp_sumber > 0 ? round($qty_src * $hpp_sumber, 2) : 0.0;
    $hpp_tujuan = ($qty_dst > 0 && $nilai_sumber > 0) ? round($nilai_sumber / $qty_dst, 6) : 0.0;
    return [$hpp_sumber, $nilai_sumber, $hpp_tujuan, $nilai_sumber];
}

try {
    $id=(int)($_POST['id']??0); $row=Capsule::table('tb_konversi_produk')->where('id_entitas',$id_entitas)->where('id_konversi_produk',$id)->first();
    if(!$row) throw new RuntimeException('Data konversi produk tidak ditemukan.');
    if($row->status_posting!=='draft') throw new RuntimeException('Data posted tidak bisa diubah.');
    $tanggal=trim((string)($_POST['tanggal_konversi']??''));
    $id_gudang=(int)($_POST['id_gudang']??0); $src=(int)($_POST['id_produk_sumber']??0); $dst=(int)($_POST['id_produk_tujuan']??0);
    $qty_src=(float)($_POST['qty_sumber']??0); $qty_dst=(float)($_POST['qty_tujuan']??0); $catatan=trim((string)($_POST['catatan']??''));
    if($tanggal===''||$id_gudang<=0||$src<=0||$dst<=0||$qty_src<=0||$qty_dst<=0) throw new RuntimeException('Lengkapi data konversi produk.');
    if($src===$dst) throw new RuntimeException('Produk sumber dan produk tujuan tidak boleh sama.');
    [$hpp_sumber, $nilai_sumber, $hpp_tujuan, $nilai_tujuan] = kp_hitung_nilai_konversi($id_entitas, $id_gudang, $src, $qty_src, $qty_dst);
    Capsule::table('tb_konversi_produk')->where('id_konversi_produk',$id)->update([
        'tanggal_konversi'=>$tanggal,'id_gudang'=>$id_gudang,'id_produk_sumber'=>$src,'qty_sumber'=>$qty_src,
        'hpp_sumber'=>$hpp_sumber,'nilai_sumber'=>$nilai_sumber,
        'id_produk_tujuan'=>$dst,'qty_tujuan'=>$qty_dst,'hpp_tujuan'=>$hpp_tujuan,'nilai_tujuan'=>$nilai_tujuan,
        'catatan'=>$catatan,'tanggal_diubah'=>date('Y-m-d H:i:s'),'diubah_oleh'=>$id_pengguna?:null
    ]);
    set_flash('success','Konversi produk berhasil diupdate.');
} catch(Throwable $e) { set_flash('error',$e->getMessage()); }
redirect_admin('persediaan/konversi-produk');
