<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../../helpers/config.php';
require_once __DIR__ . '/../../../../helpers/koneksi.php';
require_once __DIR__ . '/../../../../helpers/fungsi.php';
require_once __DIR__ . '/../../../../helpers/auth.php';

use Illuminate\Database\Capsule\Manager as Capsule;

harus_login();

header('Content-Type: application/json; charset=utf-8');

$id_entitas = (int) (user_login()['id_entitas'] ?? 0);
$id_faktur_penjualan = (int) ($_GET['id_faktur_penjualan'] ?? 0);

if ($id_faktur_penjualan <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Piutang pelanggan wajib dipilih.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$faktur = Capsule::table('tb_faktur_penjualan as f')
    ->leftJoin('tb_pelanggan as pl', 'pl.id_pelanggan', '=', 'f.id_pelanggan')
    ->where('f.id_entitas', $id_entitas)
    ->where('f.id_faktur_penjualan', $id_faktur_penjualan)
    ->select([
        'f.*',
        'pl.kode_pelanggan',
        'pl.nama_pelanggan',
        'pl.no_hp',
        'pl.alamat',
    ])
    ->first();

if (!$faktur) {
    echo json_encode([
        'success' => false,
        'message' => 'Piutang pelanggan tidak ditemukan.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((string) $faktur->status_faktur !== 'posted') {
    echo json_encode([
        'success' => false,
        'message' => 'Hanya faktur posted yang bisa dibayar.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((string) $faktur->jenis_pembayaran !== 'kredit') {
    echo json_encode([
        'success' => false,
        'message' => 'Pembayaran manual hanya untuk piutang kredit. Faktur tunai/transfer dianggap lunas saat posting faktur.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ((float) $faktur->sisa_piutang <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Faktur ini sudah lunas.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pembayaranDraft = Capsule::table('tb_pembayaran_penjualan')
    ->where('id_entitas', $id_entitas)
    ->where('id_faktur_penjualan', $id_faktur_penjualan)
    ->where('status_posting', 'draft')
    ->sum('jumlah_bayar');

$sisaTersedia = max(0, (float) $faktur->sisa_piutang - (float) $pembayaranDraft);

if ($sisaTersedia <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Faktur ini sudah memiliki draft pembayaran sebesar sisa piutang. Posting atau hapus draft pembayaran terlebih dahulu.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Detail faktur berhasil dimuat.',
    'faktur' => [
        'id_faktur_penjualan' => (int) $faktur->id_faktur_penjualan,
        'no_faktur_penjualan' => (string) $faktur->no_faktur_penjualan,
        'tanggal_faktur' => (string) $faktur->tanggal_faktur,
        'jatuh_tempo' => (string) ($faktur->jatuh_tempo ?? ''),
        'id_pelanggan' => (int) $faktur->id_pelanggan,
        'kode_pelanggan' => (string) ($faktur->kode_pelanggan ?? ''),
        'nama_pelanggan' => (string) ($faktur->nama_pelanggan ?? ''),
        'pelanggan_label' => (string) (($faktur->kode_pelanggan ?? '-') . ' - ' . ($faktur->nama_pelanggan ?? '-')),
        'total' => (float) $faktur->total,
        'sisa_piutang' => $sisaTersedia,
        'sisa_piutang_asli' => (float) $faktur->sisa_piutang,
        'draft_pembayaran' => (float) $pembayaranDraft,
    ],
], JSON_UNESCAPED_UNICODE);
exit;