<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class PesananPembelianDetailORM extends Model
{
    protected $table = 'tb_pesanan_pembelian_detail';
    protected $primaryKey = 'id_pesanan_pembelian_detail';
    public $timestamps = false;
    protected $guarded = [];
}