<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../_fungsi_penjualan.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id'] ?? 0);

if (!function_exists('pos_buat_jurnal_pembalik')) {
    function pos_buat_jurnal_pembalik(
        int $id_entitas,
        int $id_pengguna,
        int $id_jurnal_asal,
        string $tanggal_jurnal,
        string $tabel_sumber,
        int $id_sumber,
        string $no_sumber,
        string $keterangan
    ): int {
        $jurnal_asal = Capsule::table('tb_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('id_jurnal', $id_jurnal_asal)
            ->where('status_jurnal', 'posted')
            ->first();

        if (!$jurnal_asal) {
            throw new RuntimeException('Jurnal asal tidak ditemukan atau belum posted.');
        }

        $detail_asal = Capsule::table('tb_jurnal_detail')
            ->where('id_jurnal', $id_jurnal_asal)
            ->orderBy('urutan', 'asc')
            ->get();

        if ($detail_asal->count() <= 0) {
            throw new RuntimeException('Detail jurnal asal kosong.');
        }

        $periode = penjualan_pastikan_periode_terbuka($id_entitas, $tanggal_jurnal);
        $no_jurnal_balik = penjualan_generate_no_jurnal($id_entitas);

        $total_debit_balik = 0.0;
        $total_kredit_balik = 0.0;

        foreach ($detail_asal as $d) {
            $total_debit_balik += (float) ($d->kredit ?? 0);
            $total_kredit_balik += (float) ($d->debit ?? 0);
        }

        $total_debit_balik = round($total_debit_balik, 2);
        $total_kredit_balik = round($total_kredit_balik, 2);

        if ($total_debit_balik !== $total_kredit_balik) {
            throw new RuntimeException(
                'Jurnal pembalik tidak balance. Debit: ' .
                number_format($total_debit_balik, 2, '.', ',') .
                ', Kredit: ' .
                number_format($total_kredit_balik, 2, '.', ',')
            );
        }

        $id_jurnal_balik = (int) Capsule::table('tb_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_jurnal' => $no_jurnal_balik,
            'tanggal_jurnal' => $tanggal_jurnal,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => 'PEMBATALAN_POS',
            'keterangan' => $keterangan,
            'tabel_sumber' => $tabel_sumber,
            'id_sumber' => $id_sumber,
            'no_sumber' => $no_sumber !== '' ? $no_sumber : null,
            'status_jurnal' => 'posted',
            'total_debit' => $total_debit_balik,
            'total_kredit' => $total_kredit_balik,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);

        foreach ($detail_asal as $d) {
            Capsule::table('tb_jurnal_detail')->insert([
                'id_jurnal' => $id_jurnal_balik,
                'urutan' => (int) ($d->urutan ?? 1),
                'id_coa' => (int) $d->id_coa,
                'debit' => (float) ($d->kredit ?? 0),
                'kredit' => (float) ($d->debit ?? 0),
                'keterangan_baris' => 'Pembalik: ' . (string) ($d->keterangan_baris ?? ''),
                'id_pelanggan' => !empty($d->id_pelanggan) ? (int) $d->id_pelanggan : null,
                'id_pemasok' => !empty($d->id_pemasok) ? (int) $d->id_pemasok : null,
                'id_produk' => !empty($d->id_produk) ? (int) $d->id_produk : null,
                'id_bahan_baku' => !empty($d->id_bahan_baku) ? (int) $d->id_bahan_baku : null,
                'id_gudang' => !empty($d->id_gudang) ? (int) $d->id_gudang : null,
            ]);
        }

        Capsule::table('tb_log_jurnal_sumber')->insert([
            'id_entitas' => $id_entitas,
            'id_jurnal' => $id_jurnal_balik,
            'tabel_sumber' => $tabel_sumber,
            'id_sumber' => $id_sumber,
            'no_sumber' => $no_sumber !== '' ? $no_sumber : null,
            'kode_jenis_transaksi' => 'PEMBATALAN_POS',
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
        ]);

        return $id_jurnal_balik;
    }
}

if ($id_entitas <= 0 || $id_faktur_penjualan <= 0) {
    set_flash('error', 'Data transaksi tidak valid.');
    penjualan_redirect(admin_page_url('penjualan/kasir/riwayat'));
}

try {
    Capsule::connection()->transaction(function () use ($id_entitas, $id_pengguna, $id_faktur_penjualan) {
        $tanggal_batal = date('Y-m-d');
        $tanggal_batal_full = date('Y-m-d H:i:s');
        $tanggal_batal_label = date('d/m/Y H:i');

        /*
        |--------------------------------------------------------------------------
        | 1. Validasi faktur POS
        |--------------------------------------------------------------------------
        */

        $faktur = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$faktur) {
            throw new RuntimeException('Faktur POS tidak ditemukan.');
        }

        if ((string) ($faktur->status_faktur ?? '') !== 'posted') {
            throw new RuntimeException('Hanya transaksi POS berstatus posted yang bisa dibatalkan.');
        }

        if (!str_starts_with((string) ($faktur->catatan ?? ''), 'POS')) {
            throw new RuntimeException('Transaksi ini bukan transaksi POS.');
        }

        $id_penyerahan = (int) ($faktur->id_penyerahan_penjualan ?? 0);

        if ($id_penyerahan <= 0) {
            throw new RuntimeException('Dokumen penyerahan POS tidak ditemukan.');
        }

        $penyerahan = Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan)
            ->lockForUpdate()
            ->first();

        if (!$penyerahan) {
            throw new RuntimeException('Penyerahan POS tidak ditemukan.');
        }

        if ((string) ($penyerahan->status_penyerahan ?? '') !== 'posted') {
            throw new RuntimeException('Penyerahan POS sudah tidak berstatus posted.');
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Cegah pembatalan dobel
        |--------------------------------------------------------------------------
        */

        $sudah_ada_jurnal_pembatalan = Capsule::table('tb_log_jurnal_sumber')
            ->where('id_entitas', $id_entitas)
            ->where('kode_jenis_transaksi', 'PEMBATALAN_POS')
            ->where(function ($q) use ($id_faktur_penjualan, $id_penyerahan) {
                $q->where(function ($qq) use ($id_faktur_penjualan) {
                    $qq->where('tabel_sumber', 'tb_faktur_penjualan')
                        ->where('id_sumber', $id_faktur_penjualan);
                });

                $q->orWhere(function ($qq) use ($id_penyerahan) {
                    $qq->where('tabel_sumber', 'tb_penyerahan_penjualan')
                        ->where('id_sumber', $id_penyerahan);
                });
            })
            ->exists();

        if ($sudah_ada_jurnal_pembatalan) {
            throw new RuntimeException('Transaksi POS ini sudah pernah dibuatkan jurnal pembatalan.');
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Ambil jurnal asal dari log jurnal sumber
        |--------------------------------------------------------------------------
        */

        $jurnal_sumber_rows = Capsule::table('tb_log_jurnal_sumber as l')
            ->join('tb_jurnal as j', 'j.id_jurnal', '=', 'l.id_jurnal')
            ->where('l.id_entitas', $id_entitas)
            ->where('j.status_jurnal', 'posted')
            ->where('l.kode_jenis_transaksi', '<>', 'PEMBATALAN_POS')
            ->where(function ($q) use ($id_faktur_penjualan, $id_penyerahan) {
                $q->where(function ($qq) use ($id_faktur_penjualan) {
                    $qq->where('l.tabel_sumber', 'tb_faktur_penjualan')
                        ->where('l.id_sumber', $id_faktur_penjualan);
                });

                $q->orWhere(function ($qq) use ($id_penyerahan) {
                    $qq->where('l.tabel_sumber', 'tb_penyerahan_penjualan')
                        ->where('l.id_sumber', $id_penyerahan);
                });
            })
            ->select([
                'l.id_jurnal',
                'l.tabel_sumber',
                'l.id_sumber',
                'l.no_sumber',
                'l.kode_jenis_transaksi',
                'j.no_jurnal',
            ])
            ->orderBy('j.id_jurnal', 'asc')
            ->get();

        if ($jurnal_sumber_rows->count() <= 0) {
            throw new RuntimeException('Jurnal asal transaksi POS tidak ditemukan, pembatalan tidak dapat dilanjutkan.');
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Buat jurnal pembalik dari jurnal aktual lama
        |--------------------------------------------------------------------------
        */

        foreach ($jurnal_sumber_rows as $jr) {
            pos_buat_jurnal_pembalik(
                $id_entitas,
                $id_pengguna,
                (int) $jr->id_jurnal,
                $tanggal_batal,
                (string) $jr->tabel_sumber,
                (int) $jr->id_sumber,
                (string) ($jr->no_sumber ?? ''),
                'Jurnal pembalik POS atas jurnal ' . (string) ($jr->no_jurnal ?? '-')
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Kembalikan stok produk
        |--------------------------------------------------------------------------
        */

        $detail_penyerahan = Capsule::table('tb_penyerahan_penjualan_detail')
            ->where('id_penyerahan_penjualan', $id_penyerahan)
            ->get();

        if ($detail_penyerahan->count() <= 0) {
            throw new RuntimeException('Detail penyerahan POS kosong.');
        }

        foreach ($detail_penyerahan as $d) {
            $id_produk = (int) ($d->id_produk ?? 0);
            $qty = (float) ($d->qty ?? 0);
            $hpp_satuan = (float) ($d->hpp_satuan ?? 0);
            $hpp_total = (float) ($d->hpp_total ?? 0);
            $id_gudang = (int) ($penyerahan->id_gudang ?? 0);

            if ($id_produk <= 0 || $qty <= 0 || $id_gudang <= 0) {
                continue;
            }

            $saldo = Capsule::table('tb_saldo_stok')
                ->where('id_entitas', $id_entitas)
                ->where('id_gudang', $id_gudang)
                ->where('jenis_barang', 'produk')
                ->where('id_referensi_barang', $id_produk)
                ->lockForUpdate()
                ->first();

            if ($saldo) {
                $qty_awal = (float) ($saldo->qty_saldo ?? 0);
                $nilai_awal = (float) ($saldo->nilai_saldo ?? 0);

                $qty_akhir = round($qty_awal + $qty, 3);
                $nilai_akhir = round($nilai_awal + $hpp_total, 2);
                $hpp_rata_rata = $qty_akhir > 0 ? round($nilai_akhir / $qty_akhir, 2) : 0;

                Capsule::table('tb_saldo_stok')
                    ->where('id_saldo_stok', (int) $saldo->id_saldo_stok)
                    ->update([
                        'qty_saldo' => $qty_akhir,
                        'nilai_saldo' => $nilai_akhir,
                        'hpp_rata_rata' => $hpp_rata_rata,
                        'tanggal_update' => $tanggal_batal_full,
                        'tanggal_diubah' => $tanggal_batal_full,
                        'diubah_oleh' => $id_pengguna ?: null,
                    ]);
            } else {
                Capsule::table('tb_saldo_stok')->insert([
                    'id_entitas' => $id_entitas,
                    'id_gudang' => $id_gudang,
                    'jenis_barang' => 'produk',
                    'id_referensi_barang' => $id_produk,
                    'qty_saldo' => round($qty, 3),
                    'nilai_saldo' => round($hpp_total, 2),
                    'hpp_rata_rata' => $qty > 0 ? round($hpp_total / $qty, 2) : 0,
                    'tanggal_update' => $tanggal_batal_full,
                    'tanggal_dibuat' => $tanggal_batal_full,
                    'dibuat_oleh' => $id_pengguna ?: null,
                    'tanggal_diubah' => null,
                    'diubah_oleh' => null,
                ]);
            }

            Capsule::table('tb_mutasi_stok')->insert([
                'id_entitas' => $id_entitas,
                'tanggal_mutasi' => $tanggal_batal_full,
                'jenis_barang' => 'produk',
                'id_referensi_barang' => $id_produk,
                'id_gudang' => $id_gudang,
                'jenis_mutasi' => 'pembatalan_penjualan_pos',
                'qty_masuk' => $qty,
                'qty_keluar' => 0,
                'harga_satuan' => $hpp_satuan,
                'nilai_total' => $hpp_total,
                'tabel_sumber' => 'tb_faktur_penjualan',
                'id_sumber' => (int) $faktur->id_faktur_penjualan,
                'no_sumber' => (string) ($faktur->no_faktur_penjualan ?? ''),
                'keterangan' => 'Pembatalan transaksi POS',
                'tanggal_dibuat' => $tanggal_batal_full,
                'dibuat_oleh' => $id_pengguna ?: null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 6. Update status dokumen
        |--------------------------------------------------------------------------
        */

        $catatan_faktur = trim((string) ($faktur->catatan ?? ''));
        $catatan_penyerahan = trim((string) ($penyerahan->catatan ?? ''));

        Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->update([
                'status_faktur' => 'batal',
                'sisa_piutang' => 0,
                'catatan' => trim($catatan_faktur . ' | DIBATALKAN POS pada ' . $tanggal_batal_label),
                'tanggal_diubah' => $tanggal_batal_full,
                'diubah_oleh' => $id_pengguna ?: null,
            ]);

        Capsule::table('tb_penyerahan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_penyerahan_penjualan', $id_penyerahan)
            ->update([
                'status_penyerahan' => 'batal',
                'catatan' => trim($catatan_penyerahan . ' | DIBATALKAN POS pada ' . $tanggal_batal_label),
            ]);

        $pembayaran_rows = Capsule::table('tb_pembayaran_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->get();

        foreach ($pembayaran_rows as $pembayaran) {
            $catatan_pembayaran = trim((string) ($pembayaran->catatan ?? ''));

            Capsule::table('tb_pembayaran_penjualan')
                ->where('id_pembayaran_penjualan', (int) $pembayaran->id_pembayaran_penjualan)
                ->update([
                    'status_posting' => 'batal',
                    'catatan' => trim($catatan_pembayaran . ' | DIBATALKAN POS pada ' . $tanggal_batal_label),
                    'tanggal_diubah' => $tanggal_batal_full,
                    'diubah_oleh' => $id_pengguna ?: null,
                ]);
        }
    });

    set_flash('success', 'Transaksi POS berhasil dibatalkan. Stok sudah dikembalikan dan jurnal pembalik sudah dibuat.');
    penjualan_redirect(admin_page_url('penjualan/kasir/detail') . '&id=' . $id_faktur_penjualan);
} catch (Throwable $e) {
    set_flash('error', 'Gagal membatalkan transaksi POS: ' . $e->getMessage());
    penjualan_redirect(admin_page_url('penjualan/kasir/detail') . '&id=' . $id_faktur_penjualan);
}