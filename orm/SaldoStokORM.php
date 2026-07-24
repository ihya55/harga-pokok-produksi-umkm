<?php
use Illuminate\Database\Eloquent\Model;

class SaldoStokORM extends Model
{
    protected $table = 'tb_saldo_stok';
    protected $primaryKey = 'id_saldo_stok';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'jenis_barang',
        'id_referensi_barang',
        'id_gudang',
        'qty_saldo',
        'nilai_saldo',
        'hpp_rata_rata',
        'tanggal_update',
    ];
}