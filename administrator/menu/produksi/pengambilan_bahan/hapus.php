<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PengambilanBahanORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengambilan_bahan = (int) ($_GET['id'] ?? 0);

$back_url = trim((string) ($_GET['back_url'] ?? ''));

if ($back_url === '') {
    $back_url = admin_url('index.php?menu=produksi/pengambilan-bahan');
}

$row = PengambilanBahanORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pengambilan_bahan);

if (!$row) {
    set_flash('error', 'Data pengambilan bahan tidak ditemukan.');
    header('Location: ' . $back_url);
    exit;
}

if ((string) $row->status_posting !== 'draft') {
    set_flash('error', 'Hanya pengambilan bahan draft yang bisa dihapus.');
    header('Location: ' . $back_url);
    exit;
}

try {
    $row->delete();
    set_flash('success', 'Pengambilan bahan berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal menghapus pengambilan bahan: ' . $e->getMessage());
}

header('Location: ' . $back_url);
exit;