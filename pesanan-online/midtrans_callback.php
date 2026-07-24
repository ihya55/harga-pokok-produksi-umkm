<?php
declare(strict_types=1);

require_once __DIR__ . '/_helper.php';

use Illuminate\Database\Capsule\Manager as Capsule;

header('Content-Type: application/json');

$raw = file_get_contents('php://input') ?: '';
$payload = json_decode($raw, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid JSON']);
    exit;
}

$orderId = (string) ($payload['order_id'] ?? '');
$statusCode = (string) ($payload['status_code'] ?? '');
$grossAmount = (string) ($payload['gross_amount'] ?? '');
$signatureKey = (string) ($payload['signature_key'] ?? '');
$transactionStatus = (string) ($payload['transaction_status'] ?? '');
$fraudStatus = (string) ($payload['fraud_status'] ?? '');

if ($orderId === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'order_id kosong']);
    exit;
}

try {
    $pesanan = Capsule::table('tb_pesanan_penjualan')
        ->where(function ($query) use ($orderId) {
            $query->where('no_pesanan_penjualan', $orderId);
            if (po_table_has_column('tb_pesanan_penjualan', 'midtrans_order_id')) {
                $query->orWhere('midtrans_order_id', $orderId);
            }
        })
        ->first();

    if (!$pesanan) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Pesanan tidak ditemukan']);
        exit;
    }

    $setting = po_midtrans_setting((int) $pesanan->id_entitas);
    $serverKey = trim((string) ($setting->server_key ?? ''));
    if ($serverKey !== '' && $signatureKey !== '') {
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        if (!hash_equals($expected, $signatureKey)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'message' => 'Signature tidak valid']);
            exit;
        }
    }

    po_midtrans_update_order_status($orderId, $transactionStatus, $fraudStatus);

    if (po_table_has_column('tb_pesanan_penjualan', 'midtrans_response_json')) {
        Capsule::table('tb_pesanan_penjualan')
            ->where('id_pesanan_penjualan', (int) $pesanan->id_pesanan_penjualan)
            ->update(['midtrans_response_json' => $raw]);
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
