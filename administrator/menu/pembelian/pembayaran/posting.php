<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/PembayaranPembelianORM.php';
require_once __DIR__ . '/../../../../orm/FakturPembelianORM.php';
require_once __DIR__ . '/../../../../orm/JurnalORM.php';
require_once __DIR__ . '/../../../../orm/JurnalDetailORM.php';
require_once __DIR__ . '/../../../../orm/LogJurnalSumberORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_pembayaran_pembelian = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_url('index.php?menu=pembelian/pembayaran');
}

$row = PembayaranPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pembayaran_pembelian);

if (!$row) {
    set_flash('error', 'Data pembayaran pembelian tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Hanya pembayaran draft yang bisa diposting.');
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
}

$faktur = FakturPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_faktur_pembelian', (int) $row->id_faktur_pembelian)
    ->first();

if (!$faktur) {
    set_flash('error', 'Faktur pembelian tidak ditemukan.');
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
}

if ((string) $faktur->status_faktur !== 'posted') {
    set_flash('error', 'Faktur pembelian belum diposting.');
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
}

if ((float) $row->jumlah_bayar <= 0 || (float) $row->jumlah_bayar > (float) $faktur->sisa_utang) {
    set_flash('error', 'Jumlah bayar tidak valid atau melebihi sisa utang faktur.');
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
}

try {
    Capsule::connection()->transaction(function () use (
        $row,
        $faktur,
        $id_entitas,
        $id_pengguna,
        $id_pembayaran_pembelian
    ) {
        $periode = Capsule::table('tb_periode_akuntansi')
            ->where('id_entitas', $id_entitas)
            ->where('status_periode', 'terbuka')
            ->whereDate('tanggal_mulai', '<=', $row->tanggal_pembayaran)
            ->whereDate('tanggal_selesai', '>=', $row->tanggal_pembayaran)
            ->first();

        if (!$periode) {
            throw new RuntimeException('Periode akuntansi untuk tanggal pembayaran belum dibuka.');
        }

        $konfig_utang = Capsule::table('tb_konfigurasi_akun')
            ->where('id_entitas', $id_entitas)
            ->where('kode_konfigurasi', 'utang_usaha')
            ->where('status_aktif', 1)
            ->first();

        if (!$konfig_utang || empty($konfig_utang->id_coa)) {
            throw new RuntimeException('Konfigurasi akun utang usaha belum tersedia.');
        }

        $cek_log = LogJurnalSumberORM::query()
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_pembayaran_pembelian')
            ->where('id_sumber', $id_pembayaran_pembelian)
            ->where('kode_jenis_transaksi', 'PEMBAYARAN_PEMBELIAN')
            ->first();

        if ($cek_log) {
            throw new RuntimeException('Pembayaran ini sudah pernah dibuatkan jurnal.');
        }

        $jumlah = round((float) $row->jumlah_bayar, 2);

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
            'tanggal_jurnal'         => $row->tanggal_pembayaran,
            'id_periode'             => $periode->id_periode,
            'kode_jenis_transaksi'   => 'PEMBAYARAN_PEMBELIAN',
            'keterangan'             => 'Posting pembayaran pembelian ' . $row->no_pembayaran_pembelian,
            'tabel_sumber'           => 'tb_pembayaran_pembelian',
            'id_sumber'              => $id_pembayaran_pembelian,
            'no_sumber'              => $row->no_pembayaran_pembelian,
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
            'id_jurnal'          => $jurnal->id_jurnal,
            'urutan'             => 1,
            'id_coa'             => (int) $konfig_utang->id_coa,
            'debit'              => $jumlah,
            'kredit'             => 0,
            'keterangan_baris'   => 'Pembayaran utang usaha faktur pembelian',
            'id_pelanggan'       => null,
            'id_pemasok'         => (int) $row->id_pemasok,
            'id_produk'          => null,
            'id_bahan_baku'      => null,
            'id_gudang'          => null,
        ]);

        JurnalDetailORM::create([
            'id_jurnal'          => $jurnal->id_jurnal,
            'urutan'             => 2,
            'id_coa'             => (int) $row->id_coa_kas_bank,
            'debit'              => 0,
            'kredit'             => $jumlah,
            'keterangan_baris'   => 'Kas/bank keluar untuk pembayaran pembelian',
            'id_pelanggan'       => null,
            'id_pemasok'         => (int) $row->id_pemasok,
            'id_produk'          => null,
            'id_bahan_baku'      => null,
            'id_gudang'          => null,
        ]);

        LogJurnalSumberORM::create([
            'id_entitas'             => $id_entitas,
            'id_jurnal'              => $jurnal->id_jurnal,
            'tabel_sumber'           => 'tb_pembayaran_pembelian',
            'id_sumber'              => $id_pembayaran_pembelian,
            'no_sumber'              => $row->no_pembayaran_pembelian,
            'kode_jenis_transaksi'   => 'PEMBAYARAN_PEMBELIAN',
            'tanggal_dibuat'         => date('Y-m-d H:i:s'),
        ]);

        $sisa_baru = round((float) $faktur->sisa_utang - $jumlah, 2);
        if ($sisa_baru < 0) $sisa_baru = 0;

        $faktur->update([
            'sisa_utang'       => $sisa_baru,
            'tanggal_diubah'   => date('Y-m-d H:i:s'),
            'diubah_oleh'      => $id_pengguna > 0 ? $id_pengguna : null,
        ]);

        $row->update([
            'status_posting'   => 'posted',
            'tanggal_diubah'   => date('Y-m-d H:i:s'),
            'diubah_oleh'      => $id_pengguna > 0 ? $id_pengguna : null,
        ]);
    });

    set_flash('success', 'Pembayaran pembelian berhasil diposting. Sisa utang faktur sudah diperbarui.');
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    header('Location: ' . admin_url('index.php?menu=pembelian/pembayaran/detail&id=' . $id_pembayaran_pembelian . '&back_url=' . urlencode($back_url)));
    exit;
}