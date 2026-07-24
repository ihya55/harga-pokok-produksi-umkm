<?php
use Illuminate\Database\Eloquent\Model;

class BiayaProduksiORM extends Model
{
    protected $table = 'tb_biaya_produksi';
    protected $primaryKey = 'id_biaya_produksi';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_biaya_produksi',
        'tanggal_biaya',
        'id_perintah_produksi',
        'jenis_biaya_produksi',
        'keterangan',
        'no_nota',
        'file_nota',
        'jumlah_biaya',
        'id_coa_lawan',
        'status_posting',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}