<?php
declare(strict_types=1);

require_once __DIR__ . '/_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . po_url('index.php'));
    exit;
}

$id_entitas = po_id_entitas();
$id_pesanan = (int) ($_POST['id_pesanan_penjualan'] ?? 0);
$no_pesanan = trim((string) ($_POST['no_pesanan_penjualan'] ?? ''));
$no_hp = trim((string) ($_POST['no_hp'] ?? ''));
$nama_pengirim = trim((string) ($_POST['nama_pengirim'] ?? ''));
$nominal_bayar = (float) str_replace(['.', ','], ['', '.'], (string) ($_POST['nominal_bayar'] ?? '0'));
$tanggal_bayar = trim((string) ($_POST['tanggal_bayar'] ?? date('Y-m-d')));
$catatan = trim((string) ($_POST['catatan'] ?? ''));

try {
    if (!Capsule::schema()->hasTable('tb_pembayaran_online_bukti')) {
        throw new RuntimeException('Tabel bukti pembayaran belum tersedia. Jalankan SQL update terlebih dahulu.');
    }
    $query = Capsule::table('tb_pesanan_penjualan as pp')
        ->join('tb_pelanggan as p', 'p.id_pelanggan', '=', 'pp.id_pelanggan')
        ->where('pp.id_entitas', $id_entitas);

    if ($id_pesanan > 0) {
        $query->where('pp.id_pesanan_penjualan', $id_pesanan);
    } elseif ($no_pesanan !== '') {
        $query->where('pp.no_pesanan_penjualan', $no_pesanan);
    } else {
        throw new RuntimeException('Pesanan tidak valid.');
    }

    $pesanan = $query->select(['pp.*', 'p.no_hp', 'p.nama_pelanggan'])->first();
    if (!$pesanan) {
        throw new RuntimeException('Pesanan tidak ditemukan.');
    }

    if ($no_hp !== '' && preg_replace('/\D+/', '', (string) $pesanan->no_hp) !== preg_replace('/\D+/', '', $no_hp)) {
        throw new RuntimeException('No HP tidak cocok dengan pesanan.');
    }

    $metodePesanan = strtolower(trim((string) ($pesanan->metode_pembayaran_online ?? 'cod')));
    if (!in_array($metodePesanan, ['transfer', 'qris'], true)) {
        throw new RuntimeException('Pesanan COD tidak memerlukan upload bukti pembayaran. Pembayaran COD diproses saat penyerahan barang.');
    }

    if ($nominal_bayar <= 0) {
        $nominal_bayar = (float) ($pesanan->nominal_pembayaran_online ?? $pesanan->total ?? 0);
    }

    if (empty($_FILES['file_bukti']['name'] ?? '')) {
        throw new RuntimeException('Foto/screenshot bukti pembayaran wajib diupload.');
    }

    $file = $_FILES['file_bukti'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload bukti gagal. Kode error: ' . (int) $file['error']);
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
        throw new RuntimeException('Format bukti harus JPG, PNG, WEBP, atau PDF.');
    }
    if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('Ukuran bukti maksimal 5 MB.');
    }

    $dir = __DIR__ . '/../uploads/bukti_pembayaran';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $filename = 'bukti_' . (int) $pesanan->id_pesanan_penjualan . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $dir . '/' . $filename;
    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        throw new RuntimeException('Gagal menyimpan file bukti pembayaran.');
    }
    $relativePath = 'uploads/bukti_pembayaran/' . $filename;

    $dataBuktiUpload = [
        'id_entitas' => $id_entitas,
        'id_pesanan_penjualan' => (int) $pesanan->id_pesanan_penjualan,
        'metode_pembayaran' => $metodePesanan,
        'nominal_bayar' => $nominal_bayar,
        'nama_pengirim' => $nama_pengirim !== '' ? $nama_pengirim : (string) ($pesanan->nama_pelanggan ?? ''),
        'tanggal_bayar' => $tanggal_bayar !== '' ? $tanggal_bayar : date('Y-m-d'),
        'file_bukti' => $relativePath,
        'catatan' => $catatan !== '' ? $catatan : null,
        'status_verifikasi' => 'menunggu_verifikasi',
        'tanggal_dibuat' => date('Y-m-d H:i:s'),
    ];
    if (po_table_has_column('tb_pembayaran_online_bukti', 'id_pelanggan')) {
        $dataBuktiUpload['id_pelanggan'] = (int) ($pesanan->id_pelanggan ?? 0);
    }
    Capsule::table('tb_pembayaran_online_bukti')->insert($dataBuktiUpload);

    if (po_table_has_column('tb_pesanan_penjualan', 'status_pembayaran_online')) {
        Capsule::table('tb_pesanan_penjualan')
            ->where('id_pesanan_penjualan', (int) $pesanan->id_pesanan_penjualan)
            ->update([
                'status_pembayaran_online' => 'menunggu_verifikasi',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
            ]);
    }

    header('Location: ' . po_url('sukses.php?uploaded=1&id=' . (int) $pesanan->id_pesanan_penjualan . '&no=' . urlencode((string) $pesanan->no_pesanan_penjualan) . '&entitas=' . (int) $id_entitas));
    exit;
} catch (Throwable $e) {
    header('Location: ' . po_url('sukses.php?error_bukti=' . urlencode($e->getMessage()) . '&id=' . (int) $id_pesanan . '&no=' . urlencode($no_pesanan) . '&entitas=' . (int) $id_entitas));
    exit;
}
