<?php
use Illuminate\Database\Eloquent\Model;

class StokOpnameDetailORM extends Model
{
    protected $table = 'tb_stok_opname_detail';
    protected $primaryKey = 'id_stok_opname_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_stok_opname',
        'jenis_barang',
        'id_referensi_barang',
        'qty_sistem',
        'qty_fisik',
        'selisih_qty',
        'harga_satuan',
        'nilai_selisih',
        'keterangan',
    ];
}