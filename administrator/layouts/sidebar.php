<?php
use Illuminate\Database\Capsule\Manager as Capsule;

if (!function_exists('sidebar_is_super_admin')) {
    function sidebar_is_super_admin(array $user_login): bool
    {
        $username = strtolower(trim((string) ($user_login['username'] ?? '')));
        $nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

        if ($username === 'super_admin' || $nama_role_session === 'super_admin' || $nama_role_session === 'admin_entitas') {
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

                return in_array($nama_role, ['super_admin', 'admin_entitas'], true) || in_array($kode_role, ['super_admin', 'admin_entitas'], true);
            }
        }

        return false;
    }
}

if (!function_exists('sidebar_menu_khusus_super_admin')) {
    function sidebar_menu_khusus_super_admin(string $url_menu): bool
    {
        $url_menu = trim($url_menu, '/');

        return $url_menu === 'master/role'
            || str_starts_with($url_menu, 'master/role/')
            || $url_menu === 'master_setup/reset_data'
            || str_starts_with($url_menu, 'master_setup/reset_data/');
    }
}

if (!function_exists('menu_is_active')) {
    function menu_is_active(string $menuAktif, string $urlMenu): bool
    {
        $menuAktif = trim($menuAktif, '/');
        $urlMenu = trim($urlMenu, '/');

        if ($urlMenu === '') {
            return false;
        }

        return $menuAktif === $urlMenu || str_starts_with($menuAktif, $urlMenu . '/');
    }
}

if (!function_exists('sidebar_filter_menu_by_role')) {
    function sidebar_filter_menu_by_role(array $menus, bool $is_super_admin): array
    {
        $hasil = [];

        foreach ($menus as $menu) {
            $url_menu = trim((string) ($menu['url'] ?? ''), '/');

            if (!$is_super_admin && sidebar_menu_khusus_super_admin($url_menu)) {
                continue;
            }

            if (!empty($menu['children']) && is_array($menu['children'])) {
                $menu['children'] = sidebar_filter_menu_by_role($menu['children'], $is_super_admin);
            }

            $is_group = (($menu['jenis_menu'] ?? 'menu') === 'grup');

            if ($is_group && empty($menu['children'])) {
                continue;
            }

            $hasil[] = $menu;
        }

        return $hasil;
    }
}


if (!function_exists('sidebar_has_active_descendant')) {
    function sidebar_has_active_descendant(array $menu, string $menu_aktif): bool
    {
        $url_menu = trim((string) ($menu['url'] ?? ''), '/');
        if ($url_menu !== '' && $url_menu !== '#' && menu_is_active($menu_aktif, $url_menu)) {
            return true;
        }
        if (!empty($menu['children']) && is_array($menu['children'])) {
            foreach ($menu['children'] as $child) {
                if (sidebar_has_active_descendant($child, $menu_aktif)) {
                    return true;
                }
            }
        }
        return false;
    }
}

if (!function_exists('render_sidebar_menu')) {
    function render_sidebar_menu(array $menus, string $menu_aktif): void
    {
        echo '<ul class="nav flex-column sidebar-menu-list">';

        foreach ($menus as $menu) {
            $is_group = (($menu['jenis_menu'] ?? 'menu') === 'grup');
            $has_children = !empty($menu['children']);
            $url_menu = trim((string) ($menu['url'] ?? ''), '/');
            $nama_menu = (string) ($menu['nama_menu'] ?? '-');

            if (in_array($url_menu, ['dashboard', 'profil', 'ganti-password'], true)) {
                continue;
            }

            if ($is_group) {
                $is_open = sidebar_has_active_descendant($menu, $menu_aktif) ? ' open' : '';
                echo '<li class="nav-item mt-2 sidebar-group-item">';
                echo '<details class="sidebar-group"' . $is_open . '>';
                echo '<summary class="menu-group-title"><span>' . esc($nama_menu) . '</span><span class="group-chevron">▾</span></summary>';

                if ($has_children) {
                    render_sidebar_menu($menu['children'], $menu_aktif);
                }

                echo '</details>';
                echo '</li>';
                continue;
            }

            $active = menu_is_active($menu_aktif, $url_menu) ? 'active' : '';
            $href = ($url_menu === '' || $url_menu === '#')
                ? '#'
                : admin_page_url($url_menu);

            echo '<li class="nav-item">';
            echo '<a class="nav-link sidebar-link ' . esc($active) . '" href="' . esc($href) . '">';
            echo '<span>' . esc($nama_menu) . '</span>';
            echo '</a>';
            echo '</li>';
        }

        echo '</ul>';
    }
}

