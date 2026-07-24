<?php
declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;

class PenggunaEntitasORM extends Model
{
    protected $table = 'tb_pengguna_entitas';
    protected $primaryKey = 'id_pengguna_entitas';
    public $timestamps = false;
    protected $guarded = [];

    public function entitas()
    {
        return $this->belongsTo(EntitasORM::class, 'id_entitas', 'id_entitas');
    }

    public function role()
    {
        return $this->belongsTo(RoleORM::class, 'id_role', 'id_role');
    }
}