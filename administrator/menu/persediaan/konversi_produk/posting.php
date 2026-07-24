<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
use Illuminate\Database\Capsule\Manager as Capsule;
harus_login();
$user=user_login(); $id_entitas=(int)($user['id_entitas']??0); $id_pengguna=(int)($user['id_pengguna']??0); $id=(int)($_GET['id']??0);

function kp_upsert_saldo_produk(int $id_entitas, int $id_produk, int $id_gudang, float $qty_delta, float $nilai_delta, int $id_pengguna): void {
    $saldo = Capsule::table('tb_saldo_stok')->where('id_entitas',$id_entitas)->where('jenis_barang','produk')->where('id_referensi_barang',$id_produk)->where('id_gudang',$id_gudang)->lockForUpdate()->first();
    if($saldo){
        $qty=(float)$saldo->qty_saldo + $qty_delta; $nilai=(float)$saldo->nilai_saldo + $nilai_delta;
        if(abs($qty) < 0.0001) $qty=0; if(abs($nilai) < 0.01) $nilai=0;
        $hpp=$qty>0 ? max(0,$nilai/$qty) : 0;
        Capsule::table('tb_saldo_stok')->where('id_saldo_stok',$saldo->id_saldo_stok)->update(['qty_saldo'=>$qty,'nilai_saldo'=>$nilai,'hpp_rata_rata'=>$hpp,'tanggal_update'=>date('Y-m-d H:i:s'),'tanggal_diubah'=>date('Y-m-d H:i:s'),'diubah_oleh'=>$id_pengguna?:null]);
    } else {
        $hpp=$qty_delta>0 ? max(0,$nilai_delta/$qty_delta) : 0;
        Capsule::table('tb_saldo_stok')->insert(['id_entitas'=>$id_entitas,'jenis_barang'=>'produk','id_referensi_barang'=>$id_produk,'id_gudang'=>$id_gudang,'qty_saldo'=>max(0,$qty_delta),'nilai_saldo'=>max(0,$nilai_delta),'hpp_rata_rata'=>$hpp,'tanggal_update'=>date('Y-m-d H:i:s'),'tanggal_dibuat'=>date('Y-m-d H:i:s'),'dibuat_oleh'=>$id_pengguna?:null]);
    }
}

try {
    Capsule::connection()->transaction(function() use ($id_entitas,$id_pengguna,$id) {
        $row=Capsule::table('tb_konversi_produk')->where('id_entitas',$id_entitas)->where('id_konversi_produk',$id)->lockForUpdate()->first();
        if(!$row) throw new RuntimeException('Data konversi produk tidak ditemukan.');
        if($row->status_posting!=='draft') throw new RuntimeException('Konversi produk sudah diposting.');
        if((int)$row->id_produk_sumber === (int)$row->id_produk_tujuan) throw new RuntimeException('Produk sumber dan tujuan tidak boleh sama.');
        $produkSumber=Capsule::table('tb_produk')->where('id_entitas',$id_entitas)->where('id_produk',(int)$row->id_produk_sumber)->first();
        $produkTujuan=Capsule::table('tb_produk')->where('id_entitas',$id_entitas)->where('id_produk',(int)$row->id_produk_tujuan)->first();
        if(!$produkSumber||!$produkTujuan) throw new RuntimeException('Produk sumber/tujuan tidak valid.');
        $saldo=Capsule::table('tb_saldo_stok')->where('id_entitas',$id_entitas)->where('jenis_barang','produk')->where('id_referensi_barang',(int)$row->id_produk_sumber)->where('id_gudang',(int)$row->id_gudang)->lockForUpdate()->first();
        $qty_saldo=$saldo?(float)$saldo->qty_saldo:0; $hpp_sumber=$saldo?(float)$saldo->hpp_rata_rata:0;
        if($qty_saldo + 0.0001 < (float)$row->qty_sumber) throw new RuntimeException('Stok produk sumber tidak cukup. Saldo tersedia: '.number_format($qty_saldo,3,',','.'));
        if($hpp_sumber <= 0) $hpp_sumber=(float)($produkSumber->hpp_standar ?? 0);
        if($hpp_sumber <= 0) throw new RuntimeException('HPP produk sumber masih 0. Posting hasil produksi/saldo awal terlebih dahulu supaya HPP terbentuk.');
        $nilai_sumber=round((float)$row->qty_sumber*$hpp_sumber,2); $hpp_tujuan=round($nilai_sumber/(float)$row->qty_tujuan,2); $nilai_tujuan=$nilai_sumber;
        Capsule::table('tb_mutasi_stok')->insert([
            'id_entitas'=>$id_entitas,'tanggal_mutasi'=>$row->tanggal_konversi,'jenis_barang'=>'produk','id_referensi_barang'=>(int)$row->id_produk_sumber,'id_gudang'=>(int)$row->id_gudang,
            'jenis_mutasi'=>'konversi_produk_keluar','qty_masuk'=>0,'qty_keluar'=>(float)$row->qty_sumber,'harga_satuan'=>$hpp_sumber,'nilai_total'=>$nilai_sumber,
            'tabel_sumber'=>'tb_konversi_produk','id_sumber'=>(int)$row->id_konversi_produk,'no_sumber'=>$row->no_konversi_produk,'keterangan'=>'Keluar karena konversi produk ke '.$produkTujuan->nama_produk,'tanggal_dibuat'=>date('Y-m-d H:i:s'),'dibuat_oleh'=>$id_pengguna?:null
        ]);
        Capsule::table('tb_mutasi_stok')->insert([
            'id_entitas'=>$id_entitas,'tanggal_mutasi'=>$row->tanggal_konversi,'jenis_barang'=>'produk','id_referensi_barang'=>(int)$row->id_produk_tujuan,'id_gudang'=>(int)$row->id_gudang,
            'jenis_mutasi'=>'konversi_produk_masuk','qty_masuk'=>(float)$row->qty_tujuan,'qty_keluar'=>0,'harga_satuan'=>$hpp_tujuan,'nilai_total'=>$nilai_tujuan,
            'tabel_sumber'=>'tb_konversi_produk','id_sumber'=>(int)$row->id_konversi_produk,'no_sumber'=>$row->no_konversi_produk,'keterangan'=>'Masuk dari konversi produk '.$produkSumber->nama_produk,'tanggal_dibuat'=>date('Y-m-d H:i:s'),'dibuat_oleh'=>$id_pengguna?:null
        ]);
        kp_upsert_saldo_produk($id_entitas,(int)$row->id_produk_sumber,(int)$row->id_gudang,-(float)$row->qty_sumber,-$nilai_sumber,$id_pengguna);
        kp_upsert_saldo_produk($id_entitas,(int)$row->id_produk_tujuan,(int)$row->id_gudang,(float)$row->qty_tujuan,$nilai_tujuan,$id_pengguna);
        Capsule::table('tb_konversi_produk')->where('id_konversi_produk',(int)$row->id_konversi_produk)->update(['hpp_sumber'=>$hpp_sumber,'nilai_sumber'=>$nilai_sumber,'hpp_tujuan'=>$hpp_tujuan,'nilai_tujuan'=>$nilai_tujuan,'status_posting'=>'posted','tanggal_posting'=>date('Y-m-d H:i:s'),'diposting_oleh'=>$id_pengguna?:null]);
    });
    set_flash('success','Konversi produk berhasil diposting. Stok sumber berkurang dan stok produk tujuan bertambah dengan HPP yang sama.');
} catch(Throwable $e) { set_flash('error','Posting gagal: '.$e->getMessage()); }
redirect_url(admin_page_url('persediaan/konversi-produk/detail') . '&id=' . $id);
