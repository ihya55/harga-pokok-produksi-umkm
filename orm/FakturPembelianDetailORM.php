<?php
use Illuminate\Database\Eloquent\Model;

class FakturPembelianDetailORM extends Model
{
    protected $table = 'tb_faktur_pembelian_detail';
    protected $primaryKey = 'id_faktur_pembelian_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_faktur_pembelian',
        'id_bahan_baku',
        'qty',
        'harga',
        'diskon',
        'subtotal',
    ];
}