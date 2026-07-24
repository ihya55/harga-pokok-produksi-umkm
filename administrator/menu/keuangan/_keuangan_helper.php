<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('keu_user')) {
    function keu_user(): array
    {
        return user_login();
    }
}

if (!function_exists('keu_id_entitas')) {
    function keu_id_entitas(): int
    {
        $user = keu_user();
        return (int) ($user['id_entitas'] ?? 0);
    }
}

if (!function_exists('keu_id_pengguna')) {
    function keu_id_pengguna(): int
    {
        $user = keu_user();
        return (int) ($user['id_pengguna'] ?? 0);
    }
}

if (!function_exists('keu_uang')) {
    function keu_uang($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, ',', '.');
    }
}

if (!function_exists('keu_angka')) {
    function keu_angka($value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }
}

if (!function_exists('keu_tanggal')) {
    function keu_tanggal($tanggal): string
    {
        if (empty($tanggal)) {
            return '-';
        }

        return date('d/m/Y', strtotime((string) $tanggal));
    }
}

if (!function_exists('keu_tanggal_mysql')) {
    function keu_tanggal_mysql(?string $tanggal, string $default): string
    {
        $tanggal = trim((string) $tanggal);

        if ($tanggal === '') {
            return $default;
        }

        return date('Y-m-d', strtotime($tanggal));
    }
}

if (!function_exists('keu_badge_status')) {
    function keu_badge_status($status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            'posted' => '<span class="badge text-bg-success">Posted</span>',
            'draft' => '<span class="badge text-bg-secondary">Draft</span>',
            'batal' => '<span class="badge text-bg-danger">Batal</span>',
            default => '<span class="badge text-bg-light">' . esc($status !== '' ? ucfirst($status) : '-') . '</span>',
        };
    }
}

