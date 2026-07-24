<?php
declare(strict_types=1);

/**
 * Generate kode master sederhana:
 * PREFIX-0001
 * PREFIX-0002
 * dst
 *
 * Jika $idEntitas diisi, nomor akan berjalan per entitas.
 * Jika null, nomor akan berjalan global seperti sebelumnya.
 */
function generate_kode_master(
    string $table,
    string $field,
    string $prefix,
    int $digit = 4,
    ?int $idEntitas = null
): string {
    $prefix = strtoupper(trim($prefix));
    $likePrefix = $prefix . '-';

    $query = Illuminate\Database\Capsule\Manager::table($table)
        ->where($field, 'like', $likePrefix . '%');

    if ($idEntitas !== null) {
        $query->where('id_entitas', $idEntitas);
    }

    $rows = $query->pluck($field);
    $maxNumber = 0;
    $pattern = '/^' . preg_quote($likePrefix, '/') . '(\d+)$/';

    foreach ($rows as $code) {
        $code = (string) $code;
        if (preg_match($pattern, $code, $matches)) {
            $maxNumber = max($maxNumber, (int) $matches[1]);
        }
    }

    return $prefix . '-' . str_pad((string) ($maxNumber + 1), $digit, '0', STR_PAD_LEFT);
}
