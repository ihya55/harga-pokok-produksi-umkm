<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/coa');
}

$id_entitas            = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna           = (int) (user_login()['id_pengguna'] ?? 0);
$kode_coa              = strtoupper(trim((string) ($_POST['kode_coa'] ?? '')));
$nama_coa              = trim((string) ($_POST['nama_coa'] ?? ''));
$id_coa_induk          = $_POST['id_coa_induk'] !== '' ? (int) $_POST['id_coa_induk'] : null;
$level_coa             = (int) ($_POST['level_coa'] ?? 1);
$kategori_coa          = trim((string) ($_POST['kategori_coa'] ?? ''));
$posisi_saldo_normal   = trim((string) ($_POST['posisi_saldo_normal'] ?? 'debit'));
$boleh_transaksi       = (int) ($_POST['boleh_transaksi'] ?? 1);
$status_aktif          = (int) ($_POST['status_aktif'] ?? 1);

if ($kode_coa === '') {
    set_flash('error', 'Kode COA wajib diisi.');
    redirect_admin('master_setup/coa/tambah');
}

if ($nama_coa === '') {
    set_flash('error', 'Nama COA wajib diisi.');
    redirect_admin('master_setup/coa/tambah');
}

if ($kategori_coa === '') {
    set_flash('error', 'Kategori COA wajib diisi.');
    redirect_admin('master_setup/coa/tambah');
}

$allowedKategoriCoa = ['aset', 'liabilitas', 'ekuitas', 'pendapatan', 'hpp', 'beban'];

if (!in_array($kategori_coa, $allowedKategoriCoa, true)) {
    set_flash('error', 'Kategori COA tidak valid.');
    redirect_admin('master_setup/coa/tambah');
}

if (!in_array($posisi_saldo_normal, ['debit', 'kredit'], true)) {
    set_flash('error', 'Posisi saldo normal harus debit atau kredit.');
    redirect_admin('master_setup/coa/tambah');
}

$exists = CoaORM::query()
    ->where('id_entitas', $id_entitas)
    ->where('kode_coa', $kode_coa)
    ->exists();

if ($exists) {
    set_flash('error', 'Kode COA sudah digunakan pada entitas ini.');
    redirect_admin('master_setup/coa/tambah');
}

CoaORM::create([
    'id_entitas'           => $id_entitas,
    'kode_coa'             => $kode_coa,
    'nama_coa'             => $nama_coa,
    'id_coa_induk'         => $id_coa_induk,
    'level_coa'            => $level_coa > 0 ? $level_coa : 1,
    'kategori_coa'         => $kategori_coa,
    'posisi_saldo_normal'  => $posisi_saldo_normal,
    'boleh_transaksi'      => $boleh_transaksi,
    'status_aktif'         => $status_aktif,
    'tanggal_dibuat'       => date('Y-m-d H:i:s'),
    'dibuat_oleh'          => $id_pengguna > 0 ? $id_pengguna : null,
    'tanggal_diubah'       => null,
    'diubah_oleh'          => null,
]);

set_flash('success', 'Data COA berhasil disimpan.');
redirect_admin('master_setup/coa');