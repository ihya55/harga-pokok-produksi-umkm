<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = user_login();
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

if (!function_exists('pengguna_tambah_is_super_admin')) {
    function pengguna_tambah_is_super_admin(array $user_login): bool
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
}

$is_super_admin = pengguna_tambah_is_super_admin($user_login);

$entitas_options = $is_super_admin
    ? Capsule::table('tb_entitas')->where('status_aktif', 1)->orderBy('nama_entitas', 'asc')->get()
    : Capsule::table('tb_entitas')->where('id_entitas', $id_entitas_login)->get();

$role_query = Capsule::table('tb_role')->orderBy('nama_role', 'asc');

if (!$is_super_admin) {
    $role_query->where('nama_role', '<>', 'super_admin');
}

$role_options = $role_query->get();

$data_form = [
    'id_pengguna' => null,
    'id_entitas' => $id_entitas_login,
    'id_role' => '',
    'nama_pengguna' => '',
    'username' => '',
    'nama_lengkap' => '',
    'email' => '',
    'no_hp' => '',
    'status_aktif' => 1,
];

$form_action = admin_url('menu/master_setup/pengguna/simpan.php');
$page_subtitle = 'Tambah pengguna baru.';
$button_label = 'Simpan';
$is_edit = false;

require __DIR__ . '/_form.php';