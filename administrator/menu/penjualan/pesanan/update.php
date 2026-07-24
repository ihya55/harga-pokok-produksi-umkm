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
$id_pesanan_penjualan = (int) ($_GET['id'] ?? 0);

$tanggal_pesanan = trim((string) ($_POST['tanggal_pesanan'] ?? ''));
$tanggal_kirim_rencana = trim((string) ($_POST['tanggal_kirim_rencana'] ?? ''));
$id_pelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
$sumber_pesanan = trim((string) ($_POST['sumber_pesanan'] ?? 'toko'));
$catatan = trim((string) ($_POST['catatan'] ?? ''));

$subtotal_header = penjualan_parse_number($_POST['subtotal'] ?? 0);
$diskon_header = penjualan_parse_number($_POST['diskon'] ?? 0);
$total_header = penjualan_parse_number($_POST['total'] ?? 0);

$id_produk_arr = $_POST['id_produk'] ?? [];
$qty_arr = $_POST['qty'] ?? [];
$harga_arr = $_POST['harga'] ?? [];
$diskon_detail_arr = $_POST['diskon_detail'] ?? [];
$subtotal_detail_arr = $_POST['subtotal_detail'] ?? [];
$hpp_standar_arr = $_POST['hpp_standar'] ?? [];
$catatan_detail_arr = $_POST['catatan_detail'] ?? [];

if ($id_entitas <= 0 || $id_pesanan_penjualan <= 0) {
    set_flash('error', 'Data pesanan tidak valid.');
    penjualan_redirect(admin_page_url('penjualan/pesanan'));
}