if (!function_exists('keu_table_exists')) {
    function keu_table_exists(string $table): bool
    {
        try {
            return Capsule::schema()->hasTable($table);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('keu_col_exists')) {
    function keu_col_exists(string $table, string $column): bool
    {
        try {
            return Capsule::schema()->hasColumn($table, $column);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('keu_print_head')) {
    function keu_print_head(string $title): void
    {
        ?>
        <!doctype html>
        <html lang="id">
        <head>
            <meta charset="utf-8">
            <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                *{box-sizing:border-box}
                body{font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#111827;margin:0;padding:24px;background:#fff}
                .sheet{max-width:1050px;margin:0 auto}
                .print-actions{max-width:1050px;margin:0 auto 16px;display:flex;justify-content:flex-end;gap:8px}
                .btn{border:1px solid #d1d5db;background:#fff;border-radius:6px;padding:8px 12px;cursor:pointer;text-decoration:none;color:#111827;font-size:12px}
                .btn-primary{background:#2563eb;border-color:#2563eb;color:#fff}
                .kop{display:block;text-align:center;border-bottom:2px solid #111827;padding-bottom:14px;margin-bottom:18px}.kop-brand{display:block;text-align:center}.kop-logo{width:54px;height:54px;object-fit:contain;border:1px solid #e5e7eb;border-radius:10px;padding:4px;background:#fff}.kop-logo-center{width:70px;height:70px;object-fit:contain;display:block;margin:0 auto 8px;border:1px solid #e5e7eb;border-radius:12px;padding:4px;background:#fff}.kop-center h1{margin:0 0 6px;font-size:21px;text-align:center}.kop-address{max-width:760px;margin:0 auto}.doc-title-center{margin-top:10px;text-align:center;border-top:1px solid #d1d5db;padding-top:10px}
                .kop h1{margin:0 0 6px;font-size:20px}
                .doc-title{text-align:center}
                .doc-title h2{margin:0 0 8px;font-size:22px;letter-spacing:.3px}
                .meta{color:#6b7280;line-height:1.5}
                table{width:100%;border-collapse:collapse}
                th,td{border:1px solid #d1d5db;padding:7px;vertical-align:top}
                th{background:#f3f4f6;text-align:left}
                .text-end{text-align:right}
                .text-center{text-align:center}
                .fw-bold{font-weight:bold}
                .summary{margin-top:12px;border:1px solid #d1d5db;border-radius:8px;padding:12px;background:#f9fafb}
                .section-row td{background:#eef2ff;font-weight:bold}
                .subtotal-row td{background:#f8fafc;font-weight:bold}
                .total-row td{background:#111827;color:#fff;font-weight:bold}
                @media print{
                    body{padding:0}
                    .print-actions{display:none}
                    .sheet{max-width:none;margin:0}
                    @page{size:A4;margin:12mm}
                }
            </style>
        </head>
        <body>
        <div class="print-actions">
            <button class="btn btn-primary" onclick="window.print()">Cetak</button>
            <button class="btn" onclick="window.close()">Tutup</button>
        </div>
        <?php
    }
}

if (!function_exists('keu_entitas')) {
    function keu_entitas(): ?object
    {
        return Capsule::table('tb_entitas')
            ->where('id_entitas', keu_id_entitas())
            ->first();
    }
}

if (!function_exists('keu_sumber_url')) {
    function keu_sumber_url(?string $tabel_sumber, $id_sumber): ?string
    {
        $tabel_sumber = trim((string) $tabel_sumber);
        $id_sumber = (int) $id_sumber;

        if ($tabel_sumber === '' || $id_sumber <= 0) {
            return null;
        }

        return match ($tabel_sumber) {
            'tb_faktur_penjualan' => admin_page_url('penjualan/faktur/detail') . '&id=' . $id_sumber,
            'tb_pembayaran_penjualan' => admin_page_url('penjualan/pembayaran/detail') . '&id=' . $id_sumber,
            'tb_penyerahan_penjualan' => admin_page_url('penjualan/penyerahan/detail') . '&id=' . $id_sumber,

            'tb_faktur_pembelian' => admin_page_url('pembelian/faktur/detail') . '&id=' . $id_sumber,
            'tb_pembayaran_pembelian' => admin_page_url('pembelian/pembayaran/detail') . '&id=' . $id_sumber,

            'tb_biaya_produksi' => admin_page_url('produksi/biaya/detail') . '&id=' . $id_sumber,
            'tb_pengambilan_bahan' => admin_page_url('produksi/pengambilan-bahan/detail') . '&id=' . $id_sumber,
            'tb_hasil_produksi' => admin_page_url('produksi/hasil/detail') . '&id=' . $id_sumber,

            'tb_kas_masuk' => admin_page_url('keuangan/kas-masuk/detail') . '&id=' . $id_sumber,
            'tb_kas_keluar' => admin_page_url('keuangan/kas-keluar/detail') . '&id=' . $id_sumber,

            'tb_jurnal' => admin_page_url('keuangan/jurnal/detail') . '&id=' . $id_sumber,
            'tb_pembatalan_transaksi' => admin_page_url('keuangan/pembatalan-transaksi/detail') . '&id=' . $id_sumber,

            default => null,
        };
    }
}

if (!function_exists('keu_sumber_link')) {
    function keu_sumber_link(?string $tabel_sumber, $id_sumber, ?string $no_sumber = null): string
    {
        $label = trim((string) ($no_sumber ?? ''));

        if ($label === '') {
            $label = trim((string) $tabel_sumber) !== '' ? (string) $tabel_sumber . '#' . (int) $id_sumber : '-';
        }

        $url = keu_sumber_url($tabel_sumber, $id_sumber);

        if (!$url) {
            return esc($label);
        }

        return '<a href="' . esc($url) . '" class="text-decoration-none fw-semibold">' . esc($label) . '</a>';
    }
}

if (!function_exists('keu_is_kas_bank_query')) {
    function keu_is_kas_bank_query($query, string $alias = 'c', ?int $id_entitas = null)
    {
        $id_entitas = $id_entitas ?: keu_id_entitas();

        /*
        |--------------------------------------------------------------------------
        | Akun kas/bank untuk arus kas
        |--------------------------------------------------------------------------
        | Utama tetap dari Mapping Akun > ARUS_KAS. Namun pada transaksi online,
        | akun bank/QRIS kadang dipilih langsung dari COA dan belum tercatat pada
        | mapping ARUS_KAS. Agar uang masuk online tidak hilang dari arus kas,
        | kita tambahkan fallback berdasarkan kategori/nama/kode COA kas-bank.
        */
        return $query->where(function ($kas) use ($alias, $id_entitas) {
            $kas->whereExists(function ($sub) use ($alias, $id_entitas) {
                $sub->selectRaw('1')
                    ->from('tb_mapping_akun as ma')
                    ->whereColumn('ma.id_coa', $alias . '.id_coa')
                    ->where('ma.id_entitas', $id_entitas)
                    ->where('ma.kode_jenis_transaksi', 'ARUS_KAS')
                    ->where('ma.jenis_objek', 'global')
                    ->where('ma.id_objek', 0)
                    ->where('ma.peran_akun', 'akun_kas_bank')
                    ->where('ma.status_aktif', 1);
            })
            ->orWhere(function ($q) use ($alias) {
                $q->where($alias . '.kategori_coa', 'aset')
                  ->where(function ($qq) use ($alias) {
                      $qq->where($alias . '.kode_coa', 'like', '111%')
                         ->orWhere($alias . '.kode_coa', 'like', '112%')
                         ->orWhere($alias . '.nama_coa', 'like', '%Kas%')
                         ->orWhere($alias . '.nama_coa', 'like', '%Bank%')
                         ->orWhere($alias . '.nama_coa', 'like', '%QRIS%');
                  });
            });
        });
    }
}

if (!function_exists('keu_exclude_saldo_awal_query')) {
    function keu_exclude_saldo_awal_query($query, string $alias = 'j')
    {
        return $query->where(function ($q) use ($alias) {
            $q->whereNull($alias . '.kode_jenis_transaksi')
                ->orWhere($alias . '.kode_jenis_transaksi', '<>', 'SALDO_AWAL_COA');
        });
    }
}

if (!function_exists('keu_mapping_akun')) {
    function keu_mapping_akun(
        string $kode_jenis_transaksi,
        string $peran_akun,
        string $jenis_objek = 'global',
        int $id_objek = 0,
        ?int $id_entitas = null
    ): ?object {
        $id_entitas = $id_entitas ?: keu_id_entitas();

        $mapping = Capsule::table('tb_mapping_akun as ma')
            ->join('tb_coa as c', 'c.id_coa', '=', 'ma.id_coa')
            ->where('ma.id_entitas', $id_entitas)
            ->where('ma.kode_jenis_transaksi', $kode_jenis_transaksi)
            ->where('ma.jenis_objek', $jenis_objek)
            ->where('ma.id_objek', $id_objek)
            ->where('ma.peran_akun', $peran_akun)
            ->where('ma.status_aktif', 1)
            ->where('c.status_aktif', 1)
            ->select([
                'ma.*',
                'c.kode_coa',
                'c.nama_coa',
                'c.kategori_coa',
                'c.posisi_saldo_normal',
            ])
            ->first();

        if ($mapping) {
            return $mapping;
        }

        if ($jenis_objek !== 'global' || $id_objek !== 0) {
            return Capsule::table('tb_mapping_akun as ma')
                ->join('tb_coa as c', 'c.id_coa', '=', 'ma.id_coa')
                ->where('ma.id_entitas', $id_entitas)
                ->where('ma.kode_jenis_transaksi', $kode_jenis_transaksi)
                ->where('ma.jenis_objek', 'global')
                ->where('ma.id_objek', 0)
                ->where('ma.peran_akun', $peran_akun)
                ->where('ma.status_aktif', 1)
                ->where('c.status_aktif', 1)
                ->select([
                    'ma.*',
                    'c.kode_coa',
                    'c.nama_coa',
                    'c.kategori_coa',
                    'c.posisi_saldo_normal',
                ])
                ->first();
        }

        return null;
    }
}

if (!function_exists('keu_pagination_url')) {
    function keu_pagination_url(string $menu, int $page): string
    {
        $params = $_GET;
        $params['menu'] = $menu;
        $params['hal'] = $page;

        return admin_url('index.php?' . http_build_query($params));
    }
}

if (!function_exists('keu_detail_value')) {
    function keu_detail_value(object $detail, string $tanggal_awal, string $tanggal_akhir, bool $is_neraca = false): float
    {
        $id_entitas = (int) ($detail->id_entitas ?? keu_id_entitas());

        $q = Capsule::table('tb_jurnal as j')
            ->join('tb_jurnal_detail as jd', 'jd.id_jurnal', '=', 'j.id_jurnal')
            ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
            ->where('j.id_entitas', $id_entitas)
            ->where('j.status_jurnal', 'posted');

        /*
        |--------------------------------------------------------------------------
        | SALDO_AWAL_COA hanya untuk posisi awal akun.
        | Masuk:
        | - Buku Besar
        | - Saldo Akun
        | - Neraca
        |
        | Tidak masuk:
        | - Arus Kas
        | - Kas Masuk
        | - Kas Keluar
        | - Grafik kas dashboard
        |--------------------------------------------------------------------------
        */
        $kode_laporan = (string) ($detail->kode_laporan ?? '');

        if ($kode_laporan === 'ARUS_KAS') {
            keu_exclude_saldo_awal_query($q, 'j');
        }

        if ($is_neraca) {
            $q->where('j.tanggal_jurnal', '<=', $tanggal_akhir);
        } else {
            $q->whereBetween('j.tanggal_jurnal', [$tanggal_awal, $tanggal_akhir]);
        }

        $tipe = (string) ($detail->tipe_sumber ?? '');
        $nilai = (string) ($detail->nilai_sumber ?? '');

        if ($tipe === 'COA') {
            if (!empty($detail->id_coa)) {
                $q->where('jd.id_coa', (int) $detail->id_coa);
            } else {
                // Fallback agar detail mapping lama yang hanya menyimpan kode COA tetap terbaca.
                $q->where('c.kode_coa', $nilai);
            }
        } elseif ($tipe === 'KATEGORI_COA') {
            $q->where('c.kategori_coa', $nilai);
        } elseif ($tipe === 'KODE_COA_PREFIX') {
            $prefixes = array_values(array_filter(array_map('trim', explode(',', $nilai))));
            $q->where(function ($qq) use ($prefixes, $nilai) {
                if (empty($prefixes)) {
                    $qq->where('c.kode_coa', 'like', $nilai . '%');
                    return;
                }
                foreach ($prefixes as $idx => $prefix) {
                    if ($idx === 0) {
                        $qq->where('c.kode_coa', 'like', $prefix . '%');
                    } else {
                        $qq->orWhere('c.kode_coa', 'like', $prefix . '%');
                    }
                }
            });
        } elseif ($tipe === 'KODE_JENIS_TRANSAKSI') {
            $q->where('j.kode_jenis_transaksi', $nilai);
        } elseif ($tipe === 'TABEL_SUMBER') {
            $q->where('j.tabel_sumber', $nilai);
        } elseif ($tipe === 'MAPPING_AKUN') {
            $q->whereExists(function ($sub) use ($nilai, $id_entitas) {
                $sub->selectRaw('1')
                    ->from('tb_mapping_akun as ma')
                    ->whereColumn('ma.id_coa', 'c.id_coa')
                    ->where('ma.id_entitas', $id_entitas)
                    ->where('ma.peran_akun', $nilai)
                    ->where('ma.status_aktif', 1);
            });
        }

        $arah = (string) ($detail->arah_saldo ?? 'NORMAL');

        if ($arah === 'DEBIT_MINUS_KREDIT') {
            return (float) $q
                ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit),0) as total')
                ->value('total');
        }

        if ($arah === 'KREDIT_MINUS_DEBIT') {
            return (float) $q
                ->selectRaw('COALESCE(SUM(jd.kredit - jd.debit),0) as total')
                ->value('total');
        }

        if ($arah === 'KAS_MASUK') {
            keu_is_kas_bank_query($q, 'c', $id_entitas);

            $value = (float) $q
                ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit),0) as total')
                ->value('total');

            return max(0, $value);
        }

        if ($arah === 'KAS_KELUAR') {
            keu_is_kas_bank_query($q, 'c', $id_entitas);

            $value = (float) $q
                ->selectRaw('COALESCE(SUM(jd.kredit - jd.debit),0) as total')
                ->value('total');

            return max(0, $value);
        }

        return (float) $q
            ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit),0) as total')
            ->value('total');
    }
}


if (!function_exists('keu_laba_tahun_berjalan')) {
    function keu_laba_tahun_berjalan(string $tanggal_akhir, ?int $id_entitas = null): float
    {
        $id_entitas = $id_entitas ?: keu_id_entitas();
        $tanggal_awal = date('Y-01-01', strtotime($tanggal_akhir));

        $base = Capsule::table('tb_jurnal as j')
            ->join('tb_jurnal_detail as jd', 'jd.id_jurnal', '=', 'j.id_jurnal')
            ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
            ->where('j.id_entitas', $id_entitas)
            ->where('j.status_jurnal', 'posted')
            ->whereBetween('j.tanggal_jurnal', [$tanggal_awal, $tanggal_akhir]);

        keu_exclude_saldo_awal_query($base, 'j');

        $pendapatan = (float) (clone $base)
            ->where('c.kategori_coa', 'pendapatan')
            ->selectRaw('COALESCE(SUM(jd.kredit - jd.debit),0) as total')
            ->value('total');

        $hpp = (float) (clone $base)
            ->where('c.kategori_coa', 'hpp')
            ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit),0) as total')
            ->value('total');

        $beban = (float) (clone $base)
            ->where('c.kategori_coa', 'beban')
            ->selectRaw('COALESCE(SUM(jd.debit - jd.kredit),0) as total')
            ->value('total');

        return round($pendapatan - $hpp - $beban, 2);
    }
}

if (!function_exists('keu_nilai_persediaan_stok')) {
    function keu_nilai_persediaan_stok(string $tanggal_akhir, ?int $id_entitas = null): float
    {
        $id_entitas = $id_entitas ?: keu_id_entitas();

        if (!keu_table_exists('tb_saldo_stok')) {
            return 0.0;
        }

        return (float) Capsule::table('tb_saldo_stok')
            ->where('id_entitas', $id_entitas)
            ->selectRaw('COALESCE(SUM(nilai_saldo),0) as total')
            ->value('total');
    }
}

if (!function_exists('keu_eval_rumus_laporan')) {
    function keu_eval_rumus_laporan(string $rumus, array $values): float
    {
        $rumus = trim($rumus);

        if ($rumus === '') {
            return 0.0;
        }

        /*
        |--------------------------------------------------------------------------
        | Token harus diganti dari yang paling panjang.
        | Ini mencegah TOTAL_ASET_LANCAR rusak karena ASET_LANCAR diganti duluan.
        |--------------------------------------------------------------------------
        */
        uksort($values, function ($a, $b) {
            return strlen((string) $b) <=> strlen((string) $a);
        });

        $expr = $rumus;

        foreach ($values as $key => $value) {
            $expr = preg_replace(
                '/(?<![A-Z0-9_])' . preg_quote((string) $key, '/') . '(?![A-Z0-9_])/',
                '(' . (string) ((float) $value) . ')',
                $expr
            );
        }

        if (!preg_match('/^[0-9\.\+\-\*\/\(\)\s]+$/', $expr)) {
            return 0.0;
        }

        try {
            return (float) eval('return ' . $expr . ';');
        } catch (Throwable $e) {
            return 0.0;
        }
    }
}

if (!function_exists('keu_laporan_mapping_rows')) {
    function keu_laporan_mapping_rows(string $kode_laporan, string $tanggal_awal, string $tanggal_akhir): array
    {
        $id_entitas = keu_id_entitas();
        $is_neraca = $kode_laporan === 'NERACA' || $kode_laporan === 'SALDO_AKUN';

        $headers = Capsule::table('tb_mapping_laporan')
            ->where('id_entitas', $id_entitas)
            ->where('kode_laporan', $kode_laporan)
            ->where('status_aktif', 1)
            ->orderBy('urutan', 'asc')
            ->orderBy('sub_urutan', 'asc')
            ->get();

        $result = [];
        $groupValues = [];
        $valueTokens = [];

        foreach ($headers as $row) {
            $nilai = 0.0;
            $tipe_baris = (string) $row->tipe_baris;
            $kategori_kode = (string) $row->kategori_kode;
            $sub_kode = (string) $row->sub_kode;

            if ($tipe_baris === 'DETAIL') {
                /*
                |--------------------------------------------------------------------------
                | Baris khusus laporan
                |--------------------------------------------------------------------------
                | - Laba Tahun Berjalan pada neraca dihitung otomatis dari jurnal
                |   pendapatan - HPP - beban tahun berjalan, bukan menunggu jurnal tutup buku.
                | - Persediaan pada neraca disinkronkan dengan saldo stok agar nilai gudang
                |   dan laporan tidak berbeda ketika stok berasal dari proses produksi/konversi.
                */
                if ($kode_laporan === 'NERACA' && $sub_kode === 'laba-tahun-berjalan') {
                    $nilai = keu_laba_tahun_berjalan($tanggal_akhir, $id_entitas);
                    $groupValues[$kategori_kode] = ($groupValues[$kategori_kode] ?? 0.0) + $nilai;
                    $valueTokens[$kategori_kode] = $groupValues[$kategori_kode];
                    $valueTokens[strtoupper(str_replace('-', '_', $sub_kode))] = $nilai;
                    $valueTokens[$sub_kode] = $nilai;

                    $result[] = [
                        'id_mapping_laporan' => (int) $row->id_mapping_laporan,
                        'kode_laporan' => (string) $row->kode_laporan,
                        'kategori_kode' => $kategori_kode,
                        'kategori_label' => (string) $row->kategori_label,
                        'sub_kode' => $sub_kode,
                        'sub_label' => (string) $row->sub_label,
                        'tipe_baris' => $tipe_baris,
                        'kelompok_arus_kas' => (string) $row->kelompok_arus_kas,
                        'nilai' => $nilai,
                        'urutan' => (int) $row->urutan,
                        'sub_urutan' => (int) $row->sub_urutan,
                    ];
                    continue;
                }

                /*
                | Catatan penting:
                | Persediaan neraca TIDAK dipaksa lagi dari tb_saldo_stok.
                | Laporan keuangan harus bersumber dari jurnal dan mapping laporan
                | supaya Aset = Liabilitas + Ekuitas tetap seimbang.
                | Nilai saldo stok tetap bisa dicek di laporan gudang sebagai pembanding.
                */

                /*
                |--------------------------------------------------------------------------
                | Join tb_mapping_laporan agar keu_detail_value() tahu kode_laporan.
                | Ini dipakai untuk mengecualikan SALDO_AWAL_COA dari ARUS_KAS.
                |--------------------------------------------------------------------------
                */
                $details = Capsule::table('tb_mapping_laporan_detail as d')
                    ->join('tb_mapping_laporan as ml', 'ml.id_mapping_laporan', '=', 'd.id_mapping_laporan')
                    ->where('d.id_mapping_laporan', (int) $row->id_mapping_laporan)
                    ->where('d.id_entitas', $id_entitas)
                    ->where('d.status_aktif', 1)
                    ->select([
                        'd.*',
                        'ml.kode_laporan',
                    ])
                    ->get();

                foreach ($details as $detail) {
                    $v = keu_detail_value($detail, $tanggal_awal, $tanggal_akhir, $is_neraca);

                    /*
                    | Jangan memakai abs() di sini.
                    | Nilai dari keu_detail_value() sudah mengikuti arah_saldo mapping:
                    | - DEBIT_MINUS_KREDIT untuk akun normal debit
                    | - KREDIT_MINUS_DEBIT untuk akun normal kredit
                    |
                    | Jika saldo akun normal debit menjadi kredit, nilainya memang harus negatif
                    | agar neraca tetap seimbang. Contoh: kas minus/overdraft tidak boleh
                    | dibalik menjadi positif.
                    */
                    if ((string) $detail->operator_nilai === 'KURANG') {
                        $nilai -= $v;
                    } else {
                        $nilai += $v;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Operator baris mapping tetap dihormati.
                | Untuk baris KURANG, nilai ditampilkan negatif.
                |--------------------------------------------------------------------------
                */
                if ((string) $row->operator_nilai === 'KURANG') {
                    $nilai = -abs($nilai);
                }

                $groupValues[$kategori_kode] = ($groupValues[$kategori_kode] ?? 0.0) + $nilai;

                $valueTokens[$kategori_kode] = $groupValues[$kategori_kode];
                $valueTokens[strtoupper(str_replace('-', '_', $sub_kode))] = $nilai;
                $valueTokens[$sub_kode] = $nilai;
            } else {
                $rumus = trim((string) ($row->rumus_total ?? ''));

                if (str_starts_with($rumus, 'SUM:')) {
                    $target = substr($rumus, 4);
                    $nilai = $groupValues[$target] ?? 0.0;
                } elseif ($rumus !== '') {
                    $nilai = keu_eval_rumus_laporan($rumus, $valueTokens + $groupValues);
                }

                $groupValues[$kategori_kode] = $nilai;

                $valueTokens[$kategori_kode] = $nilai;
                $valueTokens[strtoupper(str_replace('-', '_', $sub_kode))] = $nilai;
                $valueTokens[$sub_kode] = $nilai;
            }

            $result[] = [
                'id_mapping_laporan' => (int) $row->id_mapping_laporan,
                'kode_laporan' => (string) $row->kode_laporan,
                'kategori_kode' => $kategori_kode,
                'kategori_label' => (string) $row->kategori_label,
                'sub_kode' => $sub_kode,
                'sub_label' => (string) $row->sub_label,
                'tipe_baris' => $tipe_baris,
                'kelompok_arus_kas' => (string) $row->kelompok_arus_kas,
                'nilai' => $nilai,
                'urutan' => (int) $row->urutan,
                'sub_urutan' => (int) $row->sub_urutan,
            ];
        }

        return $result;
    }
}

if (!function_exists('keu_laporan_summary')) {
    function keu_laporan_summary(array $rows, string $subKode): float
    {
        foreach ($rows as $row) {
            if (($row['sub_kode'] ?? '') === $subKode) {
                return (float) ($row['nilai'] ?? 0);
            }
        }

        return 0.0;
    }
}

if (!function_exists('keu_periode_terbuka')) {
    function keu_periode_terbuka(int $id_entitas, string $tanggal): object
    {
        $periode = Capsule::table('tb_periode_akuntansi')
            ->where('id_entitas', $id_entitas)
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->whereIn('status_periode', ['terbuka', 'aktif'])
            ->first();

        if (!$periode) {
            throw new RuntimeException('Periode akuntansi untuk tanggal ' . $tanggal . ' belum terbuka.');
        }

        return $periode;
    }
}

if (!function_exists('keu_generate_nomor')) {
    function keu_generate_nomor(string $table, string $column, int $id_entitas, string $prefix): string
    {
        $rows = Capsule::table($table)
            ->where('id_entitas', $id_entitas)
            ->where($column, 'like', $prefix . '%')
            ->pluck($column);

        $max = 0;
        $pattern = '/^' . preg_quote($prefix, '/') . '(\d+)$/';

        foreach ($rows as $code) {
            if (preg_match($pattern, (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('keu_generate_no_jurnal')) {
    function keu_generate_no_jurnal(int $id_entitas): string
    {
        return keu_generate_nomor('tb_jurnal', 'no_jurnal', $id_entitas, 'JRN-');
    }
}
if (!function_exists('keu_print_kop')) {
    function keu_print_kop(?object $entitas, string $judul_laporan, string $periode = '', string $subinfo = ''): void
    {
        $user = function_exists('user_login') ? (user_login() ?: []) : [];
        $dicetak_oleh = (string) ($user['nama_lengkap'] ?? $user['username'] ?? '-');
        $tanggal_cetak = date('d/m/Y H:i');
        $logo = trim((string) ($entitas->logo_umkm ?? ''));
        $nama = (string) ($entitas->nama_entitas ?? 'Entitas');
        $alamat = (string) ($entitas->alamat ?? '-');
        $telepon = (string) ($entitas->no_hp ?? $entitas->telepon ?? '-');
        ?>
        <div class="kop kop-center">
            <?php if ($logo !== ''): ?>
                <img class="kop-logo-center" src="<?= htmlspecialchars(base_url($logo), ENT_QUOTES, 'UTF-8') ?>" alt="Logo UMKM">
            <?php endif; ?>
            <h1><?= htmlspecialchars($nama, ENT_QUOTES, 'UTF-8') ?></h1>
            <div class="meta kop-address">
                <?= nl2br(htmlspecialchars($alamat, ENT_QUOTES, 'UTF-8')) ?><br>
                Telp/WA: <?= htmlspecialchars($telepon !== '' ? $telepon : '-', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="doc-title doc-title-center">
                <h2><?= htmlspecialchars(strtoupper($judul_laporan), ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if ($periode !== ''): ?><div class="meta"><?= htmlspecialchars($periode, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <?php if ($subinfo !== ''): ?><div class="meta"><?= htmlspecialchars($subinfo, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                <div class="meta">Dicetak oleh: <?= htmlspecialchars($dicetak_oleh, ENT_QUOTES, 'UTF-8') ?> | Tanggal cetak: <?= htmlspecialchars($tanggal_cetak, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('keu_periode_label')) {
    function keu_periode_label(?string $awal, ?string $akhir, bool $gunakan_awal = true): string
    {
        if ($gunakan_awal && $awal) {
            return 'Periode: ' . keu_tanggal($awal) . ' s/d ' . keu_tanggal($akhir);
        }
        return 'Per: ' . keu_tanggal($akhir);
    }
}

if (!function_exists('keu_kas_report_rows')) {
    function keu_kas_report_rows(bool $is_masuk, int $id_entitas, string $tanggal_awal, string $tanggal_akhir, string $status = 'semua', string $q = ''): array
    {
        $manual_table = $is_masuk ? 'tb_kas_masuk' : 'tb_kas_keluar';
        $manual_pk = $is_masuk ? 'id_kas_masuk' : 'id_kas_keluar';
        $manual_no_col = $is_masuk ? 'no_kas_masuk' : 'no_kas_keluar';
        $manual_tgl_col = $is_masuk ? 'tanggal_kas_masuk' : 'tanggal_kas_keluar';
        $manual_jenis_col = $is_masuk ? 'sumber_kas_masuk' : 'jenis_kas_keluar';
        $rows_data = [];

        if (keu_table_exists($manual_table)) {
            $manualQuery = Capsule::table($manual_table . ' as k')
                ->join('tb_coa as kas', 'kas.id_coa', '=', 'k.id_coa_kas_bank')
                ->where('k.id_entitas', $id_entitas)
                ->whereBetween('k.' . $manual_tgl_col, [$tanggal_awal, $tanggal_akhir]);

            if (!$is_masuk) {
                $manualQuery->leftJoin('tb_coa as lawan', 'lawan.id_coa', '=', 'k.id_coa_beban');
            }
            if (in_array($status, ['draft', 'posted'], true)) {
                $manualQuery->where('k.status_posting', $status);
            }
            if ($q !== '') {
                $manualQuery->where(function ($sub) use ($q, $manual_no_col, $manual_jenis_col) {
                    $sub->where('k.' . $manual_no_col, 'like', '%' . $q . '%')
                        ->orWhere('k.' . $manual_jenis_col, 'like', '%' . $q . '%')
                        ->orWhere('k.keterangan', 'like', '%' . $q . '%')
                        ->orWhere('kas.kode_coa', 'like', '%' . $q . '%')
                        ->orWhere('kas.nama_coa', 'like', '%' . $q . '%');
                });
            }
            $select = [
                'k.*', 'kas.kode_coa as kode_coa_kas', 'kas.nama_coa as nama_coa_kas',
            ];
            if (!$is_masuk) {
                $select[] = 'lawan.kode_coa as kode_coa_lawan';
                $select[] = 'lawan.nama_coa as nama_coa_lawan';
            }
            $manualRows = $manualQuery->select($select)->get();
            foreach ($manualRows as $r) {
                $rows_data[] = [
                    'tipe' => 'manual',
                    'tanggal' => (string) $r->{$manual_tgl_col},
                    'nomor' => (string) $r->{$manual_no_col},
                    'jenis' => (string) $r->{$manual_jenis_col},
                    'akun_kas' => trim((string) $r->kode_coa_kas . ' - ' . (string) $r->nama_coa_kas),
                    'akun_lawan' => !$is_masuk ? trim((string) ($r->kode_coa_lawan ?? '') . ' - ' . (string) ($r->nama_coa_lawan ?? '')) : '',
                    'sumber' => 'Input Manual',
                    'keterangan' => (string) ($r->keterangan ?? '-'),
                    'nominal' => (float) ($r->jumlah ?? 0),
                    'status' => (string) ($r->status_posting ?? 'draft'),
                    'waktu_sort' => strtotime((string) $r->{$manual_tgl_col}) ?: 0,
                ];
            }
        }

        if ($status !== 'draft') {
            $jurnalQuery = Capsule::table('tb_jurnal as j')
                ->join('tb_jurnal_detail as jd', 'jd.id_jurnal', '=', 'j.id_jurnal')
                ->join('tb_coa as c', 'c.id_coa', '=', 'jd.id_coa')
                ->where('j.id_entitas', $id_entitas)
                ->where('j.status_jurnal', 'posted')
                ->whereBetween('j.tanggal_jurnal', [$tanggal_awal, $tanggal_akhir])
                ->where(function ($sub) use ($manual_table) {
                    $sub->whereNull('j.tabel_sumber')->orWhere('j.tabel_sumber', '<>', $manual_table);
                });
            keu_exclude_saldo_awal_query($jurnalQuery, 'j');
            keu_is_kas_bank_query($jurnalQuery, 'c', $id_entitas);
            if ($is_masuk) {
                $jurnalQuery->whereRaw('(jd.debit - jd.kredit) > 0');
                $nominal_sql = '(jd.debit - jd.kredit)';
            } else {
                $jurnalQuery->whereRaw('(jd.kredit - jd.debit) > 0');
                $nominal_sql = '(jd.kredit - jd.debit)';
            }
            if ($q !== '') {
                $jurnalQuery->where(function ($sub) use ($q) {
                    $sub->where('j.no_jurnal', 'like', '%' . $q . '%')
                        ->orWhere('j.no_sumber', 'like', '%' . $q . '%')
                        ->orWhere('j.kode_jenis_transaksi', 'like', '%' . $q . '%')
                        ->orWhere('j.keterangan', 'like', '%' . $q . '%')
                        ->orWhere('jd.keterangan_baris', 'like', '%' . $q . '%')
                        ->orWhere('c.kode_coa', 'like', '%' . $q . '%')
                        ->orWhere('c.nama_coa', 'like', '%' . $q . '%');
                });
            }
            $jurnalRows = $jurnalQuery->select([
                    'j.no_jurnal','j.tanggal_jurnal','j.kode_jenis_transaksi','j.no_sumber','j.keterangan','jd.keterangan_baris','c.kode_coa','c.nama_coa'
                ])->selectRaw($nominal_sql . ' as nominal')->get();
            foreach ($jurnalRows as $r) {
                $rows_data[] = [
                    'tipe' => 'jurnal',
                    'tanggal' => (string) $r->tanggal_jurnal,
                    'nomor' => (string) $r->no_jurnal,
                    'jenis' => (string) ($r->kode_jenis_transaksi ?? '-'),
                    'akun_kas' => trim((string) $r->kode_coa . ' - ' . (string) $r->nama_coa),
                    'akun_lawan' => '',
                    'sumber' => (string) ($r->no_sumber ?? '-'),
                    'keterangan' => (string) ($r->keterangan_baris ?? $r->keterangan ?? '-'),
                    'nominal' => (float) ($r->nominal ?? 0),
                    'status' => 'posted',
                    'waktu_sort' => strtotime((string) $r->tanggal_jurnal) ?: 0,
                ];
            }
        }
        usort($rows_data, function ($a, $b) {
            if ($a['waktu_sort'] === $b['waktu_sort']) return strcmp((string) $a['nomor'], (string) $b['nomor']);
            return $a['waktu_sort'] <=> $b['waktu_sort'];
        });
        return $rows_data;
    }
}
