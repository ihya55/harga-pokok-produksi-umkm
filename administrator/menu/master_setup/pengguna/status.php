<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

function pengguna_status_is_super_admin(array $user_login): bool
{
    $username = strtolower(trim((string) ($user_login['username'] ?? '')));
    $role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

    if ($username === 'super_admin' || $role_session === 'super_admin') {
        return true;
    }

    $id_role = (int) ($user_login['id_role'] ?? 0);
    $role = $id_role > 0 ? Capsule::table('tb_role')->where('id_role', $id_role)->first() : null;

    return $role && strtolower((string) $role->nama_role) === 'super_admin';
}

$user_login = user_login();
$is_super_admin = pengguna_status_is_super_admin($user_login);

$id_pengguna_login = (int) ($user_login['id_pengguna'] ?? 0);
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($_GET['id'] ?? 0);

if ($id_pengguna <= 0) {
    set_flash('error', 'ID pengguna tidak valid.');
    redirect_admin('master/pengguna');
}

if ($id_pengguna === $id_pengguna_login) {
    set_flash('error', 'Kamu tidak boleh menonaktifkan akun sendiri.');
    redirect_admin('master/pengguna');
}

$query = Capsule::table('tb_pengguna as p')
    ->leftJoin('tb_role as r', 'r.id_role', '=', 'p.id_role')
    ->where('p.id_pengguna', $id_pengguna);

if (!$is_super_admin) {
    $query->where('p.id_entitas', $id_entitas_login)
        ->where('r.nama_role', '<>', 'super_admin');
}

$row = $query->select(['p.*', 'r.nama_role'])->first();

if (!$row) {
    set_flash('error', 'Data pengguna tidak ditemukan atau kamu tidak punya akses.');
    redirect_admin('master/pengguna');
}

$statusBaru = (int) $row->status_aktif === 1 ? 0 : 1;

Capsule::table('tb_pengguna')
    ->where('id_pengguna', $id_pengguna)
    ->update([
        'status_aktif' => $statusBaru,
        'tanggal_diubah' => date('Y-m-d H:i:s'),
        'diubah_oleh' => $id_pengguna_login ?: null,
    ]);

Capsule::table('tb_pengguna_entitas')
    ->where('id_pengguna', $id_pengguna)
    ->update([
        'status_aktif' => $statusBaru,
        'tanggal_diubah' => date('Y-m-d H:i:s'),
        'diubah_oleh' => $id_pengguna_login ?: null,
    ]);

set_flash('success', 'Status pengguna berhasil diubah.');
redirect_admin('master/pengguna');