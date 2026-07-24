<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class RoleMenuORM extends Model
{
    protected $table = 'tb_role_menu';
    protected $primaryKey = 'id_role_menu';
    public $timestamps = false;
    protected $guarded = [];
}