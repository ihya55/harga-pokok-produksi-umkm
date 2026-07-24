<?php
use Illuminate\Database\Eloquent\Model;

class PesananPenjualanORM extends Model
{
    protected $table = 'tb_pesanan_penjualan';
    protected $primaryKey = 'id_pesanan_penjualan';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_pesanan_penjualan',
        'tanggal_pesanan',
        'id_pelanggan',
        'sumber_pesanan',
        'status_pesanan',
        'tanggal_kirim_rencana',
        'catatan',
        'subtotal',
        'diskon',
        'total',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}