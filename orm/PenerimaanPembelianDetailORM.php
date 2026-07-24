<?php
use Illuminate\Database\Eloquent\Model;

class PenerimaanPembelianDetailORM extends Model
{
    protected $table = 'tb_penerimaan_pembelian_detail';
    protected $primaryKey = 'id_penerimaan_pembelian_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_penerimaan_pembelian',
        'id_bahan_baku',
        'qty',
        'harga',
        'subtotal',
    ];
}