<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class PeriodeAkuntansiORM extends Model
{
    protected $table = 'tb_periode_akuntansi';
    protected $primaryKey = 'id_periode';
    public $timestamps = false;
    protected $guarded = [];
}