try {
    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_pengguna,
        $id_pesanan_penjualan,
        $tanggal_pesanan,
        $tanggal_kirim_rencana,
        $id_pelanggan,
        $sumber_pesanan,
        $catatan,
        $subtotal_header,
        $diskon_header,
        $total_header,
        $id_produk_arr,
        $qty_arr,
        $harga_arr,
        $diskon_detail_arr,
        $subtotal_detail_arr,
        $hpp_standar_arr,
        $catatan_detail_arr
    ) {
        $pesanan = Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->lockForUpdate()
            ->first();

        if (!$pesanan) {
            throw new RuntimeException('Pesanan penjualan tidak ditemukan.');
        }

        if ((string) ($pesanan->status_pesanan ?? '') !== 'draft') {
            throw new RuntimeException('Pesanan hanya bisa diubah saat status masih draft.');
        }

        if ($tanggal_pesanan === '') {
            throw new RuntimeException('Tanggal pesanan wajib diisi.');
        }

        penjualan_pastikan_pelanggan($id_entitas, $id_pelanggan);

        $sumber_pesanan = strtolower(trim($sumber_pesanan));

        if (!in_array($sumber_pesanan, ['toko', 'whatsapp', 'website', 'reseller'], true)) {
            throw new RuntimeException('Sumber pesanan tidak valid.');
        }

        if (!is_array($id_produk_arr) || count($id_produk_arr) <= 0) {
            throw new RuntimeException('Detail produk masih kosong.');
        }

        $detail_valid = [];
        $produk_unik = [];
        $subtotal_hitung = 0.0;

        foreach ($id_produk_arr as $i => $id_produk_raw) {
            $id_produk = (int) $id_produk_raw;

            if ($id_produk <= 0) {
                continue;
            }

            if (isset($produk_unik[$id_produk])) {
                throw new RuntimeException('Produk tidak boleh diinput lebih dari satu kali. Gabungkan qty pada baris yang sama.');
            }

            $produk_unik[$id_produk] = true;

            $produk = penjualan_pastikan_produk($id_entitas, $id_produk);

            $qty = (int) round(penjualan_parse_number($qty_arr[$i] ?? 0));
            $harga = penjualan_parse_number($harga_arr[$i] ?? 0);
            $diskon_detail = penjualan_parse_number($diskon_detail_arr[$i] ?? 0);
            $subtotal_detail = penjualan_parse_number($subtotal_detail_arr[$i] ?? 0);
            $hpp_standar = penjualan_parse_number($hpp_standar_arr[$i] ?? 0);
            $catatan_detail = trim((string) ($catatan_detail_arr[$i] ?? ''));

            if ($qty <= 0) {
                throw new RuntimeException('Qty produk harus lebih dari nol.');
            }

            if ($harga < 0) {
                throw new RuntimeException('Harga tidak boleh minus.');
            }

            if ($diskon_detail < 0) {
                throw new RuntimeException('Diskon detail tidak boleh minus.');
            }

            if ($hpp_standar <= 0) {
                $hpp_standar = (float) ($produk->hpp_standar ?? 0);
            }

            $subtotal_kotor = round($qty * $harga, 2);

            if ($diskon_detail > $subtotal_kotor) {
                throw new RuntimeException('Diskon detail tidak boleh lebih besar dari subtotal produk.');
            }

            $subtotal_valid = round($subtotal_kotor - $diskon_detail, 2);

            if (abs($subtotal_valid - $subtotal_detail) > 1) {
                $subtotal_detail = $subtotal_valid;
            }

            $subtotal_hitung += $subtotal_detail;

            $detail_valid[] = [
                'id_produk' => $id_produk,
                'qty' => $qty,
                'harga' => round($harga, 2),
                'diskon' => round($diskon_detail, 2),
                'subtotal' => round($subtotal_detail, 2),
                'hpp_standar' => round($hpp_standar, 2),
                'catatan' => $catatan_detail !== '' ? $catatan_detail : null,
            ];
        }

        if (count($detail_valid) <= 0) {
            throw new RuntimeException('Tidak ada detail produk valid.');
        }

        $subtotal_hitung = round($subtotal_hitung, 2);

        if (abs($subtotal_hitung - $subtotal_header) > 1) {
            $subtotal_header = $subtotal_hitung;
        }

        if ($diskon_header < 0) {
            throw new RuntimeException('Diskon header tidak boleh minus.');
        }

        if ($diskon_header > $subtotal_header) {
            throw new RuntimeException('Diskon header tidak boleh lebih besar dari subtotal.');
        }

        $total_hitung = round($subtotal_header - $diskon_header, 2);

        if (abs($total_hitung - $total_header) > 1) {
            $total_header = $total_hitung;
        }

        if ($total_header <= 0) {
            throw new RuntimeException('Total pesanan harus lebih dari nol.');
        }

        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->update([
                'tanggal_pesanan' => $tanggal_pesanan,
                'tanggal_kirim_rencana' => $tanggal_kirim_rencana !== '' ? $tanggal_kirim_rencana : null,
                'id_pelanggan' => $id_pelanggan,
                'sumber_pesanan' => $sumber_pesanan,
                'subtotal' => round($subtotal_header, 2),
                'diskon' => round($diskon_header, 2),
                'total' => round($total_header, 2),
                'catatan' => $catatan !== '' ? $catatan : null,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna ?: null,
            ]);

        Capsule::table('tb_pesanan_penjualan_detail')
            ->where('id_pesanan_penjualan', $id_pesanan_penjualan)
            ->delete();

        foreach ($detail_valid as $d) {
            Capsule::table('tb_pesanan_penjualan_detail')->insert([
                'id_pesanan_penjualan' => $id_pesanan_penjualan,
                'id_produk' => (int) $d['id_produk'],
                'qty' => (int) $d['qty'],
                'harga' => (float) $d['harga'],
                'diskon' => (float) $d['diskon'],
                'subtotal' => (float) $d['subtotal'],
                'hpp_standar' => (float) $d['hpp_standar'],
                'catatan' => $d['catatan'],
            ]);
        }
    });

    set_flash('success', 'Pesanan penjualan berhasil diperbarui.');
    penjualan_redirect(admin_page_url('penjualan/pesanan/detail') . '&id=' . $id_pesanan_penjualan);
} catch (Throwable $e) {
    set_flash('error', 'Gagal memperbarui pesanan penjualan: ' . $e->getMessage());
    penjualan_redirect(admin_page_url('penjualan/pesanan/edit') . '&id=' . $id_pesanan_penjualan);
}