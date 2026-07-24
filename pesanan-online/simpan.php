<?php
declare(strict_types=1);

require_once __DIR__ . '/_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . po_url('index.php'));
    exit;
}

$id_entitas = (int) ($_POST['id_entitas'] ?? 1);
$id_entitas = $id_entitas > 0 ? $id_entitas : 1;

$nama = trim((string) ($_POST['nama_pelanggan'] ?? ''));
$no_hp = trim((string) ($_POST['no_hp'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$alamat = trim((string) ($_POST['alamat'] ?? ''));
$tanggal_kirim_rencana = trim((string) ($_POST['tanggal_kirim_rencana'] ?? ''));
$catatan = trim((string) ($_POST['catatan'] ?? ''));
$metode_pembayaran_online = strtolower(trim((string) ($_POST['metode_pembayaran_online'] ?? 'cod')));
if (!in_array($metode_pembayaran_online, ['cod', 'transfer', 'qris'], true)) {
    $metode_pembayaran_online = 'cod';
}
$cartJson = (string) ($_POST['cart_json'] ?? '[]');

try {
    if ($nama === '') {
        throw new RuntimeException('Nama pelanggan wajib diisi.');
    }

    if ($no_hp === '') {
        throw new RuntimeException('No HP / WhatsApp wajib diisi.');
    }

    if ($alamat === '') {
        throw new RuntimeException('Alamat pengiriman wajib diisi.');
    }

    $cart = json_decode($cartJson, true);

    if (!is_array($cart) || count($cart) === 0) {
        throw new RuntimeException('Keranjang masih kosong.');
    }

    $cleanItems = [];
    $subtotal = 0.0;

    foreach ($cart as $item) {
        $id_produk = (int) ($item['id_produk'] ?? 0);
        $qty = (float) ($item['qty'] ?? 0);

        if ($id_produk <= 0 || $qty <= 0) {
            continue;
        }

        $produk = Capsule::table('tb_produk as p')
            ->leftJoin('tb_saldo_stok as ss', function ($join) {
                $join->on('ss.id_referensi_barang', '=', 'p.id_produk')
                    ->where('ss.jenis_barang', '=', 'produk');
            })
            ->where('p.id_entitas', $id_entitas)
            ->where('p.id_produk', $id_produk)
            ->where('p.status_produk', 1)
            ->whereIn('p.jenis_produk', ['barang_jadi', 'setengah_jadi'])
            ->groupBy([
                'p.id_produk',
                'p.kode_produk',
                'p.nama_produk',
                'p.harga_jual',
                'p.hpp_standar',
            ])
            ->select([
                'p.id_produk',
                'p.kode_produk',
                'p.nama_produk',
                'p.harga_jual',
                'p.hpp_standar',
            ])
            ->selectRaw('COALESCE(SUM(ss.qty_saldo), 0) as stok_tersedia')
            ->first();

        if (!$produk) {
            throw new RuntimeException('Produk tidak ditemukan atau tidak aktif.');
        }

        $stok = (float) ($produk->stok_tersedia ?? 0);

        if ($stok <= 0) {
            throw new RuntimeException('Produk ' . $produk->nama_produk . ' sudah tidak memiliki stok.');
        }

        if ($qty > $stok) {
            throw new RuntimeException('Qty produk ' . $produk->nama_produk . ' melebihi stok tersedia.');
        }

        $harga = (float) $produk->harga_jual;
        $diskon = 0.0;
        $lineSubtotal = ($qty * $harga) - $diskon;

        $cleanItems[] = [
            'id_produk' => (int) $produk->id_produk,
            'nama_produk' => (string) $produk->nama_produk,
            'qty' => $qty,
            'harga' => $harga,
            'diskon' => $diskon,
            'subtotal' => $lineSubtotal,
            'hpp_standar' => (float) $produk->hpp_standar,
        ];

        $subtotal += $lineSubtotal;
    }

    if (count($cleanItems) === 0) {
        throw new RuntimeException('Tidak ada item pesanan yang valid.');
    }

    $diskonHeader = 0.0;
    $ppnPersen = pajak_default_persen($id_entitas, 'PPN', 11.0);
    $dasarPpn = max(0, $subtotal - $diskonHeader);
    $ppn = round($dasarPpn * ($ppnPersen / 100));
    $total = $dasarPpn + $ppn;

    if ($total <= 0) {
        throw new RuntimeException('Total pesanan tidak valid.');
    }

    $idPesanan = 0;
    $noPesanan = '';
    $idPelangganPesanan = 0;

    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $nama,
        $no_hp,
        $email,
        $alamat,
        $tanggal_kirim_rencana,
        $catatan,
        $metode_pembayaran_online,
        $cleanItems,
        $subtotal,
        $diskonHeader,
        $ppnPersen,
        $ppn,
        $total,
        &$idPesanan,
        &$noPesanan,
        &$idPelangganPesanan
    ) {
        $idPelanggan = po_get_or_create_pelanggan($id_entitas, $nama, $no_hp, $email, $alamat);
        $idPelangganPesanan = (int) $idPelanggan;
        $noPesanan = po_generate_nomor_pesanan($id_entitas);

        $catatanFinal = trim(
            "Pesanan online dari website.\n" .
            "Nama: " . $nama . "\n" .
            "No HP: " . $no_hp . "\n" .
            "Email: " . ($email !== '' ? $email : '-') . "\n" .
            "Alamat: " . $alamat . "\n" .
            "Metode pembayaran: " . po_payment_method_label($metode_pembayaran_online) . "\n\n" .
            ($catatan !== '' ? "Catatan pelanggan: " . $catatan : '')
        );

        $insertPesanan = [
            'id_entitas' => $id_entitas,
            'no_pesanan_penjualan' => $noPesanan,
            'tanggal_pesanan' => date('Y-m-d'),
            'id_pelanggan' => $idPelanggan,
            'sumber_pesanan' => 'website',
            'status_pesanan' => 'draft',
            'tanggal_kirim_rencana' => $tanggal_kirim_rencana !== '' ? $tanggal_kirim_rencana : null,
            'catatan' => $catatanFinal,
            'subtotal' => $subtotal,
            'diskon' => $diskonHeader,
            'ppn_persen' => $ppnPersen,
            'ppn' => $ppn,
            'total' => $total,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => null,
            'tanggal_diubah' => null,
            'diubah_oleh' => null,
        ];

        if (po_table_has_column('tb_pesanan_penjualan', 'metode_pembayaran_online')) {
            $insertPesanan['metode_pembayaran_online'] = $metode_pembayaran_online;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'nominal_pembayaran_online')) {
            $insertPesanan['nominal_pembayaran_online'] = $total;
        }
        if (po_table_has_column('tb_pesanan_penjualan', 'status_pembayaran_online')) {
            $insertPesanan['status_pembayaran_online'] = $metode_pembayaran_online === 'cod' ? 'belum_bayar' : 'menunggu_bayar';
        }

        $idPesanan = (int) Capsule::table('tb_pesanan_penjualan')->insertGetId($insertPesanan);

        foreach ($cleanItems as $item) {
            Capsule::table('tb_pesanan_penjualan_detail')->insert([
                'id_pesanan_penjualan' => $idPesanan,
                'id_produk' => $item['id_produk'],
                'qty' => $item['qty'],
                'harga' => $item['harga'],
                'diskon' => $item['diskon'],
                'subtotal' => $item['subtotal'],
                'hpp_standar' => $item['hpp_standar'],
                'catatan' => null,
            ]);
        }
    });

    // QRIS memakai QRIS manual saja. Tidak membuat transaksi iPaymu/Duitku/Midtrans.
    // Pelanggan scan gambar QRIS manual dan upload bukti pembayaran untuk diverifikasi admin.


    // Bukti pembayaran opsional dari form checkout untuk Transfer/QRIS.
    // Perbaikan: file dari popup checkout sebelumnya bisa gagal masuk karena id_pelanggan
    // tidak terbawa ke luar transaksi, lalu error-nya tertutup. Sekarang id_pelanggan
    // disimpan dengan benar dan jika file gagal, pesan error ditampilkan di halaman sukses.
    $errorBuktiCheckout = '';
    if (in_array($metode_pembayaran_online, ['transfer', 'qris'], true)
        && !empty($_FILES['file_bukti_checkout']['name'] ?? '')) {
        try {
            if (!Capsule::schema()->hasTable('tb_pembayaran_online_bukti')) {
                throw new RuntimeException('Tabel bukti pembayaran belum tersedia. Jalankan SQL update bukti pembayaran terlebih dahulu.');
            }

            $file = $_FILES['file_bukti_checkout'];
            $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($uploadError !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload bukti gagal. Kode error: ' . $uploadError);
            }

            $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                throw new RuntimeException('Format bukti harus JPG, JPEG, PNG, WEBP, atau PDF.');
            }

            if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
                throw new RuntimeException('Ukuran bukti pembayaran maksimal 5 MB.');
            }

            $dir = __DIR__ . '/../uploads/bukti_pembayaran';
            if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
                throw new RuntimeException('Folder upload bukti pembayaran tidak bisa dibuat.');
            }

            $filename = 'bukti_' . (int) $idPesanan . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $target = $dir . '/' . $filename;
            if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
                throw new RuntimeException('Gagal menyimpan file bukti pembayaran ke folder upload.');
            }

            $dataBuktiCheckout = [
                'id_entitas' => $id_entitas,
                'id_pesanan_penjualan' => (int) $idPesanan,
                'metode_pembayaran' => $metode_pembayaran_online,
                'nominal_bayar' => $total,
                'nama_pengirim' => $nama,
                'tanggal_bayar' => date('Y-m-d'),
                'file_bukti' => 'uploads/bukti_pembayaran/' . $filename,
                'catatan' => 'Upload dari checkout pesanan online.',
                'status_verifikasi' => 'menunggu_verifikasi',
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
            ];

            if (po_table_has_column('tb_pembayaran_online_bukti', 'id_pelanggan')) {
                $dataBuktiCheckout['id_pelanggan'] = (int) $idPelangganPesanan;
            }

            Capsule::table('tb_pembayaran_online_bukti')->insert($dataBuktiCheckout);

            if (po_table_has_column('tb_pesanan_penjualan', 'status_pembayaran_online')) {
                Capsule::table('tb_pesanan_penjualan')
                    ->where('id_pesanan_penjualan', (int) $idPesanan)
                    ->update([
                        'status_pembayaran_online' => 'menunggu_verifikasi',
                        'tanggal_diubah' => date('Y-m-d H:i:s'),
                    ]);
            }
        } catch (Throwable $proofError) {
            $errorBuktiCheckout = $proofError->getMessage();
        }
    }

    $redirect = 'sukses.php?no=' . urlencode($noPesanan) . '&id=' . (int) $idPesanan . '&entitas=' . (int) $id_entitas;
    if ($errorBuktiCheckout !== '') {
        $redirect .= '&error_bukti=' . urlencode($errorBuktiCheckout);
    } elseif (in_array($metode_pembayaran_online, ['transfer', 'qris'], true) && !empty($_FILES['file_bukti_checkout']['name'] ?? '')) {
        $redirect .= '&uploaded=1';
    }
    header('Location: ' . po_url($redirect));
    exit;
} catch (Throwable $e) {
    $msg = urlencode($e->getMessage());
    header('Location: ' . po_url('index.php?error=' . $msg . '&entitas=' . (int) $id_entitas));
    exit;
}