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

try {
    if ($id <= 0) throw new RuntimeException('ID pesanan tidak valid.');
    $pesanan = Capsule::table('tb_pesanan_penjualan')
        ->where('id_entitas', $id_entitas)
        ->where('id_pesanan_penjualan', $id)
        ->where('sumber_pesanan', 'website')
        ->first();
    if (!$pesanan) throw new RuntimeException('Pesanan online tidak ditemukan.');

    $statusBayar = strtolower((string) ($pesanan->status_pembayaran_online ?? ''));
    if (in_array($statusBayar, ['lunas', 'paid', 'settlement'], true)) {
        // Jika uang sudah diterima, tidak boleh langsung ditolak tanpa pengembalian dana.
        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id)
            ->update([
                'status_pembayaran_online' => 'perlu_refund',
                'status_refund_online' => 'menunggu_refund',
                'nominal_refund_online' => (float) ($pesanan->nominal_pembayaran_online ?: $pesanan->total ?: 0),
                'catatan_refund_online' => 'Pesanan dibatalkan/ditolak setelah uang masuk. Admin wajib mengembalikan dana dan upload bukti transfer pengembalian.',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
            ]);
        set_flash('warning', 'Pembayaran sudah pernah diterima. Sistem menandai pesanan PERLU REFUND. Upload bukti transfer pengembalian uang setelah dana dikembalikan.');
    } else {
        if (Capsule::schema()->hasTable('tb_pembayaran_online_bukti')) {
            Capsule::table('tb_pembayaran_online_bukti')
                ->where('id_entitas', $id_entitas)
                ->where('id_pesanan_penjualan', $id)
                ->where('status_verifikasi', 'menunggu_verifikasi')
                ->update([
                    'status_verifikasi' => 'ditolak',
                    'catatan_admin' => 'Bukti pembayaran ditolak oleh admin.',
                    'diverifikasi_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
                    'tanggal_verifikasi' => date('Y-m-d H:i:s'),
                ]);
        }
        Capsule::table('tb_pesanan_penjualan')
            ->where('id_entitas', $id_entitas)
            ->where('id_pesanan_penjualan', $id)
            ->update([
                'status_pembayaran_online' => 'ditolak',
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
            ]);
        set_flash('success', 'Bukti pembayaran ditolak. Pesanan tetap pending sampai customer mengirim bukti yang benar.');
    }
} catch (Throwable $e) {
    set_flash('error', 'Gagal menolak pembayaran: ' . $e->getMessage());
}
redirect_admin('penjualan/pesanan-online/detail&id=' . $id);
