<?php
declare(strict_types=1);

require_once __DIR__ . '/../../helpers/config.php';
require_once __DIR__ . '/../../helpers/koneksi.php';
require_once __DIR__ . '/../../helpers/fungsi.php';

require_once __DIR__ . '/../../orm/RoleORM.php';
require_once __DIR__ . '/../../orm/EntitasORM.php';
require_once __DIR__ . '/../../orm/PenggunaORM.php';
require_once __DIR__ . '/../../orm/PenggunaEntitasORM.php';
require_once __DIR__ . '/../../orm/MenuORM.php';
require_once __DIR__ . '/../../orm/RoleMenuORM.php';

require_once __DIR__ . '/../../helpers/auth.php';
require_once __DIR__ . '/../../helpers/menu.php';
require_once __DIR__ . '/../../helpers/render.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_url(base_url('index.php?login=1'));
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    set_flash('error', 'Username dan password wajib diisi.');
    redirect_url(base_url('index.php?login=1'));
}

if (!login_user($username, $password)) {
    set_flash('error', 'Login gagal. Username atau password salah.');
    redirect_url(base_url('index.php?login=1'));
}

set_flash('success', 'Login berhasil.');
redirect_admin(halaman_awal_role());