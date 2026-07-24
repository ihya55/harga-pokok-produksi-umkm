<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class EntitasORM extends Model
{
    protected $table = 'tb_entitas';
    protected $primaryKey = 'id_entitas';
    public $timestamps = false;
    protected $guarded = [];
}