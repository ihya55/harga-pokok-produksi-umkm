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

function pengguna_simpan_is_super_admin(array $user_login): bool
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
$is_super_admin = pengguna_simpan_is_super_admin($user_login);

$id_pengguna_login = (int) ($user_login['id_pengguna'] ?? 0);
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

$id_entitas = $is_super_admin ? (int) ($_POST['id_entitas'] ?? 0) : $id_entitas_login;
$id_role = (int) ($_POST['id_role'] ?? 0);
$nama_pengguna = trim((string) ($_POST['nama_pengguna'] ?? ''));
$username = strtolower(trim((string) ($_POST['username'] ?? '')));
$nama_lengkap = trim((string) ($_POST['nama_lengkap'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$no_hp = trim((string) ($_POST['no_hp'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$password_konfirmasi = (string) ($_POST['password_konfirmasi'] ?? '');
$status_aktif = (int) ($_POST['status_aktif'] ?? 1);

if ($id_entitas <= 0 || $id_role <= 0 || $nama_pengguna === '' || $username === '' || $nama_lengkap === '') {
    set_flash('error', 'Entitas, role, nama pengguna, username, dan nama lengkap wajib diisi.');
    redirect_admin('master/pengguna/tambah');
}

if ($password === '' || $password_konfirmasi === '') {
    set_flash('error', 'Password dan konfirmasi password wajib diisi.');
    redirect_admin('master/pengguna/tambah');
}

if (strlen($password) < 6) {
    set_flash('error', 'Password minimal 6 karakter.');
    redirect_admin('master/pengguna/tambah');
}

if ($password !== $password_konfirmasi) {
    set_flash('error', 'Konfirmasi password tidak sama.');
    redirect_admin('master/pengguna/tambah');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Format email tidak valid.');
    redirect_admin('master/pengguna/tambah');
}

$role = Capsule::table('tb_role')->where('id_role', $id_role)->first();

if (!$role) {
    set_flash('error', 'Role tidak valid.');
    redirect_admin('master/pengguna/tambah');
}

if (!$is_super_admin && strtolower((string) $role->nama_role) === 'super_admin') {
    set_flash('error', 'Kamu tidak boleh membuat pengguna dengan role super_admin.');
    redirect_admin('master/pengguna/tambah');
}

$entitas = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->where('status_aktif', 1)
    ->first();

if (!$entitas) {
    set_flash('error', 'Entitas tidak valid atau tidak aktif.');
    redirect_admin('master/pengguna/tambah');
}

$cekUsername = Capsule::table('tb_pengguna')
    ->whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
    ->exists();

if ($cekUsername) {
    set_flash('error', 'Username sudah digunakan.');
    redirect_admin('master/pengguna/tambah');
}

if ($email !== '') {
    $cekEmail = Capsule::table('tb_pengguna')
        ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
        ->exists();

    if ($cekEmail) {
        set_flash('error', 'Email sudah digunakan pengguna lain.');
        redirect_admin('master/pengguna/tambah');
    }
}

try {
    Capsule::connection()->transaction(function () use (
        $id_entitas,
        $id_role,
        $nama_pengguna,
        $username,
        $password,
        $nama_lengkap,
        $email,
        $no_hp,
        $status_aktif,
        $id_pengguna_login
    ) {
        $id_pengguna = Capsule::table('tb_pengguna')->insertGetId([
            'id_entitas' => $id_entitas,
            'id_role' => $id_role,
            'nama_pengguna' => $nama_pengguna,
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'nama_lengkap' => $nama_lengkap,
            'email' => $email !== '' ? $email : null,
            'no_hp' => $no_hp !== '' ? $no_hp : null,
            'status_aktif' => $status_aktif === 1 ? 1 : 0,
            'terakhir_login' => null,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna_login ?: null,
        ]);

        Capsule::table('tb_pengguna_entitas')->insert([
            'id_pengguna' => $id_pengguna,
            'id_entitas' => $id_entitas,
            'id_role' => $id_role,
            'entitas_default' => 1,
            'status_aktif' => $status_aktif === 1 ? 1 : 0,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna_login ?: null,
        ]);
    });

    set_flash('success', 'Pengguna berhasil disimpan.');
    redirect_admin('master/pengguna');
} catch (Throwable $e) {
    set_flash('error', 'Pengguna gagal disimpan: ' . $e->getMessage());
    redirect_admin('master/pengguna/tambah');
}