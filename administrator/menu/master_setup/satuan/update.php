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

$id_satuan   = (int) ($_POST['id_satuan'] ?? 0);
$nama_satuan = trim((string) ($_POST['nama_satuan'] ?? ''));
$keterangan  = trim((string) ($_POST['keterangan'] ?? ''));
$id_pengguna = (int) (user_login()['id_pengguna'] ?? 0);

$row = SatuanORM::find($id_satuan);

if (!$row) {
    set_flash('error', 'Data satuan tidak ditemukan.');
    redirect_admin('master_setup/satuan');
}

if ($nama_satuan === '') {
    set_flash('error', 'Nama satuan wajib diisi.');
    redirect_admin('master_setup/satuan/edit&id=' . $id_satuan);
}

$cek = SatuanORM::query()
    ->whereRaw('LOWER(nama_satuan) = ?', [mb_strtolower($nama_satuan)])
    ->where('id_satuan', '!=', $id_satuan)
    ->first();

if ($cek) {
    set_flash('error', 'Nama satuan sudah digunakan.');
    redirect_admin('master_setup/satuan/edit&id=' . $id_satuan);
}

$row->nama_satuan    = $nama_satuan;
$row->keterangan     = $keterangan !== '' ? $keterangan : null;
$row->tanggal_diubah = date('Y-m-d H:i:s');
$row->diubah_oleh    = $id_pengguna > 0 ? $id_pengguna : null;
$row->save();

set_flash('success', 'Data satuan berhasil diperbarui.');
redirect_admin('master_setup/satuan');