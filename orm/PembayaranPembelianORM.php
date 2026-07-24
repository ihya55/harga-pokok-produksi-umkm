<?php
use Illuminate\Database\Eloquent\Model;

class PembayaranPembelianORM extends Model
{
    protected $table = 'tb_pembayaran_pembelian';
    protected $primaryKey = 'id_pembayaran_pembelian';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_pembayaran_pembelian',
        'tanggal_pembayaran',
        'id_faktur_pembelian',
        'id_pemasok',
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