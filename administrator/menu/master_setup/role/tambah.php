<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('role_tambah_is_super_admin')) {
    function role_tambah_is_super_admin(array $user_login): bool
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
                return strtolower(trim((string) $role->nama_role)) === 'super_admin';
            }
        }

        return false;
    }
}

if (!role_tambah_is_super_admin(user_login())) {
    set_flash('error', 'Menu Role hanya boleh diakses oleh super admin.');
    redirect_admin('dashboard');
}

$menu_rows = Capsule::table('tb_menu')
    ->where('status_aktif', 1)
    ->orderBy('urutan', 'asc')
    ->orderBy('id_menu', 'asc')
    ->get();

$role_menu_map = [];

$data_form = [
    'id_role' => null,
    'nama_role' => '',
    'keterangan' => '',
];

$form_action = admin_url('menu/master_setup/role/simpan.php');
$page_subtitle = 'Tambah role baru dan tentukan hak akses menunya.';
$button_label = 'Simpan';

require __DIR__ . '/_form.php';