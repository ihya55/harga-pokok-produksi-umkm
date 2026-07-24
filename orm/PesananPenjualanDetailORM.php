<?php
use Illuminate\Database\Eloquent\Model;

class PesananPenjualanDetailORM extends Model
{
    protected $table = 'tb_pesanan_penjualan_detail';
    protected $primaryKey = 'id_pesanan_penjualan_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_pesanan_penjualan',
        'id_produk',
        'qty',
        'harga',
        'diskon',
        'subtotal',
        'hpp_standar',
        'catatan',
    ];
}