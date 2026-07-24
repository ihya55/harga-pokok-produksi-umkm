<?php
declare(strict_types=1);
use Illuminate\Database\Capsule\Manager as Capsule;

$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
$id_entitas = (int)($user['id_entitas'] ?? 0);
$action = trim((string)($_POST['action'] ?? ''));
$session = trim((string)($_POST['session'] ?? ''));

function respondJson(bool $ok, string $message): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'delete_session' && $session !== '') {
    $deleted = Capsule::table('tb_pesanan_online_chat_general')
        ->where('id_entitas', $id_entitas)
        ->where('session_key', $session)
        ->delete();
    $message = $deleted ? "Percakapan berhasil dihapus." : "Percakapan tidak ditemukan.";
    if ($isAjax) respondJson(true, $message);
    set_flash('success', $message);
} elseif ($action === 'delete_all') {
    $deleted = Capsule::table('tb_pesanan_online_chat_general')
        ->where('id_entitas', $id_entitas)
        ->delete();
    $message = "Semua chat general berhasil dihapus ($deleted percakapan).";
    if ($isAjax) respondJson(true, $message);
    set_flash('success', $message);
} else {
    if ($isAjax) respondJson(false, 'Permintaan tidak valid.');
    set_flash('error', 'Permintaan tidak valid.');
}

redirect_admin('penjualan/chat-online');