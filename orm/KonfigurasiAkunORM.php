<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class KonfigurasiAkunORM extends Model
{
    protected $table = 'tb_konfigurasi_akun';
    protected $primaryKey = 'id_konfigurasi_akun';
    public $timestamps = false;
    protected $guarded = [];
}