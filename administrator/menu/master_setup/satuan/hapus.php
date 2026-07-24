<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/SatuanORM.php';

require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../../../../helpers/menu.php';
require_once __DIR__ . '/../../../../helpers/render.php';

harus_login();

$id_satuan = (int) ($_GET['id'] ?? 0);

$row = SatuanORM::find($id_satuan);

if (!$row) {
    set_flash('error', 'Data satuan tidak ditemukan.');
    redirect_admin('master_setup/satuan');
}

try {
    $row->delete();
    set_flash('success', 'Data satuan berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Data satuan tidak bisa dihapus karena masih digunakan di tabel lain.');
}

redirect_admin('master_setup/satuan');