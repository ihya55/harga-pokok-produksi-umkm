<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
}

$user = user_login();
$id_entitas = (int) ($user['id_entitas'] ?? 0);
$id_pengguna = (int) ($user['id_pengguna'] ?? 0);

$id_coa = (int) ($_POST['id_coa'] ?? 0);
$id_coa_lama = (int) ($_POST['id_coa_lama'] ?? 0);
$nama_bank = trim((string) ($_POST['nama_bank'] ?? ''));
$nomor_rekening = trim((string) ($_POST['nomor_rekening'] ?? ''));
$atas_nama_rekening = trim((string) ($_POST['atas_nama_rekening'] ?? ''));
$qris_nama_merchant = trim((string) ($_POST['qris_nama_merchant'] ?? ''));
$qris_static_payload = preg_replace('/\s+/', '', trim((string) ($_POST['qris_static_payload'] ?? '')));
$is_akun_qris = isset($_POST['is_akun_qris']) ? 1 : 0;
$qris_image_path_lama = trim((string) ($_POST['qris_image_path_lama'] ?? ''));
$qris_image_path = $qris_image_path_lama;

if ($id_entitas <= 0) {
    set_flash('error', 'Entitas pengguna tidak valid.');
    redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
}

if ($id_coa <= 0) {
    set_flash('error', 'Akun bank/kas wajib dipilih.');
    redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
}

$requiredColumns = ['nama_bank', 'nomor_rekening', 'atas_nama_rekening', 'qris_nama_merchant', 'qris_static_payload', 'qris_image_path', 'is_akun_qris'];
foreach ($requiredColumns as $column) {
    if (!Capsule::schema()->hasColumn('tb_coa', $column)) {
        set_flash('error', 'Kolom rekening/QRIS belum tersedia. Jalankan SQL update terlebih dahulu.');
        redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
    }
}



// Upload gambar QRIS manual jika ada.
if (!empty($_FILES['qris_image']['name'] ?? '')) {
    $file = $_FILES['qris_image'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        set_flash('error', 'Upload gambar QRIS gagal. Kode error: ' . (int) $file['error']);
        redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
    }
    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
        set_flash('error', 'Format gambar QRIS harus JPG, PNG, atau WEBP.');
        redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
    }
    if ((int) ($file['size'] ?? 0) > 3 * 1024 * 1024) {
        set_flash('error', 'Ukuran gambar QRIS maksimal 3 MB.');
        redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
    }
    $dir = __DIR__ . '/../../../../uploads/qris';
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $filename = 'qris_' . $id_entitas . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $filename)) {
        set_flash('error', 'Gagal menyimpan gambar QRIS.');
        redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
    }
    $qris_image_path = 'uploads/qris/' . $filename;
}

$coa = Capsule::table('tb_coa')
    ->where('id_entitas', $id_entitas)
    ->where('id_coa', $id_coa)
    ->first();

if (!$coa) {
    set_flash('error', 'Akun bank/kas tidak ditemukan pada entitas ini.');
    redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
}

try {
    if ($is_akun_qris === 1) {
        Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('id_coa', '<>', $id_coa)
            ->update(['is_akun_qris' => 0]);
    }

    if ($id_coa_lama > 0 && $id_coa_lama !== $id_coa) {
        Capsule::table('tb_coa')
            ->where('id_entitas', $id_entitas)
            ->where('id_coa', $id_coa_lama)
            ->update([
                'nama_bank' => null,
                'nomor_rekening' => null,
                'atas_nama_rekening' => null,
                'qris_nama_merchant' => null,
                'qris_static_payload' => null,
                'qris_image_path' => null,
                'is_akun_qris' => 0,
                'tanggal_diubah' => date('Y-m-d H:i:s'),
                'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
            ]);
    }

    Capsule::table('tb_coa')
        ->where('id_entitas', $id_entitas)
        ->where('id_coa', $id_coa)
        ->update([
            'nama_bank' => $nama_bank !== '' ? $nama_bank : null,
            'nomor_rekening' => $nomor_rekening !== '' ? $nomor_rekening : null,
            'atas_nama_rekening' => $atas_nama_rekening !== '' ? $atas_nama_rekening : null,
            'qris_nama_merchant' => $qris_nama_merchant !== '' ? $qris_nama_merchant : null,
            'qris_static_payload' => $qris_static_payload !== '' ? $qris_static_payload : null,
            'qris_image_path' => $qris_image_path !== '' ? $qris_image_path : null,
            'is_akun_qris' => $is_akun_qris,
            'tanggal_diubah' => date('Y-m-d H:i:s'),
            'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
        ]);

    // Sinkronkan juga ke tabel pengaturan pembayaran online lama agar halaman lama tetap kompatibel.
    if (Capsule::schema()->hasTable('tb_pengaturan_pembayaran_online')) {
        $exists = Capsule::table('tb_pengaturan_pembayaran_online')
            ->where('id_entitas', $id_entitas)
            ->exists();

        $paymentData = [
            'nama_bank' => $nama_bank !== '' ? $nama_bank : (string) ($coa->nama_coa ?? ''),
            'nomor_rekening' => $nomor_rekening,
            'atas_nama_rekening' => $atas_nama_rekening,
            'qris_nama_merchant' => $qris_nama_merchant,
            'qris_static_payload' => $qris_static_payload,
            'qris_image_path' => $qris_image_path,
            'catatan_pembayaran' => 'Data disinkronkan dari akun bank pada Mapping Akun.',
        ];

        if ($exists) {
            $paymentData['tanggal_diubah'] = date('Y-m-d H:i:s');
            $paymentData['diubah_oleh'] = $id_pengguna > 0 ? $id_pengguna : null;
            Capsule::table('tb_pengaturan_pembayaran_online')
                ->where('id_entitas', $id_entitas)
                ->update($paymentData);
        } else {
            $paymentData['id_entitas'] = $id_entitas;
            $paymentData['tanggal_dibuat'] = date('Y-m-d H:i:s');
            $paymentData['dibuat_oleh'] = $id_pengguna > 0 ? $id_pengguna : null;
            Capsule::table('tb_pengaturan_pembayaran_online')->insert($paymentData);
        }
    }

    set_flash('success', 'Rekening bank dan QRIS berhasil disimpan. Pesanan Online akan memakai data ini.');
} catch (Throwable $e) {
    set_flash('error', 'Gagal menyimpan rekening/QRIS: ' . $e->getMessage());
}

redirect_admin('master_setup/mapping_akun&tab=rekening_qris');
