<?php
require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');

$user = user_login();
$id_entitas = $user['id_entitas'] ?? 0;
$id_pengguna = $user['id_pengguna'] ?? 0;

$id = (int) ($_POST['id'] ?? 0);
$id_coa = (int) ($_POST['id_coa'] ?? 0);
$nama_tampilan = trim($_POST['nama_tampilan'] ?? '');
$jenis_pembayaran = $_POST['jenis_pembayaran'] ?? 'transfer';
$aktif = isset($_POST['aktif']) ? 1 : 0;
$is_default = isset($_POST['is_default']) ? 1 : 0;
$urutan = (int) ($_POST['urutan'] ?? 0);

// Field dinamis
$nama_bank = trim($_POST['nama_bank'] ?? '');
$nomor_rekening = trim($_POST['nomor_rekening'] ?? '');
$atas_nama_rekening = trim($_POST['atas_nama_rekening'] ?? '');
$qris_nama_merchant = trim($_POST['qris_nama_merchant'] ?? '');
$qris_static_payload = trim($_POST['qris_static_payload'] ?? '');
$qris_image_path = trim($_POST['qris_image_path_lama'] ?? '');

// Upload gambar QRIS

// Upload gambar QRIS
if (!empty($_FILES['qris_image']['name'])) {
    $file = $_FILES['qris_image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        set_flash('error', 'Format harus JPG/PNG/WEBP');
        header('Location: tambah.php');
        exit;
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        set_flash('error', 'Maks 3 MB');
        header('Location: tambah.php');
        exit;
    }
    $dir = __DIR__ . '/../../../../uploads/qris';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $filename = 'qris_' . $id_entitas . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $dir . '/' . $filename)) {
        $qris_image_path = 'uploads/qris/' . $filename;
    } else {
        set_flash('error', 'Gagal upload');
        header('Location: tambah.php');
        exit;
    }
}

try {
    if ($is_default) {
        Capsule::table('tb_rekening_pembayaran_online')
            ->where('id_entitas', $id_entitas)
            ->update(['is_default' => 0]);
    }

    $data = [
        'id_entitas' => $id_entitas,
        'id_coa' => $id_coa,
        'nama_tampilan' => $nama_tampilan,
        'jenis_pembayaran' => $jenis_pembayaran,
        'nama_bank' => $nama_bank ?: null,
        'nomor_rekening' => $nomor_rekening ?: null,
        'atas_nama_rekening' => $atas_nama_rekening ?: null,
        'qris_nama_merchant' => $qris_nama_merchant ?: null,
        'qris_static_payload' => $qris_static_payload ?: null,
        'qris_image_path' => $qris_image_path ?: null,
        'aktif' => $aktif,
        'is_default' => $is_default,
        'urutan' => $urutan,
        'tanggal_diubah' => date('Y-m-d H:i:s'),
        'diubah_oleh' => $id_pengguna
    ];

    if ($id > 0) {
        Capsule::table('tb_rekening_pembayaran_online')->where('id_rekening_pembayaran', $id)->update($data);
        set_flash('success', 'Rekening berhasil diupdate');
    } else {
        $data['tanggal_dibuat'] = date('Y-m-d H:i:s');
        $data['dibuat_oleh'] = $id_pengguna;
        unset($data['tanggal_diubah'], $data['diubah_oleh']);
        Capsule::table('tb_rekening_pembayaran_online')->insert($data);
        set_flash('success', 'Rekening berhasil ditambahkan');
    }
} catch (Throwable $e) {
    set_flash('error', 'Gagal simpan: ' . $e->getMessage());
}
redirect('index.php');