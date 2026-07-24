<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('penjualan_parse_number')) {
    function penjualan_parse_number($value): float
    {
        $value = trim((string) $value);
        $value = str_replace(',', '', $value);

        if ($value === '') {
            return 0.0;
        }

        return (float) $value;
    }
}

if (!function_exists('penjualan_format_qty')) {
    function penjualan_format_qty($value): string
    {
        $value = (float) $value;

        if (abs($value - round($value)) < 0.000001) {
            return number_format($value, 0, '.', ',');
        }

        return number_format($value, 2, '.', ',');
    }
}

if (!function_exists('penjualan_format_rupiah')) {
    function penjualan_format_rupiah($value): string
    {
        return 'Rp ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('penjualan_generate_nomor')) {
    function penjualan_generate_nomor(
        string $table,
        string $field,
        string $prefix,
        int $id_entitas,
        string $field_entitas = 'id_entitas'
    ): string {
        $rows = Capsule::table($table)
            ->where($field_entitas, $id_entitas)
            ->where($field, 'like', $prefix . '%')
            ->pluck($field);

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

if (!function_exists('penjualan_generate_no_pesanan')) {
    function penjualan_generate_no_pesanan(int $id_entitas): string
    {
        return penjualan_generate_nomor(
            'tb_pesanan_penjualan',
            'no_pesanan_penjualan',
            'PSJ-' . date('Ym') . '-',
            $id_entitas
        );
    }
}

if (!function_exists('penjualan_generate_no_penyerahan')) {
    function penjualan_generate_no_penyerahan(int $id_entitas): string
    {
        return penjualan_generate_nomor(
            'tb_penyerahan_penjualan',
            'no_penyerahan_penjualan',
            'PNJ-' . date('Ym') . '-',
            $id_entitas
        );
    }
}

if (!function_exists('penjualan_generate_no_faktur')) {
    function penjualan_generate_no_faktur(int $id_entitas): string
    {
        return penjualan_generate_nomor(
            'tb_faktur_penjualan',
            'no_faktur_penjualan',
            'FPJ-' . date('Ym') . '-',
            $id_entitas
        );
    }
}

if (!function_exists('penjualan_generate_no_pembayaran')) {
    function penjualan_generate_no_pembayaran(int $id_entitas): string
    {
        return penjualan_generate_nomor(
            'tb_pembayaran_penjualan',
            'no_pembayaran_penjualan',
            'BPJ-' . date('Ym') . '-',
            $id_entitas
        );
    }
}

if (!function_exists('penjualan_generate_no_jurnal')) {
    function penjualan_generate_no_jurnal(int $id_entitas): string
    {
        return penjualan_generate_nomor(
            'tb_jurnal',
            'no_jurnal',
            'JRN-',
            $id_entitas
        );
    }
}

if (!function_exists('penjualan_get_periode_terbuka')) {
    function penjualan_get_periode_terbuka(int $id_entitas, string $tanggal)
    {
        return Capsule::table('tb_periode_akuntansi')
            ->where('id_entitas', $id_entitas)
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->where('status_periode', 'terbuka')
            ->first();
    }
}

if (!function_exists('penjualan_pastikan_periode_terbuka')) {
    function penjualan_pastikan_periode_terbuka(int $id_entitas, string $tanggal)
    {
        $periode = penjualan_get_periode_terbuka($id_entitas, $tanggal);

        if (!$periode) {
            throw new RuntimeException('Periode akuntansi untuk tanggal transaksi belum dibuka atau sudah ditutup.');
        }

        return $periode;
    }
}

if (!function_exists('penjualan_sudah_ada_jurnal')) {
    function penjualan_sudah_ada_jurnal(
        int $id_entitas,
        string $tabel_sumber,
        int $id_sumber,
        string $kode_jenis_transaksi
    ): bool {
        return Capsule::table('tb_log_jurnal_sumber')
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', $tabel_sumber)
            ->where('id_sumber', $id_sumber)
            ->where('kode_jenis_transaksi', $kode_jenis_transaksi)
            ->exists();
    }
}

if (!function_exists('penjualan_pastikan_belum_ada_jurnal')) {
    function penjualan_pastikan_belum_ada_jurnal(
        int $id_entitas,
        string $tabel_sumber,
        int $id_sumber,
        string $kode_jenis_transaksi
    ): void {
        if (penjualan_sudah_ada_jurnal($id_entitas, $tabel_sumber, $id_sumber, $kode_jenis_transaksi)) {
            throw new RuntimeException('Jurnal untuk transaksi ini sudah pernah dibuat.');
        }
    }
}

if (!function_exists('penjualan_get_pelanggan')) {
    function penjualan_get_pelanggan(int $id_entitas, int $id_pelanggan)
    {
        return Capsule::table('tb_pelanggan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pelanggan', $id_pelanggan)
            ->first();
    }
}

if (!function_exists('penjualan_pastikan_pelanggan')) {
    function penjualan_pastikan_pelanggan(int $id_entitas, int $id_pelanggan)
    {
        if ($id_pelanggan <= 0) {
            throw new RuntimeException('Pelanggan wajib dipilih.');
        }

        $pelanggan = penjualan_get_pelanggan($id_entitas, $id_pelanggan);

        if (!$pelanggan) {
            throw new RuntimeException('Data pelanggan tidak ditemukan.');
        }

        return $pelanggan;
    }
}

if (!function_exists('penjualan_get_produk')) {
    function penjualan_get_produk(int $id_entitas, int $id_produk)
    {
        return Capsule::table('tb_produk as p')
            ->leftJoin('tb_satuan as s', 's.id_satuan', '=', 'p.id_satuan')
            ->where('p.id_entitas', $id_entitas)
            ->where('p.id_produk', $id_produk)
            ->select([
                'p.*',
                's.nama_satuan',
            ])
            ->first();
    }
}

if (!function_exists('penjualan_pastikan_produk')) {
    function penjualan_pastikan_produk(int $id_entitas, int $id_produk)
    {
        if ($id_produk <= 0) {
            throw new RuntimeException('Produk wajib dipilih.');
        }

        $produk = penjualan_get_produk($id_entitas, $id_produk);

        if (!$produk) {
            throw new RuntimeException('Produk tidak ditemukan.');
        }

        return $produk;
    }
}

if (!function_exists('penjualan_get_saldo_produk')) {
    function penjualan_get_saldo_produk(int $id_entitas, int $id_gudang, int $id_produk, bool $lock = false)
    {
        $query = Capsule::table('tb_saldo_stok')
            ->where('id_entitas', $id_entitas)
            ->where('id_gudang', $id_gudang)
            ->where('jenis_barang', 'produk')
            ->where('id_referensi_barang', $id_produk);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}

if (!function_exists('penjualan_pastikan_stok_produk_cukup')) {
    function penjualan_pastikan_stok_produk_cukup(
        int $id_entitas,
        int $id_gudang,
        int $id_produk,
        float $qty_keluar
    ) {
        $saldo = penjualan_get_saldo_produk($id_entitas, $id_gudang, $id_produk, true);

        $produk = penjualan_get_produk($id_entitas, $id_produk);
        $nama_produk = $produk ? (string) ($produk->nama_produk ?? '-') : '-';
        $kode_produk = $produk ? (string) ($produk->kode_produk ?? '-') : '-';

        if (!$saldo) {
            throw new RuntimeException('Saldo stok produk ' . $kode_produk . ' - ' . $nama_produk . ' tidak ditemukan di gudang terpilih.');
        }

        $qty_saldo = (float) ($saldo->qty_saldo ?? 0);

        if ($qty_saldo + 0.000001 < $qty_keluar) {
            throw new RuntimeException(
                'Stok produk ' . $kode_produk . ' - ' . $nama_produk . ' tidak cukup. ' .
                'Stok: ' . penjualan_format_qty($qty_saldo) . ', butuh: ' . penjualan_format_qty($qty_keluar) . '.'
            );
        }

        return $saldo;
    }
}

if (!function_exists('penjualan_update_saldo_produk_keluar')) {
    function penjualan_update_saldo_produk_keluar(
        int $id_entitas,
        int $id_gudang,
        int $id_produk,
        float $qty_keluar,
        int $id_pengguna
    ): array {
        $saldo = penjualan_pastikan_stok_produk_cukup($id_entitas, $id_gudang, $id_produk, $qty_keluar);

        $qty_awal = (float) ($saldo->qty_saldo ?? 0);
        $nilai_awal = (float) ($saldo->nilai_saldo ?? 0);
        $hpp_rata_rata = (float) ($saldo->hpp_rata_rata ?? 0);

        $nilai_keluar = round($qty_keluar * $hpp_rata_rata, 2);
        $qty_akhir = round($qty_awal - $qty_keluar, 3);
        $nilai_akhir = round(max(0, $nilai_awal - $nilai_keluar), 2);

        if ($qty_akhir <= 0) {
            $qty_akhir = 0.0;
            $nilai_akhir = 0.0;
        }

        Capsule::table('tb_saldo_stok')
            ->where('id_saldo_stok', (int) $saldo->id_saldo_stok)
            ->update([
                'qty_saldo' => $qty_akhir,
                'nilai_saldo' => $nilai_akhir,
                'hpp_rata_rata' => $hpp_rata_rata,
                'tanggal_update' => date('Y-m-d H:i:s'),
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);

        return [
            'qty_awal' => $qty_awal,
            'qty_keluar' => $qty_keluar,
            'qty_akhir' => $qty_akhir,
            'hpp_satuan' => $hpp_rata_rata,
            'hpp_total' => $nilai_keluar,
            'nilai_awal' => $nilai_awal,
            'nilai_akhir' => $nilai_akhir,
        ];
    }
}

if (!function_exists('penjualan_insert_mutasi_produk_keluar')) {
    function penjualan_insert_mutasi_produk_keluar(array $data): int
    {
        return (int) Capsule::table('tb_mutasi_stok')->insertGetId([
            'id_entitas' => (int) $data['id_entitas'],
            'tanggal_mutasi' => (string) $data['tanggal_mutasi'],
            'jenis_barang' => 'produk',
            'id_referensi_barang' => (int) $data['id_produk'],
            'id_gudang' => (int) $data['id_gudang'],
            'jenis_mutasi' => (string) ($data['jenis_mutasi'] ?? 'penyerahan_penjualan'),
            'qty_masuk' => 0,
            'qty_keluar' => (float) $data['qty_keluar'],
            'harga_satuan' => (float) $data['harga_satuan'],
            'nilai_total' => (float) $data['nilai_total'],
            'tabel_sumber' => (string) $data['tabel_sumber'],
            'id_sumber' => (int) $data['id_sumber'],
            'no_sumber' => (string) $data['no_sumber'],
            'keterangan' => (string) ($data['keterangan'] ?? 'Penyerahan penjualan'),
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => !empty($data['dibuat_oleh']) ? (int) $data['dibuat_oleh'] : null,
        ]);
    }
}

if (!function_exists('penjualan_hitung_subtotal_detail')) {
    function penjualan_hitung_subtotal_detail(float $qty, float $harga, float $diskon = 0): float
    {
        return round(($qty * $harga) - $diskon, 2);
    }
}

if (!function_exists('penjualan_hitung_total_header')) {
    function penjualan_hitung_total_header(float $subtotal, float $diskon = 0, float $ppn = 0): float
    {
        return round($subtotal - $diskon + $ppn, 2);
    }
}

if (!function_exists('penjualan_normalize_jenis_pembayaran')) {
    function penjualan_normalize_jenis_pembayaran(string $jenis): string
    {
        $jenis = strtolower(trim($jenis));

        if (!in_array($jenis, ['tunai', 'transfer', 'kredit'], true)) {
            throw new RuntimeException('Jenis pembayaran tidak valid.');
        }

        return $jenis;
    }
}

if (!function_exists('penjualan_template_faktur_by_jenis_pembayaran')) {
    function penjualan_template_faktur_by_jenis_pembayaran(string $jenis_pembayaran): string
    {
        $jenis_pembayaran = penjualan_normalize_jenis_pembayaran($jenis_pembayaran);

        if ($jenis_pembayaran === 'kredit') {
            return 'FAKTUR_PENJUALAN_KREDIT';
        }

        if ($jenis_pembayaran === 'transfer') {
            return 'FAKTUR_PENJUALAN_TRANSFER';
        }

        return 'FAKTUR_PENJUALAN_TUNAI';
    }
}

if (!function_exists('penjualan_template_pembayaran_by_metode')) {
    function penjualan_template_pembayaran_by_metode(string $metode_pembayaran): string
    {
        $metode_pembayaran = strtolower(trim($metode_pembayaran));

        if ($metode_pembayaran === 'transfer') {
            return 'PEMBAYARAN_PENJUALAN_TRANSFER';
        }

        if ($metode_pembayaran === 'tunai') {
            return 'PEMBAYARAN_PENJUALAN_TUNAI';
        }

        throw new RuntimeException('Metode pembayaran tidak valid. Gunakan tunai atau transfer.');
    }
}

if (!function_exists('penjualan_redirect')) {
    function penjualan_redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('penjualan_parse_decimal_umum')) {
    function penjualan_parse_decimal_umum($value): float
    {
        $value = trim((string) $value);
        $value = str_replace(['Rp', 'rp', ' ', ','], '', $value);
        if ($value === '') {
            return 0.0;
        }
        return round((float) $value, 2);
    }
}

if (!function_exists('penjualan_default_akun_biaya_pengiriman')) {
    function penjualan_default_akun_biaya_pengiriman(int $id_entitas): int
    {
        if (Capsule::schema()->hasTable('tb_konfigurasi_akun')) {
            $id = Capsule::table('tb_konfigurasi_akun')
                ->where('id_entitas', $id_entitas)
                ->where('status_aktif', 1)
                ->whereIn('kode_konfigurasi', ['BIAYA_PENGIRIMAN_PENJUALAN', 'ONGKIR_PENJUALAN', 'BEBAN_PENGIRIMAN_PENJUALAN'])
                ->value('id_coa');
            if ($id) {
                return (int) $id;
            }
        }

        $akun = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->where(function ($q) {
                $q->where('kode_coa', '6310')
                  ->orWhere('nama_coa', 'like', '%Beban Pengiriman%')
                  ->orWhere('nama_coa', 'like', '%Beban Transportasi%')
                  ->orWhere('kode_coa', '6300');
            })
            ->orderByRaw("CASE WHEN kode_coa='6310' THEN 0 WHEN kode_coa='6300' THEN 1 ELSE 2 END")
            ->first();

        return $akun ? (int) $akun->id_coa : 0;
    }
}

if (!function_exists('penjualan_default_akun_kas')) {
    function penjualan_default_akun_kas(int $id_entitas): int
    {
        $akun = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->where(function ($q) {
                $q->where('kode_coa', '1110')->orWhere('nama_coa', 'like', '%Kas%');
            })
            ->orderByRaw("CASE WHEN kode_coa='1110' THEN 0 ELSE 1 END")
            ->first();
        return $akun ? (int) $akun->id_coa : 0;
    }
}

if (!function_exists('penjualan_buat_jurnal_biaya_pengiriman_penyerahan')) {
    function penjualan_buat_jurnal_biaya_pengiriman_penyerahan(array $params): ?int
    {
        $id_entitas = (int) $params['id_entitas'];
        $biaya = round((float) ($params['biaya_pengiriman'] ?? 0), 2);
        if ($biaya <= 0) {
            return null;
        }
        $id_penyerahan = (int) $params['id_penyerahan_penjualan'];
        if (penjualan_sudah_ada_jurnal($id_entitas, 'tb_penyerahan_penjualan', $id_penyerahan, 'BIAYA_PENGIRIMAN_PENJUALAN')) {
            return null;
        }
        $id_coa_beban = (int) ($params['id_coa_biaya_pengiriman'] ?? 0);
        if ($id_coa_beban <= 0) {
            $id_coa_beban = penjualan_default_akun_biaya_pengiriman($id_entitas);
        }
        $id_coa_kas = penjualan_default_akun_kas($id_entitas);
        if ($id_coa_beban <= 0 || $id_coa_kas <= 0) {
            throw new RuntimeException('Akun biaya pengiriman atau akun kas belum tersedia. Jalankan SQL update dan cek COA.');
        }
        $tanggal = (string) $params['tanggal_penyerahan'];
        $periode = penjualan_pastikan_periode_terbuka($id_entitas, $tanggal);
        $no_jurnal = penjualan_generate_no_jurnal($id_entitas);
        $id_jurnal = (int) Capsule::table('tb_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_jurnal' => $no_jurnal,
            'tanggal_jurnal' => $tanggal,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => 'BIAYA_PENGIRIMAN_PENJUALAN',
            'keterangan' => 'Biaya pengiriman/surat jalan ' . (string) $params['no_penyerahan_penjualan'],
            'tabel_sumber' => 'tb_penyerahan_penjualan',
            'id_sumber' => $id_penyerahan,
            'no_sumber' => (string) $params['no_penyerahan_penjualan'],
            'status_jurnal' => 'posted',
            'total_debit' => $biaya,
            'total_kredit' => $biaya,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => (int) ($params['id_pengguna'] ?? 0) ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => (int) ($params['id_pengguna'] ?? 0) ?: null,
        ]);
        $refs = [
            'id_pelanggan' => (int) ($params['id_pelanggan'] ?? 0) ?: null,
            'id_pemasok' => null,
            'id_produk' => null,
            'id_bahan_baku' => null,
            'id_gudang' => (int) ($params['id_gudang'] ?? 0) ?: null,
        ];
        Capsule::table('tb_jurnal_detail')->insert(array_merge([
            'id_jurnal' => $id_jurnal,
            'urutan' => 1,
            'id_coa' => $id_coa_beban,
            'debit' => $biaya,
            'kredit' => 0,
            'keterangan_baris' => 'Beban pengiriman penjualan',
        ], $refs));
        Capsule::table('tb_jurnal_detail')->insert(array_merge([
            'id_jurnal' => $id_jurnal,
            'urutan' => 2,
            'id_coa' => $id_coa_kas,
            'debit' => 0,
            'kredit' => $biaya,
            'keterangan_baris' => 'Kas keluar untuk pengiriman',
        ], $refs));
        Capsule::table('tb_log_jurnal_sumber')->insert([
            'id_entitas' => $id_entitas,
            'id_jurnal' => $id_jurnal,
            'tabel_sumber' => 'tb_penyerahan_penjualan',
            'id_sumber' => $id_penyerahan,
            'no_sumber' => (string) $params['no_penyerahan_penjualan'],
            'kode_jenis_transaksi' => 'BIAYA_PENGIRIMAN_PENJUALAN',
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
        ]);
        return $id_jurnal;
    }
}
