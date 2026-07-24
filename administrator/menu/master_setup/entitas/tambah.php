<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

$user_login = user_login();

$is_super_admin = false;
$username = strtolower(trim((string) ($user_login['username'] ?? '')));
$nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

if ($username === 'super_admin' || $nama_role_session === 'super_admin') {
    $is_super_admin = true;
}

if (!$is_super_admin) {
    $id_role = (int) ($user_login['id_role'] ?? 0);

    if ($id_role > 0) {
        $role = Capsule::table('tb_role')
            ->where('id_role', $id_role)
            ->first();

        if ($role) {
            $nama_role = strtolower(trim((string) ($role->nama_role ?? '')));
            $kode_role = strtolower(trim((string) ($role->kode_role ?? '')));

            $is_super_admin = $nama_role === 'super_admin' || $kode_role === 'super_admin';
        }
    }
}

if (!$is_super_admin) {
    set_flash('error', 'Hanya super admin yang boleh menambah entitas.');
    redirect_admin('master/entitas');
}

$data_form = [
    'id_entitas' => null,
    'kode_entitas' => '',
    'nama_entitas' => '',
    'nama_pemilik' => '',
    'alamat' => '',
    'no_hp' => '',
    'email' => '',
    'jenis_usaha' => '',
    'status_aktif' => 1,
    'is_super_admin' => true,
];

$form_action = admin_url('menu/master_setup/entitas/simpan.php');
$page_subtitle = 'Tambahkan data entitas/perusahaan baru';
$button_label = 'Simpan';

require __DIR__ . '/_form.php';