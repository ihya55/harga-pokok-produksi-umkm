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
// tambahkan seterusnya disini
require_once __DIR__ . '/../orm/SatuanORM.php';
require_once __DIR__ . '/../orm/GudangORM.php';
require_once __DIR__ . '/../orm/PelangganORM.php';
require_once __DIR__ . '/../orm/PemasokORM.php';
require_once __DIR__ . '/../orm/KategoriProdukORM.php';
require_once __DIR__ . '/../orm/PajakORM.php';
require_once __DIR__ . '/../orm/ProdukORM.php';
require_once __DIR__ . '/../orm/CoaORM.php';
require_once __DIR__ . '/../orm/PeriodeAkuntansiORM.php';
require_once __DIR__ . '/../orm/TemplateJurnalORM.php';
require_once __DIR__ . '/../orm/TemplateJurnalDetailORM.php';
require_once __DIR__ . '/../orm/MappingAkunORM.php';
require_once __DIR__ . '/../orm/BahanBakuORM.php';
require_once __DIR__ . '/../orm/KonfigurasiAkunORM.php';
require_once __DIR__ . '/../orm/ResepORM.php';
require_once __DIR__ . '/../orm/ResepDetailORM.php';
require_once __DIR__ . '/../orm/PerintahProduksiORM.php';
require_once __DIR__ . '/../orm/PesananPenjualanORM.php';
require_once __DIR__ . '/../orm/PengambilanBahanORM.php';
require_once __DIR__ . '/../orm/PengambilanBahanDetailORM.php';
require_once __DIR__ . '/../orm/PesananPembelianORM.php';
require_once __DIR__ . '/../orm/PesananPembelianDetailORM.php';
require_once __DIR__ . '/../orm/PenerimaanPembelianORM.php';
require_once __DIR__ . '/../orm/PenerimaanPembelianDetailORM.php';
require_once __DIR__ . '/../orm/MutasiStokORM.php';
require_once __DIR__ . '/../orm/SaldoStokORM.php';
require_once __DIR__ . '/../orm/FakturPembelianORM.php';
require_once __DIR__ . '/../orm/FakturPembelianDetailORM.php';
require_once __DIR__ . '/../orm/JurnalORM.php';
require_once __DIR__ . '/../orm/JurnalDetailORM.php';
require_once __DIR__ . '/../orm/LogJurnalSumberORM.php';
require_once __DIR__ . '/../orm/PembayaranPembelianORM.php';
require_once __DIR__ . '/../orm/BiayaProduksiORM.php';
require_once __DIR__ . '/../orm/BiayaProduksiDetailORM.php';
require_once __DIR__ . '/../orm/HasilProduksiORM.php';
require_once __DIR__ . '/../orm/SaldoAwalStokORM.php';
require_once __DIR__ . '/../orm/SaldoAwalStokDetailORM.php';
require_once __DIR__ . '/../orm/StokOpnameORM.php';
require_once __DIR__ . '/../orm/StokOpnameDetailORM.php';
require_once __DIR__ . '/../orm/PesananPenjualanDetailORM.php';
require_once __DIR__ . '/../orm/PenyerahanPenjualanORM.php';
require_once __DIR__ . '/../orm/PenyerahanPenjualanDetailORM.php';
require_once __DIR__ . '/../orm/FakturPenjualanORM.php';
require_once __DIR__ . '/../orm/FakturPenjualanDetailORM.php';
require_once __DIR__ . '/../orm/PembayaranPenjualanORM.php';










require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/menu.php';
require_once __DIR__ . '/../helpers/render.php';
require_once __DIR__ . '/../helpers/kode.php';
require_once __DIR__ . '/../helpers/pajak.php';

harus_login();

$user = user_login();

$menu_aktif = trim((string) ($_GET['menu'] ?? halaman_awal_role()), '/');
if ($menu_aktif === '') {
    $menu_aktif = halaman_awal_role();
}

if (!boleh_akses_menu($menu_aktif)) {
    $page_title = 'Akses Ditolak';
    render_admin(__DIR__ . '/menu/not_ready/index.php', compact('page_title', 'menu_aktif'));
    exit;
}


