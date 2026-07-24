<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';

require_once __DIR__ . '/../../../../orm/SatuanORM.php';

require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/satuan');
}

$nama_satuan = trim((string) ($_POST['nama_satuan'] ?? ''));
$keterangan  = trim((string) ($_POST['keterangan'] ?? ''));
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

if ($nama_satuan === '') {
    set_flash('error', 'Nama satuan wajib diisi.');
    redirect_admin('master_setup/satuan/tambah');
}

$cek = SatuanORM::query()
    ->whereRaw('LOWER(nama_satuan) = ?', [mb_strtolower($nama_satuan)])
    ->first();

if ($cek) {
    set_flash('error', 'Nama satuan sudah ada.');
    redirect_admin('master_setup/satuan/tambah');
}

SatuanORM::create([
    'nama_satuan'    => $nama_satuan,
    'keterangan'     => $keterangan !== '' ? $keterangan : null,
    'tanggal_dibuat' => date('Y-m-d H:i:s'),
    'dibuat_oleh'    => $id_pengguna > 0 ? $id_pengguna : null,
    'tanggal_diubah' => null,
    'diubah_oleh'    => null,
]);

set_flash('success', 'Data satuan berhasil disimpan.');
redirect_admin('master_setup/satuan');