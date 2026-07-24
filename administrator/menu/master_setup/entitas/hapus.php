<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

function entitas_hapus_is_super_admin(array $user_login): bool
{
    $username = strtolower(trim((string) ($user_login['username'] ?? '')));
    $nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

    if ($username === 'super_admin' || $nama_role_session === 'super_admin') {
        return true;
    }

    $id_role = (int) ($user_login['id_role'] ?? 0);

    if ($id_role > 0) {
        $role = Capsule::table('tb_role')
            ->where('id_role', $id_role)
            ->first();

        if ($role) {
            $nama_role = strtolower(trim((string) ($role->nama_role ?? '')));
            $kode_role = strtolower(trim((string) ($role->kode_role ?? '')));

            return $nama_role === 'super_admin' || $kode_role === 'super_admin';
        }
    }

    return false;
}

$user_login = user_login();
$is_super_admin = entitas_hapus_is_super_admin($user_login);

if (!$is_super_admin) {
    set_flash('error', 'Hanya super admin yang boleh menghapus entitas.');
    redirect_admin('master/entitas');
}

$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);
$id_entitas = (int) ($_GET['id'] ?? 0);

if ($id_entitas <= 0) {
    set_flash('error', 'ID entitas tidak valid.');
    redirect_admin('master/entitas');
}

$row = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

if (!$row) {
    set_flash('error', 'Data entitas tidak ditemukan.');
    redirect_admin('master/entitas');
}

if ($id_entitas_login === $id_entitas) {
    set_flash('error', 'Entitas yang sedang aktif digunakan tidak boleh dihapus.');
    redirect_admin('master/entitas');
}

$dipakaiPengguna = Capsule::table('tb_pengguna')
    ->where('id_entitas', $id_entitas)
    ->exists();

$dipakaiAkses = Capsule::table('tb_pengguna_entitas')
    ->where('id_entitas', $id_entitas)
    ->exists();

$dipakaiGudang = Capsule::table('tb_gudang')
    ->where('id_entitas', $id_entitas)
    ->exists();

$dipakaiPelanggan = Capsule::table('tb_pelanggan')
    ->where('id_entitas', $id_entitas)
    ->exists();

$dipakaiPemasok = Capsule::table('tb_pemasok')
    ->where('id_entitas', $id_entitas)
    ->exists();

$dipakaiProduk = Capsule::table('tb_produk')
    ->where('id_entitas', $id_entitas)
    ->exists();

$dipakaiBahanBaku = Capsule::table('tb_bahan_baku')
    ->where('id_entitas', $id_entitas)
    ->exists();

$dipakaiJurnal = Capsule::table('tb_jurnal')
    ->where('id_entitas', $id_entitas)
    ->exists();

$dipakaiTransaksi = (
    $dipakaiPengguna ||
    $dipakaiAkses ||
    $dipakaiGudang ||
    $dipakaiPelanggan ||
    $dipakaiPemasok ||
    $dipakaiProduk ||
    $dipakaiBahanBaku ||
    $dipakaiJurnal
);

if ($dipakaiTransaksi) {
    set_flash('error', 'Entitas tidak bisa dihapus karena sudah digunakan. Gunakan fitur nonaktifkan status.');
    redirect_admin('master/entitas');
}

try {
    Capsule::table('tb_entitas')
        ->where('id_entitas', $id_entitas)
        ->delete();

    set_flash('success', 'Data entitas berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Entitas tidak bisa dihapus karena masih digunakan di tabel lain.');
}

redirect_admin('master/entitas');