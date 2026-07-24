<?php
use Illuminate\Database\Eloquent\Model;

class PerintahProduksiORM extends Model
{
    protected $table = 'tb_perintah_produksi';
    protected $primaryKey = 'id_perintah_produksi';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_perintah_produksi',
        'tanggal_perintah',
        'id_produk',
        'id_resep',
        'qty_rencana',
        'qty_hasil',
        'status_produksi',
        'tanggal_mulai',
        'tanggal_selesai',
        'id_pesanan_penjualan',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}