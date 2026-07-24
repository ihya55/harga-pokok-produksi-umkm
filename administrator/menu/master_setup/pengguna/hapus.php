<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

function pengguna_hapus_is_super_admin(array $user_login): bool
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
$is_super_admin = pengguna_hapus_is_super_admin($user_login);

$id_pengguna_login = (int) ($user_login['id_pengguna'] ?? 0);
$id_entitas_login = (int) ($user_login['id_entitas'] ?? 0);
$id_pengguna = (int) ($_GET['id'] ?? 0);

if ($id_pengguna <= 0) {
    set_flash('error', 'ID pengguna tidak valid.');
    redirect_admin('master/pengguna');
}

if ($id_pengguna === $id_pengguna_login) {
    set_flash('error', 'Kamu tidak boleh menghapus akun sendiri.');
    redirect_admin('master/pengguna');
}

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

$dipakaiTransaksi = false;

$kolomAudit = [
    ['tb_jurnal', 'dibuat_oleh'],
    ['tb_pesanan_penjualan', 'dibuat_oleh'],
    ['tb_faktur_penjualan', 'dibuat_oleh'],
    ['tb_perintah_produksi', 'dibuat_oleh'],
    ['tb_pembelian', 'dibuat_oleh'],
];

foreach ($kolomAudit as [$tabel, $kolom]) {
    try {
        if (Capsule::table($tabel)->where($kolom, $id_pengguna)->exists()) {
            $dipakaiTransaksi = true;
            break;
        }
    } catch (Throwable $e) {
        continue;
    }
}

if ($dipakaiTransaksi) {
    set_flash('error', 'Pengguna tidak bisa dihapus karena sudah dipakai pada transaksi. Gunakan nonaktifkan status.');
    redirect_admin('master/pengguna');
}

try {
    Capsule::connection()->transaction(function () use ($id_pengguna) {
        Capsule::table('tb_pengguna_entitas')
            ->where('id_pengguna', $id_pengguna)
            ->delete();

        Capsule::table('tb_pengguna')
            ->where('id_pengguna', $id_pengguna)
            ->delete();
    });

    set_flash('success', 'Pengguna berhasil dihapus.');
} catch (Throwable $e) {
    set_flash('error', 'Pengguna gagal dihapus karena masih digunakan di tabel lain.');
}

redirect_admin('master/pengguna');