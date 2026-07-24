<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class PajakORM extends Model
{
    protected $table = 'tb_pajak';
    protected $primaryKey = 'id_pajak';
    public $timestamps = false;
    protected $guarded = [];
}
