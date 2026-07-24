<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master/entitas');
}


function entitas_upload_logo_umkm(string $field = 'logo_umkm'): ?string
{
    if (empty($_FILES[$field]) || !is_array($_FILES[$field]) || (int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) ($_FILES[$field]['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload logo UMKM gagal. Silakan pilih file gambar yang valid.');
    }

    $tmp = (string) ($_FILES[$field]['tmp_name'] ?? '');
    $name = (string) ($_FILES[$field]['name'] ?? 'logo');
    $size = (int) ($_FILES[$field]['size'] ?? 0);

    if ($size <= 0 || $size > 2 * 1024 * 1024) {
        throw new RuntimeException('Ukuran logo UMKM maksimal 2 MB.');
    }

    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg' => 'jpg', 'jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp'];

    if (!isset($allowed[$ext])) {
        throw new RuntimeException('Format logo UMKM harus JPG, PNG, atau WEBP.');
    }

    if (function_exists('getimagesize') && @getimagesize($tmp) === false) {
        throw new RuntimeException('File logo UMKM bukan gambar yang valid.');
    }

    $dir = ROOT_PATH . '/uploads/logo_umkm';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $filename = 'logo_umkm_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$ext];
    $dest = $dir . '/' . $filename;

    if (!move_uploaded_file($tmp, $dest)) {
        throw new RuntimeException('Logo UMKM gagal disimpan ke folder uploads.');
    }

    return 'uploads/logo_umkm/' . $filename;
}

function entitas_update_is_super_admin(array $user_login): bool
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

$user_login = user_login();
$is_super_admin = entitas_update_is_super_admin($user_login);

$id_pengguna = (int) ($user_login['id_pengguna'] ?? 0);
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);
$id_entitas_post = (int) ($_POST['id_entitas'] ?? 0);

$id_entitas = $is_super_admin ? $id_entitas_post : $id_entitas_login;

$nama_entitas = trim((string) ($_POST['nama_entitas'] ?? ''));
$nama_pemilik = trim((string) ($_POST['nama_pemilik'] ?? ''));
$alamat = trim((string) ($_POST['alamat'] ?? ''));
$no_hp = trim((string) ($_POST['no_hp'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$jenis_usaha = trim((string) ($_POST['jenis_usaha'] ?? ''));

$row = Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->first();

if (!$row) {
    set_flash('error', 'Data entitas tidak ditemukan atau kamu tidak punya akses.');
    redirect_admin('master/entitas');
}

if ($nama_entitas === '') {
    set_flash('error', 'Nama entitas wajib diisi.');
    redirect_admin('master/entitas/edit&id=' . $id_entitas);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('error', 'Format email tidak valid.');
    redirect_admin('master/entitas/edit&id=' . $id_entitas);
}

$updateData = [
    'nama_entitas' => $nama_entitas,
    'nama_pemilik' => $nama_pemilik !== '' ? $nama_pemilik : null,
    'alamat' => $alamat !== '' ? $alamat : null,
    'no_hp' => $no_hp !== '' ? $no_hp : null,
    'email' => $email !== '' ? $email : null,
    'jenis_usaha' => $jenis_usaha !== '' ? $jenis_usaha : null,
    'tanggal_diubah' => date('Y-m-d H:i:s'),
    'diubah_oleh' => $id_pengguna ?: null,
];

$logo_umkm = entitas_upload_logo_umkm();
if ($logo_umkm !== null && Capsule::schema()->hasColumn('tb_entitas', 'logo_umkm')) {
    $updateData['logo_umkm'] = $logo_umkm;
}

if ($is_super_admin) {
    $kode_entitas = strtoupper(trim((string) ($_POST['kode_entitas'] ?? '')));
    $status_aktif = (int) ($_POST['status_aktif'] ?? 1);

    if ($kode_entitas === '') {
        set_flash('error', 'Kode entitas wajib diisi.');
        redirect_admin('master/entitas/edit&id=' . $id_entitas);
    }

    $cekKode = Capsule::table('tb_entitas')
        ->whereRaw('LOWER(kode_entitas) = ?', [mb_strtolower($kode_entitas)])
        ->where('id_entitas', '!=', $id_entitas)
        ->exists();

    if ($cekKode) {
        set_flash('error', 'Kode entitas sudah digunakan.');
        redirect_admin('master/entitas/edit&id=' . $id_entitas);
    }

    $updateData['kode_entitas'] = $kode_entitas;
    $updateData['status_aktif'] = $status_aktif === 1 ? 1 : 0;
}

Capsule::table('tb_entitas')
    ->where('id_entitas', $id_entitas)
    ->update($updateData);

if ((int) ($user_login['id_entitas'] ?? 0) === $id_entitas) {
    $_SESSION['user_login']['nama_entitas'] = $nama_entitas;
}

set_flash('success', 'Data entitas berhasil diperbarui.');
redirect_admin('master/entitas');