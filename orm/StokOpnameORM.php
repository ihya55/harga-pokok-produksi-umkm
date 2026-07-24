<?php
use Illuminate\Database\Eloquent\Model;

class StokOpnameORM extends Model
{
    protected $table = 'tb_stok_opname';
    protected $primaryKey = 'id_stok_opname';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_stok_opname',
        'tanggal_stok_opname',
        'id_gudang',
        'status_posting',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}