<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('mapping_laporan_id_entitas')) {
    function mapping_laporan_id_entitas(): int
    {
        $user = user_login();
        return (int) ($user['id_entitas'] ?? 0);
    }
}

if (!function_exists('mapping_laporan_id_pengguna')) {
    function mapping_laporan_id_pengguna(): int
    {
        $user = user_login();
        return (int) ($user['id_pengguna'] ?? 0);
    }
}

if (!function_exists('mapping_laporan_report_label')) {
    function mapping_laporan_report_label(?string $kode): string
    {
        $kode = strtoupper(trim((string) $kode));
        return match ($kode) {
            'SALDO_AKUN' => 'Saldo Akun',
            'LABA_RUGI' => 'Laba Rugi',
            'NERACA' => 'Neraca',
            'ARUS_KAS' => 'Arus Kas',
            default => ucwords(strtolower(str_replace('_', ' ', $kode !== '' ? $kode : '-'))),
        };
    }
}

if (!function_exists('mapping_laporan_source_types')) {
    function mapping_laporan_source_types(): array
    {
        return [
            'COA' => 'COA tertentu',
            'KATEGORI_COA' => 'Kategori COA',
            'KODE_COA_PREFIX' => 'Prefix kode COA',
            'KODE_JENIS_TRANSAKSI' => 'Kode jenis transaksi',
            'TABEL_SUMBER' => 'Tabel sumber',
            'MAPPING_AKUN' => 'Peran mapping akun',
        ];
    }
}

if (!function_exists('mapping_laporan_arah_saldo_options')) {
    function mapping_laporan_arah_saldo_options(): array
    {
        return [
            'NORMAL' => 'Normal',
            'DEBIT_MINUS_KREDIT' => 'Debit - Kredit',
            'KREDIT_MINUS_DEBIT' => 'Kredit - Debit',
            'KAS_MASUK' => 'Kas Masuk',
            'KAS_KELUAR' => 'Kas Keluar',
            'NETTO' => 'Netto',
        ];
    }
}

if (!function_exists('mapping_laporan_operator_options')) {
    function mapping_laporan_operator_options(): array
    {
        return [
            'TAMBAH' => 'Tambah',
            'KURANG' => 'Kurang',
        ];
    }
}

if (!function_exists('mapping_laporan_kategori_coa_options')) {
    function mapping_laporan_kategori_coa_options(): array
    {
        return [
            'aset' => 'Aset',
            'liabilitas' => 'Liabilitas',
            'ekuitas' => 'Ekuitas',
            'pendapatan' => 'Pendapatan',
            'hpp' => 'HPP / Produksi',
            'beban' => 'Beban',
        ];
    }
}

if (!function_exists('mapping_laporan_parse_code')) {
    function mapping_laporan_parse_code(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $parts = explode(' - ', $value, 2);
        return trim($parts[0]);
    }
}

if (!function_exists('mapping_laporan_source_display')) {
    function mapping_laporan_source_display(object $detail): string
    {
        if (($detail->tipe_sumber ?? '') === 'COA' && !empty($detail->kode_coa)) {
            return (string) $detail->kode_coa;
        }
        return (string) ($detail->nilai_sumber ?? '');
    }
}

if (!function_exists('mapping_laporan_validate_source')) {
    function mapping_laporan_validate_source(string $tipe, string $nilai, int $id_entitas): array
    {
        $tipe = strtoupper(trim($tipe));
        $nilai = trim($nilai);
        $validTipe = array_keys(mapping_laporan_source_types());

        if (!in_array($tipe, $validTipe, true)) {
            throw new RuntimeException('Tipe sumber mapping laporan tidak valid.');
        }

        if ($nilai === '') {
            throw new RuntimeException('Nilai sumber mapping laporan wajib diisi.');
        }

        $idCoa = null;

        if ($tipe === 'COA') {
            $kodeCoa = mapping_laporan_parse_code($nilai);
            $coa = Capsule::table('tb_coa')
                ->where('id_entitas', $id_entitas)
                ->where('status_aktif', 1)
                ->where('kode_coa', $kodeCoa)
                ->first();

            if (!$coa) {
                throw new RuntimeException('Kode COA ' . $kodeCoa . ' tidak ditemukan pada entitas aktif.');
            }

            $idCoa = (int) $coa->id_coa;
            $nilai = (string) $coa->kode_coa;
        } elseif ($tipe === 'KATEGORI_COA') {
            $nilai = strtolower($nilai);
            if (!array_key_exists($nilai, mapping_laporan_kategori_coa_options())) {
                throw new RuntimeException('Kategori COA ' . $nilai . ' tidak valid.');
            }
        } elseif ($tipe === 'KODE_COA_PREFIX') {
            if (!preg_match('/^[A-Za-z0-9\.\-]+$/', $nilai)) {
                throw new RuntimeException('Prefix kode COA hanya boleh berisi huruf, angka, titik, atau strip.');
            }
        }

        return [$tipe, $nilai, $idCoa];
    }
}

if (!function_exists('mapping_laporan_nav_url')) {
    function mapping_laporan_nav_url(string $tab, string $q = ''): string
    {
        $params = ['menu' => 'master_setup/mapping_laporan', 'tab' => $tab];
        if ($q !== '') {
            $params['q'] = $q;
        }
        return admin_url('index.php?' . http_build_query($params));
    }
}
