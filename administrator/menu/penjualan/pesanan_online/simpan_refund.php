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
$id = (int) ($_POST['id_pesanan_penjualan'] ?? 0);
$nominalRefund = (float) str_replace([',', '.'], ['', '.'], (string) ($_POST['nominal_refund'] ?? 0));
$catatanRefund = trim((string) ($_POST['catatan_refund'] ?? ''));

function po_refund_no_jurnal(int $id_entitas): string
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

function po_refund_periode(int $id_entitas, string $tanggal): object
{
    $periode = Capsule::table('tb_periode_akuntansi')
        ->where('id_entitas', $id_entitas)
        ->where('tanggal_mulai', '<=', $tanggal)
        ->where('tanggal_selesai', '>=', $tanggal)
        ->whereIn('status_periode', ['terbuka', 'aktif'])
        ->first();
    if (!$periode) throw new RuntimeException('Periode akuntansi tanggal ' . $tanggal . ' belum terbuka.');
    return $periode;
}

function po_refund_akun_kas_bank(int $id_entitas): int
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
    if (!$akun) throw new RuntimeException('Akun kas/bank/QRIS belum disetting.');
    return (int) $akun->id_coa;
}

function po_refund_akun_pdd(int $id_entitas): int
{
    $akun = Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('boleh_transaksi', 1)
        ->where('status_aktif', 1)
        ->where(function ($q) {
            $q->where('nama_coa', 'like', '%Pendapatan Diterima Dimuka%')->orWhere('kode_coa', '2150');
        })->first();
    if (!$akun) {
        $akun = Capsule::table('tb_coa')->where('id_entitas', $id_entitas)->where('kategori_coa', 'liabilitas')->where('boleh_transaksi', 1)->where('status_aktif', 1)->orderBy('kode_coa')->first();
    }
    if (!$akun) throw new RuntimeException('Akun Pendapatan Diterima Dimuka / Liabilitas belum tersedia.');
    return (int) $akun->id_coa;
}

try {
    if ($id <= 0) throw new RuntimeException('ID pesanan tidak valid.');
    $pesanan = Capsule::table('tb_pesanan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('id_pesanan_penjualan', $id)
        ->where('sumber_pesanan', 'website')
        ->first();
    if (!$pesanan) throw new RuntimeException('Pesanan online tidak ditemukan.');
    if ((string) ($pesanan->status_pembayaran_online ?? '') !== 'perlu_refund') {
        throw new RuntimeException('Pesanan belum berada pada status perlu refund.');
    }
    if ($nominalRefund <= 0) {
        $nominalRefund = (float) ($pesanan->nominal_refund_online ?: $pesanan->nominal_pembayaran_online ?: $pesanan->total ?: 0);
    }
    if ($nominalRefund <= 0) throw new RuntimeException('Nominal refund wajib lebih dari 0.');

    $uploadPath = null;
    if (!empty($_FILES['bukti_refund']['name'])) {
        $ext = strtolower(pathinfo((string) $_FILES['bukti_refund']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','pdf'], true)) {
            throw new RuntimeException('Bukti refund harus berupa JPG, PNG, WEBP, atau PDF.');
        }
        $dir = __DIR__ . '/../../../../uploads/refund_online';
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $name = 'refund_' . $id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file((string) $_FILES['bukti_refund']['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('Gagal upload bukti refund.');
        }
        $uploadPath = 'uploads/refund_online/' . $name;
    }
    if (!$uploadPath) throw new RuntimeException('Bukti transfer pengembalian uang wajib diupload.');

    Capsule::connection()->transaction(function () use ($id, $id_entitas, $id_pengguna, $pesanan, $nominalRefund, $catatanRefund, $uploadPath) {
        $kode = 'REFUND_PEMBAYARAN_ONLINE';
        $sudah = Capsule::table('tb_log_jurnal_sumber')
            ->where('id_entitas', $id_entitas)
            ->where('tabel_sumber', 'tb_pesanan_penjualan')
            ->where('id_sumber', $id)
            ->where('kode_jenis_transaksi', $kode)
            ->exists();
        if (!$sudah) {
            $tanggal = date('Y-m-d');
            $periode = po_refund_periode($id_entitas, $tanggal);
            $idCoaDebit = po_refund_akun_pdd($id_entitas);
            $idCoaKredit = po_refund_akun_kas_bank($id_entitas);
            $idJurnal = (int) Capsule::table('tb_jurnal')->insertGetId([
                'id_entitas' => $id_entitas,
                'no_jurnal' => po_refund_no_jurnal($id_entitas),
                'tanggal_jurnal' => $tanggal,
                'id_periode' => (int) $periode->id_periode,
                'kode_jenis_transaksi' => $kode,
                'keterangan' => 'Pengembalian uang pesanan online ' . (string) $pesanan->no_pesanan_penjualan,
                'tabel_sumber' => 'tb_pesanan_penjualan',
                'id_sumber' => $id,
                'no_sumber' => (string) $pesanan->no_pesanan_penjualan,
                'status_jurnal' => 'posted',
                'total_debit' => $nominalRefund,
                'total_kredit' => $nominalRefund,
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna ?: null,
                'tanggal_posting' => date('Y-m-d H:i:s'),
                'diposting_oleh' => $id_pengguna ?: null,
            ]);
            Capsule::table('tb_jurnal_detail')->insert([
                'id_jurnal' => $idJurnal,
                'urutan' => 1,
                'id_coa' => $idCoaDebit,
                'debit' => $nominalRefund,
                'kredit' => 0,
                'keterangan_baris' => 'Mengurangi uang muka/pendapatan diterima dimuka karena refund',
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
                'kredit' => $nominalRefund,
                'keterangan_baris' => 'Kas/Bank keluar untuk pengembalian dana customer',
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
                'id_sumber' => $id,
                'no_sumber' => (string) $pesanan->no_pesanan_penjualan,
                'kode_jenis_transaksi' => $kode,
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
            ]);
        }
        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id)
            ->update([
                'status_pembayaran_online' => 'sudah_refund',
                'status_refund_online' => 'sudah_refund',
                'nominal_refund_online' => $nominalRefund,
                'bukti_refund_online' => $uploadPath,
                'tanggal_refund_online' => date('Y-m-d H:i:s'),
                'catatan_refund_online' => $catatanRefund ?: 'Dana sudah dikembalikan ke customer.',
                'status_pesanan' => 'batal',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);
    });
    set_flash('success', 'Refund berhasil dicatat. Jurnal pengembalian uang dibuat, kas/bank berkurang, dan pesanan dibatalkan.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal simpan refund: ' . $e->getMessage());
}
redirect_admin('penjualan/pesanan-online/detail&id=' . $id);
