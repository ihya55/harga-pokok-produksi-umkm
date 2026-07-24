<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/PeriodeAkuntansiORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/periode_akuntansi');
}

$id_entitas        = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna       = (int) (user_login()['id_pengguna'] ?? 0);
$tahun             = (int) ($_POST['tahun'] ?? 0);
$bulan             = (int) ($_POST['bulan'] ?? 0);
$tanggal_mulai     = trim((string) ($_POST['tanggal_mulai'] ?? ''));
$tanggal_selesai   = trim((string) ($_POST['tanggal_selesai'] ?? ''));
$status_periode    = trim((string) ($_POST['status_periode'] ?? 'terbuka'));

if ($tahun < 2000 || $tahun > 2100) {
    set_flash('error', 'Tahun tidak valid.');
    redirect_admin('master_setup/periode_akuntansi/tambah');
}

if ($bulan < 1 || $bulan > 12) {
    set_flash('error', 'Bulan tidak valid.');
    redirect_admin('master_setup/periode_akuntansi/tambah');
}

if ($tanggal_mulai === '' || $tanggal_selesai === '') {
    set_flash('error', 'Tanggal mulai dan tanggal selesai wajib diisi.');
    redirect_admin('master_setup/periode_akuntansi/tambah');
}

if ($tanggal_mulai > $tanggal_selesai) {
    set_flash('error', 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
    redirect_admin('master_setup/periode_akuntansi/tambah');
}

if (!in_array($status_periode, ['terbuka', 'tertutup'], true)) {
    set_flash('error', 'Status periode tidak valid.');
    redirect_admin('master_setup/periode_akuntansi/tambah');
}

$exists = PeriodeAkuntansiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('tahun', $tahun)
    ->where('bulan', $bulan)
    ->exists();

if ($exists) {
    set_flash('error', 'Periode untuk tahun dan bulan tersebut sudah ada pada entitas ini.');
    redirect_admin('master_setup/periode_akuntansi/tambah');
}

PeriodeAkuntansiORM::create([
    'id_entitas'       => $id_entitas,
    'tahun'            => $tahun,
    'bulan'            => $bulan,
    'tanggal_mulai'    => $tanggal_mulai,
    'tanggal_selesai'  => $tanggal_selesai,
    'status_periode'   => $status_periode,
    'tanggal_dibuat'   => date('Y-m-d H:i:s'),
    'dibuat_oleh'      => $id_pengguna > 0 ? $id_pengguna : null,
]);

set_flash('success', 'Data periode akuntansi berhasil disimpan.');
redirect_admin('master_setup/periode_akuntansi');