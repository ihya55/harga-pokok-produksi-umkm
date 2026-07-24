<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/SaldoAwalStokORM.php';
require_once __DIR__ . '/../../../../orm/SaldoAwalStokDetailORM.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_page_url('persediaan/saldo-stok/saldo-awal');
}

$tanggal_saldo_awal = trim((string) ($_POST['tanggal_saldo_awal'] ?? ''));
$id_gudang = (int) ($_POST['id_gudang'] ?? 0);
$id_coa_lawan = (int) ($_POST['id_coa_lawan'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));

$jenis_barang_arr = $_POST['jenis_barang'] ?? [];
$id_referensi_barang_arr = $_POST['id_referensi_barang'] ?? [];
$qty_awal_arr = $_POST['qty_awal'] ?? [];
$harga_satuan_arr = $_POST['harga_satuan'] ?? [];

function parse_number_saldo_awal_simpan($value): float
{
    $value = trim((string) $value);
    $value = str_replace(',', '', $value);

    if ($value === '') {
        return 0.0;
    }

    return (float) $value;
}

function generate_no_saldo_awal_stok(int $id_entitas): string
{
    $last = SaldoAwalStokORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('no_saldo_awal_stok', 'like', 'SAW-%')
        ->orderBy('id_saldo_awal_stok', 'desc')
        ->first();

    $next = 1;

    if ($last && preg_match('/SAW-(\d+)/', (string) $last->no_saldo_awal_stok, $matches)) {
        $next = ((int) $matches[1]) + 1;
    }

    return 'SAW-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

if ($id_entitas <= 0) {
    set_flash('error', 'Sesi entitas tidak valid. Silakan login ulang.');
    header('Location: ' . $back_url);
    exit;
}

if ($tanggal_saldo_awal === '' || $id_gudang <= 0 || $id_coa_lawan <= 0) {
    set_flash('error', 'Tanggal saldo awal, gudang, dan akun lawan wajib diisi.');
    header('Location: ' . admin_page_url('persediaan/saldo-stok/saldo-awal/tambah') . '&back_url=' . urlencode($back_url));
    exit;
}

if (!is_array($jenis_barang_arr) || count($jenis_barang_arr) === 0) {
    set_flash('error', 'Detail barang wajib diisi minimal 1 baris.');
    header('Location: ' . admin_page_url('persediaan/saldo-stok/saldo-awal/tambah') . '&back_url=' . urlencode($back_url));
    exit;
}

try {
    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $tanggal_saldo_awal,
        $id_gudang,
        $id_coa_lawan,
        $catatan,
        $jenis_barang_arr,
        $id_referensi_barang_arr,
        $qty_awal_arr,
        $harga_satuan_arr
    ) {
        $no_saldo_awal_stok = generate_no_saldo_awal_stok($id_entitas);

        $header = SaldoAwalStokORM::create([
            'id_entitas' => $id_entitas,
            'no_saldo_awal_stok' => $no_saldo_awal_stok,
            'tanggal_saldo_awal' => $tanggal_saldo_awal,
            'id_gudang' => $id_gudang,
            'id_coa_lawan' => $id_coa_lawan,
            'total_nilai' => 0,
            'status_posting' => 'draft',
            'catatan' => $catatan !== '' ? $catatan : null,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
        ]);

        $total_nilai = 0;
        $jumlah_detail_valid = 0;
        $barang_unik = [];

        foreach ($jenis_barang_arr as $i => $jenis_barang) {
            $jenis_barang = trim((string) $jenis_barang);
            $id_referensi_barang = (int) ($id_referensi_barang_arr[$i] ?? 0);
            $qty_awal = parse_number_saldo_awal_simpan($qty_awal_arr[$i] ?? 0);
            $harga_satuan = parse_number_saldo_awal_simpan($harga_satuan_arr[$i] ?? 0);

            if (!in_array($jenis_barang, ['bahan_baku', 'produk'], true)) {
                continue;
            }

            if ($id_referensi_barang <= 0) {
                continue;
            }

            if ($qty_awal <= 0) {
                continue;
            }

            if ($harga_satuan < 0) {
                continue;
            }

            $key = $jenis_barang . ':' . $id_referensi_barang;

            if (isset($barang_unik[$key])) {
                throw new Exception('Barang tidak boleh diinput lebih dari satu kali dalam dokumen yang sama.');
            }

            $barang_unik[$key] = true;

            $nilai_total = round($qty_awal * $harga_satuan, 2);

            SaldoAwalStokDetailORM::create([
                'id_saldo_awal_stok' => (int) $header->id_saldo_awal_stok,
                'jenis_barang' => $jenis_barang,
                'id_referensi_barang' => $id_referensi_barang,
                'qty_awal' => $qty_awal,
                'harga_satuan' => $harga_satuan,
                'nilai_total' => $nilai_total,
                'keterangan' => null,
            ]);

            $total_nilai += $nilai_total;
            $jumlah_detail_valid++;
        }

        if ($jumlah_detail_valid <= 0) {
            throw new Exception('Detail barang tidak valid. Pastikan barang, qty, dan harga sudah diisi.');
        }

        if ($total_nilai <= 0) {
            throw new Exception('Total nilai saldo awal harus lebih dari 0.');
        }

        $header->total_nilai = $total_nilai;
        $header->save();
    });

    set_flash('success', 'Saldo awal stok berhasil disimpan sebagai draft.');
    header('Location: ' . admin_page_url('persediaan/saldo-stok/saldo-awal'));
    exit;
} catch (Throwable $e) {
    set_flash('error', 'Gagal menyimpan saldo awal stok: ' . $e->getMessage());
    header('Location: ' . admin_page_url('persediaan/saldo-stok/saldo-awal/tambah') . '&back_url=' . urlencode($back_url));
    exit;
}