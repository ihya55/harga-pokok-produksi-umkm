<?php
use Illuminate\Database\Eloquent\Model;

class PengambilanBahanDetailORM extends Model
{
    protected $table = 'tb_pengambilan_bahan_detail';
    protected $primaryKey = 'id_pengambilan_bahan_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_pengambilan_bahan',
        'id_bahan_baku',
        'qty',
        'harga_satuan',
        'subtotal',
    ];
}