<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class TemplateJurnalDetailORM extends Model
{
    protected $table = 'tb_template_jurnal_detail';
    protected $primaryKey = 'id_template_jurnal_detail';
    public $timestamps = false;
    protected $guarded = [];
}