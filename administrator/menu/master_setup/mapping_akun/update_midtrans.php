<?php
declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
if (file_exists(__DIR__ . '/../../../../helpers/auth.php')) {
    require_once __DIR__ . '/../../../../helpers/auth.php';
}
require_once __DIR__ . '/helpers_mapping_akun.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_admin('master_setup/mapping_akun&tab=payment_gateway');
}

$id_entitas = mapping_akun_id_entitas();
$id_pengguna = mapping_akun_id_pengguna();

if (!Capsule::schema()->hasTable('tb_payment_gateway_setting')) {
    set_flash('error', 'Tabel tb_payment_gateway_setting belum ada. Jalankan SQL update Midtrans terlebih dahulu.');
    redirect_admin('master_setup/mapping_akun&tab=payment_gateway');
}

$provider = 'midtrans';
$mode = strtolower(trim((string) ($_POST['mode'] ?? 'sandbox')));
$mode = $mode === 'production' ? 'production' : 'sandbox';
$is_active = (int) ($_POST['is_active'] ?? 0) === 1 ? 1 : 0;
$server_key = trim((string) ($_POST['server_key'] ?? ''));
$client_key = trim((string) ($_POST['client_key'] ?? ''));
$merchant_id = trim((string) ($_POST['merchant_id'] ?? ''));
$enabled_payments = strtolower(trim((string) ($_POST['enabled_payments'] ?? 'qris')));
$enabled_payments = $enabled_payments !== '' ? $enabled_payments : 'qris';

try {
    if ($is_active === 1 && ($server_key === '' || $client_key === '')) {
        throw new RuntimeException('Server Key dan Client Key wajib diisi jika Midtrans diaktifkan.');
    }

    $data = [
        'provider' => $provider,
        'mode' => $mode,
        'is_active' => $is_active,
        'server_key' => $server_key,
        'client_key' => $client_key,
        'merchant_id' => $merchant_id !== '' ? $merchant_id : null,
        'enabled_payments' => $enabled_payments,
        'tanggal_diubah' => date('Y-m-d H:i:s'),
        'diubah_oleh' => $id_pengguna > 0 ? $id_pengguna : null,
    ];

    $exists = Capsule::table('tb_payment_gateway_setting')
        ->where('id_entitas', $id_entitas)
        ->where('provider', $provider)
        ->exists();

    if ($exists) {
        Capsule::table('tb_payment_gateway_setting')
            ->where('id_entitas', $id_entitas)
            ->where('provider', $provider)
            ->update($data);
    } else {
        $data['id_entitas'] = $id_entitas;
        $data['tanggal_dibuat'] = date('Y-m-d H:i:s');
        $data['dibuat_oleh'] = $id_pengguna > 0 ? $id_pengguna : null;
        Capsule::table('tb_payment_gateway_setting')->insert($data);
    }

    set_flash('success', 'Setting Midtrans QRIS otomatis berhasil disimpan.');
} catch (Throwable $e) {
    set_flash('error', $e->getMessage());
}

redirect_admin('master_setup/mapping_akun&tab=payment_gateway');
