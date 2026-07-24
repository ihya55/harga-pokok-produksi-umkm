<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set(APP_TIMEZONE);

function base_url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}

function admin_url(string $path = ''): string
{
    return base_url('administrator/' . ltrim($path, '/'));
}

function admin_page_url(string $menu = 'dashboard'): string
{
    // Mendukung tambahan query seperti: master_setup/mapping_akun&tab=rekening_qris
    // agar tidak dibaca sebagai nama menu penuh yang menyebabkan Akses Ditolak.
    $menu = trim($menu);
    $extra = [];

    if (str_contains($menu, '&')) {
        [$menuOnly, $queryString] = explode('&', $menu, 2);
        $menu = $menuOnly;
        parse_str($queryString, $extra);
    }

    return admin_url('index.php?' . http_build_query(array_merge(['menu' => $menu], $extra)));
}

function redirect_url(string $url): never
{
    $url = trim($url);

    if ($url === '') {
        $url = base_url();
    }

    /*
    |--------------------------------------------------------------------------
    | Redirect aman
    |--------------------------------------------------------------------------
    | Jika header belum terkirim, pakai header Location.
    | Jika layout/sidebar sudah terlanjur tampil, fallback pakai JavaScript.
    | Ini mencegah warning:
    | Cannot modify header information - headers already sent
    |--------------------------------------------------------------------------
    */
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    }

    $safeUrl = json_encode($url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $metaUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

    echo '<script>window.location.href = ' . $safeUrl . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . $metaUrl . '"></noscript>';
    exit;
}

function redirect_admin(string $menu = 'dashboard'): never
{
    redirect_url(admin_page_url($menu));
}

function esc(?string $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}