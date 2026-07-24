<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class TemplateJurnalORM extends Model
{
    protected $table = 'tb_template_jurnal';
    protected $primaryKey = 'id_template_jurnal';
    public $timestamps = false;
    protected $guarded = [];
}