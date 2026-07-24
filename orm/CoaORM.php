<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class CoaORM extends Model
{
    protected $table = 'tb_coa';
    protected $primaryKey = 'id_coa';
    public $timestamps = false;
    protected $guarded = [];
}