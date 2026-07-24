<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();
$user = user_login();
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pengguna = (int) ($user['id_pengguna'] ?? 0);
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!function_exists('po_verif_periode_terbuka')) {
    function po_verif_periode_terbuka(int $id_entitas, string $tanggal): object
    {
        $periode = Capsule::table('tb_periode_akuntansi')
            ->where('id_entitas', $id_entitas)
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->whereIn('status_periode', ['terbuka', 'aktif'])
            ->first();
        if (!$periode) {
            throw new RuntimeException('Periode akuntansi tanggal ' . $tanggal . ' belum terbuka. Pembayaran tidak dapat dijurnal.');
        }
        return $periode;
    }
}

if (!function_exists('po_verif_no_jurnal')) {
    function po_verif_no_jurnal(int $id_entitas): string
{
    $prefix = 'JRN-';
    $rows = Capsule::table('tb_jurnal')
        ->where('id_entitas', $id_entitas)
        ->where('no_jurnal', 'like', $prefix . '%')
        ->pluck('no_jurnal');

    $max = 0;
    foreach ($rows as $code) {
        if (preg_match('/^JRN-(\d+)$/', (string) $code, $matches)) {
            $max = max($max, (int) $matches[1]);
        }
    }

    return $prefix . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
}
}

if (!function_exists('po_verif_akun_kas_bank_online')) {
    function po_verif_akun_kas_bank_online(int $id_entitas): int
    {
        $akun = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->where(function ($q) {
                $q->where('is_akun_qris', 1)
                  ->orWhereNotNull('qris_image_path')
                  ->orWhereNotNull('nomor_rekening')
                  ->orWhere('kode_coa', '1120')
                  ->orWhere('kode_coa', '1110');
            })
            ->orderByRaw("CASE WHEN is_akun_qris = 1 THEN 0 WHEN qris_image_path IS NOT NULL THEN 1 WHEN nomor_rekening IS NOT NULL THEN 2 WHEN kode_coa = '1120' THEN 3 ELSE 4 END")
            ->first();
        if (!$akun) {
            throw new RuntimeException('Akun kas/bank/QRIS belum disetting di Mapping Akun > Rekening / QRIS.');
        }
        return (int) $akun->id_coa;
    }
}

if (!function_exists('po_verif_akun_pendapatan_diterima_dimuka')) {
    function po_verif_akun_pendapatan_diterima_dimuka(int $id_entitas): int
    {
        $akun = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->where(function ($q) {
                $q->where('nama_coa', 'like', '%Pendapatan Diterima Dimuka%')
                  ->orWhere('kode_coa', '2150');
            })
            ->first();
        if (!$akun) {
            $akun = Capsule::table('tb_coa')
                ->where('id_entitas', $id_entitas)
                ->where('kategori_coa', 'liabilitas')
                ->where('boleh_transaksi', 1)
                ->where('status_aktif', 1)
                ->orderBy('kode_coa')
                ->first();
        }
        if (!$akun) {
            throw new RuntimeException('Akun Pendapatan Diterima Dimuka / Liabilitas belum tersedia di COA.');
        }
        return (int) $akun->id_coa;
    }
}

