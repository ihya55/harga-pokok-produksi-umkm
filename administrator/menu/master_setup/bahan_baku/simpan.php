<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';

require_once __DIR__ . '/../../../../orm/BahanBakuORM.php';
require_once __DIR__ . '/../../../../orm/SatuanORM.php';
require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/bahan_baku');
}

$id_entitas         = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna        = (int) (user_login()['id_pengguna'] ?? 0);
$nama_bahan_baku    = trim((string) ($_POST['nama_bahan_baku'] ?? ''));
$id_satuan          = (int) ($_POST['id_satuan'] ?? 0);

$harga_standar      = (float) ($_POST['harga_standar'] ?? 0);
$stok_minimum       = (int) ($_POST['stok_minimum'] ?? 0);

$id_coa_persediaan  = ($_POST['id_coa_persediaan'] ?? '') !== '' ? (int) $_POST['id_coa_persediaan'] : null;
$status_aktif       = (int) ($_POST['status_aktif'] ?? 1);

if ($nama_bahan_baku === '') {
    set_flash('error', 'Nama bahan wajib diisi.');
    redirect_admin('master_setup/bahan_baku/tambah');
}

if ($id_satuan <= 0 || !SatuanORM::query()->where('id_satuan', $id_satuan)->exists()) {
    set_flash('error', 'Satuan tidak valid.');
    redirect_admin('master_setup/bahan_baku/tambah');
}

if ($harga_standar < 0 || $stok_minimum < 0) {
    set_flash('error', 'Nilai angka tidak valid.');
    redirect_admin('master_setup/bahan_baku/tambah');
}

if ($id_coa_persediaan !== null && !CoaORM::query()->where('id_entitas', $id_entitas)->where('id_coa', $id_coa_persediaan)->exists()) {
    set_flash('error', 'COA persediaan tidak valid.');
    redirect_admin('master_setup/bahan_baku/tambah');
}

$kode_bahan_baku = generate_kode_master('tb_bahan_baku', 'kode_bahan_baku', 'BHN', 4, $id_entitas);

BahanBakuORM::create([
    'id_entitas'         => $id_entitas,
    'kode_bahan_baku'    => $kode_bahan_baku,
    'nama_bahan_baku'    => $nama_bahan_baku,
    'id_satuan'          => $id_satuan,
    'harga_standar'      => $harga_standar,
    'stok_minimum'       => $stok_minimum,
    'status_aktif'       => $status_aktif,
    'id_coa_persediaan'  => $id_coa_persediaan,
    'tanggal_dibuat'     => date('Y-m-d H:i:s'),
    'dibuat_oleh'        => $id_pengguna > 0 ? $id_pengguna : null,
    'tanggal_diubah'     => null,
    'diubah_oleh'        => null,
]);

set_flash('success', 'Data bahan baku berhasil disimpan.');
redirect_admin('master_setup/bahan_baku');