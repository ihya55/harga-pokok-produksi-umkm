<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class PenggunaORM extends Model
{
    protected $table = 'tb_pengguna';
    protected $primaryKey = 'id_pengguna';
    public $timestamps = false;
    protected $guarded = [];
}