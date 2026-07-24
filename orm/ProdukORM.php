<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class ProdukORM extends Model
{
    protected $table = 'tb_produk';
    protected $primaryKey = 'id_produk';
    public $timestamps = false;
    protected $guarded = [];
}