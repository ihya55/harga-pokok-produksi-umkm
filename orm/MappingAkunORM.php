<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class MappingAkunORM extends Model
{
    protected $table = 'tb_mapping_akun';
    protected $primaryKey = 'id_mapping_akun';
    public $timestamps = false;
    protected $guarded = [];
}