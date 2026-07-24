<?php
declare(strict_types=1);

function render_guest(string $body_file, array $data = []): void
{
    $is_guest_page = true;
    $page_title = $data['page_title'] ?? APP_NAME;
    $flash_success = get_flash('success');
    $flash_error = get_flash('error');

    extract($data, EXTR_SKIP);

    require ROOT_PATH . '/administrator/layouts/header.php';
    require $body_file;
    require ROOT_PATH . '/administrator/layouts/footer.php';
}

function render_admin(string $body_file, array $data = []): void
{
    $is_guest_page = false;
    $page_title = $data['page_title'] ?? APP_NAME;
    $flash_success = get_flash('success');
    $flash_error = get_flash('error');
    $user = user_login();
    $menu_aktif = $data['menu_aktif'] ?? 'dashboard';
    $menu_tree = ambil_menu_role_tree((int) ($user['id_role'] ?? 0));

    extract($data, EXTR_SKIP);

    require ROOT_PATH . '/administrator/layouts/header.php';
    require ROOT_PATH . '/administrator/layouts/sidebar.php';
    require $body_file;
    require ROOT_PATH . '/administrator/layouts/footer.php';
}