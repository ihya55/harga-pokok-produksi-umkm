<?php
use Illuminate\Database\Eloquent\Model;

class SaldoAwalStokDetailORM extends Model
{
    protected $table = 'tb_saldo_awal_stok_detail';
    protected $primaryKey = 'id_saldo_awal_stok_detail';
    public $timestamps = false;

    protected $fillable = [
        'id_saldo_awal_stok',
        'jenis_barang',
        'id_referensi_barang',
        'qty_awal',
        'harga_satuan',
        'nilai_total',
        'keterangan',
    ];
}