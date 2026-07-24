<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/HasilProduksiORM.php';
require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../orm/JurnalORM.php';
require_once __DIR__ . '/../../../../orm/JurnalDetailORM.php';
require_once __DIR__ . '/../../../../orm/LogJurnalSumberORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_hasil_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/hasil');
}

$row = HasilProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_hasil_produksi);

if (!$row) {
    set_flash('error', 'Data hasil produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Hanya hasil produksi draft yang bisa diposting.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$perintah = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_perintah_produksi', (int) $row->id_perintah_produksi)
    ->where('status_produksi', 'posted')
    ->first();

if (!$perintah) {
    set_flash('error', 'Perintah produksi tidak valid atau belum posted.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$produk = Capsule::table('tb_produk')
    ->where('id_entitas', $id_entitas)
    ->where('id_produk', (int) $row->id_produk)
    ->where('status_produk', 1)
    ->first();

if (!$produk) {
    set_flash('error', 'Produk hasil produksi tidak valid atau tidak aktif.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$gudang = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->where('id_gudang', (int) $row->id_gudang)
    ->where('status_aktif', 1)
    ->first();

if (!$gudang) {
    set_flash('error', 'Gudang hasil produksi tidak valid atau tidak aktif.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

$qty_hasil = (int) $row->qty_hasil;
$total_hpp = round((float) $row->total_hpp, 2);
$hpp_per_unit = round((float) $row->hpp_per_unit, 2);

if ($qty_hasil <= 0) {
    set_flash('error', 'Qty hasil harus lebih besar dari 0.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

if ($total_hpp <= 0 || $hpp_per_unit <= 0) {
    set_flash('error', 'Total HPP dan HPP per unit harus lebih besar dari 0.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $perintah,
        $produk,
        $id_entitas,
        $id_pengguna,
        $id_hasil_produksi,
        $qty_hasil,
        $total_hpp,
        $hpp_per_unit
    ) {
        /*
        |--------------------------------------------------------------------------
        | Cegah double posting jurnal
        |--------------------------------------------------------------------------
        */
        $cek_log = LogJurnalSumberORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_hasil_produksi')
            ->where('id_sumber', $id_hasil_produksi)
            ->first();

        if ($cek_log) {
            throw new RuntimeException('Hasil produksi ini sudah pernah dibuatkan jurnal.');
        }

        /*
        |--------------------------------------------------------------------------
        | Periode akuntansi
        |--------------------------------------------------------------------------
        */
        $periode = Capsule::table('tb_periode_akuntansi')
            ->where('id_entitas', $id_entitas)
            ->where('status_periode', 'terbuka')
            ->whereDate('tanggal_mulai', '<=', $row->tanggal_hasil)
            ->whereDate('tanggal_selesai', '>=', $row->tanggal_hasil)
            ->first();

        if (!$periode) {
            throw new RuntimeException('Periode akuntansi untuk tanggal hasil produksi belum dibuka.');
        }

        /*
        |--------------------------------------------------------------------------
        | Akun jurnal
        |--------------------------------------------------------------------------
        | Debit:
        |   Persediaan produk jadi / persediaan produk dari master produk.
        |
        | Kredit:
        |   Diutamakan dari template jurnal HASIL_PRODUKSI.
        |
        | Jika template tidak ada:
        |   fallback ke id_coa_hpp produk supaya sistem tetap jalan,
        |   tetapi secara akuntansi sebaiknya siapkan template jurnal khusus
        |   untuk akun Barang Dalam Proses / Ikhtisar Biaya Produksi.
        |--------------------------------------------------------------------------
        */
        $kode_jenis_transaksi = 'HASIL_PRODUKSI';

        $akun_debit = (int) ($produk->id_coa_persediaan ?? 0);
        $akun_kredit = 0;

        $template = Capsule::table('tb_template_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('kode_jenis_transaksi', $kode_jenis_transaksi)
            ->where('status_aktif', 1)
            ->first();

        if ($template) {
            $detail_template = Capsule::table('tb_template_jurnal_detail')
                ->where('id_template_jurnal', $template->id_template_jurnal)
                ->orderBy('urutan', 'asc')
                ->get();

            foreach ($detail_template as $dt) {
                if ((string) $dt->posisi_dc === 'debit' && $akun_debit <= 0) {
                    $akun_debit = (int) $dt->id_coa_default;
                }

                if ((string) $dt->posisi_dc === 'kredit' && $akun_kredit <= 0) {
                    $akun_kredit = (int) $dt->id_coa_default;
                }
            }
        }

        if ($akun_debit <= 0) {
            throw new RuntimeException('Akun persediaan produk belum diset di master produk atau template jurnal.');
        }

        if ($akun_kredit <= 0) {
            $akun_kredit = (int) ($produk->id_coa_hpp ?? 0);
        }

        if ($akun_kredit <= 0) {
            throw new RuntimeException('Akun kredit hasil produksi belum tersedia. Siapkan template jurnal HASIL_PRODUKSI atau isi akun HPP produk.');
        }

        /*
        |--------------------------------------------------------------------------
        | Update / insert saldo stok produk
        |--------------------------------------------------------------------------
        */
        $saldo = Capsule::table('tb_saldo_stok')
            ->where('id_entitas', $id_entitas)
            ->where('jenis_barang', 'produk')
            ->where('id_referensi_barang', (int) $row->id_produk)
            ->where('id_gudang', (int) $row->id_gudang)
            ->lockForUpdate()
            ->first();

        if ($saldo) {
            $qty_lama = (float) $saldo->qty_saldo;
            $nilai_lama = (float) $saldo->nilai_saldo;

            $qty_baru = $qty_lama + $qty_hasil;
            $nilai_baru = $nilai_lama + $total_hpp;
            $hpp_rata_baru = $qty_baru > 0 ? ($nilai_baru / $qty_baru) : $hpp_per_unit;

            Capsule::table('tb_saldo_stok')
                ->where('id_saldo_stok', $saldo->id_saldo_stok)
                ->update([
                    'qty_saldo'       => round($qty_baru, 3),
                    'nilai_saldo'     => round($nilai_baru, 2),
                    'hpp_rata_rata'   => round($hpp_rata_baru, 2),
                    'tanggal_update'  => date('Y-m-d H:i:s'),
                    'tanggal_diubah'  => date('Y-m-d H:i:s'),
                    'diubah_oleh'     => $id_pengguna > 0 ? $id_pengguna : null,
                ]);
        } else {
            Capsule::table('tb_saldo_stok')->insert([
                'id_entitas'           => $id_entitas,
                'jenis_barang'         => 'produk',
                'id_referensi_barang'  => (int) $row->id_produk,
                'id_gudang'            => (int) $row->id_gudang,
                'qty_saldo'            => round($qty_hasil, 3),
                'nilai_saldo'          => round($total_hpp, 2),
                'hpp_rata_rata'        => round($hpp_per_unit, 2),
                'tanggal_update'       => date('Y-m-d H:i:s'),
                'tanggal_dibuat'       => date('Y-m-d H:i:s'),
                'dibuat_oleh'          => $id_pengguna > 0 ? $id_pengguna : null,
                'tanggal_diubah'       => null,
                'diubah_oleh'          => null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Mutasi stok masuk produk
        |--------------------------------------------------------------------------
        */
        Capsule::table('tb_mutasi_stok')->insert([
            'id_entitas'           => $id_entitas,
            'tanggal_mutasi'       => $row->tanggal_hasil,
            'jenis_barang'         => 'produk',
            'id_referensi_barang'  => (int) $row->id_produk,
            'id_gudang'            => (int) $row->id_gudang,
            'jenis_mutasi'         => 'masuk',
            'qty_masuk'            => round($qty_hasil, 3),
            'qty_keluar'           => 0,
            'harga_satuan'         => round($hpp_per_unit, 2),
            'nilai_total'          => round($total_hpp, 2),
            'tabel_sumber'         => 'tb_hasil_produksi',
            'id_sumber'            => $id_hasil_produksi,
            'no_sumber'            => $row->no_hasil_produksi,
            'keterangan'           => 'Hasil produksi ' . $row->no_hasil_produksi,
            'tanggal_dibuat'       => date('Y-m-d H:i:s'),
            'dibuat_oleh'          => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'       => null,
            'diubah_oleh'          => null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Jurnal hasil produksi
        |--------------------------------------------------------------------------
        */
        $no_jurnal = generate_kode_master(
            'tb_jurnal',
            'no_jurnal',
            'JRN',
            4,
            $id_entitas
        );

        $jurnal = JurnalORM::create([
            'id_entitas'             => $id_entitas,
            'no_jurnal'              => $no_jurnal,
            'tanggal_jurnal'         => $row->tanggal_hasil,
            'id_periode'             => $periode->id_periode,
            'kode_jenis_transaksi'   => $kode_jenis_transaksi,
            'keterangan'             => 'Posting hasil produksi ' . $row->no_hasil_produksi,
            'tabel_sumber'           => 'tb_hasil_produksi',
            'id_sumber'              => $id_hasil_produksi,
            'no_sumber'              => $row->no_hasil_produksi,
            'status_jurnal'          => 'posted',
            'total_debit'            => $total_hpp,
            'total_kredit'           => $total_hpp,
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
            'dibuat_oleh'            => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_posting'        => date('Y-m-d H:i:s'),
            'diposting_oleh'         => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'         => null,
            'diubah_oleh'            => null,
        ]);

        JurnalDetailORM::create([
            'id_jurnal'        => $jurnal->id_jurnal,
            'urutan'           => 1,
            'id_coa'           => $akun_debit,
            'debit'            => $total_hpp,
            'kredit'           => 0,
            'keterangan_baris' => 'Persediaan produk jadi - ' . $row->no_hasil_produksi,
            'id_pelanggan'     => null,
            'id_pemasok'       => null,
            'id_produk'        => (int) $row->id_produk,
            'id_bahan_baku'    => null,
            'id_gudang'        => (int) $row->id_gudang,
        ]);

        JurnalDetailORM::create([
            'id_jurnal'        => $jurnal->id_jurnal,
            'urutan'           => 2,
            'id_coa'           => $akun_kredit,
            'debit'            => 0,
            'kredit'           => $total_hpp,
            'keterangan_baris' => 'Pemindahan biaya produksi ke produk jadi - ' . $row->no_hasil_produksi,
            'id_pelanggan'     => null,
            'id_pemasok'       => null,
            'id_produk'        => (int) $row->id_produk,
            'id_bahan_baku'    => null,
            'id_gudang'        => (int) $row->id_gudang,
        ]);

        LogJurnalSumberORM::create([
            'id_entitas'             => $id_entitas,
            'id_jurnal'              => $jurnal->id_jurnal,
            'tabel_sumber'           => 'tb_hasil_produksi',
            'id_sumber'              => $id_hasil_produksi,
            'no_sumber'              => $row->no_hasil_produksi,
            'kode_jenis_transaksi'   => $kode_jenis_transaksi,
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update hasil produksi dan perintah produksi
        |--------------------------------------------------------------------------
        */
        $row->update([
            'status_posting'   => 'posted',
            'tanggal_posting'  => date('Y-m-d H:i:s'),
            'diposting_oleh'   => $id_pengguna > 0 ? $id_pengguna : null,
            'tanggal_diubah'   => date('Y-m-d H:i:s'),
            'diubah_oleh'      => $id_pengguna > 0 ? $id_pengguna : null,
        ]);

        $perintah->update([
            'qty_hasil'       => $qty_hasil,
            'tanggal_selesai' => $row->tanggal_hasil,
            'tanggal_diubah'  => date('Y-m-d H:i:s'),
            'diubah_oleh'     => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Hasil produksi berhasil diposting. Stok produk jadi bertambah dan jurnal otomatis sudah dibuat.');
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    header('Location: ' . admin_url('index.php?menu=produksi/hasil/detail&id=' . $id_hasil_produksi . '&back_url=' . urlencode($back_url)));
    exit;
}