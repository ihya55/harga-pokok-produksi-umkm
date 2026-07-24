<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class ResepORM extends Model
{
    protected $table = 'tb_resep';
    protected $primaryKey = 'id_resep';
    public $timestamps = false;
    protected $guarded = [];
}