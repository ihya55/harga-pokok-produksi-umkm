<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/BahanBakuORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_bahan_baku = (int) ($_GET['id'] ?? 0);

$row = BahanBakuORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_bahan_baku);

if (!$row) {
    set_flash('error', 'Data bahan baku tidak ditemukan.');
    redirect_admin('master_setup/bahan_baku');
}

try {
    $row->delete();
    set_flash('success', 'Data bahan baku berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Data bahan baku tidak bisa dihapus karena masih digunakan di tabel lain.');
}

redirect_admin('master_setup/bahan_baku');