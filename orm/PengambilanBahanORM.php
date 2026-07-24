<?php
use Illuminate\Database\Eloquent\Model;

class PengambilanBahanORM extends Model
{
    protected $table = 'tb_pengambilan_bahan';
    protected $primaryKey = 'id_pengambilan_bahan';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_pengambilan_bahan',
        'tanggal_pengambilan',
        'id_perintah_produksi',
        'id_gudang',
        'status_posting',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}