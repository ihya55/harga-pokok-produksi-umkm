<?php
use Illuminate\Database\Eloquent\Model;

class BiayaProduksiDetailORM extends Model
{
    protected $table = 'tb_biaya_produksi_detail';
    protected $primaryKey = 'id_biaya_produksi_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_biaya_produksi',
        'jenis_biaya_produksi',
        'id_coa_lawan',
        'kode_jenis_transaksi_template',
        'jumlah_biaya',
        'keterangan',
    ];
}