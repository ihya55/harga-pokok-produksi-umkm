<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class MappingLaporanDetailORM extends Model
{
    protected $table = 'tb_mapping_laporan_detail';
    protected $primaryKey = 'id_mapping_laporan_detail';
    public $timestamps = false;
    protected $guarded = [];
}