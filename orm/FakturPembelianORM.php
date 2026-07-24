<?php
use Illuminate\Database\Eloquent\Model;

class FakturPembelianORM extends Model
{
    protected $table = 'tb_faktur_pembelian';
    protected $primaryKey = 'id_faktur_pembelian';
    public $timestamps = false;

    protected $fillable = [
        'id_entitas',
        'no_faktur_pembelian',
        'tanggal_faktur',
        'id_pemasok',
        'id_penerimaan_pembelian',
        'jenis_pembayaran',
        'id_coa_kas_bank',
        'status_faktur',
        'jatuh_tempo',
        'subtotal',
        'diskon',
        'diskon_persen',
        'ppn',
        'ppn_persen',
        'ada_biaya_kirim',
        'biaya_kirim',
        'id_coa_biaya_kirim',
        'total',
        'sisa_utang',
        'catatan',
        'tanggal_dibuat',
        'dibuat_oleh',
        'tanggal_posting',
        'diposting_oleh',
        'tanggal_diubah',
        'diubah_oleh',
    ];
}