<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/kode.php';
require_once __DIR__ . '/helpers_produk.php';

require_once __DIR__ . '/../../../../orm/ProdukORM.php';
require_once __DIR__ . '/../../../../orm/KategoriProdukORM.php';
require_once __DIR__ . '/../../../../orm/SatuanORM.php';
require_once __DIR__ . '/../../../../orm/CoaORM.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/produk');
}

$id_entitas          = (int) (user_login()['id_entitas'] ?? 0);
$id_pengguna         = (int) (user_login()['id_pengguna'] ?? 0);
$nama_produk         = trim((string) ($_POST['nama_produk'] ?? ''));
$barcode_produk_input = normalisasi_barcode_produk((string) ($_POST['barcode_produk'] ?? ''));
$id_kategori_produk  = ($_POST['id_kategori_produk'] ?? '') !== '' ? (int) $_POST['id_kategori_produk'] : null;
$id_satuan           = (int) ($_POST['id_satuan'] ?? 0);
$jenis_produk        = trim((string) ($_POST['jenis_produk'] ?? ''));
$harga_jual          = (float) ($_POST['harga_jual'] ?? 0);
$hpp_standar         = (float) ($_POST['hpp_standar'] ?? 0);
$stok_minimum        = (float) ($_POST['stok_minimum'] ?? 0);
$berat_bersih        = ($_POST['berat_bersih'] ?? '') !== '' ? (float) $_POST['berat_bersih'] : null;
$satuan_berat_bersih = trim((string) ($_POST['satuan_berat_bersih'] ?? 'gram'));
$id_coa_penjualan    = ($_POST['id_coa_penjualan'] ?? '') !== '' ? (int) $_POST['id_coa_penjualan'] : null;
$id_coa_hpp          = ($_POST['id_coa_hpp'] ?? '') !== '' ? (int) $_POST['id_coa_hpp'] : null;
$id_coa_persediaan   = ($_POST['id_coa_persediaan'] ?? '') !== '' ? (int) $_POST['id_coa_persediaan'] : null;
$status_produk       = (int) ($_POST['status_produk'] ?? 1);

if ($nama_produk === '') {
    set_flash('error', 'Nama produk wajib diisi.');
    redirect_admin('master_setup/produk/tambah');
}

if ($id_kategori_produk !== null && !KategoriProdukORM::query()->where('id_entitas', $id_entitas)->where('id_kategori_produk', $id_kategori_produk)->exists()) {
    set_flash('error', 'Kategori produk tidak valid.');
    redirect_admin('master_setup/produk/tambah');
}

if ($id_satuan <= 0 || !SatuanORM::query()->where('id_satuan', $id_satuan)->exists()) {
    set_flash('error', 'Satuan tidak valid.');
    redirect_admin('master_setup/produk/tambah');
}

$allowedJenisProduk = ['barang_jadi', 'setengah_jadi', 'jasa', 'non_persediaan'];
if (!in_array($jenis_produk, $allowedJenisProduk, true)) {
    set_flash('error', 'Jenis produk tidak valid.');
    redirect_admin('master_setup/produk/tambah');
}

if (!in_array($satuan_berat_bersih, ['gram', 'kg'], true)) {
    set_flash('error', 'Satuan berat bersih tidak valid.');
    redirect_admin('master_setup/produk/tambah');
}

if ($harga_jual < 0 || $hpp_standar < 0 || $stok_minimum < 0) {
    set_flash('error', 'Nilai angka tidak valid.');
    redirect_admin('master_setup/produk/tambah');
}

if ($barcode_produk_input !== '' && !format_barcode_produk_valid($barcode_produk_input)) {
    set_flash('error', 'Kode barcode hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung. Maksimal 100 karakter.');
    redirect_admin('master_setup/produk/tambah');
}

if ($barcode_produk_input !== '' && barcode_produk_sudah_dipakai($barcode_produk_input, $id_entitas)) {
    set_flash('error', 'Kode barcode sudah digunakan pada produk lain.');
    redirect_admin('master_setup/produk/tambah');
}

foreach ([$id_coa_penjualan, $id_coa_hpp, $id_coa_persediaan] as $idCoa) {
    if ($idCoa !== null && !CoaORM::query()->where('id_entitas', $id_entitas)->where('id_coa', $idCoa)->exists()) {
        set_flash('error', 'Salah satu COA tidak valid.');
        redirect_admin('master_setup/produk/tambah');
    }
}

$gambar_produk = null;

if (isset($_FILES['gambar_produk']) && ($_FILES['gambar_produk']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['gambar_produk']['error'] !== UPLOAD_ERR_OK) {
        set_flash('error', 'Upload gambar gagal.');
        redirect_admin('master_setup/produk/tambah');
    }

    $ext = strtolower(pathinfo($_FILES['gambar_produk']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg'], true)) {
        set_flash('error', 'Gambar produk harus jpg atau jpeg.');
        redirect_admin('master_setup/produk/tambah');
    }

    $uploadDir = __DIR__ . '/../../../../uploads/produk/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid('produk_', true) . '.' . $ext;
    $targetPath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['gambar_produk']['tmp_name'], $targetPath)) {
        set_flash('error', 'Gagal menyimpan file gambar.');
        redirect_admin('master_setup/produk/tambah');
    }

    $gambar_produk = $fileName;
}

$kode_produk = generate_kode_master('tb_produk', 'kode_produk', 'PRD', 4, $id_entitas);
$barcode_produk = $barcode_produk_input !== ''
    ? $barcode_produk_input
    : generate_barcode_produk_otomatis($id_entitas);

ProdukORM::create([
    'id_entitas'           => $id_entitas,
    'kode_produk'          => $kode_produk,
    'barcode_produk'       => $barcode_produk,
    'nama_produk'          => $nama_produk,
    'id_kategori_produk'   => $id_kategori_produk,
    'id_satuan'            => $id_satuan,
    'jenis_produk'         => $jenis_produk,
    'harga_jual'           => $harga_jual,
    'hpp_standar'          => $hpp_standar,
    'stok_minimum'         => $stok_minimum,
    'berat_bersih'         => $berat_bersih,
    'satuan_berat_bersih'  => $satuan_berat_bersih,
    'gambar_produk'        => $gambar_produk,
    'status_produk'        => $status_produk,
    'id_coa_penjualan'     => $id_coa_penjualan,
    'id_coa_hpp'           => $id_coa_hpp,
    'id_coa_persediaan'    => $id_coa_persediaan,
    'tanggal_dibuat'       => date('Y-m-d H:i:s'),
    'dibuat_oleh'          => $id_pengguna > 0 ? $id_pengguna : null,
    'tanggal_diubah'       => null,
    'diubah_oleh'          => null,
]);

set_flash('success', 'Data produk berhasil disimpan.');
redirect_admin('master_setup/produk');