<?php
declare(strict_types=1);

function get_default_coa_id(int $idEntitas, string $kodeKonfigurasi): ?int
{
    $row = KonfigurasiAkunORM::query()
        ->where('id_entitas', $idEntitas)
        ->where('kode_konfigurasi', $kodeKonfigurasi)
        ->where('status_aktif', 1)
        ->first();

    return $row ? (int) $row->id_coa : null;
}

function default_coa_bahan_baku(int $idEntitas): ?int
{
    return get_default_coa_id($idEntitas, 'persediaan_bahan_baku');
}