<?php
declare(strict_types=1);

require_once __DIR__ . '/_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    parse_str((string) $raw, $input);
}

$merchantCode = trim((string) ($input['merchantCode'] ?? $input['merchantcode'] ?? ''));
$amount = trim((string) ($input['amount'] ?? ''));
$merchantOrderId = trim((string) ($input['merchantOrderId'] ?? ''));
$resultCode = trim((string) ($input['resultCode'] ?? ''));
$reference = trim((string) ($input['reference'] ?? ''));
$signature = trim((string) ($input['signature'] ?? ''));

try {
    if ($merchantCode === '' || $amount === '' || $merchantOrderId === '' || $signature === '') {
        http_response_code(400);
        echo 'Missing required callback fields';
        exit;
    }

    $order = Capsule::table('tb_pesanan_penjualan')
        ->where('no_pesanan_penjualan', $merchantOrderId)
        ->first();

    if (!$order) {
        http_response_code(404);
        echo 'Order not found';
        exit;
    }

    $setting = po_duitku_setting((int) $order->id_entitas);
    $apiKey = trim((string) ($setting->api_key ?? ''));
    if ($apiKey === '') {
        http_response_code(400);
        echo 'Duitku API Key is empty';
        exit;
    }

    $expected = hash_hmac('sha256', $merchantCode . $amount . $merchantOrderId, $apiKey);
    if (!hash_equals(strtolower($expected), strtolower($signature))) {
        http_response_code(403);
        echo 'Invalid signature';
        exit;
    }

    po_duitku_update_order_status($merchantOrderId, $resultCode, $reference, json_encode($input));
    http_response_code(200);
    echo 'OK';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
}
