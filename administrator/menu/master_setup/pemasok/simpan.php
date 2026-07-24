<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/PemasokORM.php';

require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/pemasok');
}

$id_entitas     = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna    = (int) (user_login()['id_pengguna'] ?? 0);
$nama_pemasok   = trim((string) ($_POST['nama_pemasok'] ?? ''));
$alamat         = trim((string) ($_POST['alamat'] ?? ''));
$no_hp          = trim((string) ($_POST['no_hp'] ?? ''));
$email          = trim((string) ($_POST['email'] ?? ''));
$tempo_hari     = (int) ($_POST['tempo_hari'] ?? 0);
$status_aktif   = (int) ($_POST['status_aktif'] ?? 1);

if ($nama_pemasok === '') {
    set_flash('error', 'Nama pemasok wajib diisi.');
    redirect_admin('master_setup/pemasok/tambah');
}

$kode_pemasok = generate_kode_master(
    'tb_pemasok',
    'kode_pemasok',
    'PMS',
    4,
    $id_entitas
);

PemasokORM::create([
    'id_entitas'      => $id_entitas,
    'kode_pemasok'    => $kode_pemasok,
    'nama_pemasok'    => $nama_pemasok,
    'alamat'          => $alamat !== '' ? $alamat : null,
    'no_hp'           => $no_hp !== '' ? $no_hp : null,
    'email'           => $email !== '' ? $email : null,
    'tempo_hari'      => $tempo_hari,
    'status_aktif'    => $status_aktif,
    'tanggal_dibuat'  => date('Y-m-d H:i:s'),
    'dibuat_oleh'     => $id_pengguna > 0 ? $id_pengguna : null,
    'tanggal_diubah'  => null,
    'diubah_oleh'     => null,
]);

set_flash('success', 'Data pemasok berhasil disimpan dengan kode ' . $kode_pemasok . '.');
redirect_admin('master_setup/pemasok');