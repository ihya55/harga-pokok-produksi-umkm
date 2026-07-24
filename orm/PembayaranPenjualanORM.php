<?php
use Illuminate\Database\Eloquent\Model;

class PembayaranPenjualanORM extends Model
{
    protected $table = 'tb_pembayaran_penjualan';
    protected $primaryKey = 'id_pembayaran_penjualan';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_pembayaran_penjualan',
        'tanggal_pembayaran',
        'id_faktur_penjualan',
        'id_pelanggan',
        'metode_pembayaran',
        'id_coa_kas_bank',
        'jumlah_bayar',
        'catatan',
        'status_posting',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}