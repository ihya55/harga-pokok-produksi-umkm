<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/GudangORM.php';

require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_gudang  = (int) ($_GET['id'] ?? 0);

$row = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_gudang);

if (!$row) {
    set_flash('error', 'Data gudang tidak ditemukan.');
    redirect_admin('master_setup/gudang');
}

try {
    $row->delete();
    set_flash('success', 'Data gudang berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Data gudang tidak bisa dihapus karena masih digunakan di tabel lain.');
}

redirect_admin('master_setup/gudang');