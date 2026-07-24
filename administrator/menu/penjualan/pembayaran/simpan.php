<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . admin_url('index.php?menu=penjualan/pembayaran'));
    exit;
}

function pembayaran_penjualan_redirect_tambah(?int $id_faktur = null): void
{
    $url = admin_url('index.php?menu=penjualan/pembayaran/tambah');

    if ($id_faktur !== null && $id_faktur > 0) {
        $url .= '&id_faktur_penjualan=' . $id_faktur;
    }

    header('Location: ' . $url);
    exit;
}

function pembayaran_penjualan_decimal($value): float
{
    return (float) preg_replace('/[^0-9.]/', '', (string) $value);
}

function pembayaran_penjualan_generate_nomor(int $id_entitas): string
{
    $prefix = 'PPJ-';

    $last = Capsule::table('tb_pembayaran_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('no_pembayaran_penjualan', 'like', $prefix . '%')
        ->orderBy('id_pembayaran_penjualan', 'desc')
        ->value('no_pembayaran_penjualan');

    $next = 1;

    if ($last) {
        $angka = preg_replace('/[^0-9]/', '', (string) $last);
        if ($angka !== '') {
            $next = ((int) $angka) + 1;
        }
    }

    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$tanggal_pembayaran = trim((string) ($_POST['tanggal_pembayaran'] ?? ''));
$id_faktur_penjualan = (int) ($_POST['id_faktur_penjualan'] ?? 0);
$id_pelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
$metode_pembayaran = strtolower(trim((string) ($_POST['metode_pembayaran'] ?? '')));
$id_coa_kas_bank = (int) ($_POST['id_coa_kas_bank'] ?? 0);
$jumlah_bayar = pembayaran_penjualan_decimal($_POST['jumlah_bayar'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));

if ($tanggal_pembayaran === '') {
    set_flash('error', 'Tanggal pembayaran wajib diisi.');
    pembayaran_penjualan_redirect_tambah($id_faktur_penjualan);
}

if ($id_faktur_penjualan <= 0) {
    set_flash('error', 'Piutang pelanggan wajib dipilih.');
    pembayaran_penjualan_redirect_tambah();
}

if (!in_array($metode_pembayaran, ['tunai', 'transfer'], true)) {
    set_flash('error', 'Metode pembayaran tidak valid.');
    pembayaran_penjualan_redirect_tambah($id_faktur_penjualan);
}

if ($id_coa_kas_bank <= 0) {
    set_flash('error', 'Akun kas/bank wajib dipilih.');
    pembayaran_penjualan_redirect_tambah($id_faktur_penjualan);
}

if ($jumlah_bayar <= 0) {
    set_flash('error', 'Jumlah bayar wajib lebih besar dari 0.');
    pembayaran_penjualan_redirect_tambah($id_faktur_penjualan);
}

try {
    $id_baru = null;

    Capsule::connection()->transaction(function () use (
        &$id_baru,
        $id_entitas,
        $id_pengguna,
        $tanggal_pembayaran,
        $id_faktur_penjualan,
        $id_pelanggan,
        $metode_pembayaran,
        $id_coa_kas_bank,
        $jumlah_bayar,
        $catatan
    ) {
        $faktur = Capsule::table('tb_faktur_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$faktur) {
            throw new RuntimeException('Piutang pelanggan tidak ditemukan.');
        }

        if ((string) $faktur->status_faktur !== 'posted') {
            throw new RuntimeException('Hanya faktur posted yang bisa dibayar.');
        }

        if ((string) $faktur->jenis_pembayaran !== 'kredit') {
            throw new RuntimeException('Pembayaran manual hanya untuk piutang kredit.');
        }

        if ((float) $faktur->sisa_piutang <= 0) {
            throw new RuntimeException('Faktur ini sudah lunas.');
        }

        if ($id_pelanggan <= 0) {
            $id_pelanggan = (int) $faktur->id_pelanggan;
        }

        if ((int) $faktur->id_pelanggan !== $id_pelanggan) {
            throw new RuntimeException('Pelanggan pembayaran tidak sesuai dengan faktur.');
        }

        $coa = Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('id_coa', $id_coa_kas_bank)
            ->where('boleh_transaksi', 1)
            ->where('status_aktif', 1)
            ->first();

        if (!$coa) {
            throw new RuntimeException('Akun kas/bank tidak valid atau tidak aktif.');
        }

        $namaAkun = strtolower((string) ($coa->nama_coa ?? ''));
        $kodeAkun = (string) ($coa->kode_coa ?? '');

        if ($metode_pembayaran === 'tunai' && str_contains($namaAkun, 'bank')) {
            throw new RuntimeException('Metode tunai sebaiknya memakai akun kas, bukan bank.');
        }

        if ($metode_pembayaran === 'transfer' && !(str_contains($namaAkun, 'bank') || str_starts_with($kodeAkun, '1120'))) {
            throw new RuntimeException('Metode transfer wajib memakai akun bank.');
        }

        $totalDraft = (float) Capsule::table('tb_pembayaran_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_faktur_penjualan', $id_faktur_penjualan)
            ->where('status_posting', 'draft')
            ->sum('jumlah_bayar');

        $sisaTersedia = max(0, (float) $faktur->sisa_piutang - $totalDraft);

        if ($jumlah_bayar > $sisaTersedia) {
            throw new RuntimeException('Jumlah bayar tidak boleh melebihi sisa piutang yang tersedia. Sisa tersedia: Rp ' . number_format($sisaTersedia, 2, '.', ','));
        }

        $id_baru = Capsule::table('tb_pembayaran_penjualan')->insertGetId([
            'id_entitas' => $id_entitas,
            'no_pembayaran_penjualan' => pembayaran_penjualan_generate_nomor($id_entitas),
            'tanggal_pembayaran' => $tanggal_pembayaran,
            'id_faktur_penjualan' => $id_faktur_penjualan,
            'id_pelanggan' => $id_pelanggan,
            'metode_pembayaran' => $metode_pembayaran,
            'id_coa_kas_bank' => $id_coa_kas_bank,
            'jumlah_bayar' => round($jumlah_bayar, 2),
            'catatan' => $catatan !== '' ? $catatan : null,
            'status_posting' => 'draft',
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
        ]);
    });

    set_flash('success', 'Draft pembayaran penjualan berhasil dibuat.');
    header('Location: ' . admin_url('index.php?menu=penjualan/pembayaran/detail&id=' . (int) $id_baru));
    exit;
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
    pembayaran_penjualan_redirect_tambah($id_faktur_penjualan);
}