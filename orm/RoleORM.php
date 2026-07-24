<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class RoleORM extends Model
{
    protected $table = 'tb_role';
    protected $primaryKey = 'id_role';
    public $timestamps = false;
    protected $guarded = [];
}