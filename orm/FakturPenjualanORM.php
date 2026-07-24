<?php
use Illuminate\Database\Eloquent\Model;

class FakturPenjualanORM extends Model
{
    protected $table = 'tb_faktur_penjualan';
    protected $primaryKey = 'id_faktur_penjualan';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_faktur_penjualan',
        'tanggal_faktur',
        'id_pelanggan',
        'id_penyerahan_penjualan',
        'jenis_pembayaran',
        'status_faktur',
        'jatuh_tempo',
        'subtotal',
        'diskon',
        'ppn',
        'total',
        'sisa_piutang',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_posting',
        'diposting_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}