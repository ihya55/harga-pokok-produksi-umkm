<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers/config.php';
require_once __DIR__ . '/helpers/koneksi.php';
require_once __DIR__ . '/helpers/fungsi.php';

require_once __DIR__ . '/orm/RoleORM.php';
require_once __DIR__ . '/orm/EntitasORM.php';
require_once __DIR__ . '/orm/PenggunaORM.php';
require_once __DIR__ . '/orm/PenggunaEntitasORM.php';
require_once __DIR__ . '/orm/MenuORM.php';
require_once __DIR__ . '/orm/RoleMenuORM.php';
// tambahkan seterusnya
require_once __DIR__ . '/orm/SatuanORM.php';
require_once __DIR__ . '/orm/GudangORM.php';
require_once __DIR__ . '/orm/PelangganORM.php';
require_once __DIR__ . '/orm/PemasokORM.php';
require_once __DIR__ . '/orm/KategoriProdukORM.php';
require_once __DIR__ . '/orm/ProdukORM.php';
require_once __DIR__ . '/orm/CoaORM.php';
require_once __DIR__ . '/orm/PeriodeAkuntansiORM.php';
require_once __DIR__ . '/orm/TemplateJurnalORM.php';
require_once __DIR__ . '/orm/TemplateJurnalDetailORM.php';
require_once __DIR__ . '/orm/MappingAkunORM.php';
require_once __DIR__ . '/orm/BahanBakuORM.php';
require_once __DIR__ . '/orm/KonfigurasiAkunORM.php';
require_once __DIR__ . '/orm/ResepORM.php';
require_once __DIR__ . '/orm/ResepDetailORM.php';
require_once __DIR__ . '/orm/PerintahProduksiORM.php';
require_once __DIR__ . '/orm/PesananPenjualanORM.php';
require_once __DIR__ . '/orm/PengambilanBahanORM.php';
require_once __DIR__ . '/orm/PengambilanBahanDetailORM.php';
require_once __DIR__ . '/orm/PesananPembelianORM.php';
require_once __DIR__ . '/orm/PesananPembelianDetailORM.php';
require_once __DIR__ . '/orm/PenerimaanPembelianORM.php';
require_once __DIR__ . '/orm/PenerimaanPembelianDetailORM.php';
require_once __DIR__ . '/orm/MutasiStokORM.php';
require_once __DIR__ . '/orm/SaldoStokORM.php';
require_once __DIR__ . '/orm/FakturPembelianORM.php';   
require_once __DIR__ . '/orm/FakturPembelianDetailORM.php';
require_once __DIR__ . '/orm/JurnalORM.php';
require_once __DIR__ . '/orm/JurnalDetailORM.php';
require_once __DIR__ . '/orm/LogJurnalSumberORM.php';
require_once __DIR__ . '/orm/PembayaranPembelianORM.php';
require_once __DIR__ . '/orm/BiayaProduksiORM.php';
require_once __DIR__ . '/orm/BiayaProduksiDetailORM.php';
require_once __DIR__ . '/orm/HasilProduksiORM.php';
require_once __DIR__ . '/orm/SaldoAwalStokORM.php';
require_once __DIR__ . '/orm/SaldoAwalStokDetailORM.php';
require_once __DIR__ . '/orm/StokOpnameORM.php';
require_once __DIR__ . '/orm/StokOpnameDetailORM.php';
require_once __DIR__ . '/orm/PesananPenjualanDetailORM.php';
require_once __DIR__ . '/orm/PenyerahanPenjualanORM.php';
require_once __DIR__ . '/orm/PenyerahanPenjualanDetailORM.php';
require_once __DIR__ . '/orm/FakturPenjualanORM.php';
require_once __DIR__ . '/orm/FakturPenjualanDetailORM.php';
require_once __DIR__ . '/orm/PembayaranPenjualanORM.php';



require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/menu.php';
require_once __DIR__ . '/helpers/render.php';
require_once __DIR__ . '/helpers/kode.php';

if (is_login()) {
    redirect_admin(halaman_awal_role());
}

$page_title = 'Login';
render_guest(__DIR__ . '/administrator/auth/login.php', compact('page_title'));