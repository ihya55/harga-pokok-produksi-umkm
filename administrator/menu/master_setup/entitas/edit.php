<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = user_login();
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);

if (!function_exists('entitas_edit_is_super_admin')) {
    function entitas_edit_is_super_admin(array $user_login): bool
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
}

$is_super_admin = entitas_edit_is_super_admin($user_login);

$id_entitas = (int) ($_GET['id'] ?? 0);

if (!$is_super_admin) {
    $id_entitas = $id_entitas_login;
}

$row = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

if (!$row) {
    set_flash('error', 'Data entitas tidak ditemukan atau kamu tidak punya akses.');
    redirect_admin('master/entitas');
}

$data_form = [
    'id_entitas' => (int) $row->id_entitas,
    'kode_entitas' => (string) $row->kode_entitas,
    'nama_entitas' => (string) $row->nama_entitas,
    'nama_pemilik' => (string) ($row->nama_pemilik ?? ''),
    'alamat' => (string) ($row->alamat ?? ''),
    'no_hp' => (string) ($row->no_hp ?? ''),
    'email' => (string) ($row->email ?? ''),
    'jenis_usaha' => (string) ($row->jenis_usaha ?? ''),
    'status_aktif' => (int) $row->status_aktif,
    'is_super_admin' => $is_super_admin,
];

$form_action = admin_url('menu/master_setup/entitas/update.php');
$page_subtitle = $is_super_admin ? 'Edit data entitas/perusahaan' : 'Edit data entitas kamu sendiri';
$button_label = 'Update';

require __DIR__ . '/_form.php';