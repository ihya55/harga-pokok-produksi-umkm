<?php
use Illuminate\Database\Eloquent\Model;

class LogJurnalSumberORM extends Model
{
    protected $table = 'tb_log_jurnal_sumber';
    protected $primaryKey = 'id_log_jurnal_sumber';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'id_jurnal',
        'tabel_sumber',
        'id_sumber',
        'no_sumber',
        'kode_jenis_transaksi',
        'tanggal_dibuat',
    ];
}