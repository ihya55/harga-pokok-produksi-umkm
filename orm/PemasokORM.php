<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class PemasokORM extends Model
{
    protected $table = 'tb_pemasok';
    protected $primaryKey = 'id_pemasok';
    public $timestamps = false;
    protected $guarded = [];
}