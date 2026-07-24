<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = user_login();
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

if (!function_exists('pengguna_edit_is_super_admin')) {
    function pengguna_edit_is_super_admin(array $user_login): bool
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

$is_super_admin = pengguna_edit_is_super_admin($user_login);
$id_pengguna = (int) ($_GET['id'] ?? 0);

$row_query = Capsule::table('tb_pengguna as p')
    ->leftJoin('tb_role as r', 'r.id_role', '=', 'p.id_role')
    ->where('p.id_pengguna', $id_pengguna);

if (!$is_super_admin) {
    $row_query->where('p.id_entitas', $id_entitas_login)
        ->where('r.nama_role', '<>', 'super_admin');
}

$row = $row_query->select(['p.*', 'r.nama_role'])->first();

if (!$row) {
    set_flash('error', 'Data pengguna tidak ditemukan atau kamu tidak punya akses.');
    redirect_admin('master/pengguna');
}

$entitas_options = $is_super_admin
    ? Capsule::table('tb_entitas')->where('status_aktif', 1)->orderBy('nama_entitas', 'asc')->get()
    : Capsule::table('tb_entitas')->where('id_entitas', $id_entitas_login)->get();

$role_query = Capsule::table('tb_role')->orderBy('nama_role', 'asc');

if (!$is_super_admin) {
    $role_query->where('nama_role', '<>', 'super_admin');
}

$role_options = $role_query->get();

$data_form = [
    'id_pengguna' => (int) $row->id_pengguna,
    'id_entitas' => (int) $row->id_entitas,
    'id_role' => (int) $row->id_role,
    'nama_pengguna' => (string) $row->nama_pengguna,
    'username' => (string) $row->username,
    'nama_lengkap' => (string) $row->nama_lengkap,
    'email' => (string) ($row->email ?? ''),
    'no_hp' => (string) ($row->no_hp ?? ''),
    'status_aktif' => (int) $row->status_aktif,
];

$form_action = admin_url('menu/master_setup/pengguna/update.php');
$page_subtitle = 'Edit data pengguna.';
$button_label = 'Update';
$is_edit = true;

require __DIR__ . '/_form.php';