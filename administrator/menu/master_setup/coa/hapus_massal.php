<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/coa');
}

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$ids = $_POST['id_coa'] ?? [];

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih minimal satu data untuk dihapus.');
    redirect_admin('master_setup/coa');
}

$ids = array_map('intval', $ids);
$ids = array_filter($ids);

if (count($ids) === 0) {
    set_flash('error', 'Data yang dipilih tidak valid.');
    redirect_admin('master_setup/coa');
}

try {
    CoaORM::query()
        ->where('id_entitas', $id_entitas)
        ->whereIn('id_coa', $ids)
        ->delete();

    set_flash('success', 'Data COA terpilih berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Sebagian/semua data gagal dihapus karena masih dipakai di tabel lain.');
}

redirect_admin('master_setup/coa');