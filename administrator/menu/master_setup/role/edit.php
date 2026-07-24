<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('role_edit_is_super_admin')) {
    function role_edit_is_super_admin(array $user_login): bool
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

if (!role_edit_is_super_admin(user_login())) {
    set_flash('error', 'Menu Role hanya boleh diakses oleh super admin.');
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
    set_flash('error', 'Role super_admin tidak boleh diubah.');
    redirect_admin('master/role');
}

$menu_rows = Capsule::table('tb_menu')
    ->where('status_aktif', 1)
    ->orderBy('urutan', 'asc')
    ->orderBy('id_menu', 'asc')
    ->get();

$role_menu_rows = Capsule::table('tb_role_menu')
    ->where('id_role', $id_role)
    ->get();

$role_menu_map = [];

foreach ($role_menu_rows as $rm) {
    $role_menu_map[(int) $rm->id_menu] = [
        'boleh_lihat' => (int) $rm->boleh_lihat,
        'boleh_tambah' => (int) $rm->boleh_tambah,
        'boleh_ubah' => (int) $rm->boleh_ubah,
        'boleh_hapus' => (int) $rm->boleh_hapus,
        'boleh_posting' => (int) $rm->boleh_posting,
        'boleh_approve' => (int) $rm->boleh_approve,
        'boleh_cetak' => (int) $rm->boleh_cetak,
        'boleh_export' => (int) $rm->boleh_export,
        'status_aktif' => (int) $rm->status_aktif,
    ];
}

$data_form = [
    'id_role' => (int) $row->id_role,
    'nama_role' => (string) $row->nama_role,
    'keterangan' => (string) ($row->keterangan ?? ''),
];

$form_action = admin_url('menu/master_setup/role/update.php');
$page_subtitle = 'Edit role dan hak akses menu.';
$button_label = 'Update';

require __DIR__ . '/_form.php';