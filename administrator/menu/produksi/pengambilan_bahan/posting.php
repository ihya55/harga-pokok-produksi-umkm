<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/PengambilanBahanORM.php';
require_once __DIR__ . '/../../../../orm/PengambilanBahanDetailORM.php';
require_once __DIR__ . '/../../../../orm/JurnalORM.php';
require_once __DIR__ . '/../../../../orm/JurnalDetailORM.php';
require_once __DIR__ . '/../../../../orm/LogJurnalSumberORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_pengambilan_bahan = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/pengambilan-bahan');
}

$row = PengambilanBahanORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pengambilan_bahan);

if (!$row) {
    set_flash('error', 'Data pengambilan bahan tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'diperiksa') {
    set_flash('error', 'Pengambilan bahan hanya bisa diposting setelah status diperiksa oleh gudang.');
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
}

$detail_rows = PengambilanBahanDetailORM::query()
    ->from('tb_pengambilan_bahan_detail as pbd')
    ->leftJoin('tb_bahan_baku as b', 'b.id_bahan_baku', '=', 'pbd.id_bahan_baku')
    ->where('pbd.id_pengambilan_bahan', $id_pengambilan_bahan)
    ->select([
        'pbd.*',
        'b.kode_bahan_baku',
        'b.nama_bahan_baku',
    ])
    ->orderBy('pbd.id_pengambilan_bahan_detail', 'asc')
    ->get();

if ($detail_rows->count() === 0) {
    set_flash('error', 'Detail bahan belum ada.');
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
}

$total_bahan = 0;

// Pastikan nilai bahan mengikuti HPP rata-rata saldo stok di gudang.
// Ini mencegah HPP produksi menjadi 0 ketika harga standar bahan baku belum diisi,
// tetapi bahan sudah masuk melalui pembelian dengan harga aktual.
foreach ($detail_rows as $detail) {
    $saldo_hpp = Capsule::table('tb_saldo_stok')
        ->where('id_entitas', $id_entitas)
        ->where('jenis_barang', 'bahan_baku')
        ->where('id_referensi_barang', (int) $detail->id_bahan_baku)
        ->where('id_gudang', (int) $row->id_gudang)
        ->value('hpp_rata_rata');

    $harga_pakai = (float) ($saldo_hpp ?: $detail->harga_satuan);
    $subtotal_pakai = round((float) $detail->qty * $harga_pakai, 2);

    $detail->harga_satuan = round($harga_pakai, 2);
    $detail->subtotal = $subtotal_pakai;
    $total_bahan += $subtotal_pakai;
}

if ($total_bahan <= 0) {
    set_flash('error', 'Total nilai bahan harus lebih besar dari 0. Pastikan penerimaan pembelian sudah diposting dan harga bahan tidak 0.');
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
}

/*
|--------------------------------------------------------------------------
| Cek stok ulang sebelum posting
|--------------------------------------------------------------------------
| Walaupun sudah diperiksa, stok tetap wajib dicek ulang saat posting
| karena stok bisa berubah setelah pemeriksaan.
|--------------------------------------------------------------------------
*/
$kurang = [];

foreach ($detail_rows as $detail) {
    $saldo = Capsule::table('tb_saldo_stok')
            ->where('id_entitas', $id_entitas)
            ->where('jenis_barang', 'bahan_baku')
            ->where('id_referensi_barang', (int) $detail->id_bahan_baku)
            ->where('id_gudang', (int) $row->id_gudang)
            ->first();

    $qty_saldo = $saldo ? (int) round((float) $saldo->qty_saldo) : 0;
    $qty_keluar = (int) round((float) $detail->qty);

    if ($qty_saldo < $qty_keluar) {
        $kurang[] = [
            'bahan' => (($detail->kode_bahan_baku ?? '-') . ' - ' . ($detail->nama_bahan_baku ?? '-')),
            'stok' => $qty_saldo,
            'butuh' => $qty_keluar,
        ];
    }
}

if (count($kurang) > 0) {
    $bagian = [];

    foreach ($kurang as $item) {
        $bagian[] = $item['bahan'] . ' stok ' . number_format((int) $item['stok'], 0, '.', ',') . ', butuh ' . number_format((int) $item['butuh'], 0, '.', ',');
    }

    set_flash('error', 'Posting ditolak. Stok tidak cukup: ' . implode(' | ', $bagian));
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $detail_rows,
        $id_entitas,
        $id_pengguna,
        $id_pengambilan_bahan,
        $total_bahan
    ) {
        /*
        |--------------------------------------------------------------------------
        | Cegah double posting jurnal
        |--------------------------------------------------------------------------
        */
        $cek_log = LogJurnalSumberORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_pengambilan_bahan')
            ->where('id_sumber', $id_pengambilan_bahan)
            ->first();

        if ($cek_log) {
            throw new RuntimeException('Pengambilan bahan ini sudah pernah dibuatkan jurnal.');
        }

        /*
        |--------------------------------------------------------------------------
        | Periode akuntansi
        |--------------------------------------------------------------------------
        */
        $periode = Capsule::table('tb_periode_akuntansi')
            ->where('id_entitas', $id_entitas)
            ->where('status_periode', 'terbuka')
            ->whereDate('tanggal_mulai', '<=', $row->tanggal_pengambilan)
            ->whereDate('tanggal_selesai', '>=', $row->tanggal_pengambilan)
            ->first();

        if (!$periode) {
            throw new RuntimeException('Periode akuntansi untuk tanggal pengambilan belum dibuka.');
        }

        /*
        |--------------------------------------------------------------------------
        | Template jurnal
        |--------------------------------------------------------------------------
        | Utamakan template jurnal jika tersedia.
        | Kode transaksi disarankan: PENGAMBILAN_BAHAN_PRODUKSI
        |--------------------------------------------------------------------------
        */
        $kode_jenis_transaksi = 'PENGAMBILAN_BAHAN_PRODUKSI';

        $template = Capsule::table('tb_template_jurnal')
            ->where('id_entitas', $id_entitas)
            ->where('kode_jenis_transaksi', $kode_jenis_transaksi)
            ->where('status_aktif', 1)
            ->first();

        $akun_debit = null;
        $akun_kredit = null;

        if ($template) {
            $detail_template = Capsule::table('tb_template_jurnal_detail')
                ->where('id_template_jurnal', $template->id_template_jurnal)
                ->orderBy('urutan', 'asc')
                ->get();

            foreach ($detail_template as $dt) {
                if ((string) $dt->posisi_dc === 'debit' && !$akun_debit) {
                    $akun_debit = (int) $dt->id_coa_default;
                }

                if ((string) $dt->posisi_dc === 'kredit' && !$akun_kredit) {
                    $akun_kredit = (int) $dt->id_coa_default;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback COA jika template belum ada
        |--------------------------------------------------------------------------
        | Berdasarkan COA yang sebelumnya dipakai:
        | 5100 = Bahan Baku Terpakai
        | 1140 = Persediaan Bahan Baku
        |--------------------------------------------------------------------------
        */
        if (!$akun_debit) {
            $akun_debit = (int) Capsule::table('tb_coa')
                ->where('id_entitas', $id_entitas)
                ->where('kode_coa', '5100')
                ->value('id_coa');
        }

        if (!$akun_kredit) {
            $akun_kredit = (int) Capsule::table('tb_coa')
                ->where('id_entitas', $id_entitas)
                ->where('kode_coa', '1140')
                ->value('id_coa');
        }

        if ($akun_debit <= 0 || $akun_kredit <= 0) {
            throw new RuntimeException('Akun jurnal pengambilan bahan belum lengkap. Pastikan template jurnal atau COA 5100 dan 1140 tersedia.');
        }

        /*
        |--------------------------------------------------------------------------
        | Mutasi stok dan saldo stok
        |--------------------------------------------------------------------------
        */
        foreach ($detail_rows as $detail) {
            $saldo = Capsule::table('tb_saldo_stok')
                    ->where('id_entitas', $id_entitas)
                    ->where('jenis_barang', 'bahan_baku')
                    ->where('id_referensi_barang', (int) $detail->id_bahan_baku)
                    ->where('id_gudang', (int) $row->id_gudang)
                    ->lockForUpdate()
                    ->first();

            $qty_saldo = $saldo ? (int) round((float) $saldo->qty_saldo) : 0;
            $qty_keluar = (int) round((float) $detail->qty);

            if ($qty_saldo < $qty_keluar) {
                throw new RuntimeException(
                    'Stok tidak cukup untuk ' .
                    (($detail->kode_bahan_baku ?? '-') . ' - ' . ($detail->nama_bahan_baku ?? '-')) .
                    '. Stok: ' . number_format((int) $qty_saldo, 0, '.', ',') .
                    ', butuh: ' . number_format((int) $qty_keluar, 0, '.', ',')
                );
            }

            $qty_akhir = $qty_saldo - $qty_keluar;
            $harga_pakai = (float) ($saldo->hpp_rata_rata ?? $detail->harga_satuan);
            if ($harga_pakai <= 0) {
                $harga_pakai = (float) $detail->harga_satuan;
            }
            $nilai_keluar = round($qty_keluar * $harga_pakai, 2);

            Capsule::table('tb_pengambilan_bahan_detail')
                ->where('id_pengambilan_bahan_detail', (int) $detail->id_pengambilan_bahan_detail)
                ->update([
                    'harga_satuan' => round($harga_pakai, 2),
                    'subtotal' => $nilai_keluar,
                ]);

            Capsule::table('tb_mutasi_stok')->insert([
                    'id_entitas'           => $id_entitas,
                    'tanggal_mutasi'       => $row->tanggal_pengambilan,
                    'jenis_barang'         => 'bahan_baku',
                    'id_referensi_barang'  => (int) $detail->id_bahan_baku,
                    'id_gudang'            => (int) $row->id_gudang,
                    'jenis_mutasi'         => 'keluar',
                    'qty_masuk'            => 0,
                    'qty_keluar'           => $qty_keluar,
                    'harga_satuan' => round($harga_pakai, 2),
                    'nilai_total'  => $nilai_keluar,
                    'tabel_sumber'         => 'tb_pengambilan_bahan',
                    'id_sumber'            => $id_pengambilan_bahan,
                    'no_sumber'            => $row->no_pengambilan_bahan,
                    'keterangan'           => 'Pengambilan bahan produksi ' . $row->no_pengambilan_bahan,
                    'tanggal_dibuat'       => date('Y-m-d H:i:s'),
                    'dibuat_oleh'          => $id_pengguna > 0 ? $id_pengguna : null,
                    'tanggal_diubah'       => null,
                    'diubah_oleh'          => null,
                ]);

            Capsule::table('tb_saldo_stok')
                    ->where('id_saldo_stok', $saldo->id_saldo_stok)
                    ->update([
                        'qty_saldo'      => $qty_akhir,
                        'nilai_saldo'    => round($qty_akhir * $harga_pakai, 2),
                        'hpp_rata_rata'  => round($harga_pakai, 2),
                        'tanggal_update' => date('Y-m-d H:i:s'),
                        'tanggal_diubah' => date('Y-m-d H:i:s'),
                        'diubah_oleh'    => $id_pengguna > 0 ? $id_pengguna : null,
                    ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Jurnal
        |--------------------------------------------------------------------------
        */
        $no_jurnal = generate_kode_master(
            'tb_jurnal',
            'no_jurnal',
            'JRN',
            4,
            $id_entitas
        );

        $jumlah = round((float) $total_bahan, 2);

        $jurnal = JurnalORM::create([
            'id_entitas'             => $id_entitas,
            'no_jurnal'              => $no_jurnal,
            'tanggal_jurnal'         => $row->tanggal_pengambilan,
            'id_periode'             => $periode->id_periode,
            'kode_jenis_transaksi'   => $kode_jenis_transaksi,
            'keterangan'             => 'Posting pengambilan bahan ' . $row->no_pengambilan_bahan,
            'tabel_sumber'           => 'tb_pengambilan_bahan',
            'id_sumber'              => $id_pengambilan_bahan,
            'no_sumber'              => $row->no_pengambilan_bahan,
            'status_jurnal'          => 'posted',
            'total_debit'            => $jumlah,
            'total_kredit'           => $jumlah,
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
            'debit'            => $jumlah,
            'kredit'           => 0,
            'keterangan_baris' => 'Bahan baku terpakai - ' . $row->no_pengambilan_bahan,
            'id_pelanggan'     => null,
            'id_pemasok'       => null,
            'id_produk'        => null,
            'id_bahan_baku'    => null,
            'id_gudang'        => (int) $row->id_gudang,
        ]);

        JurnalDetailORM::create([
            'id_jurnal'        => $jurnal->id_jurnal,
            'urutan'           => 2,
            'id_coa'           => $akun_kredit,
            'debit'            => 0,
            'kredit'           => $jumlah,
            'keterangan_baris' => 'Persediaan bahan baku keluar - ' . $row->no_pengambilan_bahan,
            'id_pelanggan'     => null,
            'id_pemasok'       => null,
            'id_produk'        => null,
            'id_bahan_baku'    => null,
            'id_gudang'        => (int) $row->id_gudang,
        ]);

        LogJurnalSumberORM::create([
            'id_entitas'             => $id_entitas,
            'id_jurnal'              => $jurnal->id_jurnal,
            'tabel_sumber'           => 'tb_pengambilan_bahan',
            'id_sumber'              => $id_pengambilan_bahan,
            'no_sumber'              => $row->no_pengambilan_bahan,
            'kode_jenis_transaksi'   => $kode_jenis_transaksi,
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
        ]);

        $row->update([
            'status_posting' => 'posted',
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh'    => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Pengambilan bahan berhasil diposting. Stok bahan keluar dan jurnal otomatis sudah dibuat.');
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    header('Location: ' . admin_url('index.php?menu=produksi/pengambilan-bahan/detail&id=' . $id_pengambilan_bahan . '&back_url=' . urlencode($back_url)));
    exit;
}