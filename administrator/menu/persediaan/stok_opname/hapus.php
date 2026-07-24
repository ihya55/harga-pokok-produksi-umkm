<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/StokOpnameORM.php';

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);

$id_stok_opname = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/stok-opname');
}

if ($id_stok_opname <= 0) {
    set_flash('error', 'ID stok opname tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

$row = StokOpnameORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_stok_opname', $id_stok_opname)
    ->first();

if (!$row) {
    set_flash('error', 'Data stok opname tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Stok opname yang sudah posted tidak bisa dihapus.');
    header('Location: ' . admin_page_url('persediaan/stok-opname/detail') . '&id=' . $id_stok_opname . '&back_url=' . urlencode($back_url));
    exit;
}

$row->delete();

set_flash('success', 'Stok opname berhasil dihapus.');
header('Location: ' . $back_url);
exit;