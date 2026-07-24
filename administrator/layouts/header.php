<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($page_title ?? APP_NAME) ?> - <?= esc(APP_NAME) ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= esc(base_url('assets/css/admin.css')) ?>" rel="stylesheet">
    <link rel="icon" type="image/png" href="<?= esc(base_url('assets/logo/icon-logo-apk.png')) ?>">
</head>
<body>

<?php if (!empty($is_guest_page)): ?>
    <div class="guest-bg">
        <div class="container py-5">
            <?php if (!empty($flash_success)): ?>
                <div class="alert alert-success"><?= esc($flash_success) ?></div>
            <?php endif; ?>
            <?php if (!empty($flash_error)): ?>
                <div class="alert alert-danger"><?= esc($flash_error) ?></div>
            <?php endif; ?>
<?php else: ?>
    <nav class="navbar navbar-expand-xl admin-navbar shadow-sm sticky-top">
        <div class="container-fluid">

            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <button class="btn btn-sm btn-light nav-toggle-btn" type="button" id="btnToggleSidebar" aria-label="Toggle Sidebar">
                    <i class="bi bi-list"></i>
                </button>

                <a class="navbar-brand fw-bold mb-0 text-truncate d-flex align-items-center gap-2" href="<?= esc(admin_page_url(halaman_awal_role())) ?>">
                    <img class="navbar-app-logo" src="<?= esc(base_url('assets/logo/icon-logo-apk.png')) ?>" alt="Dodol Kapuh Online">
                    <span><?= esc(APP_NAME) ?></span>
                </a>
            </div>

            <button class="navbar-toggler border-0 shadow-none ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTopMenu" aria-controls="navbarTopMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="text-white fs-4"><i class="bi bi-three-dots-vertical"></i></span>
            </button>

            <div class="collapse navbar-collapse mt-3 mt-xl-0" id="navbarTopMenu">
                <div class="d-flex flex-column flex-xl-row align-items-stretch align-items-xl-center justify-content-xl-end w-100 gap-3">

                    <ul class="navbar-nav nav nav-pills nav-topbar me-xl-3">
                        <li class="nav-item">
                            <a class="nav-link <?= (($menu_aktif ?? '') === 'dashboard' || str_starts_with((string)($menu_aktif ?? ''), 'dashboard/')) ? 'active' : '' ?>"
                               href="<?= esc(admin_page_url(halaman_awal_role())) ?>">
                                <i class="bi bi-speedometer2 me-1"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (($menu_aktif ?? '') === 'profil') ? 'active' : '' ?>"
                               href="<?= esc(admin_page_url('profil')) ?>">
                                <i class="bi bi-person-circle me-1"></i>
                                <span>Profil Saya</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (($menu_aktif ?? '') === 'ganti-password') ? 'active' : '' ?>"
                               href="<?= esc(admin_page_url('ganti-password')) ?>">
                                <i class="bi bi-key me-1"></i>
                                <span>Ganti Password</span>
                            </a>
                        </li>
                    </ul>

                    <div class="topbar-user text-xl-end text-white small">
                        <div class="fw-semibold text-truncate"><?= esc($user['nama_lengkap'] ?? '-') ?></div>
                        <div class="opacity-75 text-truncate"><?= esc($user['nama_entitas'] ?? '-') ?></div>
                        <div class="opacity-50 text-truncate"><?= esc($user['nama_role'] ?? '-') ?></div>
                    </div>

                    <div class="d-flex align-items-center">
                        <a href="<?= esc(admin_url('auth/logout.php')) ?>" class="btn btn-sm btn-light text-dark fw-semibold w-100 w-xl-auto">
                            <i class="bi bi-box-arrow-right me-1"></i>Logout
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </nav>

    <div class="app-shell" id="appShell">
<?php endif; ?>