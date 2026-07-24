<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_perintah_produksi = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/perintah');
}

if ($id_perintah_produksi <= 0) {
    set_flash('error', 'ID perintah produksi tidak valid.');
    header('Location: ' . $back_url);
    exit;
}

$row = PerintahProduksiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_perintah_produksi);

if (!$row) {
    set_flash('error', 'Data perintah produksi tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_produksi !== 'draft') {
    set_flash('error', 'Perintah produksi yang sudah posted tidak bisa dihapus.');
    header('Location: ' . $back_url);
    exit;
}

try {
    $row->delete();
    set_flash('success', 'Perintah produksi berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal menghapus perintah produksi. Kemungkinan data sudah digunakan di modul lain.');
}

header('Location: ' . $back_url);
exit;