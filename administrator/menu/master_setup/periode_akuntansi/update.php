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
$id_periode        = (int) ($_POST['id_periode'] ?? 0);
$tahun             = (int) ($_POST['tahun'] ?? 0);
$bulan             = (int) ($_POST['bulan'] ?? 0);
$tanggal_mulai     = trim((string) ($_POST['tanggal_mulai'] ?? ''));
$tanggal_selesai   = trim((string) ($_POST['tanggal_selesai'] ?? ''));
$status_periode    = trim((string) ($_POST['status_periode'] ?? 'terbuka'));

$row = PeriodeAkuntansiORM::query()
    ->where('id_entitas', $id_entitas)
    ->find($id_periode);

if (!$row) {
    set_flash('error', 'Data periode akuntansi tidak ditemukan.');
    redirect_admin('master_setup/periode_akuntansi');
}

if ($tahun < 2000 || $tahun > 2100) {
    set_flash('error', 'Tahun tidak valid.');
    redirect_admin('master_setup/periode_akuntansi/edit&id=' . $id_periode);
}

if ($bulan < 1 || $bulan > 12) {
    set_flash('error', 'Bulan tidak valid.');
    redirect_admin('master_setup/periode_akuntansi/edit&id=' . $id_periode);
}

if ($tanggal_mulai === '' || $tanggal_selesai === '') {
    set_flash('error', 'Tanggal mulai dan tanggal selesai wajib diisi.');
    redirect_admin('master_setup/periode_akuntansi/edit&id=' . $id_periode);
}

if ($tanggal_mulai > $tanggal_selesai) {
    set_flash('error', 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.');
    redirect_admin('master_setup/periode_akuntansi/edit&id=' . $id_periode);
}

if (!in_array($status_periode, ['terbuka', 'tertutup'], true)) {
    set_flash('error', 'Status periode tidak valid.');
    redirect_admin('master_setup/periode_akuntansi/edit&id=' . $id_periode);
}

$exists = PeriodeAkuntansiORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('tahun', $tahun)
    ->where('bulan', $bulan)
    ->where('id_periode', '!=', $id_periode)
    ->exists();

if ($exists) {
    set_flash('error', 'Periode untuk tahun dan bulan tersebut sudah ada pada entitas ini.');
    redirect_admin('master_setup/periode_akuntansi/edit&id=' . $id_periode);
}

$row->tahun           = $tahun;
$row->bulan           = $bulan;
$row->tanggal_mulai   = $tanggal_mulai;
$row->tanggal_selesai = $tanggal_selesai;
$row->status_periode  = $status_periode;
$row->save();

set_flash('success', 'Data periode akuntansi berhasil diperbarui.');
redirect_admin('master_setup/periode_akuntansi');