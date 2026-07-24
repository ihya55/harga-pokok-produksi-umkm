<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

require_once __DIR__ . '/../../../../orm/SaldoAwalStokORM.php';

harus_login();

$user_login = user_login();
$id_entitas = (int) ($user_login['id_entitas'] ?? 0);

$id_saldo_awal_stok = (int) ($_GET['id'] ?? 0);
$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_page_url('persediaan/saldo-stok/saldo-awal');
}

if ($id_saldo_awal_stok <= 0) {
    set_flash('error', 'ID saldo awal stok tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

$row = SaldoAwalStokORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('id_saldo_awal_stok', $id_saldo_awal_stok)
    ->first();

if (!$row) {
    set_flash('error', 'Data saldo awal stok tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Saldo awal stok yang sudah posted tidak bisa dihapus.');
    header('Location: ' . admin_page_url('persediaan/saldo-stok/saldo-awal/detail') . '&id=' . $id_saldo_awal_stok . '&back_url=' . urlencode($back_url));
    exit;
}

$row->delete();

set_flash('success', 'Saldo awal stok berhasil dihapus.');
header('Location: ' . $back_url);
exit;