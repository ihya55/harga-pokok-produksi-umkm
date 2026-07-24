<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();
set_flash('info', 'Menu Mapping Laporan dikelola langsung dari halaman utama.');
redirect_admin('master_setup/mapping_laporan');
