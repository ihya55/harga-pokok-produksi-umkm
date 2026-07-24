<?php
use Illuminate\Database\Eloquent\Model;

class SaldoAwalStokORM extends Model
{
    protected $table = 'tb_saldo_awal_stok';
    protected $primaryKey = 'id_saldo_awal_stok';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_saldo_awal_stok',
        'tanggal_saldo_awal',
        'id_gudang',
        'id_coa_lawan',
        'total_nilai',
        'status_posting',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_posting',
        'diposting_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}