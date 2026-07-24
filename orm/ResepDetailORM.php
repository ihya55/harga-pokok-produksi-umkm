<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class ResepDetailORM extends Model
{
    protected $table = 'tb_resep_detail';
    protected $primaryKey = 'id_resep_detail';
    public $timestamps = false;
    protected $guarded = [];
}