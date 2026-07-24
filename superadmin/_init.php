<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers/config.php';
require_once __DIR__ . '/../helpers/koneksi.php';
require_once __DIR__ . '/../helpers/fungsi.php';
require_once __DIR__ . '/../orm/RoleORM.php';
require_once __DIR__ . '/../orm/EntitasORM.php';
require_once __DIR__ . '/../orm/PenggunaORM.php';
require_once __DIR__ . '/../orm/PenggunaEntitasORM.php';
require_once __DIR__ . '/../orm/MenuORM.php';
require_once __DIR__ . '/../orm/RoleMenuORM.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/menu.php';

use Illuminate\Database\Capsule\Manager as Capsule;

function sa_esc($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function sa_url(string $path = ''): string { return base_url('superadmin/' . ltrim($path, '/')); }
function sa_admin_url(string $menu): string { return admin_page_url($menu); }
function sa_is_allowed(?array $user = null): bool
{
    $user = $user ?: user_login();
    if (!$user) return false;
    $role = strtolower(trim((string) ($user['nama_role'] ?? '')));
    $username = strtolower(trim((string) ($user['username'] ?? '')));
    return in_array($role, ['super_admin', 'admin_entitas'], true) || in_array($username, ['superadmin','super_admin','admin'], true);
}
function sa_require(): void
{
    if (!is_login()) { redirect_url(sa_url('index.php?login=1')); }
    if (!sa_is_allowed()) { http_response_code(403); echo 'Akses ditolak. Halaman superadmin hanya untuk pembuat aplikasi/admin utama.'; exit; }
}
function sa_count(string $table): int
{
    try { return Capsule::schema()->hasTable($table) ? (int) Capsule::table($table)->count() : 0; }
    catch (Throwable $e) { return 0; }
}
function sa_first_entitas(): ?object
{
    try { return Capsule::table('tb_entitas')->orderBy('id_entitas')->first(); }
    catch (Throwable $e) { return null; }
}
