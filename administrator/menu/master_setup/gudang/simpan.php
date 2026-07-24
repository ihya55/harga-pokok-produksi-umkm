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
$nama_gudang   = trim((string) ($_POST['nama_gudang'] ?? ''));
$jenis_gudang  = trim((string) ($_POST['jenis_gudang'] ?? ''));
$alamat        = trim((string) ($_POST['alamat'] ?? ''));
$status_aktif  = (int) ($_POST['status_aktif'] ?? 1);

if ($nama_gudang === '' || $jenis_gudang === '') {
    set_flash('error', 'Nama gudang dan jenis gudang wajib diisi.');
    redirect_admin('master_setup/gudang/tambah');
}

$kode_gudang = generate_kode_master(
    'tb_gudang',
    'kode_gudang',
    'GDG',
    4,
    $id_entitas
);

GudangORM::create([
    'id_entitas'      => $id_entitas,
    'kode_gudang'     => $kode_gudang,
    'nama_gudang'     => $nama_gudang,
    'jenis_gudang'    => $jenis_gudang,
    'alamat'          => $alamat !== '' ? $alamat : null,
    'status_aktif'    => $status_aktif,
    'tanggal_dibuat'  => date('Y-m-d H:i:s'),
    'dibuat_oleh'     => $id_pengguna,
]);

set_flash('success', 'Data gudang berhasil disimpan dengan kode ' . $kode_gudang . '.');
redirect_admin('master_setup/gudang');