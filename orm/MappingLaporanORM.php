<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class MappingLaporanORM extends Model
{
    protected $table = 'tb_mapping_laporan';
    protected $primaryKey = 'id_mapping_laporan';
    public $timestamps = false;
    protected $guarded = [];
}