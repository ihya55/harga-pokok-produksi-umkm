<?php
use Illuminate\Database\Eloquent\Model;

class MutasiStokORM extends Model
{
    protected $table = 'tb_mutasi_stok';
    protected $primaryKey = 'id_mutasi_stok';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'tanggal_mutasi',
        'jenis_barang',
        'id_referensi_barang',
        'id_gudang',
        'jenis_mutasi',
        'qty_masuk',
        'qty_keluar',
        'harga_satuan',
        'nilai_total',
        'tabel_sumber',
        'id_sumber',
        'no_sumber',
        'keterangan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}