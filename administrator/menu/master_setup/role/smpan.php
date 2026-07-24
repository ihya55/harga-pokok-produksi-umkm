<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master/role');
}

function role_simpan_is_super_admin(array $user_login): bool
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

$user_login = user_login();

if (!role_simpan_is_super_admin($user_login)) {
    set_flash('error', 'Hanya super admin yang boleh menambah role.');
    redirect_admin('dashboard');
}

$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);
$nama_role = strtolower(trim((string) ($_POST['nama_role'] ?? '')));
$keterangan = trim((string) ($_POST['keterangan'] ?? ''));
$akses = $_POST['akses'] ?? [];

if ($nama_role === '') {
    set_flash('error', 'Nama role wajib diisi.');
    redirect_admin('master/role/tambah');
}

if ($nama_role === 'super_admin') {
    set_flash('error', 'Role super_admin adalah role sistem dan tidak boleh dibuat ulang.');
    redirect_admin('master/role/tambah');
}

$cek = Capsule::table('tb_role')
    ->whereRaw('LOWER(nama_role) = ?', [mb_strtolower($nama_role)])
    ->exists();

if ($cek) {
    set_flash('error', 'Nama role sudah digunakan.');
    redirect_admin('master/role/tambah');
}

try {
    Capsule::connection()->transaction(function () use ($id_pengguna, $nama_role, $keterangan, $akses) {
        $id_role = Capsule::table('tb_role')->insertGetId([
            'nama_role' => $nama_role,
            'keterangan' => $keterangan !== '' ? $keterangan : null,
            'tanggal_dibuat' => date('Y-m-d H:i:s'),
            'dibuat_oleh' => $id_pengguna ?: null,
        ]);

        $menuIds = Capsule::table('tb_menu')
            ->where('status_aktif', 1)
            ->pluck('id_menu')
            ->map(fn ($v) => (int) $v)
            ->all();

        foreach ($menuIds as $id_menu) {
            $baris = $akses[$id_menu] ?? [];

            Capsule::table('tb_role_menu')->insert([
                'id_role' => $id_role,
                'id_menu' => $id_menu,
                'boleh_lihat' => !empty($baris['boleh_lihat']) ? 1 : 0,
                'boleh_tambah' => !empty($baris['boleh_tambah']) ? 1 : 0,
                'boleh_ubah' => !empty($baris['boleh_ubah']) ? 1 : 0,
                'boleh_hapus' => !empty($baris['boleh_hapus']) ? 1 : 0,
                'boleh_posting' => !empty($baris['boleh_posting']) ? 1 : 0,
                'boleh_approve' => !empty($baris['boleh_approve']) ? 1 : 0,
                'boleh_cetak' => !empty($baris['boleh_cetak']) ? 1 : 0,
                'boleh_export' => !empty($baris['boleh_export']) ? 1 : 0,
                'status_aktif' => 1,
                'tanggal_dibuat' => date('Y-m-d H:i:s'),
                'dibuat_oleh' => $id_pengguna ?: null,
            ]);
        }
    });

    set_flash('success', 'Role berhasil disimpan.');
    redirect_admin('master/role');
} catch (Throwable $e) {
    set_flash('error', 'Role gagal disimpan: ' . $e->getMessage());
    redirect_admin('master/role/tambah');
}