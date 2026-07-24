<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/GudangORM.php';

require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/gudang');
}

$id_entitas    = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna   = (int) (user_login()['id_pengguna'] ?? 0);
$id_gudang     = (int) ($_POST['id_gudang'] ?? 0);
$nama_gudang   = trim((string) ($_POST['nama_gudang'] ?? ''));
$jenis_gudang  = trim((string) ($_POST['jenis_gudang'] ?? ''));
$alamat        = trim((string) ($_POST['alamat'] ?? ''));
$status_aktif  = (int) ($_POST['status_aktif'] ?? 1);

$row = GudangORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_gudang);

if (!$row) {
    set_flash('error', 'Data gudang tidak ditemukan.');
    redirect_admin('master_setup/gudang');
}

if ($nama_gudang === '' || $jenis_gudang === '') {
    set_flash('error', 'Nama gudang dan jenis gudang wajib diisi.');
    redirect_admin('master_setup/gudang/edit&id=' . $id_gudang);
}

$row->nama_gudang    = $nama_gudang;
$row->jenis_gudang   = $jenis_gudang;
$row->alamat         = $alamat !== '' ? $alamat : null;
$row->status_aktif   = $status_aktif;
$row->tanggal_diubah = date('Y-m-d H:i:s');
$row->diubah_oleh    = $id_pengguna;
$row->save();

set_flash('success', 'Data gudang berhasil diperbarui.');
redirect_admin('master_setup/gudang');