<?php
use Illuminate\Database\Eloquent\Model;

class PenerimaanPembelianORM extends Model
{
    protected $table = 'tb_penerimaan_pembelian';
    protected $primaryKey = 'id_penerimaan_pembelian';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_penerimaan_pembelian',
        'tanggal_penerimaan',
        'id_pesanan_pembelian',
        'id_pemasok',
        'id_gudang',
        'status_penerimaan',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_posting',
        'diposting_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}