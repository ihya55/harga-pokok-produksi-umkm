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

logout_user();
set_flash('success', 'Anda berhasil logout.');
redirect_url(base_url('index.php'));