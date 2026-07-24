<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class GudangORM extends Model
{
    protected $table = 'tb_gudang';
    protected $primaryKey = 'id_gudang';
    public $timestamps = false;
    protected $guarded = [];
}