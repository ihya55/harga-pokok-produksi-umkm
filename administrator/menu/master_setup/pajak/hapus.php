<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../../../../orm/PajakORM.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pajak = (int) ($_GET['id'] ?? 0);
$row = PajakORM::query()->where('id_entitas', $id_entitas)->find($id_pajak);

if (!$row) {
    set_flash('error', 'Data pajak tidak ditemukan.');
    redirect_admin('master_setup/pajak');
}

$row->delete();
set_flash('success', 'Data pajak berhasil dihapus.');
redirect_admin('master_setup/pajak');
