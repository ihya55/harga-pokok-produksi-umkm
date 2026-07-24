<?php
use Illuminate\Database\Eloquent\Model;

class JurnalDetailORM extends Model
{
    protected $table = 'tb_jurnal_detail';
    protected $primaryKey = 'id_jurnal_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_jurnal',
        'urutan',
        'id_coa',
        'debit',
        'kredit',
        'keterangan_baris',
        'id_pelanggan',
        'id_pemasok',
        'id_produk',
        'id_bahan_baku',
        'id_gudang',
    ];
}