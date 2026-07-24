<?php
use Illuminate\Database\Eloquent\Model;

class FakturPenjualanDetailORM extends Model
{
    protected $table = 'tb_faktur_penjualan_detail';
    protected $primaryKey = 'id_faktur_penjualan_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_faktur_penjualan',
        'id_produk',
        'qty',
        'harga',
        'diskon',
        'subtotal',
    ];
}