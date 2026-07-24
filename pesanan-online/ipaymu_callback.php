<?php
declare(strict_types=1);

require_once __DIR__ . '/_helper.php';

$raw = file_get_contents('php://input') ?: '';
$data = $_POST;
if (empty($data) && $raw !== '') {
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $data = $json;
    }
}

$orderId = trim((string) ($data['reference_id'] ?? $data['referenceId'] ?? $data['reference'] ?? $data['sid'] ?? $data['trx_id'] ?? $data['id'] ?? ''));
$status = trim((string) ($data['status'] ?? $data['transaction_status'] ?? $data['status_code'] ?? ''));
$trxId = trim((string) ($data['trx_id'] ?? $data['transaction_id'] ?? $data['id'] ?? ''));

try {
    if ($orderId !== '') {
        po_ipaymu_update_order_status($orderId, $status, $trxId, $raw !== '' ? $raw : json_encode($data));
    }
    http_response_code(200);
    echo 'OK';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'ERROR: ' . $e->getMessage();
}
