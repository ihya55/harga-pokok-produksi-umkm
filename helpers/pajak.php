<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('pajak_default_persen')) {
    function pajak_default_persen(int $id_entitas, string $kode_pajak = 'PPN', float $fallback = 11.0): float
    {
        try {
            if (!Capsule::schema()->hasTable('tb_pajak')) {
                return $fallback;
            }

            $row = Capsule::table('tb_pajak')
                ->where('id_entitas', $id_entitas)
                ->where('kode_pajak', strtoupper(trim($kode_pajak)))
                ->where('status_aktif', 1)
                ->where('is_default', 1)
                ->orderBy('id_pajak', 'desc')
                ->first();

            if (!$row) {
                $row = Capsule::table('tb_pajak')
                    ->where('id_entitas', $id_entitas)
                    ->where('kode_pajak', strtoupper(trim($kode_pajak)))
                    ->where('status_aktif', 1)
                    ->orderBy('id_pajak', 'desc')
                    ->first();
            }

            if (!$row) {
                return $fallback;
            }

            $persen = (float) ($row->persentase ?? $fallback);

            return max(0.0, min(100.0, $persen));
        } catch (Throwable $e) {
            return $fallback;
        }
    }
}
