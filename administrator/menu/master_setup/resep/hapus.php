<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/ResepORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_resep = (int) ($_GET['id'] ?? 0);

$row = ResepORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_resep);

if (!$row) {
    set_flash('error', 'Data resep tidak ditemukan.');
    redirect_admin('master_setup/resep');
}

try {
    $row->delete();
    set_flash('success', 'Data resep berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Data resep tidak bisa dihapus karena masih digunakan di tabel lain.');
}

redirect_admin('master_setup/resep');