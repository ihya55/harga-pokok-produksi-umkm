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
$id_pelanggan = (int) ($_POST['id_pelanggan'] ?? 0);
$pesan = trim((string) ($_POST['pesan'] ?? ''));
$is_ajax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest' || (string) ($_POST['ajax'] ?? '') === '1';

try {
    if ($id <= 0 || $pesan === '') throw new RuntimeException('Pesan tidak valid.');
    if (!Capsule::schema()->hasTable('tb_pesanan_online_chat')) throw new RuntimeException('Tabel chat belum tersedia. Jalankan SQL update terlebih dahulu.');
    if (!Capsule::schema()->hasColumn('tb_pesanan_online_chat', 'id_pelanggan')) throw new RuntimeException('Kolom id_pelanggan pada tabel chat belum tersedia. Import SQL patch terlebih dahulu agar chat tidak bercampur.');
    $pesanan = Capsule::table('tb_pesanan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('id_pesanan_penjualan', $id)
        ->where('sumber_pesanan', 'website')
        ->select(['id_pesanan_penjualan', 'id_pelanggan'])
        ->first();

    if (!$pesanan) throw new RuntimeException('Pesanan tidak ditemukan.');
    if ($id_pelanggan > 0 && (int) ($pesanan->id_pelanggan ?? 0) !== $id_pelanggan) {
        throw new RuntimeException('Customer tidak sesuai dengan pesanan.');
    }
    $nama_pengirim = (string) ($user['nama_lengkap'] ?? $user['username'] ?? 'Admin');
    $tanggal = date('Y-m-d H:i:s');
    Capsule::table('tb_pesanan_online_chat')->insert([
        'id_entitas' => $id_entitas,
        'id_pesanan_penjualan' => $id,
        'id_pelanggan' => (int) ($pesanan->id_pelanggan ?? 0),
        'pengirim_tipe' => 'admin',
        'nama_pengirim' => $nama_pengirim,
        'pesan' => $pesan,
        'tanggal_dibuat' => $tanggal,
        'dibuat_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
    ]);
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'message' => 'Balasan terkirim.',
            'nama_pengirim' => $nama_pengirim,
            'pesan' => $pesan,
            'tanggal' => date('d/m/Y H:i', strtotime($tanggal)),
        ]);
        exit;
    }
    set_flash('success', 'Balasan chat berhasil dikirim.');
} catch (Throwable $e) {
    if ($is_ajax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
        exit;
    }
    set_flash('error', 'Gagal mengirim chat: ' . $e->getMessage());
}
redirect_admin('penjualan/pesanan-online/detail&id=' . $id);
