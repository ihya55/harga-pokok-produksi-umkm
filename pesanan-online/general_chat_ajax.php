<?php
declare(strict_types=1);
require_once __DIR__ . '/_helper.php';
use Illuminate\Database\Capsule\Manager as Capsule;
header('Content-Type: application/json; charset=utf-8');
try {
    if (!Capsule::schema()->hasTable('tb_pesanan_online_chat_general')) {
        throw new RuntimeException('Tabel chat general belum tersedia. Jalankan SQL update terlebih dahulu.');
    }
    $id_entitas = po_id_entitas();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $session = trim((string)($_POST['session'] ?? ''));
        $nama = trim((string)($_POST['nama'] ?? 'Pelanggan')) ?: 'Pelanggan';
        $no_hp = trim((string)($_POST['no_hp'] ?? ''));
        $pesan = trim((string)($_POST['pesan'] ?? ''));
        if ($session === '') $session = 'GEN'.date('YmdHis').bin2hex(random_bytes(3));
        if ($pesan === '') throw new RuntimeException('Pesan tidak boleh kosong.');
        Capsule::table('tb_pesanan_online_chat_general')->insert([
            'id_entitas'=>$id_entitas,'session_key'=>$session,'nama_pelanggan'=>$nama,'no_hp'=>$no_hp,
            'pengirim_tipe'=>'customer','nama_pengirim'=>$nama,'pesan'=>$pesan,'status_dibaca_admin'=>0,'tanggal_dibuat'=>date('Y-m-d H:i:s')
        ]);
        echo json_encode(['ok'=>true,'session'=>$session]); exit;
    }
    $session = trim((string)($_GET['session'] ?? ''));
    if ($session === '') { echo json_encode(['ok'=>true,'rows'=>[]]); exit; }
    $rows = Capsule::table('tb_pesanan_online_chat_general')
        ->where('id_entitas',$id_entitas)->where('session_key',$session)
        ->orderBy('id_chat_general','asc')->limit(200)->get()->map(function($r){return [
            'pengirim_tipe'=>(string)$r->pengirim_tipe,'nama_pengirim'=>(string)($r->nama_pengirim ?? ''),'pesan'=>(string)$r->pesan,'tanggal'=>date('d/m/Y H:i', strtotime((string)$r->tanggal_dibuat))
        ];});
    echo json_encode(['ok'=>true,'rows'=>$rows]);
} catch (Throwable $e) { http_response_code(400); echo json_encode(['ok'=>false,'message'=>$e->getMessage()]); }
