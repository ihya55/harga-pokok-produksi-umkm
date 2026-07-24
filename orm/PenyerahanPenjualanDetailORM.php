<?php
use Illuminate\Database\Eloquent\Model;

class PenyerahanPenjualanDetailORM extends Model
{
    protected $table = 'tb_penyerahan_penjualan_detail';
    protected $primaryKey = 'id_penyerahan_penjualan_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_penyerahan_penjualan',
        'id_produk',
        'qty',
        'hpp_satuan',
        'hpp_total',
        'catatan',
    ];
}