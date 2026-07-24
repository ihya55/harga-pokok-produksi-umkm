<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('mapping_akun_user_login')) {
    function mapping_akun_user_login(): array
    {
        if (function_exists('user_login')) {
            $user = user_login();
            if (is_array($user)) {
                return $user;
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_login']) && is_array($_SESSION['user_login'])) {
            return $_SESSION['user_login'];
        }

        return [];
    }
}

if (!function_exists('mapping_akun_id_entitas')) {
    function mapping_akun_id_entitas(): int
    {
        $user = mapping_akun_user_login();
        return (int) ($user['id_entitas'] ?? ($_SESSION['id_entitas'] ?? 0));
    }
}

if (!function_exists('mapping_akun_id_pengguna')) {
    function mapping_akun_id_pengguna(): int
    {
        $user = mapping_akun_user_login();
        return (int) ($user['id_pengguna'] ?? ($_SESSION['id_pengguna'] ?? 0));
    }
}

if (!function_exists('opsi_jenis_objek_mapping_akun')) {
    function opsi_jenis_objek_mapping_akun(): array
    {
        return [
            'global'     => 'Global / Umum',
            'produk'     => 'Produk',
            'bahan_baku' => 'Bahan Baku',
            'pelanggan'  => 'Pelanggan',
            'pemasok'    => 'Pemasok',
            'gudang'     => 'Gudang',
        ];
    }
}

if (!function_exists('opsi_peran_akun_mapping')) {
    function opsi_peran_akun_mapping(): array
    {
        return [
            'akun_kas_bank'              => 'Akun Kas/Bank',
            'akun_kas_default'           => 'Akun Kas Default',
            'akun_bank_default'          => 'Akun Bank Default',

            'akun_piutang'               => 'Akun Piutang',
            'akun_utang'                 => 'Akun Utang',
            'akun_penjualan'             => 'Akun Penjualan',
            'akun_pendapatan_lain'       => 'Akun Pendapatan Lain',
            'akun_pendapatan_selisih_stok' => 'Akun Pendapatan Selisih Stok',

            'akun_hpp'                   => 'Akun HPP',
            'akun_beban_lain'            => 'Akun Beban Lain',
            'akun_beban_selisih_stok'    => 'Akun Beban Selisih Stok',
            'akun_biaya'                 => 'Akun Biaya',

            'akun_persediaan_bahan'      => 'Akun Persediaan Bahan',
            'akun_persediaan_produk'     => 'Akun Persediaan Produk',
            'akun_persediaan_kemasan'    => 'Akun Persediaan Kemasan',

            'akun_bahan_baku_terpakai'   => 'Akun Bahan Baku Terpakai',
            'akun_tenaga_kerja_langsung' => 'Akun Tenaga Kerja Langsung',
            'akun_listrik_produksi'      => 'Akun Listrik Produksi',
            'akun_kemasan_produksi'      => 'Akun Kemasan Produksi',
            'akun_bop_lain'              => 'Akun BOP Lain',

            'akun_pajak_masukan'         => 'Akun Pajak Masukan',
            'akun_pajak_keluaran'        => 'Akun Pajak Keluaran',
            'akun_modal'                 => 'Akun Modal',
        ];
    }
}

if (!function_exists('opsi_jenis_transaksi_mapping_akun')) {
    function opsi_jenis_transaksi_mapping_akun(): array
    {
        return [
            'ARUS_KAS'                         => 'Arus Kas',

            'KAS_MASUK'                        => 'Kas Masuk',
            'KAS_MASUK_LAIN'                   => 'Kas Masuk Lain',
            'KAS_KELUAR'                       => 'Kas Keluar',
            'KAS_KELUAR_LAIN'                  => 'Kas Keluar Lain',

            'FAKTUR_PENJUALAN_TUNAI'           => 'Faktur Penjualan Tunai',
            'FAKTUR_PENJUALAN_TRANSFER'        => 'Faktur Penjualan Transfer',
            'FAKTUR_PENJUALAN_KREDIT'          => 'Faktur Penjualan Kredit',
            'PEMBAYARAN_PENJUALAN'             => 'Pembayaran Penjualan',
            'PEMBAYARAN_PENJUALAN_TUNAI'       => 'Pembayaran Penjualan Tunai',
            'PEMBAYARAN_PENJUALAN_TRANSFER'    => 'Pembayaran Penjualan Transfer',
            'PENYERAHAN_PENJUALAN_HPP'         => 'Penyerahan Penjualan - HPP',

            'FAKTUR_PEMBELIAN'                 => 'Faktur Pembelian',
            'FAKTUR_PEMBELIAN_KREDIT'          => 'Faktur Pembelian Kredit',
            'FAKTUR_PEMBELIAN_TUNAI'           => 'Faktur Pembelian Tunai',
            'FAKTUR_PEMBELIAN_TRANSFER'        => 'Faktur Pembelian Transfer',
            'PEMBAYARAN_PEMBELIAN'             => 'Pembayaran Pembelian',

            'PENGAMBILAN_BAHAN_PRODUKSI'       => 'Pengambilan Bahan Produksi',
            'BIAYA_PRODUKSI'                   => 'Biaya Produksi',
            'BIAYA_LISTRIK_PRODUKSI'           => 'Biaya Listrik Produksi',
            'BIAYA_KEMASAN_PRODUKSI'           => 'Biaya Kemasan Produksi',
            'BIAYA_LAIN_LAIN_PRODUKSI'         => 'Biaya Lain-lain Produksi',
            'HASIL_PRODUKSI'                   => 'Hasil Produksi',

            'SALDO_AWAL_STOK'                  => 'Saldo Awal Stok',
            'STOK_OPNAME_PLUS'                 => 'Stok Opname Plus',
            'STOK_OPNAME_MINUS'                => 'Stok Opname Minus',

            'JURNAL_MANUAL'                    => 'Jurnal Manual',
            'SALDO_AWAL_COA'                   => 'Saldo Awal COA',
        ];
    }
}

if (!function_exists('label_jenis_objek_mapping_akun')) {
    function label_jenis_objek_mapping_akun(?string $jenis): string
    {
        $opsi = opsi_jenis_objek_mapping_akun();
        return $opsi[$jenis ?? ''] ?? (string) ($jenis ?: '-');
    }
}

if (!function_exists('label_peran_akun_mapping')) {
    function label_peran_akun_mapping(?string $peran): string
    {
        $opsi = opsi_peran_akun_mapping();
        return $opsi[$peran ?? ''] ?? (string) ($peran ?: '-');
    }
}

if (!function_exists('label_jenis_transaksi_mapping_akun')) {
    function label_jenis_transaksi_mapping_akun(?string $kode): string
    {
        $opsi = opsi_jenis_transaksi_mapping_akun();
        return $opsi[$kode ?? ''] ?? (string) ($kode ?: '-');
    }
}

if (!function_exists('mapping_akun_status_badge')) {
    function mapping_akun_status_badge($status): string
    {
        return (int) $status === 1
            ? '<span class="badge text-bg-success">Aktif</span>'
            : '<span class="badge text-bg-secondary">Nonaktif</span>';
    }
}

if (!function_exists('mapping_akun_nama_objek')) {
    function mapping_akun_nama_objek(string $jenis_objek, int $id_objek, int $id_entitas): string
    {
        if ($jenis_objek === 'global') {
            return 'Global / Semua Objek';
        }

        if ($id_objek <= 0) {
            return '-';
        }

        try {
            return match ($jenis_objek) {
                'produk' => (function () use ($id_objek, $id_entitas) {
                    $row = Capsule::table('tb_produk')
                        ->where('id_entitas', $id_entitas)
                        ->where('id_produk', $id_objek)
                        ->first();

                    return $row ? (($row->kode_produk ?? '-') . ' - ' . ($row->nama_produk ?? '-')) : '-';
                })(),

                'bahan_baku' => (function () use ($id_objek, $id_entitas) {
                    $row = Capsule::table('tb_bahan_baku')
                        ->where('id_entitas', $id_entitas)
                        ->where('id_bahan_baku', $id_objek)
                        ->first();

                    return $row ? (($row->kode_bahan_baku ?? '-') . ' - ' . ($row->nama_bahan_baku ?? '-')) : '-';
                })(),

                'pelanggan' => (function () use ($id_objek, $id_entitas) {
                    $row = Capsule::table('tb_pelanggan')
                        ->where('id_entitas', $id_entitas)
                        ->where('id_pelanggan', $id_objek)
                        ->first();

                    return $row ? (($row->kode_pelanggan ?? '-') . ' - ' . ($row->nama_pelanggan ?? '-')) : '-';
                })(),

                'pemasok' => (function () use ($id_objek, $id_entitas) {
                    $row = Capsule::table('tb_pemasok')
                        ->where('id_entitas', $id_entitas)
                        ->where('id_pemasok', $id_objek)
                        ->first();

                    return $row ? (($row->kode_pemasok ?? '-') . ' - ' . ($row->nama_pemasok ?? '-')) : '-';
                })(),

                'gudang' => (function () use ($id_objek, $id_entitas) {
                    $row = Capsule::table('tb_gudang')
                        ->where('id_entitas', $id_entitas)
                        ->where('id_gudang', $id_objek)
                        ->first();

                    return $row ? (($row->kode_gudang ?? '-') . ' - ' . ($row->nama_gudang ?? '-')) : '-';
                })(),

                default => '-',
            };
        } catch (Throwable $e) {
            return '-';
        }
    }
}

if (!function_exists('mapping_akun_parse_form')) {
    function mapping_akun_parse_form(array $post): array
    {
        $kode_jenis_transaksi = strtoupper(trim((string) ($post['kode_jenis_transaksi'] ?? '')));
        $jenis_objek = trim((string) ($post['jenis_objek'] ?? ''));
        $peran_akun = trim((string) ($post['peran_akun'] ?? ''));
        $id_coa = (int) ($post['id_coa'] ?? 0);
        $status_aktif = (int) ($post['status_aktif'] ?? 1);

        $id_objek = 0;

        if ($jenis_objek !== 'global') {
            $id_objek = match ($jenis_objek) {
                'produk' => (int) ($post['id_objek_produk'] ?? 0),
                'bahan_baku' => (int) ($post['id_objek_bahan_baku'] ?? 0),
                'pelanggan' => (int) ($post['id_objek_pelanggan'] ?? 0),
                'pemasok' => (int) ($post['id_objek_pemasok'] ?? 0),
                'gudang' => (int) ($post['id_objek_gudang'] ?? 0),
                default => 0,
            };
        }

        return [
            'kode_jenis_transaksi' => $kode_jenis_transaksi,
            'jenis_objek' => $jenis_objek,
            'id_objek' => $id_objek,
            'peran_akun' => $peran_akun,
            'id_coa' => $id_coa,
            'status_aktif' => $status_aktif === 1 ? 1 : 0,
        ];
    }
}

if (!function_exists('mapping_akun_validate')) {
    function mapping_akun_validate(array $data, int $id_entitas): void
    {
        if (!array_key_exists($data['kode_jenis_transaksi'], opsi_jenis_transaksi_mapping_akun())) {
            throw new RuntimeException('Jenis transaksi tidak valid.');
        }

        if (!array_key_exists($data['jenis_objek'], opsi_jenis_objek_mapping_akun())) {
            throw new RuntimeException('Jenis objek tidak valid.');
        }

        if ($data['jenis_objek'] !== 'global' && (int) $data['id_objek'] <= 0) {
            throw new RuntimeException('Objek wajib dipilih.');
        }

        if (!array_key_exists($data['peran_akun'], opsi_peran_akun_mapping())) {
            throw new RuntimeException('Peran akun tidak valid.');
        }

        if ((int) $data['id_coa'] <= 0) {
            throw new RuntimeException('COA wajib dipilih.');
        }

        $coaExists = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('id_coa', (int) $data['id_coa'])
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->exists();

        if (!$coaExists) {
            throw new RuntimeException('COA tidak valid, nonaktif, atau tidak boleh transaksi.');
        }
    }
}