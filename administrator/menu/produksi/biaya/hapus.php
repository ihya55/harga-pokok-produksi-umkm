<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/BiayaProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_biaya_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/biaya');
}

if ($id_biaya_produksi <= 0) {
    set_flash('error', 'ID biaya produksi tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

$row = BiayaProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_biaya_produksi);

if (!$row) {
    set_flash('error', 'Data biaya produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Biaya produksi yang sudah diposting tidak bisa dihapus.');
    header('Location: ' . $back_url);
    exit;
}

try {
    $row->delete();

    set_flash('success', 'Biaya produksi berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal menghapus biaya produksi: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;