if (!function_exists('admin_is_super_admin')) {
    function admin_is_super_admin(array $user_login): bool
    {
        $username = strtolower(trim((string) ($user_login['username'] ?? '')));
        $nama_role_session = strtolower(trim((string) ($user_login['nama_role'] ?? $user_login['role'] ?? '')));

        if ($username === 'super_admin' || $nama_role_session === 'super_admin' || $nama_role_session === 'admin_entitas') {
            return true;
        }

        $id_role = (int) ($user_login['id_role'] ?? 0);

        if ($id_role > 0) {
            $role = \Illuminate\Database\Capsule\Manager::table('tb_role')
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

if (!function_exists('wajib_super_admin')) {
    function wajib_super_admin(?array $user_login): void
    {
        $user_login = $user_login ?? user_login();

        if (!admin_is_super_admin($user_login)) {
            set_flash('error', 'Menu ini hanya boleh diakses oleh super admin/admin utama.');
            redirect_admin('dashboard');
        }
    }
}

switch ($menu_aktif) {
    
    case 'dashboard':
        $page_title = 'Dashboard';
        render_admin(__DIR__ . '/dashboard/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/dashboard':
        $page_title = 'Dashboard Penjualan';
        render_admin(__DIR__ . '/dashboard/penjualan.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/dashboard':
        $page_title = 'Dashboard Pembelian';
        render_admin(__DIR__ . '/dashboard/pembelian.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/dashboard':
        $page_title = 'Dashboard Produksi';
        render_admin(__DIR__ . '/dashboard/produksi.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/dashboard':
        $page_title = 'Dashboard Gudang';
        render_admin(__DIR__ . '/dashboard/gudang.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/dashboard':
        $page_title = 'Dashboard Keuangan';
        render_admin(__DIR__ . '/dashboard/keuangan.php', compact('page_title', 'menu_aktif'));
        break;

    case 'profil':
        $page_title = 'Profil Saya';
        render_admin(__DIR__ . '/menu/profil_saya/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'ganti-password':
        $page_title = 'Ganti Password';
        render_admin(__DIR__ . '/menu/ganti_password/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'logout':
        require __DIR__ . '/auth/logout.php';
        exit;

        // tambahan halaman nya disini :

    case 'master_setup/satuan':
        $page_title = 'Satuan';
        render_admin(__DIR__ . '/menu/master_setup/satuan/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/satuan/tambah':
        $page_title = 'Tambah Satuan';
        render_admin(__DIR__ . '/menu/master_setup/satuan/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/satuan/edit':
        $page_title = 'Edit Satuan';
        render_admin(__DIR__ . '/menu/master_setup/satuan/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/gudang':
        $page_title = 'Gudang';
        render_admin(__DIR__ . '/menu/master_setup/gudang/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/gudang/tambah':
        $page_title = 'Tambah Gudang';
        render_admin(__DIR__ . '/menu/master_setup/gudang/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/gudang/edit':
        $page_title = 'Edit Gudang';
        render_admin(__DIR__ . '/menu/master_setup/gudang/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/gudang/detail':
        $page_title = 'Detail Gudang';
        render_admin(__DIR__ . '/menu/master_setup/gudang/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/pelanggan':
        $page_title = 'Pelanggan';
        render_admin(__DIR__ . '/menu/master_setup/pelanggan/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/pelanggan/tambah':
        $page_title = 'Tambah Pelanggan';
        render_admin(__DIR__ . '/menu/master_setup/pelanggan/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/pelanggan/edit':
        $page_title = 'Edit Pelanggan';
        render_admin(__DIR__ . '/menu/master_setup/pelanggan/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/pelanggan/detail':
        $page_title = 'Detail Pelanggan';
        render_admin(__DIR__ . '/menu/master_setup/pelanggan/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/pemasok':
        $page_title = 'Pemasok';
        render_admin(__DIR__ . '/menu/master_setup/pemasok/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/pemasok/tambah':
        $page_title = 'Tambah Pemasok';
        render_admin(__DIR__ . '/menu/master_setup/pemasok/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/pemasok/detail':
        $page_title = 'Detail Pemasok';
        render_admin(__DIR__ . '/menu/master_setup/pemasok/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/pemasok/edit':
        $page_title = 'Edit Pemasok';
        render_admin(__DIR__ . '/menu/master_setup/pemasok/edit.php', compact('page_title', 'menu_aktif'));
        break;


    case 'master_setup/pajak':
    case 'master_setup/pajak/tambah':
    case 'master_setup/pajak/edit':
        redirect_admin('master_setup/mapping_akun&tab=pajak_ppn');
        break;

    case 'master_setup/reset_data':
        $page_title = 'Reset Data Percobaan';
        render_admin(__DIR__ . '/menu/master_setup/reset_data/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/kategori_produk':
        $page_title = 'Kategori Produk';
        render_admin(__DIR__ . '/menu/master_setup/kategori_produk/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/kategori_produk/tambah':
        $page_title = 'Tambah Kategori Produk';
        render_admin(__DIR__ . '/menu/master_setup/kategori_produk/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/kategori_produk/detail':
        $page_title = 'Detail Kategori Produk';
        render_admin(__DIR__ . '/menu/master_setup/kategori_produk/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/kategori_produk/edit':
        $page_title = 'Edit Kategori Produk';
        render_admin(__DIR__ . '/menu/master_setup/kategori_produk/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/produk':
        $page_title = 'Produk';
        render_admin(__DIR__ . '/menu/master_setup/produk/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/produk/tambah':
        $page_title = 'Tambah Produk';
        render_admin(__DIR__ . '/menu/master_setup/produk/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/produk/detail':
        $page_title = 'Detail Produk';
        render_admin(__DIR__ . '/menu/master_setup/produk/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/produk/edit':
        $page_title = 'Edit Produk';
        render_admin(__DIR__ . '/menu/master_setup/produk/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/coa':
        $page_title = 'COA';
        render_admin(__DIR__ . '/menu/master_setup/coa/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/coa/tambah':
        $page_title = 'Tambah COA';
        render_admin(__DIR__ . '/menu/master_setup/coa/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/coa/edit':
        $page_title = 'Edit COA';
        render_admin(__DIR__ . '/menu/master_setup/coa/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/coa/detail':
        $page_title = 'Detail COA';
        render_admin(__DIR__ . '/menu/master_setup/coa/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/periode_akuntansi':
        $page_title = 'Periode Akuntansi';
        render_admin(__DIR__ . '/menu/master_setup/periode_akuntansi/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/periode_akuntansi/tambah':
        $page_title = 'Tambah Periode Akuntansi';
        render_admin(__DIR__ . '/menu/master_setup/periode_akuntansi/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/periode_akuntansi/edit':
        $page_title = 'Edit Periode Akuntansi';
        render_admin(__DIR__ . '/menu/master_setup/periode_akuntansi/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/periode_akuntansi/detail':
        $page_title = 'Detail Periode Akuntansi';
        render_admin(__DIR__ . '/menu/master_setup/periode_akuntansi/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/template_jurnal':
        $page_title = 'Template Jurnal';
        render_admin(__DIR__ . '/menu/master_setup/template_jurnal/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/template_jurnal/tambah':
        $page_title = 'Tambah Template Jurnal';
        render_admin(__DIR__ . '/menu/master_setup/template_jurnal/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/template_jurnal/edit':
        $page_title = 'Edit Template Jurnal';
        render_admin(__DIR__ . '/menu/master_setup/template_jurnal/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/template_jurnal/detail':
        $page_title = 'Detail Template Jurnal';
        render_admin(__DIR__ . '/menu/master_setup/template_jurnal/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/template_jurnal/detail_baris':
        $page_title = 'Detail Baris Template Jurnal';
        render_admin(__DIR__ . '/menu/master_setup/template_jurnal/detail_baris.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/template_jurnal/simulasi':
        $page_title = 'Simulasi Template Jurnal';
        render_admin(__DIR__ . '/menu/master_setup/template_jurnal/simulasi.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/mapping_akun':
        $page_title = 'Mapping Akun';
        render_admin(__DIR__ . '/menu/master_setup/mapping_akun/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/mapping_akun/tambah':
        $page_title = 'Tambah Mapping Akun';
        render_admin(__DIR__ . '/menu/master_setup/mapping_akun/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/mapping_akun/edit':
        $page_title = 'Edit Mapping Akun';
        render_admin(__DIR__ . '/menu/master_setup/mapping_akun/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/mapping_akun/detail':
        $page_title = 'Detail Mapping Akun';
        render_admin(__DIR__ . '/menu/master_setup/mapping_akun/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/mapping_laporan':
        $page_title = 'Mapping Laporan';
        render_admin(__DIR__ . '/menu/master_setup/mapping_laporan/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/bahan_baku':
        $page_title = 'Bahan Baku';
        render_admin(__DIR__ . '/menu/master_setup/bahan_baku/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/bahan_baku/tambah':
        $page_title = 'Tambah Bahan Baku';
        render_admin(__DIR__ . '/menu/master_setup/bahan_baku/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/bahan_baku/edit':
        $page_title = 'Edit Bahan Baku';
        render_admin(__DIR__ . '/menu/master_setup/bahan_baku/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/bahan_baku/detail':
        $page_title = 'Detail Bahan Baku';
        render_admin(__DIR__ . '/menu/master_setup/bahan_baku/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/konfigurasi_akun':
        $page_title = 'Konfigurasi Akun Sistem';
        render_admin(__DIR__ . '/menu/master_setup/konfigurasi_akun/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/konfigurasi_akun/tambah':
        $page_title = 'Tambah Konfigurasi Akun Sistem';
        render_admin(__DIR__ . '/menu/master_setup/konfigurasi_akun/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/konfigurasi_akun/edit':
        $page_title = 'Edit Konfigurasi Akun Sistem';
        render_admin(__DIR__ . '/menu/master_setup/konfigurasi_akun/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/konfigurasi_akun/detail':
        $page_title = 'Detail Konfigurasi Akun Sistem';
        render_admin(__DIR__ . '/menu/master_setup/konfigurasi_akun/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/resep':
        $page_title = 'Resep / BOM';
        render_admin(__DIR__ . '/menu/master_setup/resep/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/resep/tambah':
        $page_title = 'Tambah Resep / BOM';
        render_admin(__DIR__ . '/menu/master_setup/resep/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/resep/edit':
        $page_title = 'Edit Resep / BOM';
        render_admin(__DIR__ . '/menu/master_setup/resep/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master_setup/resep/detail':
        $page_title = 'Detail Resep / BOM';
        render_admin(__DIR__ . '/menu/master_setup/resep/detail.php', compact('page_title', 'menu_aktif'));
        break;
    

    // 
    case 'produksi/perintah':
        $page_title = 'Perintah Produksi';
        render_admin(__DIR__ . '/menu/produksi/perintah/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/perintah/tambah':
        $page_title = 'Tambah Perintah Produksi';
        render_admin(__DIR__ . '/menu/produksi/perintah/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/perintah/edit':
        $page_title = 'Edit Perintah Produksi';
        render_admin(__DIR__ . '/menu/produksi/perintah/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/perintah/detail':
        $page_title = 'Detail Perintah Produksi';
        render_admin(__DIR__ . '/menu/produksi/perintah/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/pengambilan-bahan':
        $page_title = 'Pengambilan Bahan';
        render_admin(__DIR__ . '/menu/produksi/pengambilan_bahan/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/pengambilan-bahan/tambah':
        $page_title = 'Tambah Pengambilan Bahan';
        render_admin(__DIR__ . '/menu/produksi/pengambilan_bahan/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/pengambilan-bahan/edit':
        $page_title = 'Edit Pengambilan Bahan';
        render_admin(__DIR__ . '/menu/produksi/pengambilan_bahan/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/pengambilan-bahan/detail':
        $page_title = 'Detail Pengambilan Bahan';
        render_admin(__DIR__ . '/menu/produksi/pengambilan_bahan/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/pengambilan-bahan/load-detail-resep':
        require __DIR__ . '/menu/produksi/pengambilan_bahan/load_detail_resep.php';
        exit;

    case 'produksi/pengambilan-bahan/periksa-stok':
        require __DIR__ . '/menu/produksi/pengambilan_bahan/periksa_stok.php';
        exit;

    case 'produksi/pengambilan-bahan/posting':
        require __DIR__ . '/menu/produksi/pengambilan_bahan/posting.php';
        exit;

    case 'produksi/pengambilan-bahan/cetak':
        require __DIR__ . '/menu/produksi/pengambilan_bahan/cetak.php';
        exit;

    case 'pembelian/pesanan':
        $page_title = 'Pesanan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pesanan/index.php', compact('page_title', 'menu_aktif'));
        break;
        
    case 'pembelian/pesanan/tambah':
        $page_title = 'Tambah Pesanan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pesanan/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/pesanan/edit':
        $page_title = 'Edit Pesanan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pesanan/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/pesanan/detail':
        $page_title = 'Detail Pesanan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pesanan/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/penerimaan':
        $page_title = 'Penerimaan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/penerimaan/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/penerimaan/tambah':
        $page_title = 'Tambah Penerimaan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/penerimaan/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/penerimaan/edit':
        $page_title = 'Edit Penerimaan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/penerimaan/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/penerimaan/detail':
        $page_title = 'Detail Penerimaan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/penerimaan/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/faktur':
        $page_title = 'Faktur Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/faktur/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/faktur/tambah':
        $page_title = 'Tambah Faktur Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/faktur/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/faktur/edit':
        $page_title = 'Edit Faktur Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/faktur/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/faktur/detail':
        $page_title = 'Detail Faktur Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/faktur/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/faktur/load-detail-penerimaan':
        require __DIR__ . '/menu/pembelian/faktur/load_detail_penerimaan.php';
        exit;
    
    case 'pembelian/pembayaran':
        $page_title = 'Pembayaran Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pembayaran/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/pembayaran/tambah':
        $page_title = 'Tambah Pembayaran Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pembayaran/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/pembayaran/edit':
        $page_title = 'Edit Pembayaran Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pembayaran/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/pembayaran/detail':
        $page_title = 'Detail Pembayaran Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pembayaran/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/pembayaran/detail-faktur':
        $page_title = 'Detail Tagihan Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pembayaran/detail_faktur.php', compact('page_title', 'menu_aktif'));
        break;

    case 'pembelian/pembayaran/riwayat':
        $page_title = 'Riwayat Pembayaran Pembelian';
        render_admin(__DIR__ . '/menu/pembelian/pembayaran/riwayat.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/biaya':
        $page_title = 'Biaya Produksi';
        render_admin(__DIR__ . '/menu/produksi/biaya/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/biaya/tambah':
        $page_title = 'Tambah Biaya Produksi';
        render_admin(__DIR__ . '/menu/produksi/biaya/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/biaya/edit':
        $page_title = 'Edit Biaya Produksi';
        render_admin(__DIR__ . '/menu/produksi/biaya/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/biaya/detail':
        $page_title = 'Detail Biaya Produksi';
        render_admin(__DIR__ . '/menu/produksi/biaya/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/hasil':
        $page_title = 'Hasil Produksi';
        render_admin(__DIR__ . '/menu/produksi/hasil/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/hasil/tambah':
        $page_title = 'Tambah Hasil Produksi';
        render_admin(__DIR__ . '/menu/produksi/hasil/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/hasil/edit':
        $page_title = 'Edit Hasil Produksi';
        render_admin(__DIR__ . '/menu/produksi/hasil/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/hasil/detail':
        $page_title = 'Detail Hasil Produksi';
        render_admin(__DIR__ . '/menu/produksi/hasil/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'produksi/hasil/hitung-hpp':
        require __DIR__ . '/menu/produksi/hasil/hitung_hpp.php';
        exit;

    case 'produksi/hasil/posting':
        require __DIR__ . '/menu/produksi/hasil/posting.php';
        exit;

    case 'produksi/hasil/cetak':
        require __DIR__ . '/menu/produksi/hasil/cetak.php';
        exit;

    


    case 'persediaan/mutasi-stok':
        $page_title = 'Mutasi Stok';
        render_admin(__DIR__ . '/menu/persediaan/mutasi_stok/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/mutasi-stok/detail':
        $page_title = 'Detail Mutasi Stok';
        render_admin(__DIR__ . '/menu/persediaan/mutasi_stok/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/mutasi-stok/cetak':
        require __DIR__ . '/menu/persediaan/mutasi_stok/cetak.php';
        exit;


    case 'persediaan/konversi-produk':
        $page_title = 'Konversi Produk';
        render_admin(__DIR__ . '/menu/persediaan/konversi_produk/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/konversi-produk/tambah':
        $page_title = 'Tambah Konversi Produk';
        render_admin(__DIR__ . '/menu/persediaan/konversi_produk/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/konversi-produk/edit':
        $page_title = 'Edit Konversi Produk';
        render_admin(__DIR__ . '/menu/persediaan/konversi_produk/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/konversi-produk/detail':
        $page_title = 'Detail Konversi Produk';
        render_admin(__DIR__ . '/menu/persediaan/konversi_produk/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/konversi-produk/simpan':
        require __DIR__ . '/menu/persediaan/konversi_produk/simpan.php';
        exit;

    case 'persediaan/konversi-produk/update':
        require __DIR__ . '/menu/persediaan/konversi_produk/update.php';
        exit;

    case 'persediaan/konversi-produk/posting':
        require __DIR__ . '/menu/persediaan/konversi_produk/posting.php';
        exit;

    case 'persediaan/saldo-stok':
        $page_title = 'Saldo Stok';
        render_admin(__DIR__ . '/menu/persediaan/saldo_stok/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/saldo-stok/cetak':
        require __DIR__ . '/menu/persediaan/saldo_stok/cetak.php';
        exit;

    case 'persediaan/saldo-stok/saldo-awal':
        $page_title = 'Saldo Awal Stok';
        render_admin(__DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/saldo-stok/saldo-awal/tambah':
        $page_title = 'Tambah Saldo Awal Stok';
        render_admin(__DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/saldo-stok/saldo-awal/edit':
        $page_title = 'Edit Saldo Awal Stok';
        render_admin(__DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/saldo-stok/saldo-awal/detail':
        $page_title = 'Detail Saldo Awal Stok';
        render_admin(__DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/saldo-stok/saldo-awal/simpan':
        require __DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_simpan.php';
        exit;

    case 'persediaan/saldo-stok/saldo-awal/update':
        require __DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_update.php';
        exit;

    case 'persediaan/saldo-stok/saldo-awal/posting':
        require __DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_posting.php';
        exit;

    case 'persediaan/saldo-stok/saldo-awal/hapus':
        require __DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_hapus.php';
        exit;

    case 'persediaan/saldo-stok/saldo-awal/hapus-massal':
        require __DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_hapus_massal.php';
        exit;

    case 'persediaan/saldo-stok/saldo-awal/cetak':
        require __DIR__ . '/menu/persediaan/saldo_stok/saldo_awal_cetak.php';
        exit;

    case 'persediaan/stok-minimum':
        $page_title = 'Stok Minimum';
        render_admin(__DIR__ . '/menu/persediaan/stok_minimum/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/stok-minimum/cetak':
        require __DIR__ . '/menu/persediaan/stok_minimum/cetak.php';
        exit;

    case 'persediaan/stok-opname':
        $page_title = 'Stok Opname';
        render_admin(__DIR__ . '/menu/persediaan/stok_opname/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/stok-opname/tambah':
        $page_title = 'Tambah Stok Opname';
        render_admin(__DIR__ . '/menu/persediaan/stok_opname/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/stok-opname/edit':
        $page_title = 'Edit Stok Opname';
        render_admin(__DIR__ . '/menu/persediaan/stok_opname/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/stok-opname/detail':
        $page_title = 'Detail Stok Opname';
        render_admin(__DIR__ . '/menu/persediaan/stok_opname/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'persediaan/stok-opname/simpan':
        require __DIR__ . '/menu/persediaan/stok_opname/simpan.php';
        exit;

    case 'persediaan/stok-opname/update':
        require __DIR__ . '/menu/persediaan/stok_opname/update.php';
        exit;

    case 'persediaan/stok-opname/hapus':
        require __DIR__ . '/menu/persediaan/stok_opname/hapus.php';
        exit;

    case 'persediaan/stok-opname/hapus-massal':
        require __DIR__ . '/menu/persediaan/stok_opname/hapus_massal.php';
        exit;

    case 'persediaan/stok-opname/posting':
        require __DIR__ . '/menu/persediaan/stok_opname/posting.php';
        exit;

    case 'persediaan/stok-opname/cetak':
        require __DIR__ . '/menu/persediaan/stok_opname/cetak.php';
        exit;

    case 'penjualan/dashboard':
        $page_title = 'Dashboard Penjualan';
        render_admin(__DIR__ . '/dashboard/penjualan.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/kasir':
        $page_title = 'Kasir / POS';
        render_admin(__DIR__ . '/menu/penjualan/kasir/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/kasir/simpan':
        require __DIR__ . '/menu/penjualan/kasir/simpan.php';
        exit;

    case 'penjualan/kasir/struk':
        require __DIR__ . '/menu/penjualan/kasir/struk.php';
        exit;

    case 'penjualan/kasir/riwayat':
        $page_title = 'Riwayat Kasir / POS';
        render_admin(__DIR__ . '/menu/penjualan/kasir/riwayat.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/kasir/detail':
        $page_title = 'Detail Transaksi Kasir';
        render_admin(__DIR__ . '/menu/penjualan/kasir/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/kasir/batal':
        require __DIR__ . '/menu/penjualan/kasir/batal.php';
        exit;

    case 'penjualan/pesanan':
        $page_title = 'Pesanan Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/pesanan/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pesanan/tambah':
        $page_title = 'Tambah Pesanan Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/pesanan/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pesanan/edit':
        $page_title = 'Edit Pesanan Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/pesanan/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pesanan/detail':
        $page_title = 'Detail Pesanan Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/pesanan/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pesanan/simpan':
        require __DIR__ . '/menu/penjualan/pesanan/simpan.php';
        exit;

    case 'penjualan/pesanan/update':
        require __DIR__ . '/menu/penjualan/pesanan/update.php';
        exit;

    case 'penjualan/pesanan/hapus':
        require __DIR__ . '/menu/penjualan/pesanan/hapus.php';
        exit;

    case 'penjualan/pesanan/hapus-massal':
        require __DIR__ . '/menu/penjualan/pesanan/hapus_massal.php';
        exit;

    case 'penjualan/pesanan/konfirmasi':
        require __DIR__ . '/menu/penjualan/pesanan/konfirmasi.php';
        exit;

    case 'penjualan/pesanan/batal':
        require __DIR__ . '/menu/penjualan/pesanan/batal.php';
        exit;

    case 'penjualan/pesanan/cetak':
        require __DIR__ . '/menu/penjualan/pesanan/cetak.php';
        exit;

    case 'penjualan/penyerahan':
        $page_title = 'Penyerahan Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/penyerahan/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/penyerahan/tambah':
        $page_title = 'Tambah Penyerahan Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/penyerahan/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/penyerahan/edit':
        $page_title = 'Edit Penyerahan Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/penyerahan/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/penyerahan/detail':
        $page_title = 'Detail Penyerahan Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/penyerahan/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/penyerahan/simpan':
        require __DIR__ . '/menu/penjualan/penyerahan/simpan.php';
        exit;

    case 'penjualan/penyerahan/update':
        require __DIR__ . '/menu/penjualan/penyerahan/update.php';
        exit;

    case 'penjualan/penyerahan/hapus':
        require __DIR__ . '/menu/penjualan/penyerahan/hapus.php';
        exit;

    case 'penjualan/penyerahan/hapus-massal':
        require __DIR__ . '/menu/penjualan/penyerahan/hapus_massal.php';
        exit;

    case 'penjualan/penyerahan/posting':
        require __DIR__ . '/menu/penjualan/penyerahan/posting.php';
        exit;

    case 'penjualan/penyerahan/cetak':
        require __DIR__ . '/menu/penjualan/penyerahan/cetak.php';
        exit;

    case 'penjualan/penyerahan/load-detail-pesanan':
        require __DIR__ . '/menu/penjualan/penyerahan/load_detail_pesanan.php';
        exit;

    case 'penjualan/penyerahan/load-pesanan-online-barcode':
        require __DIR__ . '/menu/penjualan/penyerahan/load_pesanan_online_barcode.php';
        exit;

    case 'penjualan/penyerahan/proses-barcode-cod':
        require __DIR__ . '/menu/penjualan/penyerahan/proses_barcode_cod.php';
        exit;

    case 'penjualan/faktur':
        $page_title = 'Daftar Piutang Pelanggan';
        render_admin(__DIR__ . '/menu/penjualan/faktur/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/faktur/tambah':
        $page_title = 'Tambah Piutang Pelanggan';
        render_admin(__DIR__ . '/menu/penjualan/faktur/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/faktur/edit':
        $page_title = 'Edit Piutang Pelanggan';
        render_admin(__DIR__ . '/menu/penjualan/faktur/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/faktur/detail':
        $page_title = 'Detail Piutang Pelanggan';
        render_admin(__DIR__ . '/menu/penjualan/faktur/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/faktur/simpan':
        require __DIR__ . '/menu/penjualan/faktur/simpan.php';
        exit;

    case 'penjualan/faktur/update':
        require __DIR__ . '/menu/penjualan/faktur/update.php';
        exit;

    case 'penjualan/faktur/hapus':
        require __DIR__ . '/menu/penjualan/faktur/hapus.php';
        exit;

    case 'penjualan/faktur/hapus-massal':
        require __DIR__ . '/menu/penjualan/faktur/hapus_massal.php';
        exit;

    case 'penjualan/faktur/posting':
        require __DIR__ . '/menu/penjualan/faktur/posting.php';
        exit;

    case 'penjualan/faktur/cetak':
        require __DIR__ . '/menu/penjualan/faktur/cetak.php';
        exit;

    case 'penjualan/faktur/load-detail-penyerahan':
        require __DIR__ . '/menu/penjualan/faktur/load_detail_penyerahan.php';
        exit;

    case 'penjualan/pembayaran':
        $page_title = 'Pembayaran Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/pembayaran/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pembayaran/tambah':
        $page_title = 'Tambah Pembayaran Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/pembayaran/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pembayaran/detail':
        $page_title = 'Detail Pembayaran Penjualan';
        render_admin(__DIR__ . '/menu/penjualan/pembayaran/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pembayaran/simpan':
        require __DIR__ . '/menu/penjualan/pembayaran/simpan.php';
        exit;

    case 'penjualan/pembayaran/hapus':
        require __DIR__ . '/menu/penjualan/pembayaran/hapus.php';
        exit;

    case 'penjualan/pembayaran/posting':
        require __DIR__ . '/menu/penjualan/pembayaran/posting.php';
        exit;

    case 'penjualan/pembayaran/cetak':
        require __DIR__ . '/menu/penjualan/pembayaran/cetak.php';
        exit;

    case 'penjualan/pembayaran/load-detail-faktur':
        require __DIR__ . '/menu/penjualan/pembayaran/load_detail_faktur.php';
        exit;

    case 'penjualan/pesanan-online':
        $page_title = 'Pesanan Online';
        render_admin(__DIR__ . '/menu/penjualan/pesanan_online/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pesanan-online/detail':
        $page_title = 'Detail Pesanan Online';
        render_admin(__DIR__ . '/menu/penjualan/pesanan_online/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/pesanan-online/konfirmasi':
        require __DIR__ . '/menu/penjualan/pesanan_online/konfirmasi.php';
        exit;

    case 'penjualan/pesanan-online/batal':
        require __DIR__ . '/menu/penjualan/pesanan_online/batal.php';
        exit;

    case 'penjualan/pesanan-online/cetak':
        require __DIR__ . '/menu/penjualan/pesanan_online/cetak.php';
        exit;

    case 'penjualan/pesanan-online/verifikasi-pembayaran':
        require __DIR__ . '/menu/penjualan/pesanan_online/verifikasi_pembayaran.php';
        exit;

    case 'penjualan/pesanan-online/tolak-pembayaran':
        require __DIR__ . '/menu/penjualan/pesanan_online/tolak_pembayaran.php';
        exit;

    case 'penjualan/pesanan-online/simpan-refund':
        require __DIR__ . '/menu/penjualan/pesanan_online/simpan_refund.php';
        exit;

    case 'penjualan/pesanan-online/struk':
        require __DIR__ . '/menu/penjualan/pesanan_online/struk.php';
        exit;

    case 'penjualan/pesanan-online/chat-kirim':
        require __DIR__ . '/menu/penjualan/pesanan_online/chat_kirim.php';
        exit;


    case 'penjualan/chat-online':
        $page_title = 'Log Chat Online';
        render_admin(__DIR__ . '/menu/penjualan/chat_online/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'penjualan/chat-online/kirim':
        require __DIR__ . '/menu/penjualan/chat_online/kirim.php';
        exit;


    case 'master/entitas':
        $page_title = 'Entitas';
        render_admin(__DIR__ . '/menu/master_setup/entitas/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/entitas/tambah':
        $page_title = 'Tambah Entitas';
        render_admin(__DIR__ . '/menu/master_setup/entitas/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/entitas/edit':
        $page_title = 'Edit Entitas';
        render_admin(__DIR__ . '/menu/master_setup/entitas/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/entitas/detail':
        $page_title = 'Detail Entitas';
        render_admin(__DIR__ . '/menu/master_setup/entitas/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/role':
        wajib_super_admin($user);
        $page_title = 'Role';
        render_admin(__DIR__ . '/menu/master_setup/role/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/role/tambah':
        wajib_super_admin($user);
        $page_title = 'Tambah Role';
        render_admin(__DIR__ . '/menu/master_setup/role/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/role/edit':
        wajib_super_admin($user);
        $page_title = 'Edit Role';
        render_admin(__DIR__ . '/menu/master_setup/role/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/role/detail':
        wajib_super_admin($user);
        $page_title = 'Detail Role';
        render_admin(__DIR__ . '/menu/master_setup/role/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/role/simpan':
        wajib_super_admin($user);
        require __DIR__ . '/menu/master_setup/role/simpan.php';
        exit;

    case 'master/role/update':
        wajib_super_admin($user);
        require __DIR__ . '/menu/master_setup/role/update.php';
        exit;

    case 'master/role/hapus':
        wajib_super_admin($user);
        require __DIR__ . '/menu/master_setup/role/hapus.php';
        exit;

    case 'master/pengguna':
        $page_title = 'Pengguna';
        render_admin(__DIR__ . '/menu/master_setup/pengguna/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/pengguna/tambah':
        $page_title = 'Tambah Pengguna';
        render_admin(__DIR__ . '/menu/master_setup/pengguna/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/pengguna/edit':
        $page_title = 'Edit Pengguna';
        render_admin(__DIR__ . '/menu/master_setup/pengguna/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'master/pengguna/detail':
        $page_title = 'Detail Pengguna';
        render_admin(__DIR__ . '/menu/master_setup/pengguna/detail.php', compact('page_title', 'menu_aktif'));
        break;


   

   

    case 'keuangan/kas-masuk':
        $page_title = 'Kas Masuk';
        render_admin(__DIR__ . '/menu/keuangan/kas_masuk/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/kas-masuk/tambah':
        $page_title = 'Tambah Kas Masuk';
        render_admin(__DIR__ . '/menu/keuangan/kas_masuk/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/kas-masuk/edit':
        $page_title = 'Edit Kas Masuk';
        render_admin(__DIR__ . '/menu/keuangan/kas_masuk/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/kas-masuk/detail':
        $page_title = 'Detail Kas Masuk';
        render_admin(__DIR__ . '/menu/keuangan/kas_masuk/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/kas-masuk/simpan':
        require __DIR__ . '/menu/keuangan/kas_masuk/simpan.php';
        exit;

    case 'keuangan/kas-masuk/update':
        require __DIR__ . '/menu/keuangan/kas_masuk/update.php';
        exit;

    case 'keuangan/kas-masuk/posting':
        require __DIR__ . '/menu/keuangan/kas_masuk/posting.php';
        exit;

    case 'keuangan/kas-masuk/hapus':
        require __DIR__ . '/menu/keuangan/kas_masuk/hapus.php';
        exit;

    case 'keuangan/kas-masuk/cetak':
        require __DIR__ . '/menu/keuangan/kas_masuk/cetak.php';
        exit;

    case 'keuangan/kas-keluar':
        $page_title = 'Kas Keluar';
        render_admin(__DIR__ . '/menu/keuangan/kas_keluar/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/kas-keluar/tambah':
        $page_title = 'Tambah Kas Keluar';
        render_admin(__DIR__ . '/menu/keuangan/kas_keluar/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/kas-keluar/edit':
        $page_title = 'Edit Kas Keluar';
        render_admin(__DIR__ . '/menu/keuangan/kas_keluar/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/kas-keluar/detail':
        $page_title = 'Detail Kas Keluar';
        render_admin(__DIR__ . '/menu/keuangan/kas_keluar/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/kas-keluar/simpan':
        require __DIR__ . '/menu/keuangan/kas_keluar/simpan.php';
        exit;

    case 'keuangan/kas-keluar/update':
        require __DIR__ . '/menu/keuangan/kas_keluar/update.php';
        exit;

    case 'keuangan/kas-keluar/posting':
        require __DIR__ . '/menu/keuangan/kas_keluar/posting.php';
        exit;

    case 'keuangan/kas-keluar/hapus':
        require __DIR__ . '/menu/keuangan/kas_keluar/hapus.php';
        exit;

    case 'keuangan/kas-keluar/cetak':
        require __DIR__ . '/menu/keuangan/kas_keluar/cetak.php';
        exit;

    case 'keuangan/jurnal':
        $page_title = 'Jurnal';
        render_admin(__DIR__ . '/menu/keuangan/jurnal/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/jurnal/tambah':
        $page_title = 'Tambah Jurnal';
        render_admin(__DIR__ . '/menu/keuangan/jurnal/tambah.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/jurnal/edit':
        $page_title = 'Edit Jurnal';
        render_admin(__DIR__ . '/menu/keuangan/jurnal/edit.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/jurnal/detail':
        $page_title = 'Detail Jurnal';
        render_admin(__DIR__ . '/menu/keuangan/jurnal/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/jurnal/simpan':
        require __DIR__ . '/menu/keuangan/jurnal/simpan.php';
        exit;

    case 'keuangan/jurnal/update':
        require __DIR__ . '/menu/keuangan/jurnal/update.php';
        exit;

    case 'keuangan/jurnal/posting':
        require __DIR__ . '/menu/keuangan/jurnal/posting.php';
        exit;

    case 'keuangan/jurnal/hapus':
        require __DIR__ . '/menu/keuangan/jurnal/hapus.php';
        exit;

    case 'keuangan/jurnal/cetak':
        require __DIR__ . '/menu/keuangan/jurnal/cetak.php';
        exit;

    case 'keuangan/log-jurnal-sumber':
        $page_title = 'Log Jurnal Sumber';
        render_admin(__DIR__ . '/menu/keuangan/log_jurnal_sumber/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/log-jurnal-sumber/detail':
        $page_title = 'Detail Log Jurnal Sumber';
        render_admin(__DIR__ . '/menu/keuangan/log_jurnal_sumber/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/log-jurnal-sumber/cetak':
        require __DIR__ . '/menu/keuangan/log_jurnal_sumber/cetak.php';
        exit;

    case 'keuangan/buku-besar':
        $page_title = 'Buku Besar';
        render_admin(__DIR__ . '/menu/keuangan/buku_besar/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/buku-besar/cetak':
        require __DIR__ . '/menu/keuangan/buku_besar/cetak.php';
        exit;

    case 'keuangan/saldo-akun':
        $page_title = 'Saldo Akun';
        render_admin(__DIR__ . '/menu/keuangan/saldo_akun/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/saldo-akun/cetak':
        require __DIR__ . '/menu/keuangan/saldo_akun/cetak.php';
        exit;

    case 'keuangan/laba-rugi':
        $page_title = 'Laba Rugi';
        render_admin(__DIR__ . '/menu/keuangan/laba_rugi/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/laba-rugi/cetak':
        require __DIR__ . '/menu/keuangan/laba_rugi/cetak.php';
        exit;

    case 'keuangan/neraca':
        $page_title = 'Neraca';
        render_admin(__DIR__ . '/menu/keuangan/neraca/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/neraca/cetak':
        require __DIR__ . '/menu/keuangan/neraca/cetak.php';
        exit;

    case 'keuangan/arus-kas':
        $page_title = 'Arus Kas';
        render_admin(__DIR__ . '/menu/keuangan/arus_kas/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/arus-kas/cetak':
        require __DIR__ . '/menu/keuangan/arus_kas/cetak.php';
        exit;




    case 'keuangan/pembatalan-transaksi':
        $page_title = 'Pembatalan Transaksi';
        render_admin(__DIR__ . '/menu/keuangan/pembatalan_transaksi/index.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/pembatalan-transaksi/detail':
        $page_title = 'Detail Pembatalan Transaksi';
        render_admin(__DIR__ . '/menu/keuangan/pembatalan_transaksi/detail.php', compact('page_title', 'menu_aktif'));
        break;

    case 'keuangan/pembatalan-transaksi/proses':
        require __DIR__ . '/menu/keuangan/pembatalan_transaksi/proses.php';
        exit;

    case 'keuangan/pembatalan-transaksi/cetak':
        require __DIR__ . '/menu/keuangan/pembatalan_transaksi/cetak.php';
        exit;




















        

    default:
        $page_title = 'Halaman Belum Dibuat';
        render_admin(__DIR__ . '/menu/not_ready/index.php', compact('page_title', 'menu_aktif'));
        break;
}