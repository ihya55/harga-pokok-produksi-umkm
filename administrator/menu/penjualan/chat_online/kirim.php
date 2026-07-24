<?php
declare(strict_types=1);
use Illuminate\Database\Capsule\Manager as Capsule;

$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest'
    || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

function chat_online_json(array $payload): never
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$session = trim((string)($_POST['session'] ?? ''));
$pesan = trim((string)($_POST['pesan'] ?? ''));
$id_entitas = (int)($user['id_entitas'] ?? 0);

if ($session === '' || $pesan === '') {
    if ($isAjax) chat_online_json(['ok'=>false,'message'=>'Session dan pesan wajib diisi.']);
    redirect_admin('penjualan/chat-online');
}

if (!Capsule::schema()->hasTable('tb_pesanan_online_chat_general')) {
    if ($isAjax) chat_online_json(['ok'=>false,'message'=>'Tabel chat general belum tersedia. Jalankan SQL update.']);
    set_flash('error', 'Tabel chat general belum tersedia.');
    redirect_admin('penjualan/chat-online&session=' . urlencode($session));
}

$last = Capsule::table('tb_pesanan_online_chat_general')
    ->where('id_entitas', $id_entitas)
    ->where('session_key', $session)
    ->orderByDesc('id_chat_general')
    ->first();

if (!$last) {
    if ($isAjax) chat_online_json(['ok'=>false,'message'=>'Percakapan tidak ditemukan.']);
    set_flash('error', 'Percakapan tidak ditemukan.');
    redirect_admin('penjualan/chat-online');
}

$now = date('Y-m-d H:i:s');
$namaPengirim = (string)($user['nama_lengkap'] ?? $user['username'] ?? 'Admin');

Capsule::table('tb_pesanan_online_chat_general')->insert([
    'id_entitas' => $id_entitas,
    'session_key' => $session,
    'nama_pelanggan' => (string)($last->nama_pelanggan ?? ''),
    'no_hp' => (string)($last->no_hp ?? ''),
    'pengirim_tipe' => 'admin',
    'nama_pengirim' => $namaPengirim,
    'pesan' => $pesan,
    'status_dibaca_customer' => 0,
    'status_dibaca_admin' => 1,
    'tanggal_dibuat' => $now,
]);

if ($isAjax) {
    chat_online_json([
        'ok' => true,
        'pesan' => $pesan,
        'nama_pengirim' => $namaPengirim,
        'tanggal' => date('d/m/Y H:i', strtotime($now)),
    ]);
}

set_flash('success', 'Balasan chat berhasil dikirim.');
redirect_admin('penjualan/chat-online&session=' . urlencode($session));
