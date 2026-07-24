<?php
declare(strict_types=1);

function is_login(): bool
{
    return isset($_SESSION['user_login']) && is_array($_SESSION['user_login']);
}

function user_login(): ?array
{
    return $_SESSION['user_login'] ?? null;
}

function harus_login(): void
{
    if (!is_login()) {
        redirect_url(base_url('index.php?login=1'));
    }
}

function logout_user(): void
{
    unset($_SESSION['user_login']);
    session_regenerate_id(true);
}

function simpan_log_login(?int $id_pengguna, ?int $id_entitas, string $username_input, string $status_login, string $keterangan = ''): void
{
    try {
        Illuminate\Database\Capsule\Manager::table('tb_log_login')->insert([
            'id_pengguna'    => $id_pengguna,
            'id_entitas'     => $id_entitas,
            'username_input' => $username_input,
            'waktu_login'    => date('Y-m-d H:i:s'),
            'alamat_ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent'     => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'status_login'   => $status_login,
            'keterangan'     => $keterangan,
        ]);
    } catch (Throwable $e) {
        // abaikan bila tabel log belum siap
    }
}

function login_user(string $username, string $password): bool
{
    $pengguna = PenggunaORM::query()
        ->where('username', $username)
        ->where('status_aktif', 1)
        ->first();

    if (!$pengguna) {
        simpan_log_login(null, null, $username, 'gagal', 'User tidak ditemukan');
        return false;
    }

    if (!password_verify($password, (string) $pengguna->password_hash)) {
        simpan_log_login((int) $pengguna->id_pengguna, null, $username, 'gagal', 'Password salah');
        return false;
    }

    $akses = PenggunaEntitasORM::query()
        ->with(['entitas', 'role'])
        ->where('id_pengguna', $pengguna->id_pengguna)
        ->where('status_aktif', 1)
        ->orderByDesc('entitas_default')
        ->first();

    if ($akses) {
        $_SESSION['user_login'] = [
            'id_pengguna'   => (int) $pengguna->id_pengguna,
            'username'      => (string) $pengguna->username,
            'nama_lengkap'  => (string) $pengguna->nama_lengkap,
            'id_entitas'    => (int) $akses->id_entitas,
            'nama_entitas'  => (string) ($akses->entitas->nama_entitas ?? '-'),
            'id_role'       => (int) $akses->id_role,
            'nama_role'     => (string) ($akses->role->nama_role ?? '-'),
        ];

        $pengguna->terakhir_login = date('Y-m-d H:i:s');
        $pengguna->save();

        session_regenerate_id(true);
        simpan_log_login((int) $pengguna->id_pengguna, (int) $akses->id_entitas, $username, 'berhasil', 'Login berhasil');
        return true;
    }

    $entitas = EntitasORM::find($pengguna->id_entitas);
    $role = RoleORM::find($pengguna->id_role);

    if (!$entitas || !$role) {
        simpan_log_login((int) $pengguna->id_pengguna, null, $username, 'gagal', 'Entitas/role tidak ditemukan');
        return false;
    }

    $_SESSION['user_login'] = [
        'id_pengguna'   => (int) $pengguna->id_pengguna,
        'username'      => (string) $pengguna->username,
        'nama_lengkap'  => (string) $pengguna->nama_lengkap,
        'id_entitas'    => (int) $pengguna->id_entitas,
        'nama_entitas'  => (string) $entitas->nama_entitas,
        'id_role'       => (int) $pengguna->id_role,
        'nama_role'     => (string) $role->nama_role,
    ];

    $pengguna->terakhir_login = date('Y-m-d H:i:s');
    $pengguna->save();

    session_regenerate_id(true);
    simpan_log_login((int) $pengguna->id_pengguna, (int) $pengguna->id_entitas, $username, 'berhasil', 'Login fallback berhasil');
    return true;
}

function halaman_awal_role(): string
{
    $role = strtolower((string) (user_login()['nama_role'] ?? ''));

    return match ($role) {
        'super_admin'     => 'dashboard',
        'admin_entitas'   => 'dashboard',
        'admin_penjualan' => 'penjualan/dashboard',
        'admin_pembelian' => 'pembelian/dashboard',
        'admin_produksi'  => 'produksi/dashboard',
        'admin_gudang'    => 'persediaan/dashboard',
        'admin_keuangan'  => 'keuangan/dashboard',
        default           => 'dashboard',
    };
}