<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master/pengguna');
}

function pengguna_update_is_super_admin(array $user_login): bool
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
$is_super_admin = pengguna_update_is_super_admin($user_login);

$id_pengguna_login = (int) ($user_login['id_pengguna'] ?? 0);
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

$id_pengguna = (int) ($_POST['id_pengguna'] ?? 0);
$id_entitas_post = (int) ($_POST['id_entitas'] ?? 0);
$id_role = (int) ($_POST['id_role'] ?? 0);
$nama_pengguna = trim((string) ($_POST['nama_pengguna'] ?? ''));
$username = strtolower(trim((string) ($_POST['username'] ?? '')));
$nama_lengkap = trim((string) ($_POST['nama_lengkap'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$no_hp = trim((string) ($_POST['no_hp'] ?? ''));
$status_aktif = (int) ($_POST['status_aktif'] ?? 1);

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

$id_entitas = $is_super_admin ? $id_entitas_post : (int) $row->id_entitas;

if ($id_entitas <= 0 || $id_role <= 0 || $nama_pengguna === '' || $username === '' || $nama_lengkap === '') {
    set_flash('error', 'Entitas, role, nama pengguna, username, dan nama lengkap wajib diisi.');
    redirect_admin('master/pengguna/edit&id=' . $id_pengguna);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Format email tidak valid.');
    redirect_admin('master/pengguna/edit&id=' . $id_pengguna);
}

$role = Capsule::table('tb_role')->where('id_role', $id_role)->first();

if (!$role) {
    set_flash('error', 'Role tidak valid.');
    redirect_admin('master/pengguna/edit&id=' . $id_pengguna);
}

if (!$is_super_admin && strtolower((string) $role->nama_role) === 'super_admin') {
    set_flash('error', 'Kamu tidak boleh mengubah pengguna menjadi role super_admin.');
    redirect_admin('master/pengguna/edit&id=' . $id_pengguna);
}

$cekUsername = Capsule::table('tb_pengguna')
    ->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
    ->where('id_pengguna', '!=', $id_pengguna)
    ->exists();

if ($cekUsername) {
    set_flash('error', 'Username sudah digunakan.');
    redirect_admin('master/pengguna/edit&id=' . $id_pengguna);
}

if ($email !== '') {
    $cekEmail = Capsule::table('tb_pengguna')
        ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
        ->where('id_pengguna', '!=', $id_pengguna)
        ->exists();

    if ($cekEmail) {
        set_flash('error', 'Email sudah digunakan pengguna lain.');
        redirect_admin('master/pengguna/edit&id=' . $id_pengguna);
    }
}

try {
    Capsule::connection()->transaction(function () use (
        $id_pengguna,
        $id_entitas,
        $id_role,
        $nama_pengguna,
        $username,
        $nama_lengkap,
        $email,
        $no_hp,
        $status_aktif,
        $id_pengguna_login
    ) {
        Capsule::table('tb_pengguna')
            ->where('id_pengguna', $id_pengguna)
            ->update([
                'id_entitas' => $id_entitas,
                'id_role' => $id_role,
                'nama_pengguna' => $nama_pengguna,
                'username' => $username,
                'nama_lengkap' => $nama_lengkap,
                'email' => $email !== '' ? $email : null,
                'no_hp' => $no_hp !== '' ? $no_hp : null,
                'status_aktif' => $status_aktif === 1 ? 1 : 0,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna_login ?: null,
            ]);

        $relasi = Capsule::table('tb_pengguna_entitas')
            ->where('id_pengguna', $id_pengguna)
            ->where('id_entitas', $id_entitas)
            ->first();

        if ($relasi) {
            Capsule::table('tb_pengguna_entitas')
                ->where('id_pengguna_entitas', (int) $relasi->id_pengguna_entitas)
                ->update([
                    'id_role' => $id_role,
                    'entitas_default' => 1,
                    'status_aktif' => $status_aktif === 1 ? 1 : 0,
                    'tanggal_diubah' => date('Y-m-d H:i:s'),
                    'diubah_oleh' => $id_pengguna_login ?: null,
                ]);
        } else {
            Capsule::table('tb_pengguna_entitas')->insert([
                'id_pengguna' => $id_pengguna,
                'id_entitas' => $id_entitas,
                'id_role' => $id_role,
                'entitas_default' => 1,
                'status_aktif' => $status_aktif === 1 ? 1 : 0,
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna_login ?: null,
            ]);
        }

        Capsule::table('tb_pengguna_entitas')
            ->where('id_pengguna', $id_pengguna)
            ->where('id_entitas', '!=', $id_entitas)
            ->update([
                'entitas_default' => 0,
            ]);
    });

    set_flash('success', 'Pengguna berhasil diperbarui.');
    redirect_admin('master/pengguna');
} catch (Throwable $e) {
    set_flash('error', 'Pengguna gagal diperbarui: ' . $e->getMessage());
    redirect_admin('master/pengguna/edit&id=' . $id_pengguna);
}   