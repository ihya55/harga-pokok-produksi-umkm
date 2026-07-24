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

$back_url = trim((string) ($_POST['back_url'] ?? ''));
if ($back_url === '') {
    $back_url = admin_page_url('persediaan/stok-opname');
}

$ids = $_POST['ids'] ?? [];

if (!is_array($ids) || count($ids) === 0) {
    set_flash('error', 'Pilih minimal satu data yang ingin dihapus.');
    header('Location: ' . $back_url);
    exit;
}

$ids = array_values(array_unique(array_filter(array_map('intval', $ids))));

if (count($ids) === 0) {
    set_flash('error', 'Data yang dipilih tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

$jumlah = StokOpnameORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('status_posting', 'draft')
    ->whereIn('id_stok_opname', $ids)
    ->delete();

if ($jumlah <= 0) {
    set_flash('error', 'Tidak ada data draft yang dapat dihapus.');
} else {
    set_flash('success', $jumlah . ' data stok opname berhasil dihapus.');
}

header('Location: ' . $back_url);
exit;