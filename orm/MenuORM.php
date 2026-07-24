<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class MenuORM extends Model
{
    protected $table = 'tb_menu';
    protected $primaryKey = 'id_menu';
    public $timestamps = false;
    protected $guarded = [];
}