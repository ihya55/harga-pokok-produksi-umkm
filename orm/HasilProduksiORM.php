<?php
use Illuminate\Database\Eloquent\Model;

class HasilProduksiORM extends Model
{
    protected $table = 'tb_hasil_produksi';
    protected $primaryKey = 'id_hasil_produksi';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_hasil_produksi',
        'tanggal_hasil',
        'id_perintah_produksi',
        'id_produk',
        'id_gudang',
        'qty_hasil',
        'total_biaya_bahan',
        'total_biaya_tenaga_kerja',
        'total_biaya_bop',
        'total_hpp',
        'hpp_per_unit',
        'status_posting',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_posting',
        'diposting_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}