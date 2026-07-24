<?php
use Illuminate\Database\Eloquent\Model;

class PenyerahanPenjualanORM extends Model
{
    protected $table = 'tb_penyerahan_penjualan';
    protected $primaryKey = 'id_penyerahan_penjualan';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_penyerahan_penjualan',
        'tanggal_penyerahan',
        'id_pesanan_penjualan',
        'id_pelanggan',
        'id_gudang',
        'status_penyerahan',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_posting',
        'diposting_oleh',
    ];
}