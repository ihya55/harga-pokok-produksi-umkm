<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class PesananPembelianORM extends Model
{
    protected $table = 'tb_pesanan_pembelian';
    protected $primaryKey = 'id_pesanan_pembelian';
    public $timestamps = false;
    protected $guarded = [];
}