$user_login_sidebar = user_login();
$is_super_admin_sidebar = sidebar_is_super_admin($user_login_sidebar);
$menu_tree_sidebar = sidebar_filter_menu_by_role($menu_tree, $is_super_admin_sidebar);
?>


<style>
.sidebar-group > summary.menu-group-title{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:8px;user-select:none;padding:8px 12px;border-radius:12px;background:rgba(79,70,229,.08);font-weight:800;color:#334155;}
.sidebar-group > summary.menu-group-title::-webkit-details-marker{display:none;}
.sidebar-group .group-chevron{font-size:12px;transition:transform .18s ease;}
.sidebar-group[open] .group-chevron{transform:rotate(180deg);}
.sidebar-group > .sidebar-menu-list{margin-top:6px;margin-left:8px;padding-left:8px;border-left:1px dashed rgba(100,116,139,.28);}
.sidebar-group-item .sidebar-group .sidebar-group > summary.menu-group-title{background:rgba(15,23,42,.04);font-size:.86rem;}
</style>

<aside class="sidebar-admin" id="sidebarAdmin">
    <div class="sidebar-inner" id="sidebarInner">
        <?php render_sidebar_menu($menu_tree_sidebar, $menu_aktif); ?>
    </div>
</aside>

<main class="main-content bg-page" id="mainContent">
    <?php if (!empty($flash_success)): ?>
        <div class="alert alert-success"><?= esc((string) $flash_success) ?></div>
    <?php endif; ?>

    <?php if (!empty($flash_error)): ?>
        <div class="alert alert-danger"><?= esc((string) $flash_error) ?></div>
    <?php endif; ?>

<script>
(function () {
    const sidebar = document.getElementById('sidebarAdmin');
    const sidebarInner = document.getElementById('sidebarInner');
    const storageKey = 'hpp_sidebar_scroll_top';

    if (!sidebar) {
        return;
    }

    function getScrollableSidebar() {
        const sidebarOverflowY = window.getComputedStyle(sidebar).overflowY;
        const innerOverflowY = sidebarInner ? window.getComputedStyle(sidebarInner).overflowY : '';

        if (
            sidebarInner &&
            sidebarInner.scrollHeight > sidebarInner.clientHeight &&
            (innerOverflowY === 'auto' || innerOverflowY === 'scroll')
        ) {
            return sidebarInner;
        }

        if (
            sidebar.scrollHeight > sidebar.clientHeight &&
            (sidebarOverflowY === 'auto' || sidebarOverflowY === 'scroll')
        ) {
            return sidebar;
        }

        return sidebarInner || sidebar;
    }

    const scrollTarget = getScrollableSidebar();

    function simpanScrollSidebar() {
        try {
            sessionStorage.setItem(storageKey, String(scrollTarget.scrollTop || 0));
        } catch (error) {
            // Abaikan jika browser memblokir sessionStorage.
        }
    }

    function pulihkanScrollSidebar() {
        try {
            const saved = sessionStorage.getItem(storageKey);

            if (saved === null) {
                return;
            }

            const scrollTop = parseInt(saved, 10);

            if (!Number.isNaN(scrollTop)) {
                scrollTarget.scrollTop = scrollTop;
            }
        } catch (error) {
            // Abaikan jika browser memblokir sessionStorage.
        }
    }

    window.addEventListener('load', function () {
        setTimeout(pulihkanScrollSidebar, 50);
    });

    scrollTarget.addEventListener('scroll', function () {
        simpanScrollSidebar();
    }, { passive: true });

    document.querySelectorAll('#sidebarAdmin a.sidebar-link').forEach(function (link) {
        link.addEventListener('click', function () {
            simpanScrollSidebar();
        });
    });

    window.addEventListener('beforeunload', function () {
        simpanScrollSidebar();
    });
})();
</script>