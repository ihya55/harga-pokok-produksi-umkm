<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/StokOpnameORM.php';
require_once __DIR__ . '/../../../../orm/StokOpnameDetailORM.php';
require_once __DIR__ . '/../../../../orm/SaldoStokORM.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_page_url('persediaan/stok-opname');
}

$tanggal_stok_opname = trim((string) ($_POST['tanggal_stok_opname'] ?? ''));
$id_gudang = (int) ($_POST['id_gudang'] ?? 0);
$catatan = trim((string) ($_POST['catatan'] ?? ''));

$jenis_barang_arr = $_POST['jenis_barang'] ?? [];
$id_referensi_barang_arr = $_POST['id_referensi_barang'] ?? [];
$qty_sistem_arr = $_POST['qty_sistem'] ?? [];
$qty_fisik_arr = $_POST['qty_fisik'] ?? [];
$harga_satuan_arr = $_POST['harga_satuan'] ?? [];

function parse_number_stok_opname_simpan($value): float
{
    $value = trim((string) $value);
    $value = str_replace(',', '', $value);

    if ($value === '') {
        return 0.0;
    }

    return (float) $value;
}

function generate_no_stok_opname(int $id_entitas): string
{
    $prefix = 'SOP-' . date('Ym') . '-';

    $last = StokOpnameORM::query()
        ->where('id_entitas', $id_entitas)
        ->where('no_stok_opname', 'like', $prefix . '%')
        ->orderBy('id_stok_opname', 'desc')
        ->first();

    $next = 1;

    if ($last && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', (string) $last->no_stok_opname, $matches)) {
        $next = ((int) $matches[1]) + 1;
    }

    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

if ($id_entitas <= 0) {
    set_flash('error', 'Sesi entitas tidak valid. Silakan login ulang.');
    header('Location: ' . $back_url);
    exit;
}

if ($tanggal_stok_opname === '' || $id_gudang <= 0) {
    set_flash('error', 'Tanggal stok opname dan gudang wajib diisi.');
    header('Location: ' . admin_page_url('persediaan/stok-opname/tambah') . '&back_url=' . urlencode($back_url));
    exit;
}

if (!is_array($jenis_barang_arr) || count($jenis_barang_arr) === 0) {
    set_flash('error', 'Detail barang wajib diisi minimal 1 baris.');
    header('Location: ' . admin_page_url('persediaan/stok-opname/tambah') . '&back_url=' . urlencode($back_url));
    exit;
}

try {
    $id_stok_opname_baru = 0;

    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $tanggal_stok_opname,
        $id_gudang,
        $catatan,
        $jenis_barang_arr,
        $id_referensi_barang_arr,
        $qty_sistem_arr,
        $qty_fisik_arr,
        $harga_satuan_arr,
        &$id_stok_opname_baru
    ) {
        $no_stok_opname = generate_no_stok_opname($id_entitas);

        $header = StokOpnameORM::create([
            'id_entitas' => $id_entitas,
            'no_stok_opname' => $no_stok_opname,
            'tanggal_stok_opname' => $tanggal_stok_opname,
            'id_gudang' => $id_gudang,
            'status_posting' => 'draft',
            'catatan' => $catatan !== '' ? $catatan : null,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
        ]);

        $id_stok_opname_baru = (int) $header->id_stok_opname;

        $jumlah_detail_valid = 0;
        $barang_unik = [];

        foreach ($jenis_barang_arr as $i => $jenis_barang) {
            $jenis_barang = trim((string) $jenis_barang);
            $id_referensi_barang = (int) ($id_referensi_barang_arr[$i] ?? 0);
            $qty_sistem = parse_number_stok_opname_simpan($qty_sistem_arr[$i] ?? 0);
            $qty_fisik = parse_number_stok_opname_simpan($qty_fisik_arr[$i] ?? 0);
            $harga_satuan = parse_number_stok_opname_simpan($harga_satuan_arr[$i] ?? 0);

            if (!in_array($jenis_barang, ['bahan_baku', 'produk'], true)) {
                continue;
            }

            if ($id_referensi_barang <= 0) {
                continue;
            }

            if ($qty_fisik < 0) {
                throw new Exception('Qty fisik tidak boleh minus.');
            }

            if ($harga_satuan < 0) {
                throw new Exception('Harga satuan tidak boleh minus.');
            }

            $key = $jenis_barang . ':' . $id_referensi_barang;

            if (isset($barang_unik[$key])) {
                throw new Exception('Barang tidak boleh diinput lebih dari satu kali dalam dokumen stok opname yang sama.');
            }

            $barang_unik[$key] = true;

            $saldo = SaldoStokORM::query()
                ->where('id_entitas', $id_entitas)
                ->where('id_gudang', $id_gudang)
                ->where('jenis_barang', $jenis_barang)
                ->where('id_referensi_barang', $id_referensi_barang)
                ->first();

            if (!$saldo) {
                throw new Exception('Saldo stok untuk salah satu barang tidak ditemukan di gudang terpilih.');
            }

            $qty_sistem_db = (float) ($saldo->qty_saldo ?? 0);
            $harga_satuan_db = (float) ($saldo->hpp_rata_rata ?? 0);

            $selisih_qty = round($qty_fisik - $qty_sistem_db, 3);
            $nilai_selisih = round($selisih_qty * $harga_satuan_db, 2);

            StokOpnameDetailORM::create([
                'id_stok_opname' => (int) $header->id_stok_opname,
                'jenis_barang' => $jenis_barang,
                'id_referensi_barang' => $id_referensi_barang,
                'qty_sistem' => $qty_sistem_db,
                'qty_fisik' => $qty_fisik,
                'selisih_qty' => $selisih_qty,
                'harga_satuan' => $harga_satuan_db,
                'nilai_selisih' => $nilai_selisih,
                'keterangan' => null,
            ]);

            $jumlah_detail_valid++;
        }

        if ($jumlah_detail_valid <= 0) {
            throw new Exception('Detail barang tidak valid. Pastikan barang dan qty fisik sudah diisi.');
        }
    });

    set_flash('success', 'Stok opname berhasil disimpan sebagai draft.');
    header('Location: ' . admin_page_url('persediaan/stok-opname/detail') . '&id=' . $id_stok_opname_baru . '&back_url=' . urlencode($back_url));
    exit;
} catch (Throwable $e) {
    set_flash('error', 'Gagal menyimpan stok opname: ' . $e->getMessage());
    header('Location: ' . admin_page_url('persediaan/stok-opname/tambah') . '&back_url=' . urlencode($back_url));
    exit;
}