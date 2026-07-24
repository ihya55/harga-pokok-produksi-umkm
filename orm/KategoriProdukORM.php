<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class KategoriProdukORM extends Model
{
    protected $table = 'tb_kategori_produk';
    protected $primaryKey = 'id_kategori_produk';
    public $timestamps = false;
    protected $guarded = [];
}