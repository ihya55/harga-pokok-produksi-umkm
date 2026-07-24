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
$id_pelanggan    = (int) ($_POST['id_pelanggan'] ?? 0);
$nama_pelanggan  = trim((string) ($_POST['nama_pelanggan'] ?? ''));
$alamat          = trim((string) ($_POST['alamat'] ?? ''));
$no_hp           = trim((string) ($_POST['no_hp'] ?? ''));
$email           = trim((string) ($_POST['email'] ?? ''));
$jenis_pelanggan = trim((string) ($_POST['jenis_pelanggan'] ?? 'umum'));
$batas_piutang   = (float) ($_POST['batas_piutang'] ?? 0);
$tempo_hari      = (int) ($_POST['tempo_hari'] ?? 0);
$status_aktif    = (int) ($_POST['status_aktif'] ?? 1);

$row = PelangganORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_pelanggan);

if (!$row) {
    set_flash('error', 'Data pelanggan tidak ditemukan.');
    redirect_admin('master_setup/pelanggan');
}

if ($nama_pelanggan === '') {
    set_flash('error', 'Nama pelanggan wajib diisi.');
    redirect_admin('master_setup/pelanggan/edit&id=' . $id_pelanggan);
}

$row->nama_pelanggan  = $nama_pelanggan;
$row->alamat          = $alamat !== '' ? $alamat : null;
$row->no_hp           = $no_hp !== '' ? $no_hp : null;
$row->email           = $email !== '' ? $email : null;
$row->jenis_pelanggan = $jenis_pelanggan !== '' ? $jenis_pelanggan : 'umum';
$row->batas_piutang   = $batas_piutang;
$row->tempo_hari      = $tempo_hari;
$row->status_aktif    = $status_aktif;
$row->tanggal_diubah  = date('Y-m-d H:i:s');
$row->diubah_oleh     = $id_pengguna > 0 ? $id_pengguna : null;
$row->save();

set_flash('success', 'Data pelanggan berhasil diperbarui.');
redirect_admin('master_setup/pelanggan');