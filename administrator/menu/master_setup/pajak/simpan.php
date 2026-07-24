<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';
require_once __DIR__ . '/../../../../orm/PajakORM.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect_admin('master_setup/pajak');

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);
$kode_pajak = strtoupper(trim((string) ($_POST['kode_pajak'] ?? '')));
$nama_pajak = trim((string) ($_POST['nama_pajak'] ?? ''));
$persentase = (float) ($_POST['persentase'] ?? 0);
$is_default = (int) ($_POST['is_default'] ?? 0);
$status_aktif = (int) ($_POST['status_aktif'] ?? 1);
$keterangan = trim((string) ($_POST['keterangan'] ?? ''));

if ($kode_pajak === '' || $nama_pajak === '') {
    set_flash('error', 'Kode dan nama pajak wajib diisi.');
    redirect_admin('master_setup/pajak/tambah');
}
if ($persentase < 0 || $persentase > 100) {
    set_flash('error', 'Persentase pajak harus antara 0 sampai 100.');
    redirect_admin('master_setup/pajak/tambah');
}

if ($is_default === 1) {
    PajakORM::query()->where('id_entitas', $id_entitas)->where('kode_pajak', $kode_pajak)->update(['is_default' => 0]);
}

PajakORM::create([
    'id_entitas' => $id_entitas,
    'kode_pajak' => $kode_pajak,
    'nama_pajak' => $nama_pajak,
    'persentase' => round($persentase, 2),
    'is_default' => $is_default,
    'status_aktif' => $status_aktif,
    'keterangan' => $keterangan !== '' ? $keterangan : null,
    'tanggal_dibuat' => date('Y-m-d H:i:s'),
    'dibuat_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
]);

set_flash('success', 'Data pajak berhasil disimpan.');
redirect_admin('master_setup/pajak');
