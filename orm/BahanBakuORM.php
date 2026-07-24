<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class BahanBakuORM extends Model
{
    protected $table = 'tb_bahan_baku';
    protected $primaryKey = 'id_bahan_baku';
    public $timestamps = false;
    protected $guarded = [];
}