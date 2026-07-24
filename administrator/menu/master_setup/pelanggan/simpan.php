<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/PelangganORM.php';

require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/pelanggan');
}

$id_entitas      = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna     = (int) (user_login()['id_pengguna'] ?? 0);
$nama_pelanggan  = trim((string) ($_POST['nama_pelanggan'] ?? ''));
$alamat          = trim((string) ($_POST['alamat'] ?? ''));
$no_hp           = trim((string) ($_POST['no_hp'] ?? ''));
$email           = trim((string) ($_POST['email'] ?? ''));
$jenis_pelanggan = trim((string) ($_POST['jenis_pelanggan'] ?? 'umum'));
$batas_piutang   = (float) ($_POST['batas_piutang'] ?? 0);
$tempo_hari      = (int) ($_POST['tempo_hari'] ?? 0);
$status_aktif    = (int) ($_POST['status_aktif'] ?? 1);

if ($nama_pelanggan === '') {
    set_flash('error', 'Nama pelanggan wajib diisi.');
    redirect_admin('master_setup/pelanggan/tambah');
}

$kode_pelanggan = generate_kode_master(
    'tb_pelanggan',
    'kode_pelanggan',
    'PLG',
    4,
    $id_entitas
);

PelangganORM::create([
    'id_entitas'      => $id_entitas,
    'kode_pelanggan'  => $kode_pelanggan,
    'nama_pelanggan'  => $nama_pelanggan,
    'alamat'          => $alamat !== '' ? $alamat : null,
    'no_hp'           => $no_hp !== '' ? $no_hp : null,
    'email'           => $email !== '' ? $email : null,
    'jenis_pelanggan' => $jenis_pelanggan !== '' ? $jenis_pelanggan : 'umum',
    'batas_piutang'   => $batas_piutang,
    'tempo_hari'      => $tempo_hari,
    'status_aktif'    => $status_aktif,
    'tanggal_dibuat'  => date('Y-m-d H:i:s'),
    'dibuat_oleh'     => $id_pengguna > 0 ? $id_pengguna : null,
    'tanggal_diubah'  => null,
    'diubah_oleh'     => null,
]);

set_flash('success', 'Data pelanggan berhasil disimpan dengan kode ' . $kode_pelanggan . '.');
redirect_admin('master_setup/pelanggan');