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

$id_entitas    = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna   = (int) (user_login()['id_pengguna'] ?? 0);
$id_pemasok    = (int) ($_POST['id_pemasok'] ?? 0);
$nama_pemasok  = trim((string) ($_POST['nama_pemasok'] ?? ''));
$alamat        = trim((string) ($_POST['alamat'] ?? ''));
$no_hp         = trim((string) ($_POST['no_hp'] ?? ''));
$email         = trim((string) ($_POST['email'] ?? ''));
$tempo_hari    = (int) ($_POST['tempo_hari'] ?? 0);
$status_aktif  = (int) ($_POST['status_aktif'] ?? 1);

$row = PemasokORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pemasok);

if (!$row) {
    set_flash('error', 'Data pemasok tidak ditemukan.');
    redirect_admin('master_setup/pemasok');
}

if ($nama_pemasok === '') {
    set_flash('error', 'Nama pemasok wajib diisi.');
    redirect_admin('master_setup/pemasok/edit&id=' . $id_pemasok);
}

$row->nama_pemasok   = $nama_pemasok;
$row->alamat         = $alamat !== '' ? $alamat : null;
$row->no_hp          = $no_hp !== '' ? $no_hp : null;
$row->email          = $email !== '' ? $email : null;
$row->tempo_hari     = $tempo_hari;
$row->status_aktif   = $status_aktif;
$row->tanggal_diubah = date('Y-m-d H:i:s');
$row->diubah_oleh    = $id_pengguna > 0 ? $id_pengguna : null;
$row->save();

set_flash('success', 'Data pemasok berhasil diperbarui.');
redirect_admin('master_setup/pemasok');