<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PesananPembelianORM.php';
require_once __DIR__ . '/../../../../orm/PesananPembelianDetailORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$id_pesanan_pembelian = (int) ($_GET['id'] ?? 0);

$url_detail = admin_url('index.php?menu=pembelian/pesanan/detail&id=' . (int) $id_pesanan_pembelian);

$row = PesananPembelianORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pesanan_pembelian);

if (!$row) {
    set_flash('error', 'Data pesanan pembelian tidak ditemukan.');
    redirect_admin('pembelian/pesanan');
}

if ((string) $row->status_pesanan !== 'draft') {
    set_flash('error', 'Hanya pesanan berstatus draft yang bisa dikonfirmasi.');
    header('Location: ' . $url_detail);
    exit;
}

$jumlah_detail = PesananPembelianDetailORM::query()
    ->where('id_pesanan_pembelian', $id_pesanan_pembelian)
    ->count();

if ($jumlah_detail <= 0) {
    set_flash('error', 'Pesanan pembelian belum memiliki detail.');
    header('Location: ' . $url_detail);
    exit;
}

$row->update([
    'status_pesanan' => 'terkonfirmasi',
    'tanggal_diubah' => date('Y-m-d H:i:s'),
    'diubah_oleh'    => $id_pengguna > 0 ? $id_pengguna : null,
]);

set_flash('success', 'Pesanan pembelian berhasil dikonfirmasi.');
header('Location: ' . $url_detail);
exit;