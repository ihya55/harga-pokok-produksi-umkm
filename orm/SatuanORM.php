<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class SatuanORM extends Model
{
    protected $table = 'tb_satuan';
    protected $primaryKey = 'id_satuan';
    public $timestamps = false;
    protected $guarded = [];
}