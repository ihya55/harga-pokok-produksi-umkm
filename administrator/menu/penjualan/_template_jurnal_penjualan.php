<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/_fungsi_penjualan.php';

if (!function_exists('penjualan_template_get_nominal')) {
    function penjualan_template_get_nominal(string $rumus, array $context): float
    {
        $rumus = trim($rumus);

        $allowed = [
            'subtotal',
            'diskon',
            'subtotal_setelah_diskon',
            'ppn',
            'total',
            'total_hpp',
            'jumlah_bayar',
        ];

        if (!in_array($rumus, $allowed, true)) {
            throw new RuntimeException('Rumus nominal template jurnal tidak didukung: ' . $rumus);
        }

        if ($rumus === 'subtotal_setelah_diskon') {
            $subtotal = (float) ($context['subtotal'] ?? 0);
            $diskon = (float) ($context['diskon'] ?? 0);

            return round($subtotal - $diskon, 2);
        }

        return round((float) ($context[$rumus] ?? 0), 2);
    }
}

if (!function_exists('penjualan_template_load')) {
    function penjualan_template_load(int $id_entitas, string $kode_jenis_transaksi)
    {
        $template = Capsule::table('tb_template_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('kode_jenis_transaksi', $kode_jenis_transaksi)
            ->where('status_aktif', 1)
            ->first();

        if (!$template) {
            throw new RuntimeException('Template jurnal belum tersedia untuk jenis transaksi: ' . $kode_jenis_transaksi);
        }

        return $template;
    }
}

if (!function_exists('penjualan_template_load_detail')) {
    function penjualan_template_load_detail(int $id_template_jurnal)
    {
        $detail = Capsule::table('tb_template_jurnal_detail')
            ->where('id_template_jurnal', $id_template_jurnal)
            ->orderBy('urutan', 'asc')
            ->get();

        if ($detail->count() <= 0) {
            throw new RuntimeException('Detail template jurnal masih kosong.');
        }

        return $detail;
    }
}

if (!function_exists('penjualan_template_bangun_detail_jurnal')) {
    function penjualan_template_bangun_detail_jurnal(array $params): array
    {
        $id_entitas = (int) $params['id_entitas'];
        $kode_jenis_transaksi = (string) $params['kode_jenis_transaksi'];
        $context = $params['context'] ?? [];
        $default_refs = $params['default_refs'] ?? [];

        $template = penjualan_template_load($id_entitas, $kode_jenis_transaksi);
        $detail_template = penjualan_template_load_detail((int) $template->id_template_jurnal);

        $rows = [];
        $total_debit = 0.0;
        $total_kredit = 0.0;
        $urutan = 1;

        foreach ($detail_template as $dt) {
            $sumber_akun = (string) ($dt->sumber_akun ?? '');
            $sumber_nominal = (string) ($dt->sumber_nominal ?? '');
            $posisi_dc = strtolower((string) ($dt->posisi_dc ?? ''));

            if ($sumber_akun !== 'coa_default') {
                throw new RuntimeException(
                    'Template jurnal ' . $kode_jenis_transaksi .
                    ' memakai sumber_akun "' . $sumber_akun . '". Saat ini hanya didukung coa_default.'
                );
            }

            if ($sumber_nominal !== 'rumus_nominal') {
                throw new RuntimeException(
                    'Template jurnal ' . $kode_jenis_transaksi .
                    ' memakai sumber_nominal "' . $sumber_nominal . '". Saat ini hanya didukung rumus_nominal.'
                );
            }

            if (!in_array($posisi_dc, ['debit', 'kredit'], true)) {
                throw new RuntimeException('Posisi debit/kredit pada template jurnal tidak valid.');
            }

            $id_coa = (int) ($dt->id_coa_default ?? 0);

            if ($id_coa <= 0) {
                throw new RuntimeException('COA default pada template jurnal belum lengkap.');
            }

            $rumus_nominal = (string) ($dt->rumus_nominal ?? '');

            if ($rumus_nominal === '') {
                throw new RuntimeException('Rumus nominal pada template jurnal belum diisi.');
            }

            $nominal = penjualan_template_get_nominal($rumus_nominal, $context);

            if (abs($nominal) <= 0.000001) {
                continue;
            }

            $debit = 0.0;
            $kredit = 0.0;

            if ($posisi_dc === 'debit') {
                $debit = $nominal;
                $total_debit += $nominal;
            } else {
                $kredit = $nominal;
                $total_kredit += $nominal;
            }

            $rows[] = [
                'urutan' => $urutan++,
                'id_coa' => $id_coa,
                'debit' => round($debit, 2),
                'kredit' => round($kredit, 2),
                'keterangan_baris' => (string) ($dt->keterangan_baris ?? ''),
                'id_pelanggan' => $default_refs['id_pelanggan'] ?? null,
                'id_pemasok' => $default_refs['id_pemasok'] ?? null,
                'id_produk' => $default_refs['id_produk'] ?? null,
                'id_bahan_baku' => $default_refs['id_bahan_baku'] ?? null,
                'id_gudang' => $default_refs['id_gudang'] ?? null,
            ];
        }

        if (count($rows) <= 0) {
            throw new RuntimeException('Tidak ada baris jurnal yang terbentuk. Pastikan nominal transaksi tidak nol.');
        }

        if (round($total_debit, 2) !== round($total_kredit, 2)) {
            throw new RuntimeException(
                'Jurnal tidak balance. Debit: ' .
                number_format($total_debit, 2, '.', ',') .
                ', Kredit: ' .
                number_format($total_kredit, 2, '.', ',')
            );
        }

        return [
            'template' => $template,
            'rows' => $rows,
            'total_debit' => round($total_debit, 2),
            'total_kredit' => round($total_kredit, 2),
        ];
    }
}

if (!function_exists('penjualan_template_insert_jurnal')) {
    function penjualan_template_insert_jurnal(array $params): int
    {
        $id_entitas = (int) $params['id_entitas'];
        $id_pengguna = (int) ($params['id_pengguna'] ?? 0);
        $tanggal_jurnal = (string) $params['tanggal_jurnal'];
        $kode_jenis_transaksi = (string) $params['kode_jenis_transaksi'];
        $tabel_sumber = (string) $params['tabel_sumber'];
        $id_sumber = (int) $params['id_sumber'];
        $no_sumber = (string) ($params['no_sumber'] ?? '');
        $keterangan = (string) ($params['keterangan'] ?? '');
        $context = $params['context'] ?? [];
        $default_refs = $params['default_refs'] ?? [];

        if ($id_entitas <= 0) {
            throw new RuntimeException('Entitas tidak valid.');
        }

        if ($tanggal_jurnal === '') {
            throw new RuntimeException('Tanggal jurnal wajib diisi.');
        }

        if ($kode_jenis_transaksi === '') {
            throw new RuntimeException('Kode jenis transaksi jurnal wajib diisi.');
        }

        if ($tabel_sumber === '' || $id_sumber <= 0) {
            throw new RuntimeException('Sumber transaksi jurnal tidak valid.');
        }

        penjualan_pastikan_belum_ada_jurnal(
            $id_entitas,
            $tabel_sumber,
            $id_sumber,
            $kode_jenis_transaksi
        );

        $periode = penjualan_pastikan_periode_terbuka($id_entitas, $tanggal_jurnal);

        $hasil_template = penjualan_template_bangun_detail_jurnal([
            'id_entitas' => $id_entitas,
            'kode_jenis_transaksi' => $kode_jenis_transaksi,
            'context' => $context,
            'default_refs' => $default_refs,
        ]);

        $no_jurnal = penjualan_generate_no_jurnal($id_entitas);

        $id_jurnal = (int) Capsule::table('tb_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_jurnal' => $no_jurnal,
            'tanggal_jurnal' => $tanggal_jurnal,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => $kode_jenis_transaksi,
            'keterangan' => $keterangan !== '' ? $keterangan : null,
            'tabel_sumber' => $tabel_sumber,
            'id_sumber' => $id_sumber,
            'no_sumber' => $no_sumber !== '' ? $no_sumber : null,
            'status_jurnal' => 'posted',
            'total_debit' => (float) $hasil_template['total_debit'],
            'total_kredit' => (float) $hasil_template['total_kredit'],
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        foreach ($hasil_template['rows'] as $row) {
            Capsule::table('tb_jurnal_detail')->insert([
                'id_jurnal' => $id_jurnal,
                'urutan' => (int) $row['urutan'],
                'id_coa' => (int) $row['id_coa'],
                'debit' => (float) $row['debit'],
                'kredit' => (float) $row['kredit'],
                'keterangan_baris' => (string) ($row['keterangan_baris'] ?? ''),
                'id_pelanggan' => !empty($row['id_pelanggan']) ? (int) $row['id_pelanggan'] : null,
                'id_pemasok' => !empty($row['id_pemasok']) ? (int) $row['id_pemasok'] : null,
                'id_produk' => !empty($row['id_produk']) ? (int) $row['id_produk'] : null,
                'id_bahan_baku' => !empty($row['id_bahan_baku']) ? (int) $row['id_bahan_baku'] : null,
                'id_gudang' => !empty($row['id_gudang']) ? (int) $row['id_gudang'] : null,
            ]);
        }

        Capsule::table('tb_log_jurnal_sumber')->insert([
            'id_entitas' => $id_entitas,
            'id_jurnal' => $id_jurnal,
            'tabel_sumber' => $tabel_sumber,
            'id_sumber' => $id_sumber,
            'no_sumber' => $no_sumber !== '' ? $no_sumber : null,
            'kode_jenis_transaksi' => $kode_jenis_transaksi,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
        ]);

        return $id_jurnal;
    }
}

if (!function_exists('penjualan_buat_jurnal_faktur')) {
    function penjualan_buat_jurnal_faktur(array $params): int
    {
        $jenis_pembayaran = penjualan_normalize_jenis_pembayaran((string) $params['jenis_pembayaran']);
        $kode_jenis_transaksi = penjualan_template_faktur_by_jenis_pembayaran($jenis_pembayaran);

        return penjualan_template_insert_jurnal([
            'id_entitas' => (int) $params['id_entitas'],
            'id_pengguna' => (int) ($params['id_pengguna'] ?? 0),
            'tanggal_jurnal' => (string) $params['tanggal_faktur'],
            'kode_jenis_transaksi' => $kode_jenis_transaksi,
            'tabel_sumber' => 'tb_faktur_penjualan',
            'id_sumber' => (int) $params['id_faktur_penjualan'],
            'no_sumber' => (string) $params['no_faktur_penjualan'],
            'keterangan' => 'Posting faktur penjualan ' . (string) $params['no_faktur_penjualan'],
            'context' => [
                'subtotal' => (float) ($params['subtotal'] ?? 0),
                'diskon' => (float) ($params['diskon'] ?? 0),
                'ppn' => (float) ($params['ppn'] ?? 0),
                'total' => (float) ($params['total'] ?? 0),
            ],
            'default_refs' => [
                'id_pelanggan' => (int) ($params['id_pelanggan'] ?? 0),
            ],
        ]);
    }
}

if (!function_exists('penjualan_buat_jurnal_penyerahan_hpp')) {
    function penjualan_buat_jurnal_penyerahan_hpp(array $params): int
    {
        return penjualan_template_insert_jurnal([
            'id_entitas' => (int) $params['id_entitas'],
            'id_pengguna' => (int) ($params['id_pengguna'] ?? 0),
            'tanggal_jurnal' => (string) $params['tanggal_penyerahan'],
            'kode_jenis_transaksi' => 'PENYERAHAN_PENJUALAN_HPP',
            'tabel_sumber' => 'tb_penyerahan_penjualan',
            'id_sumber' => (int) $params['id_penyerahan_penjualan'],
            'no_sumber' => (string) $params['no_penyerahan_penjualan'],
            'keterangan' => 'Posting HPP penyerahan penjualan ' . (string) $params['no_penyerahan_penjualan'],
            'context' => [
                'total_hpp' => (float) ($params['total_hpp'] ?? 0),
            ],
            'default_refs' => [
                'id_pelanggan' => (int) ($params['id_pelanggan'] ?? 0),
                'id_gudang' => (int) ($params['id_gudang'] ?? 0),
            ],
        ]);
    }
}

if (!function_exists('penjualan_buat_jurnal_pembayaran')) {
    function penjualan_buat_jurnal_pembayaran(array $params): int
    {
        $metode_pembayaran = strtolower(trim((string) ($params['metode_pembayaran'] ?? '')));
        $kode_jenis_transaksi = penjualan_template_pembayaran_by_metode($metode_pembayaran);

        return penjualan_template_insert_jurnal([
            'id_entitas' => (int) $params['id_entitas'],
            'id_pengguna' => (int) ($params['id_pengguna'] ?? 0),
            'tanggal_jurnal' => (string) $params['tanggal_pembayaran'],
            'kode_jenis_transaksi' => $kode_jenis_transaksi,
            'tabel_sumber' => 'tb_pembayaran_penjualan',
            'id_sumber' => (int) $params['id_pembayaran_penjualan'],
            'no_sumber' => (string) $params['no_pembayaran_penjualan'],
            'keterangan' => 'Posting pembayaran penjualan ' . (string) $params['no_pembayaran_penjualan'],
            'context' => [
                'jumlah_bayar' => (float) ($params['jumlah_bayar'] ?? 0),
            ],
            'default_refs' => [
                'id_pelanggan' => (int) ($params['id_pelanggan'] ?? 0),
            ],
        ]);
    }
}