<?php
use Illuminate\Database\Eloquent\Model;

class JurnalORM extends Model
{
    protected $table = 'tb_jurnal';
    protected $primaryKey = 'id_jurnal';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_jurnal',
        'tanggal_jurnal',
        'id_periode',
        'kode_jenis_transaksi',
        'keterangan',
        'tabel_sumber',
        'id_sumber',
        'no_sumber',
        'status_jurnal',
        'total_debit',
        'total_kredit',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_posting',
        'diposting_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}