if (!function_exists('po_verif_buat_jurnal_uang_masuk')) {
    function po_verif_buat_jurnal_uang_masuk(object $pesanan, int $id_entitas, int $id_pengguna): void
    {
        $nominal = (float) ($pesanan->nominal_pembayaran_online ?: $pesanan->total ?: 0);
        if ($nominal <= 0) {
            throw new RuntimeException('Nominal pembayaran online masih nol.');
        }
        $kode = 'PEMBAYARAN_ONLINE_DITERIMA';
        $sudah = Capsule::table('tb_log_jurnal_sumber')
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_pesanan_penjualan')
            ->where('id_sumber', (int) $pesanan->id_pesanan_penjualan)
            ->where('kode_jenis_transaksi', $kode)
            ->exists();
        if ($sudah) {
            return;
        }
        $tanggal = date('Y-m-d');
        $periode = po_verif_periode_terbuka($id_entitas, $tanggal);
        $idCoaDebit = po_verif_akun_kas_bank_online($id_entitas);
        $idCoaKredit = po_verif_akun_pendapatan_diterima_dimuka($id_entitas);
        $idJurnal = (int) Capsule::table('tb_jurnal')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_jurnal' => po_verif_no_jurnal($id_entitas),
            'tanggal_jurnal' => $tanggal,
            'id_periode' => (int) $periode->id_periode,
            'kode_jenis_transaksi' => $kode,
            'keterangan' => 'Uang masuk pesanan online ' . (string) $pesanan->no_pesanan_penjualan,
            'tabel_sumber' => 'tb_pesanan_penjualan',
            'id_sumber' => (int) $pesanan->id_pesanan_penjualan,
            'no_sumber' => (string) $pesanan->no_pesanan_penjualan,
            'status_jurnal' => 'posted',
            'total_debit' => $nominal,
            'total_kredit' => $nominal,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
            'tanggal_posting' => date('Y-m-d H:i:s'),
            'diposting_oleh' => $id_pengguna ?: null,
        ]);
        Capsule::table('tb_jurnal_detail')->insert([
            'id_jurnal' => $idJurnal,
            'urutan' => 1,
            'id_coa' => $idCoaDebit,
            'debit' => $nominal,
            'kredit' => 0,
            'keterangan_baris' => 'Kas/Bank/QRIS diterima dari customer',
            'id_pelanggan' => (int) $pesanan->id_pelanggan,
            'id_pemasok' => null,
            'id_produk' => null,
            'id_bahan_baku' => null,
            'id_gudang' => null,
        ]);
        Capsule::table('tb_jurnal_detail')->insert([
            'id_jurnal' => $idJurnal,
            'urutan' => 2,
            'id_coa' => $idCoaKredit,
            'debit' => 0,
            'kredit' => $nominal,
            'keterangan_baris' => 'Uang muka/pendapatan diterima dimuka pesanan online',
            'id_pelanggan' => (int) $pesanan->id_pelanggan,
            'id_pemasok' => null,
            'id_produk' => null,
            'id_bahan_baku' => null,
            'id_gudang' => null,
        ]);
        Capsule::table('tb_log_jurnal_sumber')->insert([
            'id_entitas' => $id_entitas,
            'id_jurnal' => $idJurnal,
            'tabel_sumber' => 'tb_pesanan_penjualan',
            'id_sumber' => (int) $pesanan->id_pesanan_penjualan,
            'no_sumber' => (string) $pesanan->no_pesanan_penjualan,
            'kode_jenis_transaksi' => $kode,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
        ]);
    }
}

try {
    if ($id <= 0) throw new RuntimeException('ID pesanan tidak valid.');
    $pesanan = Capsule::table('tb_pesanan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('id_pesanan_penjualan', $id)
        ->where('sumber_pesanan', 'website')
        ->first();
    if (!$pesanan) throw new RuntimeException('Pesanan online tidak ditemukan.');
    if (in_array((string) ($pesanan->status_pembayaran_online ?? ''), ['lunas', 'paid', 'settlement'], true)) {
        set_flash('success', 'Pembayaran sudah berstatus lunas. Tombol uang masuk tidak perlu diklik lagi.');
        redirect_admin('penjualan/pesanan-online/detail&id=' . $id);
    }

    Capsule::connection()->transaction(function () use ($id, $id_entitas, $id_pengguna, $pesanan) {
        if (Capsule::schema()->hasTable('tb_pembayaran_online_bukti')) {
            Capsule::table('tb_pembayaran_online_bukti')
                ->where('id_entitas', $id_entitas)
                ->where('id_pesanan_penjualan', $id)
                ->where('status_verifikasi', 'menunggu_verifikasi')
                ->update([
                    'status_verifikasi' => 'lunas',
                    'catatan_admin' => 'Pembayaran diverifikasi oleh admin. Uang sudah masuk.',
                    'diverifikasi_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
                    'tanggal_verifikasi' => date('Y-m-d H:i:s'),
                ]);
        }
        po_verif_buat_jurnal_uang_masuk($pesanan, $id_entitas, $id_pengguna);
        $update = [
            'status_pembayaran_online' => 'lunas',
            'nominal_pembayaran_online' => (float) ($pesanan->nominal_pembayaran_online ?: $pesanan->total ?: 0),
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
        ];
        if ((string) ($pesanan->status_pesanan ?? '') === 'draft') {
            $update['status_pesanan'] = 'terkonfirmasi';
        }
        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id)
            ->update($update);
    });
    set_flash('success', 'Pembayaran berhasil diverifikasi. Jurnal kas/bank bertambah sebagai uang muka pesanan. HPP dan persediaan baru berkurang saat penyerahan barang diposting.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal verifikasi pembayaran: ' . $e->getMessage());
}
redirect_admin('penjualan/pesanan-online/detail&id=' . $id);
