<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();
set_flash('error', 'Menu ini sudah menjadi pengaturan akun tetap. Tidak ada tambah/hapus data; hanya kode akun yang dapat diperbarui.');
redirect_admin('master_setup/mapping_akun');
