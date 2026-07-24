<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class PelangganORM extends Model
{
    protected $table = 'tb_pelanggan';
    protected $primaryKey = 'id_pelanggan';
    public $timestamps = false;
    protected $guarded = [];
}