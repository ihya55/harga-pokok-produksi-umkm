<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

function role_hapus_is_super_admin(array $user_login): bool
{
    $username = strtolower(trim((string) ($user_login['username'] ?? '')));
    $nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

    if ($username === 'super_admin' || $nama_role_session === 'super_admin') {
        return true;
    }

    $id_role_login = (int) ($user_login['id_role'] ?? 0);

    if ($id_role_login > 0) {
        $role = Capsule::table('tb_role')
            ->where('id_role', $id_role_login)
            ->first();

        if ($role) {
            return strtolower(trim((string) $role->nama_role)) === 'super_admin';
        }
    }

    return false;
}

$user_login = user_login();

if (!role_hapus_is_super_admin($user_login)) {
    set_flash('error', 'Hanya super admin yang boleh menghapus role.');
    redirect_admin('dashboard');
}

$id_role = (int) ($_GET['id'] ?? 0);

$row = Capsule::table('tb_role')
    ->where('id_role', $id_role)
    ->first();

if (!$row) {
    set_flash('error', 'Data role tidak ditemukan.');
    redirect_admin('master/role');
}

if (strtolower((string) $row->nama_role) === 'super_admin') {
    set_flash('error', 'Role super_admin tidak boleh dihapus.');
    redirect_admin('master/role');
}

$dipakaiPengguna = Capsule::table('tb_pengguna')
    ->where('id_role', $id_role)
    ->exists();

$dipakaiPenggunaEntitas = Capsule::table('tb_pengguna_entitas')
    ->where('id_role', $id_role)
    ->exists();

if ($dipakaiPengguna || $dipakaiPenggunaEntitas) {
    set_flash('error', 'Role tidak bisa dihapus karena sudah digunakan oleh pengguna.');
    redirect_admin('master/role');
}

try {
    Capsule::connection()->transaction(function () use ($id_role) {
        Capsule::table('tb_role_menu')
            ->where('id_role', $id_role)
            ->delete();

        Capsule::table('tb_role')
            ->where('id_role', $id_role)
            ->delete();
    });

    set_flash('success', 'Role berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Role gagal dihapus: ' . $e->getMessage());
}

redirect_admin('master/role');